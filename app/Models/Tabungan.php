<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tabungan extends Model
{
    use HasFactory;
    
    // Izinkan kolom-kolom ini diisi
    protected $fillable = ['nis', 'tanggal', 'jenis', 'nominal', 'keterangan'];
}