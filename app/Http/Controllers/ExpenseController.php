<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Fetch Expenses
        $expenseQuery = $user->expenses()->latest('date');
        if ($request->filled('type')) {
            $expenseQuery->where('type', $request->type);
        }
        if ($request->filled('category')) {
            $expenseQuery->where('category', $request->category);
        }
        $expenses = $expenseQuery->get();

        // 2. Fetch Incomes
        $incomeQuery = $user->incomes()->latest('date');
        if ($request->filled('income_category')) {
            $incomeQuery->where('category', $request->income_category);
        }
        $incomes = $incomeQuery->get();

        $categories = ['Makanan', 'Transportasi', 'Belanja', 'Hiburan', 'Tagihan', 'Kesehatan', 'Pendidikan', 'Lainnya'];
        $incomeCategories = ['Gaji', 'Sampingan', 'Investasi', 'Hadiah', 'Lainnya'];

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'incomes' => $incomes,
            'categories' => $categories,
            'income_categories' => $incomeCategories,
            'filters' => $request->only(['type', 'category', 'income_category']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'category' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:needs,wants',
        ]);

        $user = $request->user();

        $expense = $user->expenses()->create([
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $request->date,
            'description' => $request->description,
            'type' => $request->type,
        ]);

        // Award 10 XP for logging an expense
        $xpResult = $user->addXp(10);
        if ($xpResult['leveled_up']) {
            session()->flash('level_up', $xpResult['current_level']);
        }

        // Check for new achievements
        $newBadges = $user->checkBadges();
        if (count($newBadges) > 0) {
            session()->flash('new_badges', collect($newBadges)->map(fn($b) => [
                'name' => $b->name,
                'icon' => $b->icon,
                'description' => $b->description,
            ])->toArray());
        }

        // Calculate limit warnings
        $totalSpent = $user->expenses()
            ->where('type', $request->type)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $needsPercent = 50;
        $wantsPercent = 30;

        if ($user->budgeting_method === '70-20-10') {
            $needsPercent = 70;
            $wantsPercent = 20;
        } elseif ($user->budgeting_method === 'custom') {
            $custom = $user->custom_budget_percentages;
            $needsPercent = $custom['needs'] ?? 50;
            $wantsPercent = $custom['wants'] ?? 30;
        }

        $limit = $request->type === 'needs'
            ? ($user->monthly_income * $needsPercent) / 100
            : ($user->monthly_income * $wantsPercent) / 100;

        $msg = 'Pengeluaran berhasil dicatat! +10 XP';
        if ($user->monthly_income > 0 && $totalSpent > $limit) {
            $typeLabel = $request->type === 'needs' ? 'Kebutuhan (Needs)' : 'Keinginan (Wants)';
            $msg .= sprintf(" (Peringatan: Pengeluaran %s Anda melebihi batas anggaran!)", $typeLabel);
        }

        if (count($newBadges) > 0) {
            $badgeNames = collect($newBadges)->pluck('name')->join(', ');
            $msg .= " Lencana Baru Dibuka: {$badgeNames}!";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
            'category' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:needs,wants',
        ]);

        $expense->update([
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $request->date,
            'description' => $request->description,
            'type' => $request->type,
        ]);

        return redirect()->back()->with('success', 'Pengeluaran berhasil diperbarui!');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            abort(403);
        }

        $expense->delete();

        return redirect()->back()->with('success', 'Pengeluaran berhasil dihapus!');
    }

    public function scanReceipt(Request $request)
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

                $prompt = "Kamu adalah Finku-AI OCR Parser, asisten cerdas khusus pencatatan keuangan pribadi.
Analisis gambar struk, nota, bon, atau invoice belanja ini secara cermat dan ekstraksilah informasi berikut:
1. 'amount': Total uang akhir yang dibayar oleh pengguna (Grand Total / Total Bayar / Cash / Credit Card Total). Abaikan nilai subtotal, diskon, tax, atau harga satuan barang jika ada total akhir yang lebih besar. Bulatkan ke nilai bulat integer rupiah (tanpa desimal/sen).
2. 'category': Klasifikasikan belanjaan ke salah satu kategori ini:
   - 'Makanan' (jajan kopi, restoran, warung, jajanan, bahan makanan mentah)
   - 'Transportasi' (bensin, toll, tiket KRL/pesawat, parkir, ojek online)
   - 'Belanja' (baju, gadget, kosmetik, groceries/sembako non-makanan, perabot)
   - 'Hiburan' (nonton bioskop, game, konser, tiket wisata)
   - 'Tagihan' (listrik, air, langganan internet, pulsa, subscription)
   - 'Kesehatan' (obat, apotek, dokter, periksa gigi)
   - 'Pendidikan' (kursus, sekolah, buku pelajaran)
   - 'Lainnya' (jika tidak masuk ke kategori di atas)
3. 'type': Tentukan 'needs' (kebutuhan wajib/pokok seperti sembako, bensin, tagihan rutin, sekolah, obat) atau 'wants' (keinginan/gaya hidup seperti jajan kopi, nongkrong, tiket bioskop, gadget, fashion).
4. 'date': Tanggal transaksi yang tertera di struk/nota. Konversikan dari format apa pun ke 'YYYY-MM-DD'. Jika tanggal tidak terbaca, gunakan tanggal hari ini: '" . now()->format('Y-m-d') . "'.
5. 'description': Ringkasan belanja yang ringkas dan informatif (maksimal 50 karakter), misalnya 'Jajan Kopi Susu di Starbucks', 'Belanja Sembako Alfamart', 'Pembayaran Listrik PLN'.

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
                                    'description' => 'Total final payment amount in IDR (Rupiah), rounded, ignoring decimals.'
                                ],
                                'category' => [
                                    'type' => 'STRING',
                                    'enum' => ['Makanan', 'Transportasi', 'Belanja', 'Hiburan', 'Tagihan', 'Kesehatan', 'Pendidikan', 'Lainnya']
                                ],
                                'type' => [
                                    'type' => 'STRING',
                                    'enum' => ['needs', 'wants']
                                ],
                                'date' => [
                                    'type' => 'STRING',
                                    'description' => 'Transaction date normalized to YYYY-MM-DD format.'
                                ],
                                'description' => [
                                    'type' => 'STRING',
                                    'description' => 'Concise summary of purchase/merchant, e.g. "Kopi & Roti Starbucks" or "Groceries Alfamart". Max 50 characters.'
                                ]
                            ],
                            'required' => ['amount', 'category', 'type', 'date', 'description']
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $jsonText = $response->json('candidates.0.content.parts.0.text');
                    $data = json_decode(trim($jsonText), true);

                    if (is_array($data) && isset($data['amount'])) {
                        // Double check validity
                        $validCategories = ['Makanan', 'Transportasi', 'Belanja', 'Hiburan', 'Tagihan', 'Kesehatan', 'Pendidikan', 'Lainnya'];
                        if (!in_array($data['category'] ?? '', $validCategories)) {
                            $data['category'] = 'Lainnya';
                        }
                        if (($data['type'] ?? '') !== 'needs' && ($data['type'] ?? '') !== 'wants') {
                            $data['type'] = 'needs';
                        }
                        return response()->json([
                            'success' => true,
                            'data' => $data,
                            'message' => 'Struk berhasil dipindai oleh AI!'
                        ]);
                    }
                }

                \Illuminate\Support\Facades\Log::warning("Gemini API OCR failed or returned invalid JSON structure: " . $response->body());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Gemini API OCR Error: " . $e->getMessage());
            }
        }

        // Local Fallback / Simulation
        $mocks = [
            [
                'amount' => 45000,
                'category' => 'Makanan',
                'type' => 'wants',
                'description' => 'Simulasi Scan: Kopi & Roti Bakar'
            ],
            [
                'amount' => 125000,
                'category' => 'Belanja',
                'type' => 'needs',
                'description' => 'Simulasi Scan: Belanja Mingguan Minimarket'
            ],
            [
                'amount' => 50000,
                'category' => 'Transportasi',
                'type' => 'needs',
                'description' => 'Simulasi Scan: Pengisian Bahan Bakar Kendaraan'
            ],
            [
                'amount' => 85000,
                'category' => 'Hiburan',
                'type' => 'wants',
                'description' => 'Simulasi Scan: Tiket Bioskop & Popcorn'
            ],
            [
                'amount' => 350000,
                'category' => 'Tagihan',
                'type' => 'needs',
                'description' => 'Simulasi Scan: Pembayaran Tagihan Listrik Bulanan'
            ],
            [
                'amount' => 15000,
                'category' => 'Makanan',
                'type' => 'needs',
                'description' => 'Simulasi Scan: Nasi Goreng Kaki Lima'
            ],
        ];

        $randomMock = $mocks[array_rand($mocks)];
        $randomMock['date'] = now()->format('Y-m-d');

        // Give the fallback a short artificial delay to simulate OCR scanning
        usleep(800000); // 800ms

        return response()->json([
            'success' => true,
            'data' => $randomMock,
            'message' => 'Simulasi Scan: Struk berhasil dipindai oleh sistem lokal (Offline Fallback).'
        ]);
    }
}
