<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tabungans', function (Blueprint $table) {
            $table->id();
            $table->string('nis'); // Untuk menyambungkan ke data murid
            $table->date('tanggal');
            $table->enum('jenis', ['Setor', 'Tarik']); // Hanya ada 2 pilihan
            $table->integer('nominal'); // Jumlah uang
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabungans');
    }
};
