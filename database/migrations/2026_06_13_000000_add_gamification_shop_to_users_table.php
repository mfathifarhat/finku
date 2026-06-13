<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('coins')->default(100)->after('last_activity_date');
            $table->string('mascot_name')->default('Finku Fox')->after('coins');
            $table->json('owned_accessories')->nullable()->after('mascot_name');
            $table->json('equipped_accessories')->nullable()->after('owned_accessories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['coins', 'mascot_name', 'owned_accessories', 'equipped_accessories']);
        });
    }
};
