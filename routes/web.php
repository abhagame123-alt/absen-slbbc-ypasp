<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

// =========================================================
// 📱 JALUR PORTAL ABSEN VIA HP GURU (Bisa diakses tanpa login)
// =========================================================
Route::get('/absen-hp', function () {
    return view('absen_hp');
});
// =========================================================

// =========================================================
// 🚀 JALAN PINTAS RAHASIA UNTUK GURU PENGGANTI (AUTO-LOGIN MESIN)
// =========================================================
Route::get('/mesin-rahasia-8899', function () {
    $akun_mesin = \App\Models\User::where('email', 'mesinabsen@gmail.com')->first();
    if ($akun_mesin) {
        auth()->login($akun_mesin);
        // ARAHKAN LANGSUNG KE HALAMAN SCANNER KHUSUS GURU
        return redirect('/scan-guru'); 
    }
    return "Akun mesin belum dibuat di database! Silakan login sebagai Admin dan buat akun mesinabsen@gmail.com di menu Data Guru.";
});
// =========================================================

Route::get('/dashboard/detail/{tipe}/{status}', [App\Http\Controllers\AbsenController::class, 'detailDashboard'])->name('dashboard.detail');

Route::get('/dashboard', function () {
    date_default_timezone_set('Asia/Jakarta');
    $tanggal_sekarang = date('Y-m-d');
    $jam_sekarang = date('H:i');
    
    $hari_ini_num = date('w'); 
    $is_weekend = ($hari_ini_num == 0 || $hari_ini_num == 6);
    
    $user = auth()->user();
    $is_admin = ($user->email == 'abhaadmin234@gmail.com');
    $nama_login = strtolower($user->name);
    
    $q_murid = \App\Models\Murid::query();
    $q_hadir = \App\Models\Absensi::join('murids', 'absensis.nis', '=', 'murids.nis')
        ->where('absensis.tanggal', $tanggal_sekarang)
        ->where(function($q) { $q->where('absensis.status', 'like', '%Hadir%')->orWhere('absensis.status', 'like', '%Terlambat%'); });
    $q_sakit = \App\Models\Absensi::join('murids', 'absensis.nis', '=', 'murids.nis')
        ->where('absensis.tanggal', $tanggal_sekarang)->where('absensis.status', 'like', '%Sakit%');
    $q_izin = \App\Models\Absensi::join('murids', 'absensis.nis', '=', 'murids.nis')
        ->where('absensis.tanggal', $tanggal_sekarang)->where('absensis.status', 'like', '%Izin%');

    if (!$is_admin) {
        $q_murid->where('wali_kelas', $nama_login);
        $q_hadir->where('murids.wali_kelas', $nama_login);
        $q_sakit->where('murids.wali_kelas', $nama_login);
        $q_izin->where('murids.wali_kelas', $nama_login);
    }

    $total_murid = $q_murid->count();
    $murid_hadir = $q_hadir->count();
    $murid_sakit = $q_sakit->count();
    $murid_izin  = $q_izin->count();
    
    $sisa_murid = $total_murid - ($murid_hadir + $murid_sakit + $murid_izin);
    if ($sisa_murid < 0) $sisa_murid = 0; 

    if ($is_weekend) {
        $murid_belum_absen = $sisa_murid;
        $murid_bolos = 0;
    } else {
        if ($jam_sekarang >= '13:30') {
            $murid_belum_absen = 0;
            $murid_bolos = $sisa_murid;
        } else {
            $murid_belum_absen = $sisa_murid;
            $murid_bolos = 0;
        }
    }

    $absen_hadir_hari_ini = \App\Models\Absensi::where('tanggal', $tanggal_sekarang)
        ->where(function($q) {
            $q->where('status', 'like', '%Hadir%')->orWhere('status', 'like', '%Terlambat%');
        })->pluck('nis')->toArray();
        
    $q_tidak_hadir_list = \App\Models\Murid::leftJoin('absensis', function($join) use ($tanggal_sekarang) {
            $join->on('murids.nis', '=', 'absensis.nis')
                 ->where('absensis.tanggal', '=', $tanggal_sekarang);
        })
        ->whereNotIn('murids.nis', $absen_hadir_hari_ini)
        ->select('murids.*', 'absensis.status as absensi_status'); 

    if (!$is_admin) {
        $q_tidak_hadir_list->where('murids.wali_kelas', $nama_login);
    }
    
    $daftar_belum_absen = $q_tidak_hadir_list->orderBy('murids.nama_lengkap', 'asc')->get();
    
    foreach($daftar_belum_absen as $d) {
        if (empty($d->absensi_status)) {
             $d->absensi_status = ($is_weekend || $jam_sekarang < '13:30') ? 'Belum Absen' : 'Bolos (Alpha)';
        }
    }

    $total_guru = \App\Models\Guru::count();
    $guru_hadir = \App\Models\Absensi::join('gurus', 'absensis.nis', '=', 'gurus.nip')
        ->where('tanggal', $tanggal_sekarang)
        ->where(function($q) { $q->where('status', 'like', '%Hadir%')->orWhere('status', 'like', '%Terlambat%'); })->count();
    $guru_sakit = \App\Models\Absensi::join('gurus', 'absensis.nis', '=', 'gurus.nip')
        ->where('tanggal', $tanggal_sekarang)->where('status', 'like', '%Sakit%')->count();
    $guru_izin = \App\Models\Absensi::join('gurus', 'absensis.nis', '=', 'gurus.nip')
        ->where('tanggal', $tanggal_sekarang)->where('status', 'like', '%Izin%')->count();
        
    $sisa_guru = $total_guru - ($guru_hadir + $guru_sakit + $guru_izin);
    if ($sisa_guru < 0) $sisa_guru = 0;

    if ($is_weekend) {
        $guru_belum_absen = $sisa_guru;
        $guru_bolos = 0;
    } else {
        if ($jam_sekarang >= '13:30') {
            $guru_belum_absen = 0;
            $guru_bolos = $sisa_guru;
        } else {
            $guru_belum_absen = $sisa_guru;
            $guru_bolos = 0;
        }
    }

    return view('dashboard', compact(
        'total_murid', 'murid_hadir', 'murid_sakit', 'murid_izin', 'murid_belum_absen', 'murid_bolos',
        'daftar_belum_absen', 
        'total_guru', 'guru_hadir', 'guru_sakit', 'guru_izin', 'guru_belum_absen', 'guru_bolos'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Rute Izin Mandiri Guru
    Route::post('/izin-mandiri', [App\Http\Controllers\AbsenController::class, 'izinMandiri'])->name('izin.mandiri');
    
    // RUTE SCANNER SISWA
    Route::get('/scan', [App\Http\Controllers\AbsenController::class, 'index'])->name('scan');
    // RUTE SCANNER KHUSUS GURU
    Route::get('/scan-guru', [App\Http\Controllers\AbsenController::class, 'scanGuru'])->name('scan.guru');
    
    Route::post('/scan/store', [App\Http\Controllers\AbsenController::class, 'store'])->name('scan.store');
    Route::post('/rekap/manual', [App\Http\Controllers\AbsenController::class, 'storeManual'])->name('rekap.manual');
    Route::get('/rekap', [App\Http\Controllers\AbsenController::class, 'rekap'])->name('rekap');
    Route::delete('/rekap/{id}', [App\Http\Controllers\AbsenController::class, 'destroy'])->name('rekap.destroy');
    
    // 👇 INI DIA JEMBATANNYA BOS! UDAH AKU TAMBAHIN DI SINI! 👇
    Route::get('/rekap/export/{tipe}', [App\Http\Controllers\AbsenController::class, 'export'])->name('rekap.export');
    Route::get('/export/{tipe}', [App\Http\Controllers\AbsenController::class, 'export'])->name('export.excel');
    // 👆 =================================================== 👆
    
    Route::get('/kelasku', [App\Http\Controllers\AbsenController::class, 'kelasku'])->name('kelasku');
    Route::post('/kelasku', [App\Http\Controllers\AbsenController::class, 'updateKelasku'])->name('kelasku.update');
    
    Route::get('/rekam-wajah', [App\Http\Controllers\AbsenController::class, 'halamanRekamWajah']);
    Route::post('/simpan-wajah', [App\Http\Controllers\AbsenController::class, 'simpanWajah']);
    Route::post('/hapus-wajah', [App\Http\Controllers\AbsenController::class, 'hapusWajah']);

    // === JALUR BARU KHUSUS REKAM WAJAH GURU ===
    Route::get('/rekam-wajah-guru', [App\Http\Controllers\AbsenController::class, 'halamanRekamWajahGuru']);
    Route::post('/simpan-wajah-guru', [App\Http\Controllers\AbsenController::class, 'simpanWajahGuru']);
    Route::post('/hapus-wajah-guru', [App\Http\Controllers\AbsenController::class, 'hapusWajahGuru']);
    Route::get('/kelola-guru', [App\Http\Controllers\AbsenController::class, 'kelolaGuru'])->name('kelola.guru');
    Route::post('/kelola-guru', [App\Http\Controllers\AbsenController::class, 'updateKelolaGuru'])->name('kelola.guru.update');
});

Route::get('/murid/profil/{nis}', [App\Http\Controllers\MuridController::class, 'profil'])->name('murid.profil');
Route::get('/murid/cetak', [App\Http\Controllers\MuridController::class, 'cetak'])->name('murid.cetak');
Route::post('/murid/cetak-terpilih', [App\Http\Controllers\MuridController::class, 'cetakTerpilih'])->name('murid.cetak.terpilih');
Route::get('/guru/cetak', [App\Http\Controllers\GuruController::class, 'cetak'])->name('guru.cetak');
Route::get('/cek-nis-murid', [App\Http\Controllers\MuridController::class, 'cekNis'])->name('cek.nis');
Route::post('/guru/cetak-terpilih', [App\Http\Controllers\GuruController::class, 'cetakTerpilih'])->name('guru.cetak.terpilih');
Route::get('/guru/profil/{nip}', [App\Http\Controllers\GuruController::class, 'profil'])->name('guru.profil');
Route::resource('murid', App\Http\Controllers\MuridController::class)->middleware(['auth', 'verified']);
Route::resource('guru', App\Http\Controllers\GuruController::class)->middleware(['auth', 'verified']);
Route::get('/tabungan', [App\Http\Controllers\TabunganController::class, 'index'])->name('tabungan');
Route::post('/tabungan/store', [App\Http\Controllers\TabunganController::class, 'store'])->name('tabungan.store');
Route::delete('/tabungan/{id}', [App\Http\Controllers\TabunganController::class, 'destroy'])->name('tabungan.destroy');
Route::get('/tabungan/export', [App\Http\Controllers\TabunganController::class, 'export'])->name('tabungan.export');

require __DIR__.'/auth.php';