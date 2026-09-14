<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('murids', function (Blueprint $table) {
            // Menambah kolom nomor WA setelah kolom kelas
            $table->string('no_wa_ortu')->nullable()->after('kelas');
        });
    }

    public function down()
    {
        Schema::table('murids', function (Blueprint $table) {
            $table->dropColumn('no_wa_ortu');
        });
    }
};