<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('murids', function (Blueprint $table) {
            // Menambahkan kolom face_data untuk simpan rumus wajah AI
            $table->longText('face_data')->nullable();
        });
    }

    public function down()
    {
        Schema::table('murids', function (Blueprint $table) {
            $table->dropColumn('face_data');
        });
    }
};