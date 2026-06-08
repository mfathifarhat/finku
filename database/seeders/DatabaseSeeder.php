<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Badge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Badges
        Badge::updateOrCreate(['code' => 'sang_visioner'], [
            'name' => 'Sang Visioner',
            'description' => 'Membuat target tabungan pertama kamu.',
            'icon' => 'Target',
        ]);

        Badge::updateOrCreate(['code' => 'hemat_pangkal_kaya'], [
            'name' => 'Hemat Pangkal Kaya',
            'description' => 'Mencatat 5 pengeluaran berturut-turut.',
            'icon' => 'ShieldCheck',
        ]);

        Badge::updateOrCreate(['code' => 'dana_darurat_master'], [
            'name' => 'Dana Darurat Master',
            'description' => 'Mencapai 50% dari target tabungan Dana Darurat.',
            'icon' => 'TrendingUp',
        ]);

        Badge::updateOrCreate(['code' => 'suhu_budgeting'], [
            'name' => 'Suhu Budgeting',
            'description' => 'Menetapkan anggaran pendapatan bulanan pertama.',
            'icon' => 'Award',
        ]);

        // Test User
        User::factory()->create([
            'name' => 'User Finku',
            'email' => 'user@finku.com',
            'password' => bcrypt('password'),
            'xp' => 120,
            'level' => 1,
            'monthly_income' => 5000000, // Rp 5.000.000
        ]);
    }
}
