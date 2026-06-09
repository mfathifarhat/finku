# FinKu - AI-Powered Gamified Financial Assistant 🚀

🌐 **Live Demo**: [https://ncpakjapan.my.id/](https://ncpakjapan.my.id/)

**FinKu** adalah aplikasi asisten pengelolaan keuangan pribadi yang dinamis, interaktif, dan tergamifikasi. Dirancang khusus untuk membantu pengguna muda di Indonesia mengelola arus kas bulanan secara cerdas dan menyenangkan. Aplikasi ini menggabungkan pencatatan keuangan pintar, analisis tren visual, pelacakan target tabungan, obrolan interaktif AI (chatbot), serta sistem leveling dan pencapaian lencana (badges).

---

## ✨ Fitur Utama

### 1. 📊 Dashboard Finansial Terpadu
* **Statistik Arus Kas**: Tampilan ringkas total pemasukan, pengeluaran, dan sisa saldo berjalan bulan ini.
* **Metode Anggaran Dinamis**: Mendukung berbagai metode alokasi anggaran populer:
  * **50/30/20** (Kebutuhan/Keinginan/Tabungan)
  * **70/20/10**
  * **Kustom** (Menentukan persentase sesuai keinginan pengguna)
* **Batas Pengeluaran & Peringatan**: Sistem secara otomatis memperingatkan pengguna ketika pengeluaran untuk pos tertentu telah melewati batas alokasi anggaran yang ditetapkan.

### 2. 🧾 AI OCR Scanner (Pemasukan & Pengeluaran)
* **Scan Struk Belanja**: Unggah foto struk belanja untuk mengisi formulir pencatatan pengeluaran (total bayar, kategori, jenis pengeluaran, tanggal, deskripsi) secara otomatis menggunakan AI.
* **Scan Bukti Transfer**: Unggah bukti transfer masuk untuk mencatat pemasukan secara otomatis.
* **Pencegahan Kegagalan Kuota**: Jika kuota token Gemini habis, sistem secara reaktif menonaktifkan tombol scan, mengubah status visual scanner (disabled), dan menampilkan banner peringatan premium agar pengguna dapat melakukan input manual.

### 3. 💬 Finku-AI Chatbot (Online & Offline Mode)
* **Asisten Keuangan Cerdas**: Konsultasikan profil keuangan Anda, minta saran hemat, atau tips pencatatan keuangan secara interaktif dengan bahasa yang bersahabat dan personal.
* **Online Mode (Gemini API)**: Menggunakan kecerdasan LLM Gemini 1.5 Flash dengan sistem instruksi yang disesuaikan secara dinamis dengan data finansial terbaru pengguna.
* **Offline Mode (Rule-Based Engine)**: Tombol alih mode offline opsional yang muncul ketika token Gemini habis. Pengguna tetap mendapatkan saran finansial taktis secara lokal untuk skenario pengeluaran, tabungan, sapaan kasual, dan motivasi.

### 4. 🎯 Pelacak Target Tabungan (Financial Goals)
* **Target Bersyarat**: Buat rencana tabungan dengan target nominal dan tenggat waktu tertentu.
* **Top-Up Saldo**: Simulasikan aktivitas menabung untuk mencapai target finansial.
* **Pencapaian XP**: Mendapatkan **+15 XP** untuk setiap kali menabung.

### 5. 🎮 Gamifikasi Keuangan (XP, Level, & Badges)
* **Sistem XP & Leveling**: Dapatkan XP dari aktivitas produktif seperti mencatat transaksi (+10 XP) atau menabung (+15 XP) untuk menaikkan level karakter finansial Anda.
* **Quest & Badges**: Buka 6 lencana pencapaian eksklusif (seperti *Saver Rookie*, *Budget Master*, *Wealth Builder*) secara otomatis saat kriteria finansial terpenuhi.

---

## 🛠️ Tech Stack

* **Backend Framework**: Laravel 11
* **Frontend Library**: Vue 3 (Composition API / `<script setup>`)
* **State & Routing**: Inertia.js (Inertia Vue 3 Adapter)
* **Styling**: Tailwind CSS v4 (menggunakan inline theme + custom fonts)
* **Language**: TypeScript & PHP
* **Bundler**: Vite
* **Database**: SQLite / MySQL / PostgreSQL (default menggunakan SQLite untuk kemudahan portabilitas)
* **AI Engine**: Google Gemini Pro & Gemini 1.5 Flash (via Generative Language API)

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi di lingkungan lokal Anda:

### 1. Prasyarat (Prerequisites)
Pastikan Anda sudah menginstal alat-alat berikut di komputer Anda:
* **PHP >= 8.2**
* **Composer**
* **Node.js (versi LTS direkomendasikan) & NPM**

### 2. Kloning Repositori
```bash
git clone https://github.com/username/imitcomp.git
cd imitcomp/finku
```

### 3. Instal Dependensi Backend (Composer)
```bash
composer install
```

### 4. Instal Dependensi Frontend (NPM)
```bash
npm install
```

### 5. Konfigurasi Environment File
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` dan konfigurasikan database Anda. Jika menggunakan SQLite (paling direkomendasikan untuk uji coba lokal):
```ini
DB_CONNECTION=sqlite
# Kosongkan baris DB_DATABASE atau biarkan menunjuk ke database.sqlite bawaan
```
Tambahkan juga API Key Gemini Anda di file `.env` untuk mengaktifkan fitur AI OCR dan Chatbot:
```ini
GEMINI_API_KEY=isi-api-key-gemini-anda-di-sini
GEMINI_MODEL=gemini-1.5-flash
```

### 6. Generate Application Key & Migrasi Database
Jalankan migrasi database beserta data awal (seeders):
```bash
php artisan key:generate
php artisan migrate --seed
```
*Catatan: Seeders akan menginisialisasi lencana gamifikasi awal agar sistem pencapaian lencana berjalan sempurna.*

### 7. Jalankan Server Lokal
Jalankan server pengembangan Laravel:
```bash
php artisan serve
```
Di terminal terpisah, jalankan server pengembangan frontend (Vite):
```bash
npm run dev
```

Buka browser Anda dan akses aplikasi di `http://127.0.0.1:8000`.

---

## 🏗️ Struktur Proyek

```text
finku/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ChatController.php        # Logika AI Chatbot (Online/Offline)
│   │       ├── ExpenseController.php     # Pencatatan Pengeluaran & AI Receipt Scanner
│   │       ├── IncomeController.php      # Pencatatan Pemasukan & AI Transfer Scanner
│   │       └── DashboardController.php   # Manajemen Anggaran dan Profil
│   └── Models/
│       └── User.php                      # Model User dengan Logika XP dan Badge
├── config/
│   └── services.php                      # Konfigurasi integrasi layanan Gemini
├── database/
│   ├── migrations/                       # Skema database (Expenses, Incomes, Goals, Badges, dll.)
│   └── seeders/                          # Pengisian data awal Badges
├── resources/
│   ├── css/
│   │   └── app.css                       # Konfigurasi Tailwind CSS v4 custom theme
│   └── js/
│       ├── components/
│       │   ├── ui/                       # Komponen UI (Button, Input, Alert, dll.)
│       │   └── ChatWidget.vue            # Widget Asisten Chatbot Finku-AI
│       ├── layouts/                      # Layout halaman dashboard
│       └── pages/
│           ├── Welcome.vue               # Landing Page Interaktif & Responsive
│           ├── Dashboard.vue             # Dashboard Anggaran
│           ├── Expenses/
│           │   └── Index.vue             # Halaman Log Transaksi & AI OCR Scanner Modals
│           ├── Goals/
│           │   └── Index.vue             # Halaman Keuangan Berencana (Target Tabungan)
│           ├── Insights/
│           │   └── Index.vue             # Analisis Grafik Visual Keuangan
│           └── Gamification/
│               └── Profile.vue           # Detail Karakter Finansial (XP, Badges)
└── routes/
    └── web.php                           # Definisi rute aplikasi
```

---

## 🛠️ Cara Kerja Penanganan Token AI Habis
Aplikasi ini memiliki sistem toleransi kegagalan (fault tolerance) yang ramah pengguna apabila kuota token Gemini API habis atau kunci API tidak terkonfigurasi:

1. **Pada Finku-AI Chatbot**:
   * Ketika chatbot mengirimkan pesan dan menerima kegagalan otentikasi/limit dari API Gemini, server merespons dengan kode error `ai_inactive`.
   * Di frontend, UI widget chatbot akan menampilkan indikator status "Offline" (kuning berkedip) di samping pesan penjelasan *"Mohon maaf AI sedang tidak aktif"*.
   * Pengguna diberikan tombol kuning untuk mengaktifkan **Asisten Offline** yang memproses pertanyaan keuangan secara lokal melalui mesin pencocokan aturan di chatbot lokal.

2. **Pada AI OCR Scanners**:
   * Area unggah file struk belanja dan bukti transfer masuk tetap aktif saat modal dibuka pertama kali.
   * Jika saat mengunggah foto server mengembalikan respons `ai_inactive` (kuota token habis), status `isAiScannerActive` diubah menjadi `false`.
   * Area unggah diubah secara dinamis menjadi tidak bisa diklik, berikon `CameraOff`, dan berwarna abu-abu.
   * Muncul spanduk (banner) peringatan merah elegan di bagian atas modal yang menyarankan pengguna melakukan pencatatan transaksi secara manual.

---

## 📝 Lisensi
Proyek ini dibangun untuk tujuan kompetisi IMITCOMP 2026. Seluruh hak cipta dilindungi oleh tim pengembang.
