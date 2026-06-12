<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $user = $request->user();
        $userMessage = $request->message;
        $history = $request->history ?? [];

        // 1. Gather User Financial Context
        $income = $user->monthly_income;
        $expenses = $user->expenses()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get();

        $spentNeeds = $expenses->where('type', 'needs')->sum('amount');
        $spentWants = $expenses->where('type', 'wants')->sum('amount');

        $needsPercent = 50;
        $wantsPercent = 30;
        $savingsPercent = 20;

        if ($user->budgeting_method === '70-20-10') {
            $needsPercent = 70;
            $wantsPercent = 20;
            $savingsPercent = 10;
        } elseif ($user->budgeting_method === 'custom') {
            $custom = $user->custom_budget_percentages;
            $needsPercent = $custom['needs'] ?? 50;
            $wantsPercent = $custom['wants'] ?? 30;
            $savingsPercent = $custom['savings'] ?? 20;
        }

        $limitNeeds = ($income * $needsPercent) / 100;
        $limitWants = ($income * $wantsPercent) / 100;
        $limitSavings = ($income * $savingsPercent) / 100;
        $spentSavings = $user->goals()->sum('current_amount');

        // Active Goals
        $activeGoals = $user->goals()->where('current_amount', '<', DB::raw('target_amount'))->get();
        $goalsText = "";
        if ($activeGoals->isEmpty()) {
            $goalsText = "- Tidak ada target tabungan aktif saat ini.";
        } else {
            foreach ($activeGoals as $goal) {
                $goalsText .= sprintf("- %s: Terkumpul Rp %s dari target Rp %s\n",
                    $goal->name,
                    number_format($goal->current_amount, 0, ',', '.'),
                    number_format($goal->target_amount, 0, ',', '.')
                );
            }
        }

        // Gamification Context
        $badges = $user->badges()->pluck('name')->join(', ');
        $badgesText = empty($badges) ? 'Belum ada lencana yang terbuka.' : $badges;

        $mascotName = $user->mascot_name ?? 'Finku Fox';
        $equippedAccessoryList = $user->equipped_accessories ?? [];
        
        // Map codes to human readable accessory names
        $accessoryMap = [
            'glasses_cool' => 'Kacamata Hitam Kece',
            'hat_detective' => 'Topi Detektif',
            'scarf_winter' => 'Syal Rajut Hangat',
            'tie_fancy' => 'Dasi Kupu-Kupu Elegan',
            'crown_gold' => 'Mahkota Emas Megah',
            'cape_royal' => 'Jubah Kerajaan Merah',
        ];
        $mappedAccessories = [];
        foreach ($equippedAccessoryList as $code) {
            $mappedAccessories[] = $accessoryMap[$code] ?? $code;
        }
        $accessoriesText = empty($mappedAccessories) ? 'Tidak mengenakan aksesoris apa pun saat ini.' : implode(', ', $mappedAccessories);

        // 2. Build System Prompt
        $systemInstruction = "Kamu adalah {$mascotName}, maskot keuangan interaktif rubah pendamping setia dan asisten keuangan pribadi yang cerdas, lucu, ramah, bersahabat, dan memotivasi untuk pengguna muda di Indonesia. Pengguna telah memberimu nama '{$mascotName}'.
Kamu sedang berbicara dengan pemilik/pengguna bernama: {$user->name}.
Selalu gunakan sapaan ramah seperti 'Kak {$user->name}'. Jawab dalam Bahasa Indonesia dengan nada santai, positif, ceria, dan penuh semangat (tidak kaku/formal, sesekali gunakan ekspresi rubah imut seperti *melompat gembira* atau *mengedipkan mata*).

Berikut adalah ringkasan data profil keuangan aktual dari Kak {$user->name} bulan ini:
- Pendapatan Bulanan: Rp " . number_format($income, 0, ',', '.') . "
- Metode Budgeting: {$user->budgeting_method} (Alokasi: Needs {$needsPercent}%, Wants {$wantsPercent}%, Savings {$savingsPercent}%)
- Pengeluaran Terpakai:
  * Kebutuhan (Needs): Rp " . number_format($spentNeeds, 0, ',', '.') . " (Limit Budget: Rp " . number_format($limitNeeds, 0, ',', '.') . ")
  * Keinginan (Wants): Rp " . number_format($spentWants, 0, ',', '.') . " (Limit Budget: Rp " . number_format($limitWants, 0, ',', '.') . ")
- Progres Tabungan & Goals Aktif:
{$goalsText}
- Pencapaian Gamifikasi & Status Maskot:
  * Nama Maskot Kamu (Pemberian Pengguna): {$mascotName}
  * Level Pengguna: {$user->level}
  * XP saat ini: {$user->xp} (XP yang dibutuhkan untuk naik level berikutnya: " . ($user->level * 100) . ")
  * Lencana Terbuka: {$badgesText}
  * Aksesoris yang Sedang Kamu (Maskot) Pakai: {$accessoriesText}
  * Saldo Finku Coins Pengguna: {$user->coins} koin

Tugasmu:
1. Berikan saran keuangan yang praktis, cerdas, dan suportif berdasarkan data keuangan aktual di atas jika pengguna menanyakannya.
2. Jika pengguna over-budget (pengeluaran melebihi limit), berikan solusi taktis (seperti mengurangi jajan kopi/keinginan).
3. Jika pengguna bertanya tentang naik level, quest lencana, atau cara cari koin, ingatkan mereka untuk rajin mencatat transaksi (+10 XP, +5 Finku Coins) dan menabung (+15 XP, +10 Finku Coins).
4. Jika pengguna menyebutkan atau menanyakan tentang aksesorismu (seperti kacamata, topi, jubah, dll.), ucapkan terima kasih dengan riang atas dandanan kece yang dibeli menggunakan koin Finku mereka!
5. Buat tanggapan yang ringkas, mudah dipahami, dan batasi hingga 2-3 paragraf pendek agar nyaman dibaca di layar chat kecil.";

        // 3. If force_offline is requested, go directly to local fallback
        if ($request->force_offline) {
            $response = $this->getLocalFallbackResponse($userMessage, $user, $spentNeeds, $limitNeeds, $spentWants, $limitWants, $activeGoals, $needsPercent, $wantsPercent, $income);
            return response()->json([
                'success' => true,
                'text' => $response,
                'mode' => 'local_fallback'
            ]);
        }

        // 4. Invoke Gemini API if Key is set
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (!empty($apiKey)) {
            try {
                // Map the conversation history to Gemini parts format
                $contents = [];
                foreach ($history as $chat) {
                    $role = ($chat['role'] ?? 'user') === 'user' ? 'user' : 'model';
                    $contents[] = [
                        'role' => $role,
                        'parts' => [
                            ['text' => $chat['text'] ?? '']
                        ]
                    ];
                }

                // Append current user message
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userMessage]
                    ]
                ];

                $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'contents' => $contents
                ]);

                if ($response->successful()) {
                    $aiResponse = $response->json('candidates.0.content.parts.0.text');
                    if (!empty($aiResponse)) {
                        return response()->json([
                            'success' => true,
                            'text' => trim($aiResponse),
                            'mode' => 'gemini'
                        ]);
                    }
                }

                Log::warning("Gemini API Chat failure response: " . $response->body());
            } catch (\Exception $e) {
                Log::warning("Gemini API Chat Exception: " . $e->getMessage());
            }
        }

        // 5. If API is not configured or failed, return inactive state to let user switch to offline mode
        return response()->json([
            'success' => false,
            'error' => 'ai_inactive',
            'message' => 'Mohon maaf AI sedang tidak aktif'
        ]);
    }

    private function getLocalFallbackResponse($msg, $user, $spentNeeds, $limitNeeds, $spentWants, $limitWants, $activeGoals, $needsPercent, $wantsPercent, $income)
    {
        $msg = trim(strtolower($msg));
        $name = $user->name;

        // 1. GREETINGS (Halo, Hai, Pagi, Siang, Sore, Malam, dll.)
        if (preg_match('/\b(halo|hai|hei|hello|pagi|siang|sore|malam|assalamualaikum|ping|p)\b/', $msg)) {
            $greetings = [
                "Halo **Kak {$name}**! 👋 Aku Finku-AI, asisten keuangan pribadimu. Kondisi keuangan kita bulan ini cukup menarik untuk dipantau. Ada yang ingin Kakak tanyakan tentang alokasi anggaran, status over-budget, atau cara naik level XP hari ini?",
                "Hai **Kak {$name}**! Glad to see you here. 😊 Gimana catatan keuanganmu hari ini? Mau cek budget bulanan atau butuh tips biar nggak over-budget?",
                "Halo **Kak {$name}**! 👋 Semoga hari ini menyenangkan ya. Finku-AI siap nemenin perjalanan finansialmu. Mau nanya tentang apa nih? Kondisi keuangan, tabungan, atau tips naik level?"
            ];
            return $greetings[array_rand($greetings)];
        }

        // 2. THANKS & APPRECIATION (Terima kasih, Makasih, Keren, Mantap, dll.)
        if (preg_match('/\b(terima\s*kasih|makasih|thanks|thank\s*you|ty|suwun|nuhun|keren|mantap|kece)\b/', $msg)) {
            $thanks = [
                "Sama-sama **Kak {$name}**! Senang banget bisa bantu kamu. Jangan lupa buat catat transaksi hari ini biar XP kamu nambah (+10 XP) ya! 🚀",
                "Siapp, sama-sama **Kak {$name}**! Tetap konsisten catat keuangan ya. Finku-AI bangga sama progres kamu! 😉",
                "Anytime **Kak {$name}**! Tetap semangat ya jalanin quest finansialnya. Kamu pasti bisa capai kebebasan finansial!"
            ];
            return $thanks[array_rand($thanks)];
        }

        // 3. CASUAL AGREEMENT (Oke, Siap, Sip, Yoi, dll.)
        if (preg_match('/^(oke|ok|siap|siapp|sip|yoi|yo|ya|baik|yes)$/', $msg) || preg_match('/\b(oke\s*sip|siap\s*bos)\b/', $msg)) {
            return "Mantap **Kak {$name}**! 👍 Ada hal lain yang mau dianalisis bareng? Misalnya status budget kamu bulan ini atau target tabungan aktif.";
        }

        // 4. EMERGENCY FUND / DANA DARURAT
        if (preg_match('/\b(darurat|emergency|dana\s*darurat)\b/', $msg)) {
            $ddGoal = $activeGoals->filter(function($g) {
                return str_contains(strtolower($g->name), 'darurat') || str_contains(strtolower($g->name), 'emergency');
            })->first();

            $ddStatus = "";
            if ($ddGoal) {
                $percent = $ddGoal->target_amount > 0 ? ($ddGoal->current_amount / $ddGoal->target_amount) * 100 : 0;
                $ddStatus = "Saat ini kamu sudah membuat target dana darurat **\"{$ddGoal->name}\"** dengan progres **" . number_format($percent, 1) . "%** (Rp " . number_format($ddGoal->current_amount, 0, ',', '.') . " / Rp " . number_format($ddGoal->target_amount, 0, ',', '.') . "). Bagus sekali!\n\n";
            }

            return "Halo **Kak {$name}**, dana darurat itu adalah pondasi keuangan paling penting! Fungsi utamanya adalah melindungimu saat ada musibah mendadak (kehilangan pekerjaan, sakit, atau perbaikan kendaraan).\n\n" .
                   $ddStatus .
                   "**Tips Dana Darurat dari Finku-AI:**\n" .
                   "- **Berapa idealnya?** Jika lajang, kumpulkan 3-6x pengeluaran bulanan. Jika sudah berkeluarga, kumpulkan 6-12x pengeluaran bulanan.\n" .
                   "- **Taruh di mana?** Simpan di rekening terpisah yang likuid (mudah dicairkan) dan tanpa biaya admin besar, atau di Reksa Dana Pasar Uang (RDPU).\n" .
                   "- **Caranya?** Konsisten sisihkan **10-20%** pendapatan bulananmu di awal gajian menggunakan fitur **Target Tabungan** FinKu!";
        }

        // 5. BUDGETING METHODS / ATUR DUIT
        if (preg_match('/\b(metode|budgeting|50-30-20|70-20-10|atur\s*uang|atur\s*duit|bagi\s*gaji|pembagian)\b/', $msg)) {
            return "Metode pembagian anggaran membantu kita membatasi pengeluaran secara terarah, **Kak {$name}**. Di FinKu, ada beberapa metode populer:\n\n" .
                   "1. **Metode 50-30-20 (Klasik)**:\n" .
                   "   - **50% Needs**: Kebutuhan wajib (makan, kontrakan, cicilan, tagihan).\n" .
                   "   - **30% Wants**: Keinginan (ngopi, beli baju, langganan film, hiburan).\n" .
                   "   - **20% Savings**: Tabungan, investasi, & dana darurat.\n\n" .
                   "2. **Metode 70-20-10 (Bagi yang Berhemat/Banyak Cicilan)**:\n" .
                   "   - **70% Needs**: Kebutuhan dasar dan cicilan.\n" .
                   "   - **20% Wants/Savings**: Fleksibel untuk keinginan & tabungan.\n" .
                   "   - **10% Investasi/Proteksi**: Fokus jangka panjang.\n\n" .
                   "Saat ini Kakak menggunakan metode **{$user->budgeting_method}**. Kakak bisa menyesuaikan alokasi ini secara custom di halaman dashboard agar sesuai dengan gaya hidup!";
        }

        // 6. ASSET PURCHASE & INVESTMENT (Beli Mobil, Motor, Rumah, Emas, Saham, Nikah)
        if (preg_match('/\b(beli|investasi|saham|reksadana|emas|crypto|kripto|mobil|motor|rumah|nikah|menikah|gadget|hp|laptop|jalan-jalan|liburan)\b/', $msg)) {
            return "Wah, target luar biasa, **Kak {$name}**! Untuk mencapai pembelian barang besar seperti kendaraan, rumah, atau biaya pernikahan, kuncinya adalah perencanaan matang:\n\n" .
                   "**Strategi FinKu untuk Capai Impianmu:**\n" .
                   "1. **Buat Target Spesifik**: Masuk ke menu **Target Tabungan** di FinKu, lalu buat target baru (misal: \"Beli Laptop Baru\"). Tentukan nominal target dan tenggat waktu.\n" .
                   "2. **Pecah Nominal**: Sistem FinKu akan otomatis menghitung berapa nominal yang harus disisihkan setiap bulan agar target tercapai tepat waktu.\n" .
                   "3. **Pilih Instrumen Investasi**: \n" .
                   "   - Untuk jangka pendek (< 1 tahun): Gunakan tabungan biasa atau Reksa Dana Pasar Uang (RDPU) karena risikonya sangat rendah.\n" .
                   "   - Untuk jangka panjang (> 3 tahun): Kakak bisa belajar investasi Reksa Dana Saham, Emas, atau Saham Blue Chip untuk melawan inflasi.";
        }

        // 7. LOW INCOME / GAJI PAS-PASAN
        if (preg_match('/\b(gaji\s*pas-pasan|gaji\s*kecil|gaji\s*dikit|pendapatan\s*rendah|susah\s*nabung|gabisa\s*nabung)\b/', $msg)) {
            return "Aku paham banget, **Kak {$name}**. Mengelola uang dengan gaji terbatas memang menantang, tapi bukan berarti nggak bisa dilakukan! Coba trik ini:\n\n" .
                   "1. **Prinsip \"Bayar Diri Sendiri Dulu\"**: Begitu gajian masuk, langsung sisihkan nominal kecil (walau cuma Rp 50.000 atau Rp 100.000). Jangan tunggu sisa belanja!\n" .
                   "2. **Audit Pengeluaran Non-Pokok**: Cek riwayat pengeluaranmu di FinKu. Cari pos kecil yang sering bocor alus (seperti biaya transfer antarbank, langganan aplikasi yang tidak ditonton, atau jajan boba).\n" .
                   "3. **Fokus Tingkatkan Skill**: Batasi pengeluaran itu ada batasnya, tapi meningkatkan pendapatan tidak terbatas. Gunakan waktu luang untuk side hustle atau upgrade kemampuan diri.";
        }

        // 8. FINKU APP FEATURES
        if (preg_match('/\b(finku|fitur|aplikasi|kelebihan|bisa\s*apa)\b/', $msg)) {
            return "Sebagai asistenmu, aku bangga banget mengenalkan **FinKu**! FinKu dirancang untuk anak muda agar mengelola keuangan jadi menyenangkan:\n\n" .
                   "- **AI Receipt Scanner**: Cukup foto struk belanjaanmu, AI FinKu akan otomatis mengurai jumlah uang, kategori, dan mencatatnya tanpa ribet ketik manual!\n" .
                   "- **Gamifikasi Level & XP**: Dapatkan +10 XP tiap mencatat transaksi dan +15 XP tiap menabung. Selesaikan misi rahasia untuk membuka lencana!\n" .
                   "- **Budgeting Dashboard**: Secara otomatis membagi pengeluaranmu menjadi pos *Needs* (Kebutuhan) dan *Wants* (Keinginan) sesuai kaidah finansial modern.";
        }

        // 9. OVER-BUDGET & BOROS (Over, Boros, Habis, Melebihi, Kanker, Bocor, dll.)
        if (preg_match('/\b(over|boros|habis|melebihi|kanker|bocor|limit|jebol|tiris|kering)\b/', $msg)) {
            $isOver = ($spentNeeds > $limitNeeds) || ($spentWants > $limitWants);
            if ($isOver) {
                $overDetails = [];
                if ($spentNeeds > $limitNeeds) {
                    $overDetails[] = "kebutuhan pokok (Needs) kamu yang udah lewat limit sebesar Rp " . number_format($spentNeeds - $limitNeeds, 0, ',', '.');
                }
                if ($spentWants > $limitWants) {
                    $overDetails[] = "keinginan (Wants) kamu yang udah ngelewatin batas sebesar Rp " . number_format($spentWants - $limitWants, 0, ',', '.');
                }
                $detailStr = implode(' dan ', $overDetails);

                return "Waduh **Kak {$name}**, kalau dilihat dari data, pengeluaran kamu emang lagi boncos nih di bagian {$detailStr}. 😭\n\n" .
                       "Tenang, Finku-AI punya tips darurat buat kamu:\n" .
                       "1. **Rem Keinginan (Wants):** Stop jajan kopi, langganan yang nggak penting, atau belanja baju dulu untuk 7 hari ke depan.\n" .
                       "2. **Menu Hemat:** Ganti makan di luar dengan masak sendiri atau cari promo makanan.\n" .
                       "3. **Evaluasi Transaksi:** Cek riwayat transaksi kamu bulan ini di menu Pengeluaran dan cari mana pos yang paling bikin bocor alus. Tetap semangat, kamu pasti bisa membalikkan keadaan!";
            } else {
                return "Aman banget, **Kak {$name}**! Pengeluaran kamu bulan ini baik kebutuhan (Needs) maupun keinginan (Wants) masih berada di bawah limit budget. Pertahankan disiplin ini ya biar sisa anggarannya bisa dialokasikan buat nabung!";
            }
        }

        // 10. SAVINGS, GOALS & MENABUNG (Tabungan, Menabung, Goal, Target, Dana Darurat, dll.)
        if (preg_match('/\b(tabungan|menabung|nabung|goal|target|celengan|simpanan)\b/', $msg)) {
            if ($activeGoals->isEmpty()) {
                return "**Kak {$name}**, aku lihat kamu belum punya target tabungan aktif nih. Menabung tanpa target itu kayak jalan tanpa arah tahu! 🧭\n\n" .
                       "Yuk, buat target pertamamu sekarang! Bisa berupa **Dana Darurat**, **Beli Gadget Baru**, atau **Liburan**. Kamu bakal dapet **+15 XP** setiap kali setor tabungan!";
            }

            $response = "Berikut ini progres target tabungan aktif kamu, **Kak {$name}**:\n";
            foreach ($activeGoals as $goal) {
                $percent = $goal->target_amount > 0 ? ($goal->current_amount / $goal->target_amount) * 100 : 0;
                $response .= sprintf("- **%s**: Terkumpul %.1f%% (Rp %s dari Rp %s)\n",
                    $goal->name,
                    $percent,
                    number_format($goal->current_amount, 0, ',', '.'),
                    number_format($goal->target_amount, 0, ',', '.')
                );
            }
            $response .= "\n💡 **Tips Finku-AI:** Selalu sisihkan uang tabungan di awal bulan begitu gajian masuk, jangan nunggu sisa belanjaan ya!";
            return $response;
        }

        // 11. BUDGET, KEUANGAN & ANGGARAN (Keuangan, Kondisi, Anggaran, Budget, Duit, Uang, dll.)
        if (preg_match('/\b(keuangan|kondisi|anggaran|budget|duit|uang|dompet|rekening|saldo|analisis|laporan)\b/', $msg)) {
            if ($income == 0) {
                return "**Kak {$name}**, kamu belum ngisi data pendapatan bulanan nih. Yuk, setel pendapatan bulanan kamu di dashboard biar aku bisa bantu bagi anggaran otomatis pakai metode pilihanmu!";
            }

            $needsWarning = $spentNeeds > $limitNeeds ? "🔴 Melebihi limit! (Selisih: Rp " . number_format($spentNeeds - $limitNeeds, 0, ',', '.') . ")" : "🟢 Aman di bawah limit";
            $wantsWarning = $spentWants > $limitWants ? "🔴 Melebihi limit! (Selisih: Rp " . number_format($spentWants - $limitWants, 0, ',', '.') . ")" : "🟢 Aman di bawah limit";

            return "Oke **Kak {$name}**, ini dia analisis cepat kondisi anggaran kamu bulan ini:\n\n" .
                   "- **Kebutuhan (Needs) - Alokasi {$needsPercent}%**\n" .
                   "  Terpakai: Rp " . number_format($spentNeeds, 0, ',', '.') . " / Rp " . number_format($limitNeeds, 0, ',', '.') . " ({$needsWarning})\n" .
                   "- **Keinginan (Wants) - Alokasi {$wantsPercent}%**\n" .
                   "  Terpakai: Rp " . number_format($spentWants, 0, ',', '.') . " / Rp " . number_format($limitWants, 0, ',', '.') . " ({$wantsWarning})\n\n" .
                   (($spentWants > $limitWants || $spentNeeds > $limitNeeds)
                       ? "Saran Finku-AI: Ada budget yang bocor nih. Coba rem dulu pengeluaran non-pokok minggu ini ya!"
                       : "Kerja bagus, Kak! Penggunaan budget kamu sangat rapi dan terkontrol. Let's keep it up!");
        }

        // 12. LEVEL, XP & GAMIFICATION (Level, XP, Lencana, Badge, Misi, Quest, dll.)
        if (preg_match('/\b(level|xp|lencana|badge|game|quest|misi|rank|leveling|karakter)\b/', $msg)) {
            $badges = $user->badges()->pluck('name')->join(', ');
            $badgesText = empty($badges) ? 'Belum ada lencana yang terbuka.' : $badges;

            return "Wih, semangat gamifikasinya mantap, **Kak {$name}**! 🎮\n" .
                   "- **Level Karakter:** Level {$user->level}\n" .
                   "- **XP Kamu:** {$user->xp} XP (Butuh " . ($user->level * 100 - $user->xp) . " XP lagi untuk naik ke Level " . ($user->level + 1) . ")\n" .
                   "- **Lencana:** {$badgesText}\n\n" .
                   "**Cara cepat dapetin XP & Lencana:**\n" .
                   "1. 📝 Catat transaksi pengeluaran baru (+10 XP)\n" .
                   "2. 💰 Setor uang ke target tabungan (+15 XP)\n" .
                   "3. 🏆 Selesaikan Quest khusus untuk buka lencana baru!";
        }

        // 13. TIPS HEMAT & UMUM (Tips, Hemat, Belanja, Cara, Bagaimana, dll.)
        if (preg_match('/\b(tips|hemat|pelit|saran|nasihat|bagaimana|gimana|cara)\b/', $msg)) {
            return "Tentu **Kak {$name}**, ini 3 tips finansial praktis dari Finku-AI:\n\n" .
                   "1. **Terapkan Aturan 24 Jam**: Sebelum beli barang keinginan (wants), tunggu 24 jam. Biasanya nafsu belanja bakal menurun!\n" .
                   "2. **Pecah Budget Mingguan**: Jangan taruh semua uang jajan di satu dompet utama. Bagi per minggu biar nggak habis di awal bulan.\n" .
                   "3. **Konsisten Mencatat**: Mencatat pengeluaran itu langkah awal sadar finansial. Setiap transaksi yang dicatat juga nambah XP kamu!";
        }

        // 14. GENERIC FALLBACK (Jika tidak ada kata kunci yang cocok)
        return "Halo **Kak {$name}**! Aku Finku-AI, asisten pribadi keuanganmu. 🤖\n\n" .
               "Aku bisa bantu kamu pantau keuangan secara interaktif lho. Coba tanyakan hal berikut ke aku:\n" .
               "- *\"Gimana kondisi keuangan/budgetku bulan ini?\"*\n" .
               "- *\"Apakah pengeluaranku boros atau over-budget?\"*\n" .
               "- *\"Gimana progres tabungan dan targetku?\"*\n" .
               "- *\"Berapa dana darurat yang ideal untukku?\"*\n" .
               "- *\"Bagaimana membagi gaji pas-pasan agar bisa menabung?\"*\n" .
               "- *\"Bagi tips hemat belanja dong!\"*\n\n" .
               "Silakan ketik pertanyaanmu secara santai ya, Kak!";
    }
}
