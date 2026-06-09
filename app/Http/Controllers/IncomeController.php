<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'category' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $income = $user->incomes()->create([
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $request->date,
            'description' => $request->description,
        ]);

        // Award 10 XP for logging income
        $xpResult = $user->addXp(10);
        if ($xpResult['leveled_up']) {
            session()->flash('level_up', $xpResult['current_level']);
        }

        // Check for new achievements/badges
        $newBadges = $user->checkBadges();
        if (count($newBadges) > 0) {
            session()->flash('new_badges', collect($newBadges)->map(fn($b) => [
                'name' => $b->name,
                'icon' => $b->icon,
                'description' => $b->description,
            ])->toArray());
        }

        $msg = 'Pemasukan berhasil dicatat! +10 XP';
        if (count($newBadges) > 0) {
            $badgeNames = collect($newBadges)->pluck('name')->join(', ');
            $msg .= " Lencana Baru Dibuka: {$badgeNames}!";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function update(Request $request, Income $income): RedirectResponse
    {
        if ($income->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
            'category' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $income->update([
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $request->date,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Pemasukan berhasil diperbarui!');
    }

    public function destroy(Request $request, Income $income): RedirectResponse
    {
        if ($income->user_id !== $request->user()->id) {
            abort(403);
        }

        $income->delete();

        return redirect()->back()->with('success', 'Pemasukan berhasil dihapus!');
    }

    public function scanTransferProof(Request $request)
    {
        $request->validate([
            'receipt' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $file = $request->file('receipt');
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (!empty($apiKey)) {
            try {
                $imageBytes = file_get_contents($file->getRealPath());
                $base64Image = base64_encode($imageBytes);
                $mimeType = $file->getMimeType();

                $prompt = "Kamu adalah Finku-AI OCR Parser, asisten cerdas khusus pencatatan pemasukan keuangan.
Analisis gambar bukti transfer bank, mutasi rekening, slip gaji, kuitansi penerimaan uang, atau nota bukti bayar masuk ini secara cermat dan ekstraksilah informasi berikut:
1. 'amount': Total nominal uang masuk yang diterima oleh pengguna. Cari angka transfer/nominal masuk (misalnya nominal transfer, total gaji bersih, kuitansi terima). Bulatkan ke nilai bulat integer rupiah (tanpa desimal/sen).
2. 'category': Klasifikasikan sumber pemasukan ke salah satu kategori ini:
   - 'Gaji' (pendapatan tetap bulanan, payroll, salary)
   - 'Sampingan' (proyek freelance, tips, honor, kerja sampingan)
   - 'Investasi' (dividen, kupon obligasi, keuntungan reksa dana, bunga bank)
   - 'Hadiah' (kado, amplop kondangan, THR, pemberian orang tua, hibah)
   - 'Lainnya' (jika tidak masuk ke kategori di atas)
3. 'date': Tanggal transaksi transfer masuk yang tertera. Konversikan dari format apa pun ke 'YYYY-MM-DD'. Jika tanggal tidak terbaca, gunakan tanggal hari ini: '" . now()->format('Y-m-d') . "'.
4. 'description': Ringkasan sumber pemasukan yang ringkas dan informatif (maksimal 50 karakter), misalnya 'Gaji Bulanan PT Maju', 'Freelance Desain Logo', 'Dividen Saham BBRI', 'Hadiah Ulang Tahun'.

Berikan hasil analisis Anda dalam format JSON yang valid.";

                $response = \Illuminate\Support\Facades\Http::timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inlineData' => [
                                        'mimeType' => $mimeType,
                                        'data' => $base64Image
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'amount' => [
                                    'type' => 'INTEGER',
                                    'description' => 'Total transfer nominal received in IDR (Rupiah), rounded, ignoring decimals.'
                                ],
                                'category' => [
                                    'type' => 'STRING',
                                    'enum' => ['Gaji', 'Sampingan', 'Investasi', 'Hadiah', 'Lainnya']
                                ],
                                'date' => [
                                    'type' => 'STRING',
                                    'description' => 'Transaction date normalized to YYYY-MM-DD format.'
                                ],
                                'description' => [
                                    'type' => 'STRING',
                                    'description' => 'Concise summary of source of income. Max 50 characters.'
                                ]
                            ],
                            'required' => ['amount', 'category', 'date', 'description']
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $jsonText = $response->json('candidates.0.content.parts.0.text');
                    $data = json_decode(trim($jsonText), true);

                    if (is_array($data) && isset($data['amount'])) {
                        $validCategories = ['Gaji', 'Sampingan', 'Investasi', 'Hadiah', 'Lainnya'];
                        if (!in_array($data['category'] ?? '', $validCategories)) {
                            $data['category'] = 'Lainnya';
                        }
                        return response()->json([
                            'success' => true,
                            'data' => $data,
                            'message' => 'Bukti transfer berhasil dipindai oleh AI!'
                        ]);
                    }
                }

                \Illuminate\Support\Facades\Log::warning("Gemini API Income OCR failed: " . $response->body());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Gemini API Income OCR Error: " . $e->getMessage());
            }
        }

        // If Gemini is inactive or empty, return inactive state warning
        return response()->json([
            'success' => false,
            'error' => 'ai_inactive',
            'message' => 'Mohon maaf, AI Scan sedang tidak aktif karena token habis.'
        ]);
    }
}
