<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User; // Mesin Akun Login
use Illuminate\Support\Facades\Hash; // Mesin Enkripsi Password
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index()
    {
        $gurus = Guru::all();
        return view('guru.index', compact('gurus'));
    }

    public function store(Request $request)
    {
        // 1. Buat Akun Login untuk Guru di tabel 'users'
        User::create([
            'name' => $request->nama_guru,
            'email' => $request->email, // Email dari form
            'password' => Hash::make($request->password), // Password dari form
        ]);

        // 2. Simpan Profil Guru di tabel 'gurus'
        Guru::create([
            'nip' => $request->nip,
            'nama_guru' => $request->nama_guru,
            'jabatan' => $request->jabatan,
        ]);
        
        return back()->with('success', 'Data Guru & Akun Login berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $guru = Guru::findOrFail($id);
        return view('guru.edit', compact('guru'));
    }

    public function update(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);
        $guru->update([
            'nip' => $request->nip,
            'nama_guru' => $request->nama_guru,
            'jabatan' => $request->jabatan,
        ]);
        return redirect()->route('guru.index')->with('success', 'Data Guru berhasil diupdate!');
    }

    public function destroy($id)
    {
        Guru::destroy($id);
        return back()->with('success', 'Data Guru berhasil dihapus!');
    }
    
    public function profil($nip)
    {
        $guru = \App\Models\Guru::where('nip', $nip)->first();
        if(!$guru) { return back()->with('error', 'Data guru tidak ditemukan.'); }

        $hadir = \App\Models\Absensi::where('nis', $nip)->where(function($q){
            $q->where('status', 'like', '%Hadir%')->orWhere('status', 'like', '%Terlambat%');
        })->count();
        $sakit = \App\Models\Absensi::where('nis', $nip)->where('status', 'like', '%Sakit%')->count();
        $izin = \App\Models\Absensi::where('nis', $nip)->where('status', 'like', '%Izin%')->count();

        // INI YANG DIPERBAIKI BOS: Mengarah ke file 'profil.blade.php' di dalam folder 'guru'
        return view('guru.profil', compact('guru', 'hadir', 'sakit', 'izin'));
    }

    public function cetak()
    {
        // Ambil semua data guru
        $guru = \App\Models\Guru::orderBy('nama_guru', 'asc')->get();
        
        return view('guru.cetak', compact('guru'));
    }
    
    public function cetakTerpilih(\Illuminate\Http\Request $request)
    {
        // 1. Ambil data ID guru yang diceklis dari form
        $ids = $request->ids;

        // 2. Cegah error kalau lupa mencentang kotak
        if (!$ids) {
            return redirect()->back()->with('error', 'Pilih minimal satu guru untuk dicetak!');
        }

        // 3. Ambil data guru HANYA yang ID-nya diceklis
        $guru = \App\Models\Guru::whereIn('id', $ids)
                    ->orderBy('nama_guru', 'asc') // Asumsi kolom nama di database adalah 'nama_guru'
                    ->get();
        
        // 4. Lempar ke halaman cetak massal guru
        return view('guru.cetak', compact('guru'));
    }
}