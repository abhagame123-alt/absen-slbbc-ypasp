<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Murid extends Model
{
    use HasFactory;

protected $fillable = [
       'nis', 
       'nama_lengkap', 
       'kelas', 
       'no_wa_ortu' // <-- Ini tambahan barunya
   ];
   }