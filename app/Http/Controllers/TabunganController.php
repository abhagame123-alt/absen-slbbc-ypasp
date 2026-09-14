<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tabungan;
use App\Models\Murid;

class TabunganController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $is_admin = ($user->email == 'abhaadmin234@gmail.com');
        $nama_login = strtolower($user->name); 

        // Tangkap inputan Filter dari URL
        $tgl_awal = $request->tgl_awal;
        $tgl_akhir = $request->tgl_akhir;
        $filter_guru = $request->filter_guru;
        $nama_siswa = $request->nama_siswa; // TANGKAP INPUTAN NAMA BARU

        // =======================================================
        // 1. FILTER DAFTAR MURID (DROPDOWN FORM TRANSAKSI)
        // =======================================================
        $query_murid = \App\Models\Murid::orderBy('nama_lengkap', 'asc');
        
        if (!$is_admin) {
            $query_murid->where('wali_kelas', $nama_login);
        }
        
        $daftar_murid = $query_murid->get();
        $nis_murid_kelasku = $daftar_murid->pluck('nis')->toArray();

        // =======================================================
        // 2. QUERY UTAMA TABUNGAN & RIWAYAT TRANSAKSI
        // =======================================================
        $query_tabungan = \App\Models\Tabungan::join('murids', 'tabungans.nis', '=', 'murids.nis')
            ->select('tabungans.*', 'murids.nama_lengkap as nama', 'murids.wali_kelas')
            ->orderBy('tabungans.tanggal', 'desc')->orderBy('tabungans.id', 'desc');

        if (!$is_admin) {
            // Guru biasa cuma bisa lihat kelasnya
            $query_tabungan->whereIn('tabungans.nis', $nis_murid_kelasku);
        } else {
            // Admin bisa milih guru tertentu
            if ($filter_guru && $filter_guru != 'semua') {
                $query_tabungan->where('murids.wali_kelas', $filter_guru);
            }
        }

        // =======================================================
        // 3. FILTER TANGGAL & NAMA
        // =======================================================
        if ($tgl_awal && $tgl_akhir) {
            $query_tabungan->whereBetween('tabungans.tanggal', [$tgl_awal, $tgl_akhir]);
        }

        // TAMBAHAN LOGIKA PENCARIAN NAMA ATAU NIS
        if ($nama_siswa) {
            $query_tabungan->where(function($q) use ($nama_siswa) {
                $q->where('murids.nama_lengkap', 'like', '%' . $nama_siswa . '%')
                  ->orWhere('tabungans.nis', 'like', '%' . $nama_siswa . '%');
            });
        }

        $riwayat_tabungan = $query_tabungan->get();

        // =======================================================
        // 4. HITUNG TOTAL UANG SECARA ADIL & AMAN
        // =======================================================
        $total_setor = $riwayat_tabungan->where('jenis', 'Setor')->sum('nominal');
        $total_tarik = $riwayat_tabungan->where('jenis', 'Tarik')->sum('nominal');
        $total_saldo = $total_setor - $total_tarik;

        // =======================================================
        // 5. KETERANGAN JUDUL DINAMIS
        // =======================================================
        if ($is_admin) {
            $judul_saldo = ($filter_guru && $filter_guru != 'semua') ? "Saldo Kelas Guru " . ucwords($filter_guru) : "Total Saldo (Seluruh Sekolah)";
            $sub_judul = "SLB BC YPASP - Keuangan Siswa";
        } else {
            $judul_saldo = "Total Saldo Kelasku";
            $sub_judul = "Keuangan Kelas Guru " . ucwords($nama_login);
        }
        
        // Tambahkan info ke sub-judul jika difilter
        if ($tgl_awal && $tgl_akhir) {
            $sub_judul .= " | Periode: " . date('d/m/y', strtotime($tgl_awal)) . " - " . date('d/m/y', strtotime($tgl_akhir));
        }
        if ($nama_siswa) {
            $judul_saldo = "Total Saldo: " . strtoupper($nama_siswa); 
        }

        // =======================================================
        // 6. DATA GURU UNTUK FILTER (KHUSUS ADMIN)
        // =======================================================
        $daftar_wali_kelas = [];
        
        if ($is_admin) {
            $daftar_wali_kelas = \App\Models\Murid::whereNotNull('wali_kelas')
                                    ->select('wali_kelas')
                                    ->distinct()
                                    ->pluck('wali_kelas');
        }

        return view('tabungan', compact('daftar_murid', 'riwayat_tabungan', 'total_saldo', 'judul_saldo', 'sub_judul', 'is_admin', 'daftar_wali_kelas', 'filter_guru', 'tgl_awal', 'tgl_akhir', 'nama_siswa'));
    }

    public function store(Request $request)
    {
        Tabungan::create([
            'nis' => $request->nis,
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'nominal' => $request->nominal,
            'keterangan' => $request->keterangan ?? '-'
        ]);

        return back()->with('success', 'Transaksi ' . $request->jenis . ' sebesar Rp ' . number_format($request->nominal, 0, ',', '.') . ' berhasil dicatat!');
    }

    public function destroy($id)
    {
        Tabungan::destroy($id);
        return back()->with('success', 'Data transaksi berhasil dihapus!');
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $is_admin = ($user->email == 'abhaadmin234@gmail.com');
        $nama_login = strtolower($user->name);
        
        $tgl_awal = $request->tgl_awal;
        $tgl_akhir = $request->tgl_akhir;
        $filter_guru = $request->filter_guru;
        $nama_siswa = $request->nama_siswa; // Tangkap inputan nama di fungsi cetak Excel juga

        $nama_file = 'Laporan_Tabungan_Siswa_' . date('Y-m-d') . '.xls';
        $judul_laporan = "DATA TABUNGAN SISWA SLB BC YPASP";

        $query_tabungan = Tabungan::join('murids', 'tabungans.nis', '=', 'murids.nis')
            ->select('tabungans.*', 'murids.nama_lengkap as nama', 'murids.wali_kelas')
            ->orderBy('tabungans.tanggal', 'desc')->orderBy('tabungans.created_at', 'desc');

        if (!$is_admin) {
            $query_tabungan->where('murids.wali_kelas', $nama_login);
            $judul_laporan = "DATA TABUNGAN KELAS - GURU " . strtoupper($nama_login);
        } else {
             if ($filter_guru && $filter_guru != 'semua') {
                $query_tabungan->where('murids.wali_kelas', $filter_guru);
                $judul_laporan = "DATA TABUNGAN KELAS - GURU " . strtoupper($filter_guru);
            }
        }

        if ($tgl_awal && $tgl_akhir) {
            $query_tabungan->whereBetween('tabungans.tanggal', [$tgl_awal, $tgl_akhir]);
            $judul_laporan .= " (PERIODE: " . date('d/m/Y', strtotime($tgl_awal)) . " SD " . date('d/m/Y', strtotime($tgl_akhir)) . ")";
        }

        // NAMA GURU UNTUK NAMA FILE
        $nama_guru_file = $is_admin ? (($filter_guru && $filter_guru != 'semua') ? ucfirst($filter_guru) : 'Admin') : ucfirst($nama_login);

        // NAMA FILE DEFAULT JIKA TIDAK FILTER SISWA TERTENTU
        $nama_file = 'Laporan_Tabungan_Siswa_Oleh_Guru_' . $nama_guru_file . '_' . date('Ymd') . '.xls';

        // TERAPKAN FILTER NAMA DI EXCEL JUGA
        if ($nama_siswa) {
            $query_tabungan->where(function($q) use ($nama_siswa) {
                $q->where('murids.nama_lengkap', 'like', '%' . $nama_siswa . '%')
                  ->orWhere('tabungans.nis', 'like', '%' . $nama_siswa . '%');
            });
            
            // CARI NAMA ASLI SISWA (Menerjemahkan NIS jadi Nama Lengkap)
            $cek_murid = \App\Models\Murid::where('nis', $nama_siswa)->orWhere('nama_lengkap', 'like', '%' . $nama_siswa . '%')->first();
            $nama_tampil = $cek_murid ? strtoupper($cek_murid->nama_lengkap) : strtoupper($nama_siswa);

            $judul_laporan .= " - PENCARIAN SISWA: " . $nama_tampil;
            
            // UPDATE NAMA FILE JIKA FILTER SISWA
            $nama_file = 'Tabungan_' . str_replace(' ', '_', $nama_tampil) . '_Oleh_Guru_' . $nama_guru_file . '_' . date('Ymd') . '.xls';
        }

        $tabungans = $query_tabungan->get();

        $total_setor = $tabungans->where('jenis', 'Setor')->sum('nominal');
        $total_tarik = $tabungans->where('jenis', 'Tarik')->sum('nominal');
        $saldo_total = $total_setor - $total_tarik;

        $headers = [
            "Content-type"        => "application/vnd-ms-excel",
            "Content-Disposition" => "attachment; filename=$nama_file",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($tabungans, $saldo_total, $judul_laporan) {
            echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head><body>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            
            echo "<tr><th colspan='8' style='background-color: #4F46E5; color: white; font-size: 16px; height: 40px;'><b>" . $judul_laporan . "</b></th></tr>";
            echo "<tr><th colspan='8' style='background-color: #E0E7FF; color: #3730A3; font-size: 14px; height: 30px; text-align: left;'><b>TOTAL SALDO: Rp " . number_format($saldo_total, 0, ',', '.') . "</b></th></tr>";
            
            echo "<tr style='background-color: #E5E7EB;'>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama Lengkap</th>
                    <th>Wali Kelas</th>
                    <th>Jenis Transaksi</th>
                    <th>Nominal (Rp)</th>
                    <th>Keterangan</th>
                  </tr>";
            
            $no = 1;
            foreach ($tabungans as $row) {
                $warna_jenis = $row->jenis == 'Setor' ? "color: #16A34A;" : "color: #DC2626;";
                
                echo "<tr>";
                echo "<td style='text-align: center;'>" . $no++ . "</td>";
                echo "<td style='text-align: center;'>" . \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') . "</td>";
                echo "<td style='text-align: center;'>" . $row->nis . "</td>";
                echo "<td>" . strtoupper($row->nama) . "</td>";
                echo "<td>" . ucwords($row->wali_kelas ?? '-') . "</td>"; 
                echo "<td style='" . $warna_jenis . " font-weight: bold; text-align: center;'>" . $row->jenis . "</td>";
                echo "<td style='text-align: right;'>" . number_format($row->nominal, 0, ',', '.') . "</td>";
                echo "<td>" . $row->keterangan . "</td>";
                echo "</tr>";
            }
            echo "</table></body></html>";
        };

        return response()->stream($callback, 200, $headers);
    }
}