<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Murid;

class MuridController extends Controller
{
    // Fungsi untuk memunculkan halaman daftar murid
    public function index()
    {
        $murids = Murid::all(); 
        return view('murid.index', compact('murids')); 
    }

    // =========================================================================
    // 1. FUNGSI CREATE (MEMUNCULKAN FORM + BIKIN NIS OTOMATIS)
    // =========================================================================
    public function create()
    {
        // Ambil Tahun dan Bulan saat ini
        $tahun = date('y'); // 2 digit tahun
        $bulan = date('m'); // 2 digit bulan
        $prefix = $tahun . $bulan; 

        // Cari NIS terakhir di bulan ini dari database
        $murid_terakhir = \App\Models\Murid::where('nis', 'like', $prefix . '%')
                            ->orderBy('nis', 'desc')
                            ->first();

        // Buat nomor urut baru
        if ($murid_terakhir) {
            $urutan_terakhir = (int) substr($murid_terakhir->nis, -3);
            $urutan_baru = $urutan_terakhir + 1;
        } else {
            $urutan_baru = 1;
        }

        // Format nomor urut jadi 3 digit 
        $urutan_baru_format = str_pad($urutan_baru, 3, '0', STR_PAD_LEFT);
        
        // Gabungkan semuanya jadi NIS Final 
        $nis_otomatis = $prefix . $urutan_baru_format;

        return view('murid.create', compact('nis_otomatis'));
    }

    // =========================================================================
    // 2. FUNGSI STORE (MENYIMPAN DATA DARI FORM KE DATABASE)
    // =========================================================================
    public function store(Request $request)
    {
        $request->validate([
            'nis' => 'required|unique:murids,nis',
            'nama_lengkap' => 'required',
            'kelas' => 'required'
        ], [
            // Pesan Error Custom agar muncul di layar
            'nis.unique' => '⚠️ GAGAL! NIS ini sudah dipakai oleh siswa lain. Silakan gunakan NIS yang berbeda.',
            'nis.required' => '⚠️ NIS tidak boleh kosong!',
            'nama_lengkap.required' => '⚠️ Nama Lengkap tidak boleh kosong!',
            'kelas.required' => '⚠️ Kelas tidak boleh kosong!',
        ]);

        Murid::create([
            'nis' => $request->nis,
            'nama_lengkap' => $request->nama_lengkap,
            'kelas' => $request->kelas,
            'no_wa_ortu' => $request->no_wa_ortu, 
        ]);

        // Kalau sukses, kembali ke form tambah murid, DAN INGAT PILIHAN SLB-NYA!
        return redirect()->route('murid.create')
            ->with('success', 'Data ' . $request->nama_lengkap . ' berhasil disimpan! Silakan input data selanjutnya.')
            ->with('last_slb', $request->jenis_slb); 
    }

    // =========================================================================
    // FUNGSI-FUNGSI LAINNYA (EDIT, UPDATE, DELETE, PROFIL, CETAK)
    // =========================================================================

    public function edit($id)
    {
        $murid = Murid::findOrFail($id);
        return view('murid.edit', compact('murid'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nis' => 'required|unique:murids,nis,'.$id,
            'nama_lengkap' => 'required',
            'kelas' => 'required'
        ], [
            'nis.unique' => '⚠️ NIS ini sudah dipakai oleh siswa lain!',
        ]);

        $murid = Murid::findOrFail($id);
        $murid->update([
            'nis' => $request->nis,
            'nama_lengkap' => $request->nama_lengkap,
            'kelas' => $request->kelas,
            'no_wa_ortu' => $request->no_wa_ortu, 
        ]);

        return redirect()->route('murid.index')->with('success', 'Data murid berhasil diubah!');
    }

    public function destroy($id)
    {
        $murid = Murid::findOrFail($id);
        $murid->delete();
        return redirect()->route('murid.index')->with('success', 'Data murid berhasil dihapus!');
    }

    public function profil($nis)
    {
        $murid = \App\Models\Murid::where('nis', $nis)->first();
        
        if(!$murid) {
            return back()->with('error', 'Data murid tidak ditemukan.');
        }

        $hadir = \App\Models\Absensi::where('nis', $nis)->where(function($q){
            $q->where('status', 'like', '%Hadir%')->orWhere('status', 'like', '%Terlambat%');
        })->count();
        $sakit = \App\Models\Absensi::where('nis', $nis)->where('status', 'like', '%Sakit%')->count();
        $izin = \App\Models\Absensi::where('nis', $nis)->where('status', 'like', '%Izin%')->count();

        $setor = \App\Models\Tabungan::where('nis', $nis)->where('jenis', 'Setor')->sum('nominal');
        $tarik = \App\Models\Tabungan::where('nis', $nis)->where('jenis', 'Tarik')->sum('nominal');
        $saldo = $setor - $tarik;

        return view('profil', compact('murid', 'hadir', 'sakit', 'izin', 'saldo'));
    }
    
    public function cetak()
    {
        $murid = \App\Models\Murid::orderBy('kelas', 'asc')->orderBy('nama_lengkap', 'asc')->get();
        return view('murid.cetak', compact('murid'));
    }
    
    public function cetakTerpilih(\Illuminate\Http\Request $request)
    {
        $ids = $request->ids;

        if (!$ids) {
            return redirect()->back()->with('error', 'Pilih minimal satu siswa untuk dicetak!');
        }

        $murid = \App\Models\Murid::whereIn('id', $ids)
                    ->orderBy('kelas', 'asc')
                    ->orderBy('nama_lengkap', 'asc')
                    ->get();
        
        return view('murid.cetak', compact('murid'));
    }
}