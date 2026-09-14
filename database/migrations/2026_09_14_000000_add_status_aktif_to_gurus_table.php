<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            if (!Schema::hasColumn('gurus', 'status_aktif')) {
                $table->boolean('status_aktif')->default(1)->after('nama_guru');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn('status_aktif');
        });
    }
};