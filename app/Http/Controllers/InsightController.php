<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class InsightController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Category breakdown for Chart.js
        $categoryBreakdown = $user->expenses()
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();
            
        // Daily spending trend in the last 30 days
        $dailyTrend = $user->expenses()
            ->select('date', DB::raw('SUM(amount) as total'))
            ->where('date', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Calculate needs vs wants totals
        $needsTotal = $user->expenses()->where('type', 'needs')->sum('amount');
        $wantsTotal = $user->expenses()->where('type', 'wants')->sum('amount');
        $savingsTotal = $user->goals()->sum('current_amount');

        // Detailed AI Recommendations (Gemini API with local Rule-based fallback)
        $recommendations = [];
        $income = $user->monthly_income;
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if ($income == 0) {
            $recommendations[] = [
                'title' => 'Atur Anggaran Pendapatan',
                'description' => 'Kamu belum mengatur pendapatan bulanan. Atur dulu pendapatanmu di dashboard agar kami bisa memberikan analisis anggaran yang tepat.',
                'type' => 'warning',
            ];
        } elseif (!empty($apiKey)) {
            try {
                $catList = '';
                foreach ($categoryBreakdown as $c) {
                    $catList .= "- {$c->category}: Rp " . number_format($c->total, 0, ',', '.') . "\n";
                }

                $prompt = "Kamu adalah Finku-AI, asisten keuangan pribadi cerdas dan memotivasi untuk pengguna muda di Indonesia.
Analisis data finansial pengguna berikut untuk bulan ini:
- Pendapatan Bulanan: Rp " . number_format($income, 0, ',', '.') . "
- Kebutuhan (Needs) Terpakai: Rp " . number_format($needsTotal, 0, ',', '.') . "
- Keinginan (Wants) Terpakai: Rp " . number_format($wantsTotal, 0, ',', '.') . "
- Tabungan (Savings) Terkumpul: Rp " . number_format($savingsTotal, 0, ',', '.') . "

Rincian Pengeluaran per Kategori:
{$catList}

Berikan analisis keuangan terperinci dan saran praktis penghematan dalam Bahasa Indonesia.
Format respon sebagai JSON objek murni tanpa tambahan markdown block atau teks lainnya dengan struktur:
{
  \"recommendations\": [
    {
      \"title\": \"Judul saran singkat\",
      \"description\": \"Penjelasan langkah penghematan praktis atau apresiasi jika sehat\",
      \"type\": \"success|warning|danger|info\"
    }
  ]
}";

                $response = Http::timeout(6)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    $text = trim($text);
                    if (str_starts_with($text, '```json')) {
                        $text = substr($text, 7);
                    }
                    if (str_ends_with($text, '```')) {
                        $text = substr($text, 0, -3);
                    }
                    $text = trim($text);

                    $parsed = json_decode($text, true);
                    if (is_array($parsed) && isset($parsed['recommendations'])) {
                        $recommendations = $parsed['recommendations'];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Gemini API error in InsightController: " . $e->getMessage());
            }
        }

        // Local Rule-based fallback if $recommendations is empty
        if (empty($recommendations)) {
            if ($income == 0) {
                $recommendations[] = [
                    'title' => 'Atur Anggaran Pendapatan',
                    'description' => 'Kamu belum mengatur pendapatan bulanan. Atur dulu pendapatanmu di dashboard agar kami bisa memberikan analisis anggaran yang tepat.',
                    'type' => 'warning',
                ];
            } else {
                $totalExpenses = $needsTotal + $wantsTotal;
                $savingsRatio = $income > 0 ? ($savingsTotal / $income) * 100 : 0;
                $wantsRatio = $income > 0 ? ($wantsTotal / $income) * 100 : 0;
                $needsRatio = $income > 0 ? ($needsTotal / $income) * 100 : 0;

                if ($totalExpenses > $income) {
                    $recommendations[] = [
                        'title' => 'Defisit Anggaran (Cash Flow Negatif)',
                        'description' => 'Awas! Pengeluaranmu bulan ini sudah melebihi pendapatanmu. Segera rem pengeluaran non-prioritas untuk menghindari hutang.',
                        'type' => 'danger',
                    ];
                }

                if ($wantsRatio > 30) {
                    $recommendations[] = [
                        'title' => 'Pengeluaran Keinginan Berlebih',
                        'description' => sprintf('Pengeluaran untuk kategori Keinginan (Wants) sudah mencapai %.1f%% dari total pendapatan. Disarankan untuk membatasi belanja impulsif atau nongkrong minggu ini.', $wantsRatio),
                        'type' => 'warning',
                    ];
                }

                if ($savingsRatio < 20) {
                    $recommendations[] = [
                        'title' => 'Tingkatkan Tabungan Kamu',
                        'description' => sprintf('Persentase tabunganmu saat ini baru sekitar %.1f%% dari target ideal 20%%. Coba sisihkan uang tabungan di awal bulan tepat setelah gajian!', $savingsRatio),
                        'type' => 'info',
                    ];
                }

                $topCategory = $user->expenses()
                    ->select('category', DB::raw('SUM(amount) as total'))
                    ->groupBy('category')
                    ->orderBy('total', 'desc')
                    ->first();

                if ($topCategory && $topCategory->total > ($income * 0.25)) {
                    $recommendations[] = [
                        'title' => 'Konsentrasi Pengeluaran Terbesar',
                        'description' => sprintf('Pengeluaran terbesar kamu ada di kategori "%s" sebesar Rp %s. Coba cari alternatif yang lebih hemat untuk pos pengeluaran ini.', $topCategory->category, number_format($topCategory->total, 0, ',', '.')),
                        'type' => 'warning',
                    ];
                }

                if (count($recommendations) === 0) {
                    $recommendations[] = [
                        'title' => 'Keuangan Sangat Sehat!',
                        'description' => 'Luar biasa! Alokasi keuanganmu sangat disiplin dan seimbang. Pertahankan kebiasaan baik ini untuk mencapai kebebasan finansial lebih cepat.',
                        'type' => 'success',
                    ];
                }
            }
        }

        return Inertia::render('Insights/Index', [
            'categoryBreakdown' => $categoryBreakdown,
            'dailyTrend' => $dailyTrend,
            'needsTotal' => $needsTotal,
            'wantsTotal' => $wantsTotal,
            'savingsTotal' => $savingsTotal,
            'recommendations' => $recommendations,
        ]);
    }
}
