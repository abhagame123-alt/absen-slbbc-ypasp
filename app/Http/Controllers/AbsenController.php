<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\Murid; 
use App\Models\Guru; 
use Illuminate\Support\Facades\Log;

class AbsenController extends Controller
{
    // Halaman Scanner Siswa
    public function index()
    {
        $murid_dengan_wajah = \App\Models\Murid::whereNotNull('face_data')
                                ->select('nis', 'nama_lengkap', 'face_data')
                                ->get();
        return view('scan', compact('murid_dengan_wajah'));
    }

    // Halaman Scanner KHUSUS GURU
    public function scanGuru()
    {
        try {
            $guru_dengan_wajah = \App\Models\Guru::whereNotNull('face_data')
                                    ->select('nip as nis', 'nama_guru as nama_lengkap', 'face_data')
                                    ->get();
        } catch (\Exception $e) {
            $guru_dengan_wajah = collect();
        }
        return view('scan_guru', compact('guru_dengan_wajah'));
    }

    public function store(Request $request)
    {
        try {
            date_default_timezone_set('Asia/Jakarta');
            
            $hari_ini = date('Y-m-d');
            $jam_sekarang = date('H:i');
            
            // =================================================================
            // 🚨 ZONA TUTUP MESIN: LEWAT JAM 15:00 TIDAK BISA ABSEN LAGI! 🚨
            // =================================================================
            if ($jam_sekarang > '15:00') {
                return response()->json([
                    'success' => false,
                    'tipe_error' => 'server',
                    'pesan' => '❌ GAGAL! Mesin Absen sudah DITUTUP karena melewati batas jam 15:00 WIB. Anda tercatat Alpha.'
                ]);
            }
            
            $petugas_login = auth()->check() ? auth()->user()->name : 'Mesin';
            $email_login = auth()->check() ? auth()->user()->email : ''; 
            $nama_petugas = strtolower($petugas_login);
            
            $jenis_mesin = $request->jenis_mesin ?? 'siswa';
            
            $murid = \App\Models\Murid::where('nis', $request->nis)->first();
            $guru = \App\Models\Guru::where('nip', $request->nis)->first();

            $nama = 'Tidak Diketahui';
            $tipe = '-';
            $wali_kelas = null;

            if($murid) {
                if ($jenis_mesin == 'guru') {
                    return response()->json([
                        'success' => false, 
                        'tipe_error' => 'server',
                        'pesan' => '❌ GAGAL! Mesin ini KHUSUS GURU. Siswa silakan absen ke wali kelas!'
                    ]);
                }
                $nama = $murid->nama_lengkap;
                $tipe = 'Murid';
                $wali_kelas = strtolower($murid->wali_kelas);
                
            } elseif($guru) {
                // 🔥 INI DIA KUNCI JAWABANNYA BOS! 🔥
                // Satpam Database sekarang paham kalau Nisa nge-scan QR di Laptop, itu BOLEH MASUK!
                if ($email_login != 'mesinabsen@gmail.com' && $email_login != 'abhaadmin234@gmail.com') {
                    if ($request->sumber != 'qr_layar_mesin') {
                        return response()->json([
                            'success' => false, 
                            'tipe_error' => 'server',
                            'pesan' => '❌ GAGAL! Absen Kehadiran Guru HANYA bisa dilakukan dengan scan QR di Laptop Sekolah!'
                        ]);
                    }
                }
                
                $nama = $guru->nama_guru;
                $tipe = 'Guru';
            } else {
                return response()->json([
                    'success' => false, 
                    'tipe_error' => 'tidak_ditemukan',
                    'pesan' => 'Data tidak ditemukan di sistem!'
                ]);
            }

            $absen_hari_ini = \App\Models\Absensi::where('nis', $request->nis)
                                ->where('tanggal', $hari_ini)
                                ->get();

            $sudah_masuk = $absen_hari_ini->filter(function($absen) { 
                return !str_contains($absen->status, 'Pulang'); 
            })->first();
            
            $sudah_pulang = $absen_hari_ini->filter(function($absen) { 
                return str_contains($absen->status, 'Pulang'); 
            })->first();

            if ($sudah_masuk && $sudah_pulang) {
                return response()->json([
                    'success' => false,
                    'tipe_error' => 'dobel',
                    'pesan' => strtoupper($nama) . ' sudah absen MASUK dan PULANG hari ini.'
                ]);
            }

            $jenis_absen = '';
            $status_awal = '';

            if (!$sudah_masuk) {
                $jenis_absen = 'masuk';
                
                if ($request->has('status_manual') && $request->status_manual != 'Otomatis') {
                    $status_awal = $request->status_manual;
                    if ($status_awal == 'Hadir Pagi' || $status_awal == 'Pagi') {
                        $status_awal = 'Hadir'; 
                    }
                } else {
                    if ($jam_sekarang <= '07:30') {
                        $status_awal = 'Hadir'; 
                    } else {
                        $status_awal = 'Terlambat'; 
                    }
                }
            } else {
                if ($tipe == 'Guru') {
                    if ($jam_sekarang < '13:30') {
                        return response()->json([
                            'success' => false,
                            'tipe_error' => 'dobel',
                            'pesan' => 'Mohon maaf Bapak/Ibu ' . ucwords(strtolower($nama)) . ', Anda sudah absen masuk. Waktu absen PULANG khusus GURU baru dibuka pada pukul 13:30 WIB.'
                        ]);
                    }
                } else {
                    if ($jam_sekarang < '10:00') {
                        return response()->json([
                            'success' => false,
                            'tipe_error' => 'dobel',
                            'pesan' => strtoupper($nama) . ' sudah absen masuk. Waktu absen PULANG untuk siswa baru dibuka jam 10:00 pagi!'
                        ]);
                    }
                }
                $jenis_absen = 'pulang';
                $status_awal = 'Pulang';
            }

            $status_final = $status_awal;
            
            if ($tipe == 'Murid' && $wali_kelas != null && $nama_petugas != 'admin') {
                if ($wali_kelas != $nama_petugas) {
                    $status_final = $status_awal . ' (via Guru ' . ucwords($petugas_login) . ')';
                }
            }

            \App\Models\Absensi::create([
                'nis' => $request->nis,
                'tanggal' => $hari_ini,
                'waktu' => $jam_sekarang,
                'status' => $status_final, 
            ]);

            if ($murid && $murid->no_wa_ortu != null && $murid->no_wa_ortu != '-') {
                $token = 'pDNx6yfHgKopj2VRZbnb'; 
                $target = $murid->no_wa_ortu;
                $waktu_absen = date('H:i:s');
                
                if ($jenis_absen == 'masuk') {
                    if ($status_awal == 'Terlambat') {
                        $pesan = "Halo Ayah/Bunda,\n\nKami menginformasikan bahwa ananda *{$murid->nama_lengkap}* telah tiba di sekolah pada pukul *{$waktu_absen} WIB* dengan status *TERLAMBAT*.\n\nMohon kerjasamanya agar ananda bisa berangkat lebih awal. Terima kasih,\nSistem Absensi SLB BC YPASP";
                    } else {
                        $pesan = "Halo Ayah/Bunda,\n\nKami menginformasikan bahwa ananda *{$murid->nama_lengkap}* telah *TIBA* di sekolah pada pukul *{$waktu_absen} WIB*.\n\nTerima kasih,\nSistem Absensi SLB BC YPASP";
                    }
                } else {
                    $pesan = "Halo Ayah/Bunda,\n\nKami menginformasikan bahwa ananda *{$murid->nama_lengkap}* telah *PULANG* dari sekolah pada pukul *{$waktu_absen} WIB*.\n\nHati-hati di jalan. Terima kasih,\nSistem Absensi SLB BC YPASP";
                }

                $curl = \curl_init();
                \curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://api.fonnte.com/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS => array('target' => $target, 'message' => $pesan, 'countryCode' => '62'),
                  CURLOPT_HTTPHEADER => array("Authorization: $token"),
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_SSL_VERIFYHOST => false,
                ));
                $response = \curl_exec($curl);
                $err = \curl_error($curl);
                \curl_close($curl);
            }

            return response()->json([
                'success' => true,
                'nama' => $nama,
                'tipe' => $tipe,
                'status' => $status_final, 
                'waktu' => date('d/m/Y - H:i') 
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'tipe_error' => 'server', 'pesan' => '💥 SERVER ERROR: ' . $e->getMessage()]);
        }
    }

    public function storeManual(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $waktu_sekarang = date('H:i:s'); 

        $murid = \App\Models\Murid::where('nis', $request->nis)->first();
        $guru = \App\Models\Guru::where('nip', $request->nis)->first();

        if (!$murid && !$guru) {
            return back()->with('error', 'Gagal! NIS / NIP tidak terdaftar di sistem.');
        }

        $sudah_absen = \App\Models\Absensi::where('nis', $request->nis)->where('tanggal', $request->tanggal)->first();
                            
        if ($sudah_absen) {
            $sudah_absen->update([
                'status' => $request->status,
                'waktu' => $waktu_sekarang 
            ]);
            return back()->with('success', 'Data sebelumnya berhasil direvisi menjadi (' . $request->status . ')!');
        }

        \App\Models\Absensi::create([
            'nis' => $request->nis,
            'tanggal' => $request->tanggal,
            'waktu' => $waktu_sekarang, 
            'status' => $request->status
        ]);
        
        return back()->with('success', 'Data absen manual (' . $request->status . ') berhasil ditambahkan!');
    }

    public function rekap()
    {
        $user = auth()->user();
        $is_admin = ($user->email == 'abhaadmin234@gmail.com');
        $nama_login = strtolower($user->name);

        $q_murid = \App\Models\Absensi::join('murids', 'absensis.nis', '=', 'murids.nis')
            ->select('absensis.*', 'murids.nama_lengkap as nama', 'murids.kelas');
            
        $q_daftar_murid = \App\Models\Murid::orderBy('nama_lengkap', 'asc');

        if (!$is_admin) {
            $q_murid->where('murids.wali_kelas', $nama_login);
            $q_daftar_murid->where('wali_kelas', $nama_login);
        }
        
        $absen_murid = $q_murid->orderBy('tanggal', 'desc')->orderBy('waktu', 'desc')->get();
        $daftar_murid = $q_daftar_murid->get();

        $absen_guru = [];
        if ($is_admin) {
            $absen_guru = \App\Models\Absensi::join('gurus', 'absensis.nis', '=', 'gurus.nip')
                ->select('absensis.*', 'gurus.nama_guru as nama')
                ->orderBy('tanggal', 'desc')->orderBy('waktu', 'desc')->get();
        }
        return view('rekap', compact('absen_murid', 'absen_guru', 'is_admin', 'daftar_murid'));
    }

    public function destroy($id)
    {
        \App\Models\Absensi::destroy($id);
        return back()->with('success', 'Riwayat absen berhasil dibatalkan!');
    }

    // ==============================================================
    // 🔥 FITUR EXCEL DEWA (AUTO-EXPAND, COLORING, FULL ZEBRA PASTEL)
    // ==============================================================
    public function export(Request $request, $tipe)
    {
        $user = auth()->user();
        $is_admin = ($user->email == 'abhaadmin234@gmail.com' || $user->email == 'mesinabsen@gmail.com');
        
        $tgl_awal = $request->tgl_awal ?? date('Y-m-01'); 
        $tgl_akhir = $request->tgl_akhir ?? date('Y-m-t'); 

        $nama_file = 'Laporan_Absensi_' . strtoupper($tipe) . '_' . $tgl_awal . '_sd_' . $tgl_akhir . '.xls';

        $headers = [
            "Content-type" => "application/vnd-ms-excel", 
            "Content-Disposition" => "attachment; filename=$nama_file", 
            "Pragma" => "no-cache", 
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0", 
            "Expires" => "0"
        ];

        if ($tipe == 'murid') {
            $query_export = \App\Models\Absensi::join('murids', 'absensis.nis', '=', 'murids.nis')
                ->select('absensis.*', 'murids.nama_lengkap as nama', 'murids.kelas')
                ->whereBetween('absensis.tanggal', [$tgl_awal, $tgl_akhir])
                ->orderBy('absensis.tanggal', 'asc')
                ->orderBy('absensis.waktu', 'asc');
                
            if (!$is_admin) { $query_export->where('murids.wali_kelas', $user->name); }
            $absensi = $query_export->get();
            $judul = "DATA KEHADIRAN MURID (Periode: $tgl_awal s/d $tgl_akhir)";
            
            $callback = function() use($absensi, $judul) {
                echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head><body>";
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th colspan='6' style='background-color: #4F46E5; color: white; font-size: 16px; height: 40px;'><b>" . $judul . "</b></th></tr>";
                echo "<tr style='background-color: #E5E7EB;'><th>No</th><th>NIS</th><th>Nama Lengkap</th><th>Tanggal</th><th>Jam Absen</th><th>Status Kehadiran</th></tr>";

                $no = 1;
                foreach ($absensi as $row) {
                    $status_absen = $row->status ? $row->status : 'Hadir';
                    $str_status = strtolower($status_absen);

                    if (str_contains($str_status, 'hadir')) { $warna_bg = "#D1FAE5"; $warna_teks = "#065F46"; } 
                    elseif (str_contains($str_status, 'alpha') || str_contains($str_status, 'bolos')) { $warna_bg = "#FEE2E2"; $warna_teks = "#991B1B"; } 
                    elseif (str_contains($str_status, 'terlambat')) { $warna_bg = "#FEF3C7"; $warna_teks = "#92400E"; } 
                    elseif (str_contains($str_status, 'sakit')) { $warna_bg = "#FFEDD5"; $warna_teks = "#C2410C"; } 
                    elseif (str_contains($str_status, 'izin')) { $warna_bg = "#E0E7FF"; $warna_teks = "#4338CA"; } 
                    else { $warna_bg = "#FFFFFF"; $warna_teks = "#000000"; }

                    echo "<tr style='background-color: " . $warna_bg . "; color: " . $warna_teks . ";'>";
                    echo "<td style='text-align: center;'>" . $no++ . "</td>";
                    echo "<td style='text-align: center;'>" . $row->nis . "</td>";
                    echo "<td>" . strtoupper($row->nama) . "</td>";
                    echo "<td style='text-align: center;'>" . $row->tanggal . "</td>";
                    echo "<td style='text-align: center;'>" . $row->waktu . "</td>";
                    echo "<td style='font-weight: bold; text-align: center;'>" . strtoupper($status_absen) . "</td>";
                    echo "</tr>";
                }
                echo "</table></body></html>";
            };
            return response()->stream($callback, 200, $headers);
            
        } else {
            // =========================================================================================
            // 🔥 FORMAT BARU UNTUK GURU: ZEBRA STRIPING KUNING LEMBUT & BIRU LEMBUT 🔥
            // =========================================================================================
            $gurus = \App\Models\Guru::where('nip', '!=', 'MESIN-01')->orderBy('nama_guru', 'asc')->get();
            $absensi = \App\Models\Absensi::whereBetween('tanggal', [$tgl_awal, $tgl_akhir])->get();
            
            $rekap = [];
            foreach($absensi as $absen) {
                $tgl_full = $absen->tanggal;
                $str_status = strtolower($absen->status);
                $waktu_jam = substr($absen->waktu, 0, 5); 
                
                if (!isset($rekap[$absen->nis][$tgl_full])) {
                    $rekap[$absen->nis][$tgl_full] = [
                        'status' => '',
                        'jam_masuk' => '-',
                        'jam_pulang' => '-'
                    ];
                }

                if (str_contains($str_status, 'hadir') || str_contains($str_status, 'terlambat')) {
                    $rekap[$absen->nis][$tgl_full]['status'] = 'v';
                    $rekap[$absen->nis][$tgl_full]['jam_masuk'] = $waktu_jam;
                } elseif (str_contains($str_status, 'pulang')) {
                    $rekap[$absen->nis][$tgl_full]['status'] = 'v';
                    $rekap[$absen->nis][$tgl_full]['jam_pulang'] = $waktu_jam;
                } elseif (str_contains($str_status, 'sakit')) {
                    $rekap[$absen->nis][$tgl_full]['status'] = 'S';
                } elseif (str_contains($str_status, 'izin')) {
                    $rekap[$absen->nis][$tgl_full]['status'] = 'I';
                } elseif (str_contains($str_status, 'alpha') || str_contains($str_status, 'bolos')) {
                    $rekap[$absen->nis][$tgl_full]['status'] = 'A';
                }
            }

            $tgl_awal_obj = new \DateTime($tgl_awal);
            $tgl_akhir_obj = new \DateTime($tgl_akhir);
            $interval = \DateInterval::createFromDateString('1 day');
            $period = new \DatePeriod($tgl_awal_obj, $interval, $tgl_akhir_obj->modify('+1 day'));

            $tanggal_list = [];
            foreach ($period as $dt) {
                $tanggal_list[] = $dt->format('Y-m-d');
            }
            $jumlah_kolom_hari = count($tanggal_list);

            $bulan_indo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
            $teks_awal = date('d', strtotime($tgl_awal)) . ' ' . $bulan_indo[date('m', strtotime($tgl_awal))] . ' ' . date('Y', strtotime($tgl_awal));
            $teks_akhir = date('d', strtotime($tgl_akhir)) . ' ' . $bulan_indo[date('m', strtotime($tgl_akhir))] . ' ' . date('Y', strtotime($tgl_akhir));
            $periode_teks = "PERIODE : " . strtoupper($teks_awal . " S/D " . $teks_akhir);

            $callback = function() use($gurus, $rekap, $tanggal_list, $jumlah_kolom_hari, $periode_teks) {
                echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head><body>";
                echo "<table border='1' cellpadding='3' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px;'>";
                
                echo "<tr><td colspan='".($jumlah_kolom_hari + 7)."' style='text-align: center; font-size: 14px; font-weight: bold; border: none;'>DAFTAR HADIR GURU DAN KARYAWAN SLB BC YPASP</td></tr>";
                echo "<tr><td colspan='".($jumlah_kolom_hari + 7)."' style='text-align: center; font-size: 12px; font-weight: bold; border: none;'>" . $periode_teks . "</td></tr>";
                echo "<tr><td colspan='".($jumlah_kolom_hari + 7)."' style='border: none;'></td></tr>"; 
                
                echo "<tr style='background-color: #f3f4f6; font-weight: bold; text-align: center;'>";
                echo "<td rowspan='2' style='width: 30px; vertical-align: middle;'>No</td>";
                echo "<td rowspan='2' style='width: 200px; vertical-align: middle;'>Nama</td>";
                echo "<td colspan='".$jumlah_kolom_hari."'>Tanggal</td>";
                echo "<td rowspan='2' style='width: 30px; vertical-align: middle;'>H</td>";
                echo "<td rowspan='2' style='width: 30px; vertical-align: middle;'>I</td>";
                echo "<td rowspan='2' style='width: 30px; vertical-align: middle;'>S</td>";
                echo "<td rowspan='2' style='width: 30px; vertical-align: middle;'>A</td>";
                echo "<td rowspan='2' style='width: 50px; vertical-align: middle;'>KET</td>";
                echo "</tr>";
                
                echo "<tr style='background-color: #f3f4f6; font-weight: bold; text-align: center;'>";
                foreach ($tanggal_list as $tgl) {
                    $day_of_week = date('N', strtotime($tgl));
                    $tgl_angka = (int) date('d', strtotime($tgl));
                    
                    $bg_color = "";
                    if ($day_of_week == 7) { $bg_color = "background-color: #ff0000; color: white;"; } 
                    elseif ($day_of_week == 6) { $bg_color = "background-color: #00b050; color: white;"; } 
                    
                    echo "<td style='width: 45px; " . $bg_color . "'>" . $tgl_angka . "</td>";
                }
                echo "</tr>";

                $no = 1;
                foreach ($gurus as $guru) {
                    $total_H = 0; $total_I = 0; $total_S = 0; $total_A = 0;
                    
                    // ==============================================================
                    // TENTUKAN WARNA BARIS: Genap BIRU LEMBUT (#DDEBF7), Ganjil KUNING LEMBUT (#FFF2CC)
                    // Dijamin TIDAK ADA yang putih polos!
                    // ==============================================================
                    $warna_baris = ($no % 2 == 0) ? '#DDEBF7' : '#FFF2CC'; 
                    
                    echo "<tr style='background-color: $warna_baris;'>";
                    echo "<td rowspan='2' style='text-align: center; vertical-align: middle; font-weight: bold; background-color: $warna_baris;'>" . $no . "</td>";
                    echo "<td style='font-weight: bold; font-size: 12px; vertical-align: middle; background-color: $warna_baris;'>" . strtoupper($guru->nama_guru) . "</td>";
                    
                    foreach ($tanggal_list as $tgl) {
                        $day_of_week = date('N', strtotime($tgl));
                        $data_harian = isset($rekap[$guru->nip][$tgl]) ? $rekap[$guru->nip][$tgl] : null;
                        
                        $status = $data_harian ? $data_harian['status'] : '';
                        $isi_kotak = "";
                        
                        // Default warna kotak ikut warna baris
                        $bg_color = $warna_baris;
                        $teks_color = "#000000";

                        // Warna Dasar Hari Libur (Menimpa warna baris)
                        if ($day_of_week == 7) { $bg_color = "#ff0000"; $teks_color = "#ffffff"; } 
                        elseif ($day_of_week == 6) { $bg_color = "#00b050"; $teks_color = "#ffffff"; } 
                        
                        // WARNA STATUS (Menimpa semuanya)
                        if ($status == 'v') { 
                            $total_H++; 
                            $bg_color = "#10B981"; // HIJAU 
                            $teks_color = "#ffffff"; 
                            
                            $jam_m = $data_harian['jam_masuk'] != '-' ? $data_harian['jam_masuk'] : '--:--';
                            $jam_p = $data_harian['jam_pulang'] != '-' ? $data_harian['jam_pulang'] : '--:--';
                            $isi_kotak = "<div style='font-size: 9px; line-height: 1.2;'>$jam_m<br>$jam_p</div>";
                        } elseif ($status == 'I') { 
                            $total_I++; 
                            $bg_color = "#3B82F6"; // BIRU 
                            $teks_color = "#ffffff"; 
                            $isi_kotak = "I";
                        } elseif ($status == 'S') { 
                            $total_S++; 
                            $bg_color = "#F59E0B"; // ORANYE 
                            $teks_color = "#ffffff"; 
                            $isi_kotak = "S";
                        } elseif ($status == 'A') { 
                            $total_A++; 
                            $bg_color = "#EF4444"; // MERAH 
                            $teks_color = "#ffffff"; 
                            $isi_kotak = "A";
                        }

                        echo "<td style='text-align: center; vertical-align: middle; background-color: $bg_color; color: $teks_color;'>" . $isi_kotak . "</td>"; 
                    }
                    
                    echo "<td rowspan='2' style='text-align: center; vertical-align: middle; font-weight: bold; background-color: $warna_baris;'>" . $total_H . "</td>";
                    echo "<td rowspan='2' style='text-align: center; vertical-align: middle; font-weight: bold; background-color: $warna_baris;'>" . $total_I . "</td>";
                    echo "<td rowspan='2' style='text-align: center; vertical-align: middle; font-weight: bold; background-color: $warna_baris;'>" . $total_S . "</td>";
                    echo "<td rowspan='2' style='text-align: center; vertical-align: middle; font-weight: bold; background-color: $warna_baris;'>" . $total_A . "</td>";
                    echo "<td rowspan='2' style='text-align: center; vertical-align: middle; background-color: $warna_baris;'></td>";
                    echo "</tr>";
                    
                    echo "<tr style='background-color: $warna_baris;'>";
                    echo "<td style='font-size: 10px; color: #555; vertical-align: top; background-color: $warna_baris;'>NIP. " . ($guru->nip ?: '-') . "</td>";
                    foreach ($tanggal_list as $tgl) {
                        $day_of_week = date('N', strtotime($tgl));
                        
                        $bg_color = $warna_baris;
                        if ($day_of_week == 7) { $bg_color = "#ff0000"; } 
                        elseif ($day_of_week == 6) { $bg_color = "#00b050"; } 
                        
                        echo "<td style='background-color: $bg_color;'></td>"; 
                    }
                    echo "</tr>";
                    
                    $no++;
                }

                echo "</table></body></html>";
            };
            return response()->stream($callback, 200, $headers);
        }
    }

    public function detailDashboard($tipe, $status)
    {
        date_default_timezone_set('Asia/Jakarta');
        $tanggal_sekarang = date('Y-m-d');
        $jam_sekarang = date('H:i');
        $judul = "Data " . strtoupper($tipe) . " - Status: " . strtoupper($status);
        $data = [];
        $user = auth()->user();
        $is_admin = ($user->email == 'abhaadmin234@gmail.com');
        $nama_login = strtolower($user->name);

        if ($status == 'semua') {
            if ($tipe == 'murid') {
                $query_semua = \App\Models\Murid::query();
                if (!$is_admin) { $query_semua->where('wali_kelas', $nama_login); }
                $data = $query_semua->get();
            } else { $data = \App\Models\Guru::all(); }
            foreach($data as $d) { $d->status = "Terdaftar"; $d->waktu = "-"; }
        } elseif ($status == 'alpha' || $status == 'belum_absen' || $status == 'bolos') {
            $absen_hari_ini = \App\Models\Absensi::where('tanggal', $tanggal_sekarang)->pluck('nis')->toArray();
            if ($tipe == 'murid') {
                $query_murid = \App\Models\Murid::whereNotIn('nis', $absen_hari_ini);
                if (!$is_admin) { $query_murid->where('wali_kelas', $nama_login); }
                $data = $query_murid->get();
            } else { $data = \App\Models\Guru::whereNotIn('nip', $absen_hari_ini)->get(); }
            $teks_status = ($status == 'belum_absen') ? 'BELUM ABSEN' : 'BOLOS (ALPHA)';
            $judul = "Data " . strtoupper($tipe) . " - Status: " . $teks_status;
            foreach($data as $d) { $d->status = $teks_status; $d->waktu = '-'; }
        } else {
            if ($tipe == 'murid') {
                $query = \App\Models\Absensi::join('murids', 'absensis.nis', '=', 'murids.nis')->select('absensis.*', 'murids.nama_lengkap as nama')->where('absensis.tanggal', $tanggal_sekarang);
                if (!$is_admin) { $query->where('murids.wali_kelas', $nama_login); }
            } else {
                $query = \App\Models\Absensi::join('gurus', 'absensis.nis', '=', 'gurus.nip')->select('absensis.*', 'gurus.nama_guru as nama')->where('absensis.tanggal', $tanggal_sekarang);
            }
            if ($status == 'hadir') {
                $query->where(function($q) { $q->where('status', 'like', '%Hadir%')->orWhere('status', 'like', '%Terlambat%'); });
            } else { $query->where('status', 'like', '%' . $status . '%'); }
            $data = $query->get();
        }
        return view('detail', compact('data', 'judul', 'tipe', 'status'));
    }

    public function kelasku()
    {
        $user = auth()->user();
        if ($user->email == 'abhaadmin234@gmail.com') return redirect('/dashboard'); 
        $semua_murid = \App\Models\Murid::orderBy('nama_lengkap', 'asc')->get();
        $nama_guru = strtolower($user->name);
        return view('kelasku', compact('semua_murid', 'nama_guru'));
    }

    public function updateKelasku(Request $request)
    {
        $user = auth()->user();
        $nama_guru = strtolower($user->name);
        \App\Models\Murid::where('wali_kelas', $nama_guru)->update(['wali_kelas' => null]);
        if ($request->has('murid_pilihan')) {
            \App\Models\Murid::whereIn('nis', $request->murid_pilihan)->update(['wali_kelas' => $nama_guru]);
        }
        return back()->with('success', 'Mantap! Daftar Kelasku berhasil diperbarui!');
    }

    public function halamanRekamWajah()
    {
        $murid_belum_rekam = \App\Models\Murid::whereNull('face_data')->get();
        $murid_sudah_rekam = \App\Models\Murid::whereNotNull('face_data')->get();
        return view('rekam_wajah', compact('murid_belum_rekam', 'murid_sudah_rekam'));
    }

    public function simpanWajah(Request $request)
    {
        try {
            $murid = \App\Models\Murid::where('nis', $request->nis)->first();
            if ($murid) {
                $murid->face_data = $request->face_data;
                $murid->save();
                if ($request->foto) {
                    $image_parts = explode(";base64,", $request->foto);
                    $image_base64 = base64_decode($image_parts[1]);
                    $file_name = $murid->nis . '.jpg';
                    if (!file_exists(public_path('wajah'))) { mkdir(public_path('wajah'), 0777, true); }
                    file_put_contents(public_path('wajah/' . $file_name), $image_base64);
                }
                return response()->json(['success' => true, 'pesan' => 'Wajah ' . $murid->nama_lengkap . ' berhasil didaftarkan!']);
            }
            return response()->json(['success' => false, 'pesan' => 'Murid tidak ditemukan.']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'pesan' => 'Error: ' . $e->getMessage()]); }
    }

    public function hapusWajah(Request $request)
    {
        $murid = \App\Models\Murid::where('nis', $request->nis)->first();
        if ($murid) {
            $murid->face_data = null;
            $murid->save();
            $file_path = public_path('wajah/' . $murid->nis . '.jpg');
            if (file_exists($file_path)) { unlink($file_path); }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }
    
    public function izinMandiri(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $hari_ini = date('Y-m-d');
        $jam_sekarang = date('H:i');
        
        $user = auth()->user();
        $guru = \App\Models\Guru::where('nama_guru', $user->name)->first();

        if (!$guru) {
            return back()->with('error', 'Gagal! Akun Anda tidak tertaut dengan data NIP Guru.');
        }

        $nip = $guru->nip; 
        $status_izin = $request->status_izin;

        $sudah_absen = \App\Models\Absensi::where('nis', $nip)
                            ->where('tanggal', $hari_ini)
                            ->where('status', 'like', '%'.$status_izin.'%')
                            ->first();

        if ($sudah_absen) {
            return back()->with('error', 'Anda sudah tercatat ' . $status_izin . ' hari ini.');
        }

        \App\Models\Absensi::create([
            'nis' => $nip,
            'tanggal' => $hari_ini,
            'waktu' => $jam_sekarang,
            'status' => $status_izin . ' (Mandiri via Web)' 
        ]);

        return back()->with('success', 'Berhasil! Status ' . $status_izin . ' Anda telah dicatat oleh sistem.');
    }
    
    // ==============================================================
    // FUNGSI KHUSUS REKAM WAJAH GURU
    // ==============================================================
    public function halamanRekamWajahGuru()
    {
        $guru_belum_rekam = \App\Models\Guru::whereNull('face_data')->get();
        $guru_sudah_rekam = \App\Models\Guru::whereNotNull('face_data')->get();
        return view('rekam_wajah_guru', compact('guru_belum_rekam', 'guru_sudah_rekam'));
    }

    public function simpanWajahGuru(Request $request)
    {
        try {
            $guru = \App\Models\Guru::where('nip', $request->nis)->first();
            if ($guru) {
                $guru->face_data = $request->face_data;
                $guru->save();
                if ($request->foto) {
                    $image_parts = explode(";base64,", $request->foto);
                    $image_base64 = base64_decode($image_parts[1]);
                    $file_name = 'GURU_' . $guru->nip . '.jpg';
                    if (!file_exists(public_path('wajah'))) { mkdir(public_path('wajah'), 0777, true); }
                    file_put_contents(public_path('wajah/' . $file_name), $image_base64);
                }
                return response()->json(['success' => true, 'pesan' => 'Wajah Guru ' . $guru->nama_guru . ' berhasil didaftarkan!']);
            }
            return response()->json(['success' => false, 'pesan' => 'Data Guru tidak ditemukan.']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'pesan' => 'Error: ' . $e->getMessage()]); }
    }

    public function hapusWajahGuru(Request $request)
    {
        $guru = \App\Models\Guru::where('nip', $request->nis)->first();
        if ($guru) {
            $guru->face_data = null;
            $guru->save();
            $file_path = public_path('wajah/GURU_' . $guru->nip . '.jpg');
            if (file_exists($file_path)) { unlink($file_path); }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }
    
    // ==============================================================
    // FUNGSI KHUSUS KELOLA GURU PENSIUN/KELUAR
    // ==============================================================
    public function kelolaGuru()
    {
        $user = auth()->user();
        if ($user->email != 'abhaadmin234@gmail.com' && $user->email != 'mesinabsen@gmail.com') return redirect('/dashboard'); 
        
        $semua_guru = \App\Models\Guru::where('nip', '!=', 'MESIN-01')->orderBy('nama_guru', 'asc')->get();
        return view('kelola_guru', compact('semua_guru'));
    }

    public function updateKelolaGuru(Request $request)
    {
        \App\Models\Guru::where('nip', '!=', 'MESIN-01')->update(['status_aktif' => 0]);
        if ($request->has('guru_pilihan')) {
            \App\Models\Guru::whereIn('nip', $request->guru_pilihan)->update(['status_aktif' => 1]);
        }
        return back()->with('success', 'Daftar Guru Aktif berhasil diperbarui! Guru yang tidak dicentang telah disembunyikan dari mesin absen.');
    }
}