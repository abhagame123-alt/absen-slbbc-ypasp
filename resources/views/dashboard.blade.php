<x-app-layout>
    <x-slot name="header">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            
            <!-- BAGIAN JUDUL & TOMBOL TEMA HP -->
            <div class="flex justify-between items-center w-full md:w-auto">
                <h2 class="font-bold text-xl md:text-2xl leading-tight header-title transition-colors" style="color: #065F46;">
                    <i class="fas fa-rocket mr-2 text-emerald-500"></i>{{ __('SISTEM ABSENSI') }}
                </h2>
                
                <!-- 🔥 TOMBOL TEMA KHUSUS HP (Hanya muncul di layar kecil) 🔥 -->
                <button onclick="toggleTemaHP()" class="md:hidden bg-gray-800 dark:bg-orange-400 text-white text-xs px-3 py-1.5 rounded-full font-bold shadow-md transition-colors flex items-center gap-1">
                    <i class="fas fa-adjust"></i> Tema
                </button>
            </div>
            
            <!-- BAGIAN JAM -->
            <div class="text-left md:text-right border-t md:border-t-0 border-emerald-200 dark:border-slate-700 w-full md:w-auto pt-2 md:pt-0 mt-1 md:mt-0">
                <div class="time-display transition-colors" style="font-size: 18px; font-weight: 800; color: #059669; letter-spacing: 1px;">
                    <i class="far fa-clock mr-1"></i> <span id="jam_dashboard">00:00:00</span>
                </div>
                <div class="text-xs text-gray-500 font-bold mt-1">{{ date('l, d F Y') }}</div>
            </div>
        </div>

        <!-- SCRIPT PENGENDALI TEMA HP -->
        <script>
            function toggleTemaHP() {
                // Ganti class 'dark-mode' di Body
                document.body.classList.toggle('dark-mode');
                
                // Ganti class 'dark' di HTML (Bawaan Tailwind)
                if(document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.theme = 'light';
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.theme = 'dark';
                }
            }
        </script>
    </x-slot>

    @php
        date_default_timezone_set('Asia/Jakarta');
        $tanggal_sekarang = date('Y-m-d');
        $jam_sekarang = date('H:i');
        $hari_angka = date('N'); 
        
        $is_weekend = ($hari_angka >= 6);
        
        // DAFTAR TANGGAL MERAH
        $tanggal_merah = [
            '2026-09-06',
            '2026-12-25',
            '2027-01-01',
            '2027-08-17',
        ];

        $is_tanggal_merah = in_array($tanggal_sekarang, $tanggal_merah);
        $is_libur = ($is_weekend || $is_tanggal_merah); 

        // ==============================================================================
        // 🧹 FITUR MATA-MATA (AUTO-ALPHA & AUTO-WA ORTU BAGI SISWA YANG KABUR)
        // ==============================================================================
        if ($jam_sekarang >= '15:00' && !$is_libur) {
            
            $absen_masuk_hari_ini = \App\Models\Absensi::where('tanggal', $tanggal_sekarang)
                ->where(function($q) {
                    $q->where('status', 'like', '%Hadir%')->orWhere('status', 'like', '%Terlambat%');
                })->get();

            foreach($absen_masuk_hari_ini as $absen_masuk) {
                $sudah_pulang = \App\Models\Absensi::where('nis', $absen_masuk->nis)
                    ->where('tanggal', $tanggal_sekarang)
                    ->where('status', 'like', '%Pulang%')
                    ->exists();

                if (!$sudah_pulang && !str_contains(strtolower($absen_masuk->status), 'bolos')) {
                    
                    $absen_masuk->update([
                        'status' => 'Bolos (Alpha - Kabur)' 
                    ]);

                    $murid_kabur = \App\Models\Murid::where('nis', $absen_masuk->nis)->first();

                    if ($murid_kabur && $murid_kabur->no_wa_ortu != null && $murid_kabur->no_wa_ortu != '-') {
                        $token = 'pDNx6yfHgKopj2VRZbnb'; 
                        $target = $murid_kabur->no_wa_ortu;
                        
                        $pesan_wa = "⚠️ *PEMBERITAHUAN PENTING & DARURAT* ⚠️\n\n"
                                  . "Halo Ayah/Bunda,\n\n"
                                  . "Kami dari SLB BC YPASP menginformasikan bahwa ananda *{$murid_kabur->nama_lengkap}* hari ini terpantau *TIDAK MELAKUKAN ABSEN PULANG* hingga pukul 15:00 WIB.\n\n"
                                  . "Oleh karena itu, sistem otomatis mencatat ananda dengan status *BOLOS (KABUR)*.\n\n"
                                  . "Mohon Ayah/Bunda segera memastikan keberadaan ananda saat ini demi keselamatan dan keamanan bersama.\n\n"
                                  . "Terima kasih,\n"
                                  . "Sistem Absensi SLB BC YPASP";

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
                          CURLOPT_POSTFIELDS => array('target' => $target, 'message' => $pesan_wa, 'countryCode' => '62'),
                          CURLOPT_HTTPHEADER => array("Authorization: $token"),
                          CURLOPT_SSL_VERIFYPEER => false,
                          CURLOPT_SSL_VERIFYHOST => false,
                        ));
                        \curl_exec($curl);
                        \curl_close($curl);
                    }
                }
            }
        }
        // ==============================================================================
        
        $is_admin = (auth()->check() && auth()->user()->email == 'abhaadmin234@gmail.com');
        $is_mesin = (auth()->check() && auth()->user()->email == 'mesinabsen@gmail.com');
        
        $daftar_guru_belum_absen = [];
        if ($is_mesin || $is_admin) {
            $absen_hadir_guru_hari_ini = \App\Models\Absensi::where('tanggal', $tanggal_sekarang)
                ->where(function($q) {
                    $q->where('status', 'like', '%Hadir%')->orWhere('status', 'like', '%Terlambat%');
                })->pluck('nis')->toArray();

            $q_guru = \App\Models\Guru::leftJoin('absensis', function($join) use ($tanggal_sekarang) {
                    $join->on('gurus.nip', '=', 'absensis.nis')
                         ->where('absensis.tanggal', '=', $tanggal_sekarang);
                })
                ->whereNotIn('gurus.nip', $absen_hadir_guru_hari_ini)
                ->where('gurus.nip', '!=', 'MESIN-01') 
                ->where(function($q) {
                    $q->where('gurus.status_aktif', 1)->orWhereNull('gurus.status_aktif');
                })
                ->select('gurus.*', 'absensis.status as absensi_status')
                ->orderBy('gurus.nama_guru', 'asc')
                ->get();

            $batas_jam_pulang = ($hari_angka == 5) ? '11:00' : '13:30';

            foreach($q_guru as $d) {
                if (empty($d->absensi_status)) {
                    if ($is_libur) {
                        $d->absensi_status = 'Libur';
                    } else {
                        $d->absensi_status = ($jam_sekarang < $batas_jam_pulang) ? 'Belum Absen' : 'Bolos (Alpha)';
                    }
                }
            }
            $daftar_guru_belum_absen = $q_guru;
        }
    @endphp

    <style>
        body, .bg-gray-100 { background-color: #F0FDF4 !important; transition: all 0.4s ease; }
        .welcome-card, .menu-section, .live-scroll-box { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.08); border: 1px solid #A7F3D0; transition: all 0.3s; margin-bottom: 25px; }
        .welcome-text h3 { font-size: 24px; font-weight: 800; color: #065F46; margin: 0; transition: 0.3s;}
        .welcome-text p { font-size: 14px; font-weight: 600; color: #047857; background: #D1FAE5; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-top: 8px; border: 1px solid #6EE7B7; transition: 0.3s;}
        .dashboard-split { display: flex; flex-direction: column; gap: 24px; margin-bottom: 30px; }
        @media(min-width: 992px) { .dashboard-split { display: grid; grid-template-columns: 40% 1fr; align-items: stretch; } }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-top: 15px; }
        .menu-card { background: #FFFFFF; border-radius: 15px; padding: 20px 10px; text-align: center; box-shadow: 0 4px 10px rgba(16,185,129,0.05); transition: all 0.3s ease; text-decoration: none; border: 1px solid #D1FAE5; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer;}
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(16,185,129,0.15); border-color: #10B981; }
        .menu-icon { font-size: 26px; margin-bottom: 12px; width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s;}
        .menu-icon.hijau { color: #10B981; background-color: #ECFDF5; }
        .menu-icon.pink { color: #EC4899; background-color: #FDF2F8; }
        .menu-icon.biru { color: #3B82F6; background-color: #EFF6FF; }
        .menu-icon.ungu { color: #8B5CF6; background-color: #F5F3FF; }
        .menu-icon.oren { color: #F59E0B; background-color: #FFFBEB; }
        .menu-card h4 { font-weight: 700; color: #374151; font-size: 14px; margin: 0; transition: 0.3s;}
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-box { border-radius: 15px; padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; transition: transform 0.3s ease; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-box:hover { transform: translateY(-5px); cursor: pointer; }
        .stat-box h4 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin:0; opacity: 0.9;}
        .stat-box p { font-size: 32px; font-weight: 800; margin: 5px 0 0 0; line-height: 1;}
        .stat-icon { font-size: 35px; opacity: 0.3; }
        .bg-blue-solid { background: linear-gradient(135deg, #3B82F6, #1D4ED8); }
        .bg-green-solid { background: linear-gradient(135deg, #10B981, #047857); }
        .bg-red-solid { background: linear-gradient(135deg, #EF4444, #B91C1C); }
        .bg-orange-solid { background: linear-gradient(135deg, #F59E0B, #C2410C); }
        .bg-gray-solid { background: linear-gradient(135deg, #64748B, #475569); }
        .scroll-area { overflow-y: auto; flex-grow: 1; max-height: 550px; padding-right: 5px; }
        .scroll-area::-webkit-scrollbar { width: 6px; }
        .scroll-area::-webkit-scrollbar-track { background: #ECFDF5; border-radius: 10px;}
        .scroll-area::-webkit-scrollbar-thumb { background: #A7F3D0; border-radius: 10px;}
        .scroll-area::-webkit-scrollbar-thumb:hover { background: #10B981; }
        .list-murid { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #D1FAE5; transition: 0.3s;}
        .list-murid:last-child { border-bottom: none; }
        .badge-sakit { color: #C2410C; background: #FFEDD5; }
        .badge-izin { color: #6D28D9; background: #EDE9FE; }
        .badge-alpha { color: #B91C1C; background: #FEE2E2; }
        .badge-belum { color: #4B5563; background: #F3F4F6; }
        .badge-libur { color: #991B1B; background: #FEE2E2; } 
        .dropdown-izin { border: 1px solid #A7F3D0 !important; background: #FFFFFF !important; color: #1F2937 !important; transition: 0.3s; }
        .dropdown-izin:focus { border-color: #10B981 !important; outline: none; box-shadow: 0 0 0 3px rgba(16,185,129,0.2); }

        body.dark-mode .bg-gray-100 { background-color: #0F172A !important; }
        body.dark-mode .welcome-card, body.dark-mode .menu-section, body.dark-mode .live-scroll-box { background-color: #1E293B !important; border-color: #064E3B !important; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        body.dark-mode .welcome-text h3 { color: #4ADE80; }
        body.dark-mode .welcome-text p { background: #064E3B; border-color: #047857; color: #A7F3D0; }
        body.dark-mode .menu-card { background-color: #0F172A; border-color: #334155; }
        body.dark-mode .menu-card:hover { border-color: #10B981; box-shadow: 0 10px 20px rgba(16,185,129,0.2); }
        body.dark-mode .menu-card h4, body.dark-mode .list-murid h4 { color: #F8FAFC !important; }
        body.dark-mode .list-murid { border-bottom-color: #334155 !important; }
        body.dark-mode .scroll-area::-webkit-scrollbar-track { background: #0F172A; }
        body.dark-mode .scroll-area::-webkit-scrollbar-thumb { background: #475569; }
        body.dark-mode .dropdown-izin { background-color: #0F172A !important; border-color: #334155 !important; color: #F8FAFC !important; }
        body.dark-mode .dropdown-izin:focus { border-color: #10B981 !important; box-shadow: 0 0 0 3px rgba(16,185,129,0.3); }
        body.dark-mode .header-title { color: #F8FAFC !important; }
        body.dark-mode .time-display { color: #4ADE80 !important; }
        body.dark-mode .badge-libur { background: rgba(220, 38, 38, 0.2); color: #FCA5A5; border: 1px solid #DC2626; }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="welcome-card" style="margin-bottom: 15px;">
                <div class="welcome-text">
                    @if($is_admin)
                        <h3>Halo, Admin {{ Auth::user()->name }}! 👋</h3><p>Role: ADMIN</p>
                    @elseif($is_mesin)
                        <h3>Halo, Mesin Absen Sekolah! 🤖</h3><p>Role: MESIN SCANNER KHUSUS</p>
                    @else
                        <h3>Halo, Guru {{ Auth::user()->name }}! 🎓</h3><p>Role: GURU</p>
                    @endif
                </div>
            </div>

            <!-- INFO HARI LIBUR & TANGGAL MERAH -->
            @if($is_libur)
            <div style="background-color: #FEF3C7; border-left: 5px solid #F59E0B; padding: 15px; margin-bottom: 25px; border-radius: 8px; color: #92400E; font-size: 15px;" class="dark:bg-yellow-900 dark:border-yellow-500 dark:text-yellow-200">
                <strong>🏖️ INFO HARI LIBUR:</strong> Hari ini adalah {{ $is_tanggal_merah ? 'Tanggal Merah (Libur Nasional)' : 'Akhir Pekan' }}. Mesin otomatis menyesuaikan status absen menjadi Libur!
            </div>
            @endif

            @if(!$is_mesin)
            <div class="w-full border rounded-xl py-3 px-4 flex items-center justify-between shadow-sm transition-colors mb-6" 
                 id="boxInfoJamDashboard" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.3);"
                 class="dark:bg-emerald-900/10 dark:border-emerald-700/50">
                <span class="text-xs md:text-sm font-bold uppercase tracking-wider" style="color: #059669;" class="dark:text-emerald-400">
                    <i class="fas fa-info-circle mr-2"></i> Info Jam Pulang Hari Ini:
                </span>
                <span id="displayJamPulangDashboard" class="text-xs md:text-sm font-extrabold px-3 py-1 rounded-md border shadow-sm" 
                      style="background: #D1FAE5; color: #065F46; border-color: #6EE7B7;"
                      class="dark:bg-emerald-900 dark:text-emerald-200 dark:border-emerald-700">
                    Memuat Jadwal...
                </span>
            </div>
            @endif

            <div class="dashboard-split">
                <!-- KOLOM KIRI: DAFTAR BELUM HADIR SCROLL -->
                <div class="live-scroll-box" style="height: 100%;">
                    <h3 class="font-bold text-lg mb-1" style="color: #DC2626;"><i class="fas fa-user-clock mr-2"></i> Belum Hadir</h3>
                    <p class="text-xs text-gray-400 mb-4 pb-3" style="border-bottom: 1px solid #D1FAE5;" class="dark:border-gray-700">Data hari ini: {{ date('d/m/Y') }}</p>
                    
                    <div class="scroll-area">
                        @if($is_mesin || $is_admin)
                            @if(count($daftar_guru_belum_absen) > 0)
                                @foreach($daftar_guru_belum_absen as $index => $guru)
                                    @php
                                        $status_class = 'badge-belum'; 
                                        if(str_contains(strtolower($guru->absensi_status), 'sakit')) $status_class = 'badge-sakit';
                                        elseif(str_contains(strtolower($guru->absensi_status), 'izin')) $status_class = 'badge-izin';
                                        elseif(str_contains(strtolower($guru->absensi_status), 'bolos') || str_contains(strtolower($guru->absensi_status), 'alpha')) $status_class = 'badge-alpha';
                                        elseif(str_contains(strtolower($guru->absensi_status), 'libur')) $status_class = 'badge-libur';
                                    @endphp
                                    <div class="list-murid">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span style="font-size: 14px; font-weight: bold; color: #9CA3AF; min-width: 15px;">{{ $index + 1 }}</span>
                                            <div>
                                                <a href="{{ route('guru.profil', $guru->nip) }}" style="font-size: 13px; font-weight: 700; color: #059669; line-height: 1.2; text-decoration: none; display: flex; align-items: center; gap: 5px; transition: 0.3s;" onmouseover="this.style.color='#047857'; this.style.textDecoration='underline'" onmouseout="this.style.color='#059669'; this.style.textDecoration='none'" class="dark:text-emerald-400">{{ strtoupper($guru->nama_guru) }}<i class="fas fa-external-link-alt" style="font-size: 9px; opacity: 0.7;"></i></a>
                                                <span style="font-size: 11px; color: #6B7280;">NIP: {{ $guru->nip }}</span>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="{{ $status_class }}" style="font-size: 10px; font-weight: bold; padding: 3px 6px; border-radius: 4px; display: block; margin-bottom: 2px;">{{ strtoupper($guru->absensi_status) }}</span>
                                            <span style="font-size: 9px; color: #9CA3AF;">GURU</span>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div style="text-align: center; padding: 40px 0; color: #9CA3AF;"><i class="fas fa-check-circle text-4xl mb-3" style="color: #10B981;"></i><p style="font-size: 14px; font-weight: bold; color: #059669;" class="dark:text-emerald-400">Semua Guru sudah hadir!</p></div>
                            @endif
                        @else
                            @if(isset($daftar_belum_absen) && count($daftar_belum_absen) > 0)
                                @foreach($daftar_belum_absen as $index => $murid)
                                    @php
                                        // Deteksi apakah libur di dashboard guru
                                        $status_awal = $murid->absensi_status ?? '';
                                        if($is_libur && empty($status_awal)) {
                                            $status_awal = 'Libur';
                                        }

                                        $status_class = 'badge-belum'; 
                                        if(str_contains(strtolower($status_awal), 'sakit')) $status_class = 'badge-sakit';
                                        elseif(str_contains(strtolower($status_awal), 'izin')) $status_class = 'badge-izin';
                                        elseif(str_contains(strtolower($status_awal), 'bolos') || str_contains(strtolower($status_awal), 'alpha')) $status_class = 'badge-alpha';
                                        elseif(str_contains(strtolower($status_awal), 'libur')) $status_class = 'badge-libur';
                                    @endphp
                                    <div class="list-murid">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span style="font-size: 14px; font-weight: bold; color: #9CA3AF; min-width: 15px;">{{ $index + 1 }}</span>
                                            <div>
                                                <a href="{{ route('murid.profil', $murid->nis) }}" style="font-size: 13px; font-weight: 700; color: #059669; line-height: 1.2; text-decoration: none; display: flex; align-items: center; gap: 5px; transition: 0.3s;" onmouseover="this.style.color='#047857'; this.style.textDecoration='underline'" onmouseout="this.style.color='#059669'; this.style.textDecoration='none'" class="dark:text-emerald-400">{{ strtoupper($murid->nama_lengkap) }}<i class="fas fa-external-link-alt" style="font-size: 9px; opacity: 0.7;"></i></a>
                                                <span style="font-size: 11px; color: #6B7280;">{{ $murid->nis }}</span>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="{{ $status_class }}" style="font-size: 10px; font-weight: bold; padding: 3px 6px; border-radius: 4px; display: block; margin-bottom: 2px;">{{ strtoupper($status_awal ?: 'BELUM ABSEN') }}</span>
                                            <span style="font-size: 9px; color: #9CA3AF;">{{ $murid->kelas ?? 'KOSONG' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div style="text-align: center; padding: 40px 0; color: #9CA3AF;"><i class="fas fa-check-circle text-4xl mb-3" style="color: #10B981;"></i><p style="font-size: 14px; font-weight: bold; color: #059669;" class="dark:text-emerald-400">Semua Siswa sudah hadir!</p></div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- KOLOM KANAN -->
                <div style="display: flex; flex-direction: column; height: 100%;">
                    <div class="menu-section">
                        <h3 class="font-bold text-lg mb-4 transition-colors" style="color: #4B5563;"><i class="fas fa-th-large mr-2"></i> Menu Utama</h3>
                        <div class="menu-grid">
                            
                            @if($is_mesin)
                                <!-- MENU UNTUK LOGIN MESIN ABSEN -->
                                <a href="{{ route('scan.guru') }}" class="menu-card"><div class="menu-icon hijau"><i class="fas fa-id-badge"></i></div><h4>Scan Guru</h4></a>
                                <a href="/rekam-wajah-guru" class="menu-card"><div class="menu-icon pink"><i class="fas fa-smile-beam"></i></div><h4>Rekam Wajah Guru</h4></a>
                                <a href="{{ route('kelola.guru') }}" class="menu-card"><div class="menu-icon ungu"><i class="fas fa-user-minus"></i></div><h4>Kelola Guru Keluar</h4></a>
                                
                                <!-- TOMBOL KHUSUS MESIN: DOWNLOAD REKAP GAJI GURU -->
                                <a href="#" onclick="bukaModalExportGuru(); return false;" class="menu-card" style="border-color: #FCD34D;">
                                    <div class="menu-icon oren" style="background-color: #FEF3C7; color: #D97706;">
                                        <i class="fas fa-file-excel"></i>
                                    </div>
                                    <h4 style="color: #B45309;">Rekap Gaji (Excel)</h4>
                                </a>
                                
                            @else
                                <!-- MENU UNTUK GURU / ADMIN -->
                                <a href="{{ route('scan') }}" class="menu-card"><div class="menu-icon biru"><i class="fas fa-qrcode"></i></div><h4>Scan Siswa</h4></a>
                                <a href="/rekam-wajah" class="menu-card"><div class="menu-icon pink"><i class="fas fa-smile-beam"></i></div><h4>Rekam Wajah Siswa</h4></a>
                                
                                <!-- 🔥 BARU: MENU SCAN GURU KINI DIBUKA UNTUK SEMUA GURU TERMASUK AFRA 🔥 -->
                                <a href="{{ route('scan.guru') }}" class="menu-card" style="border-color: #10B981;">
                                    <div class="menu-icon hijau"><i class="fas fa-id-badge"></i></div>
                                    <h4 style="color: #059669;">Scan Guru (Mesin)</h4>
                                </a>
                                <!-- ======================================================== -->

                                @if($is_admin)
                                    <a href="/rekam-wajah-guru" class="menu-card"><div class="menu-icon pink"><i class="fas fa-smile-beam"></i></div><h4>Rekam Wajah Guru</h4></a>
                                @endif
                            @endif

                            @if($is_admin)
                                <a href="{{ route('murid.index') }}" class="menu-card"><div class="menu-icon hijau"><i class="fas fa-user-graduate"></i></div><h4>Data Siswa</h4></a>
                                <a href="{{ route('guru.index') }}" class="menu-card"><div class="menu-icon biru"><i class="fas fa-chalkboard-teacher"></i></div><h4>Data Guru</h4></a>
                                <a href="{{ route('rekap') }}" class="menu-card"><div class="menu-icon oren"><i class="fas fa-file-alt"></i></div><h4>Laporan Harian</h4></a>
                                <a href="{{ route('tabungan') }}" class="menu-card"><div class="menu-icon ungu"><i class="fas fa-wallet"></i></div><h4>Tabungan Siswa</h4></a>
                                <a href="{{ route('kelola.guru') }}" class="menu-card"><div class="menu-icon ungu"><i class="fas fa-user-minus"></i></div><h4>Kelola Guru Keluar</h4></a>
                                
                                <a href="#" onclick="bukaModalJadwal(); return false;" class="menu-card"><div class="menu-icon oren"><i class="fas fa-calendar-alt"></i></div><h4>Jadwal Mingguan</h4></a>
                                <a href="#" onclick="bukaModalKendali(); return false;" class="menu-card"><div class="menu-icon" style="background-color: #FEF2F2; color: #DC2626;"><i class="fas fa-sliders-h"></i></div><h4>Kendali Scanner</h4></a>
                                
                            @elseif(!$is_mesin)
                                <a href="{{ route('kelasku') }}" class="menu-card"><div class="menu-icon hijau"><i class="fas fa-users-cog"></i></div><h4>Kelola Kelasku</h4></a>
                                <a href="{{ route('rekap') }}" class="menu-card"><div class="menu-icon biru"><i class="fas fa-book-open"></i></div><h4>Buku Rekap Absen</h4></a>
                                <a href="{{ route('tabungan') }}" class="menu-card"><div class="menu-icon ungu"><i class="fas fa-wallet"></i></div><h4>Tabungan Siswa</h4></a>
                                
                                <a href="#" onclick="bukaModalKendali(); return false;" class="menu-card"><div class="menu-icon" style="background-color: #FFF7ED; color: #EA580C;"><i class="fas fa-sliders-h"></i></div><h4>Kendali Scanner</h4></a>
                            @endif
                        </div>
                    </div>

                    @if(!$is_admin && !$is_mesin)
                    <div class="menu-section" style="padding: 24px; margin-top: auto;">
                        <h3 class="font-bold text-lg mb-2" style="color: #059669;"><i class="fas fa-notes-medical mr-2"></i> Pengajuan Izin Darurat</h3>
                        <p class="text-sm text-gray-500 mb-5">Gunakan fitur ini jika Anda berhalangan hadir atau harus pulang mendadak.</p>
                        <form action="{{ route('izin.mandiri') }}" method="POST" class="flex flex-col lg:flex-row gap-4 w-full">
                            @csrf
                            <div class="flex-1 flex items-center justify-center p-3 rounded-lg font-bold text-sm cursor-not-allowed shadow-inner transition-colors" style="background: rgba(16, 185, 129, 0.1); border: 1px solid #6EE7B7; color: #059669;" class="dark:bg-emerald-900 dark:border-emerald-500 dark:text-emerald-300">
                                <i class="fas fa-user-circle mr-2"></i> <span class="truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                            </div>
                            <select name="status_izin" required class="dropdown-izin flex-1 p-3 rounded-lg text-sm cursor-pointer">
                                <option value="">-- Pilih Alasan --</option><option value="Sakit">Sakit</option><option value="Izin">Izin (Ada Kepentingan)</option>
                            </select>
                            <button type="submit" class="flex-1 transition shadow flex items-center justify-center gap-2 text-sm p-3 rounded-lg font-bold" style="background: linear-gradient(135deg, #10B981, #059669); color: white; border: none; cursor: pointer;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <!-- ===================== STATISTIK ===================== -->
            @if(!$is_mesin)
            <h3 class="font-bold text-lg mb-4 mt-8 transition-colors" style="color: #4B5563;" class="dark:text-gray-300"><i class="fas fa-chart-bar mr-2"></i> Statistik Siswa Hari Ini</h3>
            <div class="stat-grid">
                <a href="{{ route('dashboard.detail', ['murid', 'semua']) }}" class="stat-box bg-blue-solid"><div><h4>Total Siswa</h4><p>{{ $total_murid ?? 0 }}</p></div><div class="stat-icon">👥</div></a>
                <a href="{{ route('dashboard.detail', ['murid', 'hadir']) }}" class="stat-box bg-green-solid"><div><h4>Hadir</h4><p>{{ $murid_hadir ?? 0 }}</p></div><div class="stat-icon">✅</div></a>
                <a href="{{ route('dashboard.detail', ['murid', 'belum_absen']) }}" class="stat-box bg-gray-solid"><div><h4>Belum Absen</h4><p>{{ $murid_belum_absen ?? 0 }}</p></div><div class="stat-icon">⏳</div></a>
                <a href="{{ route('dashboard.detail', ['murid', 'bolos']) }}" class="stat-box bg-red-solid"><div><h4>Bolos (Alpha)</h4><p>{{ $murid_bolos ?? 0 }}</p></div><div class="stat-icon">❌</div></a>
                <a href="{{ route('dashboard.detail', ['murid', 'sakit']) }}" class="stat-box bg-orange-solid"><div><h4>Sakit & Izin</h4><p>{{ ($murid_sakit ?? 0) + ($murid_izin ?? 0) }}</p></div><div class="stat-icon">💊</div></a>
            </div>
            @endif

        </div>
    </div>

    <!-- SCRIPT JAVASCRIPT POP-UP JADWAL, KENDALI SCANNER & EXPORT EXCEL -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const hari_angka_php = {{ $hari_angka }};
            const is_tanggal_merah = {{ $is_tanggal_merah ? 'true' : 'false' }};
            let jadwalMingguan = JSON.parse(localStorage.getItem('jadwalSekolah'));
            let tampilanJadwal = document.getElementById('displayJamPulangDashboard');
            let savedOverride = localStorage.getItem('settingJamPulang');

            if(tampilanJadwal) {
                if(savedOverride && !savedOverride.includes('Otomatis') && !savedOverride.includes('Normal')) {
                     tampilanJadwal.innerText = savedOverride;
                     if(savedOverride.includes('DITUTUP')) {
                         tampilanJadwal.style.background = '#FEE2E2'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                     } else if (savedOverride.includes('DIBUKA')) {
                         tampilanJadwal.style.background = '#D1FAE5'; tampilanJadwal.style.color = '#047857'; tampilanJadwal.style.borderColor = '#6EE7B7';
                     } else {
                         tampilanJadwal.style.background = '#FEF3C7'; tampilanJadwal.style.color = '#92400E'; tampilanJadwal.style.borderColor = '#FCD34D';
                     }
                     
                     document.querySelectorAll('.badge-libur').forEach(el => {
                         el.className = 'badge-waiting';
                         el.innerText = 'BELUM';
                     });

                } else if(is_tanggal_merah) {
                    tampilanJadwal.innerText = "TANGGAL MERAH (LIBUR NASIONAL)";
                    tampilanJadwal.style.background = '#FEE2E2'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                } else {
                    if(jadwalMingguan && jadwalMingguan[hari_angka_php]) {
                        let jadwalHariIni = jadwalMingguan[hari_angka_php];
                        if(jadwalHariIni.status === 'libur') {
                            tampilanJadwal.innerText = "HARI LIBUR";
                            tampilanJadwal.style.background = '#FEE2E2'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                        } else {
                            tampilanJadwal.innerText = `Sesuai Jadwal (${jadwalHariIni.waktu} WIB)`;
                        }
                    } else {
                        let batas = (hari_angka_php == 5) ? '11:00' : '13:30';
                        if(hari_angka_php >= 6) {
                            tampilanJadwal.innerText = "HARI LIBUR";
                            tampilanJadwal.style.background = '#FEE2E2'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                        } else {
                            tampilanJadwal.innerText = `Sesuai Jadwal (${batas} WIB)`;
                        }
                    }
                }
            }
        });

        // ==============================================================
        // FUNGSI BARU: POP-UP EXPORT EXCEL UNTUK BENDAHARA (LOGIN MESIN)
        // ==============================================================
        function bukaModalExportGuru() {
            const isDark = document.body.classList.contains('dark-mode');
            const bgColor = isDark ? '#1E293B' : '#FFFFFF';
            const textColor = isDark ? '#F8FAFC' : '#1F2937';
            const inputBg = isDark ? '#334155' : '#F3F4F6';
            const borderColor = isDark ? '#475569' : '#D1D5DB';

            const tglSkrg = new Date();
            const tahun = tglSkrg.getFullYear();
            const bulan = String(tglSkrg.getMonth() + 1).padStart(2, '0');
            const tglTerakhir = new Date(tahun, tglSkrg.getMonth() + 1, 0).getDate();
            
            const defAwal = `${tahun}-${bulan}-01`;
            const defAkhir = `${tahun}-${bulan}-${tglTerakhir}`;

            Swal.fire({
                title: '<span style="font-size: 22px; font-weight: 800; color: #D97706;"><i class="fas fa-file-excel"></i> Unduh Rekap Gaji</span>',
                html: `
                    <p style="font-size: 13px; color: ${isDark ? '#9CA3AF' : '#6B7280'}; margin-bottom: 20px;">Pilih rentang tanggal untuk mencetak laporan absensi Guru (Bahan Perhitungan Gaji Bendahara).</p>
                    <div style="display: flex; flex-direction: column; gap: 15px; text-align: left;">
                        <div>
                            <label style="font-size: 12px; font-weight: bold; color: ${textColor};">📅 Tanggal Awal:</label>
                            <input type="date" id="tglAwalExcel" value="${defAwal}" class="swal2-input" style="display:flex; margin: 5px auto 0; width: 100%; border-radius: 8px; border: 2px solid ${borderColor}; background: ${inputBg}; color: ${textColor}; outline: none;">
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: bold; color: ${textColor};">📅 Tanggal Akhir:</label>
                            <input type="date" id="tglAkhirExcel" value="${defAkhir}" class="swal2-input" style="display:flex; margin: 5px auto 0; width: 100%; border-radius: 8px; border: 2px solid ${borderColor}; background: ${inputBg}; color: ${textColor}; outline: none;">
                        </div>
                    </div>
                `,
                background: bgColor, color: textColor,
                showCancelButton: true,
                focusConfirm: false, 
                confirmButtonText: '<i class="fas fa-download mr-1"></i> Unduh File Excel',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10B981', cancelButtonColor: '#EF4444',
                customClass: { popup: 'rounded-3xl border-2 border-amber-500/30 overflow-visible', confirmButton: 'rounded-xl font-bold', cancelButton: 'rounded-xl font-bold' },
                preConfirm: () => {
                    const awal = document.getElementById('tglAwalExcel').value;
                    const akhir = document.getElementById('tglAkhirExcel').value;
                    if(!awal || !akhir) {
                        Swal.showValidationMessage('Mohon isi tanggal awal dan akhir!');
                    }
                    return { awal: awal, akhir: akhir }
                }
            }).then((result) => {
                if(result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses Laporan...',
                        text: 'File Excel sedang diunduh, mohon tunggu.',
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false,
                        background: bgColor, color: textColor
                    });
                    
                    window.location.href = `/export/guru?tgl_awal=${result.value.awal}&tgl_akhir=${result.value.akhir}`;
                }
            });
        }


        const defaultJadwal = {
            1: { status: 'jam', waktu: '13:30' }, 2: { status: 'jam', waktu: '13:30' },
            3: { status: 'jam', waktu: '13:30' }, 4: { status: 'jam', waktu: '13:30' },
            5: { status: 'jam', waktu: '11:00' }, 6: { status: 'libur', waktu: '00:00' }, 7: { status: 'libur', waktu: '00:00' }
        };

        function bukaModalJadwal() {
            const isDark = document.body.classList.contains('dark-mode');
            const bgColor = isDark ? '#1E293B' : '#FFFFFF';
            const textColor = isDark ? '#F8FAFC' : '#1F2937';
            const inputBg = isDark ? '#334155' : '#F3F4F6';
            const borderColor = isDark ? '#475569' : '#D1D5DB';

            let jadwalTersimpan = JSON.parse(localStorage.getItem('jadwalSekolah')) || defaultJadwal;
            const namaHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            
            let htmlForm = `<div style="text-align:left; font-size:14px; margin-top:15px; display:flex; flex-direction:column; gap:10px;">`;
            for(let i=1; i<=7; i++) {
                let dataHari = jadwalTersimpan[i];
                let isLibur = dataHari.status === 'libur';
                htmlForm += `
                <div style="display:flex; justify-content:space-between; align-items:center; background:${isDark ? '#0F172A' : '#F9FAFB'}; padding:10px 15px; border-radius:10px; border:1px solid ${borderColor};">
                    <span style="font-weight:bold; width:70px;">${namaHari[i-1]}</span>
                    <select id="status_${i}" class="swal2-select" onchange="toggleInputTime(${i})" style="display:flex; margin:0; padding:6px; border-radius:8px; background:${inputBg}; color:${textColor}; border:1px solid ${borderColor}; outline:none; font-weight:bold;">
                        <option value="jam" ${!isLibur ? 'selected' : ''}>Bersekolah</option>
                        <option value="libur" ${isLibur ? 'selected' : ''}>Libur</option>
                    </select>
                    <input type="time" id="waktu_${i}" value="${dataHari.waktu}" style="padding:6px; border-radius:8px; background:${inputBg}; color:${textColor}; border:1px solid #10B981; outline:none; font-weight:bold; display:${isLibur ? 'none' : 'block'}; width:100px; text-align:center;">
                    <span id="labelLibur_${i}" style="display:${isLibur ? 'block' : 'none'}; width:100px; text-align:center; font-weight:bold; color:#EF4444; font-size:12px;">LIBUR</span>
                </div>`;
            }
            htmlForm += `</div>`;

            Swal.fire({
                title: '<span style="font-size: 22px; font-weight: 800;">📅 Jadwal Mingguan</span>',
                html: htmlForm, background: bgColor, color: textColor, showCancelButton: true,
                focusConfirm: false, 
                confirmButtonText: '<i class="fas fa-save mr-1"></i> Simpan Permanen', cancelButtonText: 'Batal',
                confirmButtonColor: '#10B981', cancelButtonColor: '#6B7280',
                customClass: { popup: 'rounded-3xl shadow-xl overflow-visible', confirmButton: 'rounded-xl font-bold', cancelButton: 'rounded-xl font-bold' },
                preConfirm: () => {
                    let jadwalBaru = {};
                    for(let i=1; i<=7; i++) {
                        jadwalBaru[i] = { status: document.getElementById(`status_${i}`).value, waktu: document.getElementById(`waktu_${i}`).value };
                    }
                    return jadwalBaru;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.setItem('jadwalSekolah', JSON.stringify(result.value));
                    Swal.fire({ icon: 'success', title: 'Tersimpan!', text: 'Jadwal mingguan berhasil diperbarui.', background: bgColor, color: textColor, showConfirmButton: false, timer: 2000 });
                    setTimeout(() => location.reload(), 2000); 
                }
            });
        }

        function bukaModalKendali() {
            const isDark = document.body.classList.contains('dark-mode');
            const bgColor = isDark ? '#1E293B' : '#FFFFFF';
            const textColor = isDark ? '#F8FAFC' : '#1F2937';
            const selectBg = isDark ? '#334155' : '#F3F4F6';
            const borderColor = isDark ? '#475569' : '#D1D5DB';

            Swal.fire({
                title: '<span style="font-size: 24px; font-weight: 800;">⚙️ Kendali Scanner</span>',
                html: `
                    <p style="font-size: 14px; color: #9CA3AF; margin-bottom: 25px;">Pegang kendali penuh atas mesin scanner hari ini.</p>
                    <div style="display: flex; flex-direction: column; gap: 15px; align-items: center; width: 100%;">
                        <select id="pilihanJam" class="swal2-select" style="display:flex; margin:0 auto; width: 90%; padding: 14px 15px; font-size: 15px; font-weight: bold; background: ${selectBg}; color: ${textColor}; border: 2px solid ${borderColor}; border-radius: 12px; outline: none; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                            <option value="normal">🔄 Sesuai Jadwal Sistem (Otomatis)</option>
                            <option value="on">🟢 BUKA Absen Pulang Sekarang (ON)</option>
                            <option value="off">🔴 TUTUP Absen Pulang Sementara (OFF)</option>
                            <option value="custom">✍️ Atur Jam Manual...</option>
                        </select>
                        <div id="wrapperJam" style="display: none; width: 100%; padding-top: 10px;">
                            <p style="font-size: 13px; font-weight: bold; color: #059669; margin-bottom: 8px;">Tentukan jam kepulangan manual:</p>
                            <input type="time" id="jamCustom" class="swal2-input" style="width: 50%; min-width: 150px; padding: 12px 15px; font-size: 20px; font-weight: 900; text-align: center; background: ${selectBg}; color: ${textColor}; border: 2px solid #10B981; border-radius: 12px; outline: none; margin: 0 auto;">
                        </div>
                    </div>
                `,
                background: bgColor, color: textColor, showCancelButton: true,
                focusConfirm: false, 
                confirmButtonText: '<i class="fas fa-power-off mr-1"></i> Terapkan', cancelButtonText: 'Batal',
                confirmButtonColor: '#10B981', cancelButtonColor: '#EF4444',
                customClass: { popup: 'rounded-3xl border-2 border-emerald-500/30 overflow-visible', actions: 'mt-6 gap-3', confirmButton: 'px-6 py-3 rounded-xl font-bold', cancelButton: 'px-6 py-3 rounded-xl font-bold' },
                didOpen: () => {
                    const select = Swal.getPopup().querySelector('#pilihanJam');
                    const wrapperCustom = Swal.getPopup().querySelector('#wrapperJam');
                    select.addEventListener('change', (e) => {
                        wrapperCustom.style.display = (e.target.value === 'custom') ? 'block' : 'none';
                    });
                },
                preConfirm: () => {
                    const pilihan = Swal.getPopup().querySelector('#pilihanJam').value;
                    const jam = Swal.getPopup().querySelector('#jamCustom').value;
                    if(pilihan === 'custom' && !jam) Swal.showValidationMessage('Silakan tentukan jamnya terlebih dahulu!');
                    return { pilihan: pilihan, jam: jam }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    let teksSimpan = "Sesuai Jadwal (Otomatis)";
                    if (result.value.pilihan === 'on') { teksSimpan = "DIBUKA (Mulai Sekarang)"; }
                    else if (result.value.pilihan === 'off') { teksSimpan = "DITUTUP SEMENTARA"; }
                    else if (result.value.pilihan === 'custom') { teksSimpan = "Manual Jam " + result.value.jam + " WIB"; }
                    
                    localStorage.setItem('settingJamPulang', teksSimpan);
                    Swal.fire({ icon: 'success', title: 'Mesin Diperbarui!', text: 'Pengaturan absen pulang berhasil diterapkan ke mesin scanner.', background: bgColor, color: textColor, showConfirmButton: false, timer: 2000, customClass: { popup: 'border-2 border-emerald-500 rounded-3xl' } });
                    setTimeout(() => location.reload(), 2000); 
                }
            });
        }

        function toggleInputTime(idHari) {
            let status = document.getElementById(`status_${idHari}`).value;
            let inputWaktu = document.getElementById(`waktu_${idHari}`);
            let labelLibur = document.getElementById(`labelLibur_${idHari}`);
            if(status === 'libur') { inputWaktu.style.display = 'none'; labelLibur.style.display = 'block'; }
            else { inputWaktu.style.display = 'block'; labelLibur.style.display = 'none'; }
        }

        function jalankanJamDashboard() {
            const waktu = new Date();
            const jam = waktu.getHours().toString().padStart(2, '0');
            const menit = waktu.getMinutes().toString().padStart(2, '0');
            const detik = waktu.getSeconds().toString().padStart(2, '0');
            const elemenJam = document.getElementById('jam_dashboard');
            if(elemenJam) elemenJam.innerText = `${jam}:${menit}:${detik}`;
        }
        setInterval(jalankanJamDashboard, 1000); jalankanJamDashboard(); 
    </script>
</x-app-layout>