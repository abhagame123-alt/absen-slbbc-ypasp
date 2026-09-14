@php
    date_default_timezone_set('Asia/Jakarta');
    $hari_ini = date('Y-m-d');
    
    // DAFTAR TANGGAL MERAH
    $tanggal_merah = [
        '2026-09-06',
        '2026-12-25',
        '2027-01-01',
        '2027-08-17',
    ];
    $is_tanggal_merah = in_array($hari_ini, $tanggal_merah);

    $absen_masuk = \App\Models\Absensi::join('murids', 'absensis.nis', '=', 'murids.nis')
        ->select('absensis.tanggal', 'absensis.waktu', 'absensis.status', 'murids.*') 
        ->where('absensis.tanggal', $hari_ini)
        ->where(function($q) {
            $q->where('absensis.status', 'like', '%Hadir%')
              ->orWhere('absensis.status', 'like', '%Terlambat%');
        })
        ->orderBy('absensis.waktu', 'desc')
        ->get();
        
    $nis_sudah_absen = $absen_masuk->pluck('nis')->toArray();
    
    $murid_belum_absen = \App\Models\Murid::whereNotIn('nis', $nis_sudah_absen)
        ->orderBy('kelas', 'asc')
        ->orderBy('nama_lengkap', 'asc')
        ->get();

    // MENGAMBIL DAFTAR SEMUA KELAS YANG ADA DI DATABASE
    $semua_kelas = \App\Models\Murid::select('kelas')
        ->whereNotNull('kelas')
        ->where('kelas', '!=', '')
        ->distinct()
        ->pluck('kelas')
        ->toArray();
    sort($semua_kelas);

    // LOGIKA PENGELOMPOKAN KELAS
    $grouped_belum = [];
    foreach($murid_belum_absen as $m) {
        $k = empty($m->kelas) ? 'TANPA KELAS' : strtoupper($m->kelas);
        $grouped_belum[$k][] = $m;
    }

    $hari_angka = date('N'); 
    
    // CEK SIAPA YANG LOGIN
    $is_admin = (auth()->check() && auth()->user()->email == 'abhaadmin234@gmail.com');
    $is_mesin = (auth()->check() && auth()->user()->email == 'mesinabsen@gmail.com');

    // ==============================================================================
    // TARIK DATA WAJAH DI SINI LALU JADIKAN JSON MURNI BIAR LARAVEL NGGAK ERROR
    // ==============================================================================
    $data_wajah_db = \App\Models\Murid::whereNotNull('face_data')
                        ->select('nis', 'nama_lengkap', 'face_data')
                        ->get();
    $json_wajah_murni = json_encode($data_wajah_db);
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ULTIMATE SCANNER - SLB BC YPASP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #F0FDF4; color: #1F2937; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: background-color 0.3s; padding: 5px 0; overflow-x: hidden;}
        .btn-kembali { background: #FFFFFF; color: #059669; border: 1px solid #10B981; padding: 4px 12px; border-radius: 8px; font-weight: bold; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(16,185,129,0.1); transition: 0.3s; cursor: pointer; }
        .btn-kembali:hover { background: #ECFDF5; color: #047857; }
        
        .scanner-container { background: #FFFFFF; padding: 12px 10px; border-radius: 12px; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.15); text-align: center; border: 2px solid #6EE7B7; position: relative; transition: all 0.3s; width: 100%; height: auto; margin-bottom: 5px;}
        .title-scanner { color: #059669; font-size: 1.1rem; font-weight: bold; margin-bottom: 0.1rem; }
        .subtitle-scanner { color: #4B5563; font-size: 0.7rem; margin-bottom: 0.4rem; }
        
        .camera-wrapper { position: relative; width: 100%; min-height: 250px; background: black; border-radius: 8px; overflow: hidden; border: 3px solid #10B981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.2); display: flex; flex-direction: column; align-items: center; justify-content: center; }
        
        #reader { width: 100%; min-height: 250px; }
        #reader__dashboard_section { padding: 4px !important; background: #1E293B !important; color: white !important; }
        #reader__scan_region { position: relative !important; overflow: hidden !important; background: black !important; height: 30vh !important; max-height: 220px !important; min-height: 150px !important;}
        video { object-fit: cover !important; width: 100% !important; height: 100% !important; transform: scaleX(-1); display: block !important; position: absolute !important; top: 0 !important; left: 0 !important; }
        canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: scaleX(-1); z-index: 20; pointer-events: none;}
        
        .loader-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 30; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #FBBF24; font-weight: bold; font-size: 14px; animation: pulse 1.5s infinite; text-align: center; padding: 0 15px;}
        @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.05); } 100% { opacity: 1; transform: scale(1); } }
        .status-box { margin-top: 8px; padding: 6px; border-radius: 6px; font-weight: bold; font-size: 11px; transition: all 0.3s; background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
        .status-success { background: #10B981; color: white; box-shadow: 0 0 10px rgba(16, 185, 129, 0.5); }
        .status-error { background: #EF4444; color: white; box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }
        .scan-line { position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: rgba(16, 185, 129, 0.8); box-shadow: 0 0 10px #10B981, 0 0 20px #10B981; animation: scan 3s infinite linear; z-index: 15; display: none; }
        @keyframes scan { 0% { top: 0; } 50% { top: 100%; } 100% { top: 0; } }
        .manual-container { margin-top: 8px; background-color: #ECFDF5; padding: 8px 10px; border-radius: 8px; border: 2px dashed #34D399; }
        .manual-title { color: #059669; font-weight: bold; margin-bottom: 6px; font-size: 10px; }
        .input-manual { padding: 4px; border-radius: 4px; border: 1px solid #10B981; background-color: #FFFFFF; color: #1F2937; outline: none; transition: 0.3s; font-size: 10px; }
        .input-manual:focus { box-shadow: 0 0 0 3px rgba(16,185,129,0.3); border-color: #059669; }
        .btn-manual { background-color: #10B981; color: white; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 10px; border: none; cursor: pointer; transition: 0.3s; }
        .btn-manual:hover { background-color: #059669; }
        #reader select { background-color: #334155 !important; color: #FFFFFF !important; border: 1px solid #475569 !important; padding: 4px !important; border-radius: 4px !important; outline: none !important; margin-bottom: 2px; width: auto; max-width: 100%; font-size: 10px;}
        #reader button { background-color: #3B82F6 !important; color: white !important; border: none !important; padding: 4px 10px !important; border-radius: 4px !important; font-weight: bold !important; cursor: pointer !important; margin-top: 2px !important; font-size: 10px; }
        #reader a { color: #10B981 !important; text-decoration: none !important; font-weight: bold !important; font-size: 10px;}
        .list-card { background: #FFFFFF; border-radius: 12px; padding: 10px; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.08); border: 2px solid #6EE7B7; transition: all 0.3s; display: flex; flex-direction: column; flex: 1; min-height: 250px; overflow: hidden;}
        .list-header { font-size: 0.8rem; font-weight: bold; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 2px dashed #A7F3D0; text-align: center;}
        .scroll-list { overflow-y: auto; flex: 1; padding-right: 3px; height: 100%;}
        
        .student-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #ECFDF5; transition: 0.3s; gap: 4px;}
        .student-row:last-child { border-bottom: none; }
        .student-info { flex: 1; min-width: 0; } 
        .student-info h4 { font-size: 9.5px; font-weight: 800; color: #065F46; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .student-info p { font-size: 8px; color: #6B7280; margin: 0; font-family: monospace; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .badge-time { background: #D1FAE5; color: #059669; padding: 2px 4px; border-radius: 4px; font-size: 8px; font-weight: bold; border: 1px solid #6EE7B7; flex-shrink: 0;}
        .badge-waiting { background: #FEF2F2; color: #DC2626; padding: 2px 4px; border-radius: 4px; font-size: 8px; font-weight: bold; border: 1px solid #FCA5A5; flex-shrink: 0;}
        .badge-libur { background: #FEE2E2; color: #991B1B; padding: 2px 4px; border-radius: 4px; font-size: 8px; font-weight: bold; border: 1px solid #FCA5A5; flex-shrink: 0;}

        .class-divider { background: #F1F5F9; color: #475569; padding: 6px 12px; font-size: 10px; font-weight: 800; margin: 12px 0 6px 0; border-radius: 6px; border-left: 4px solid #3B82F6; text-transform: uppercase; letter-spacing: 1px; }
        .avatar-bulat { width: 26px; height: 26px; border-radius: 50%; background: #E2E8F0; display: flex; justify-content: center; align-items: center; color: #94A3B8; font-size: 11px; flex-shrink: 0; border: 1px solid rgba(0,0,0,0.05); }

        .scroll-list::-webkit-scrollbar { width: 3px; }
        .scroll-list::-webkit-scrollbar-track { background: transparent; }
        .scroll-list::-webkit-scrollbar-thumb { background: #A7F3D0; border-radius: 10px; }

        @media (min-width: 768px) {
            .scanner-container { height: 85vh; overflow-y: auto; margin-bottom: 0; padding: 15px;}
            #reader__scan_region { height: 35vh !important; }
            .student-info h4 { font-size: 12px; }
            .student-info p { font-size: 10px; }
            .badge-time, .badge-waiting { font-size: 10px; padding: 4px 8px; }
            .list-header { font-size: 1rem; text-align: left; }
            .title-scanner { font-size: 1.3rem; }
            .subtitle-scanner { font-size: 0.8rem; }
            .status-box { font-size: 14px; padding: 12px; }
            .manual-title { font-size: 12px; }
            .input-manual, .btn-manual { font-size: 13px; padding: 8px 12px; }
        }

        body.dark-mode { background-color: #0F172A; color: white; }
        body.dark-mode .btn-kembali { background: #1E293B; color: #9CA3AF; border: 1px solid #475569; box-shadow: none; }
        body.dark-mode .btn-kembali:hover { color: white; background: #334155; }
        body.dark-mode .scanner-container, body.dark-mode .list-card { background: #1E293B; border-color: #334155; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        body.dark-mode .scanner-container::-webkit-scrollbar-thumb { background: #475569; }
        body.dark-mode .title-scanner { color: #4ADE80; }
        body.dark-mode .subtitle-scanner { color: #9CA3AF; }
        body.dark-mode .camera-wrapper { border-color: #3B82F6; box-shadow: 0 0 15px rgba(59, 130, 246, 0.3); }
        body.dark-mode .manual-container { background-color: #0F172A; border-color: #475569; }
        body.dark-mode .manual-title { color: #FBBF24; }
        body.dark-mode .input-manual { background-color: #1E293B; border: 1px solid #475569; color: white; }
        body.dark-mode .input-manual:focus { box-shadow: 0 0 0 3px rgba(59,130,246,0.3); border-color: #3B82F6; }
        body.dark-mode .btn-manual { background-color: #3B82F6; }
        body.dark-mode .btn-manual:hover { background-color: #2563EB; }
        body.dark-mode .status-box { background: #334155; color: white; border: none; }
        body.dark-mode .status-success { background: #059669; }
        body.dark-mode .status-error { background: #DC2626; }
        body.dark-mode .list-header { border-bottom-color: #475569; }
        body.dark-mode .student-row { border-bottom-color: #0F172A; }
        body.dark-mode .student-info h4 { color: #60A5FA; }
        body.dark-mode .badge-time { background: rgba(16, 185, 129, 0.2); color: #34D399; border-color: #059669; }
        body.dark-mode .badge-waiting { background: rgba(239, 68, 68, 0.2); color: #F87171; border-color: #DC2626; }
        body.dark-mode .scroll-list::-webkit-scrollbar-thumb { background: #475569; }
        body.dark-mode .badge-libur { background: rgba(220, 38, 38, 0.2); color: #FCA5A5; border-color: #DC2626; }
        
        body.dark-mode .class-divider { background: #1E293B; color: #94A3B8; border-left-color: #3B82F6; }
        body.dark-mode .avatar-bulat { background: #334155; color: #9CA3AF; border-color: rgba(255,255,255,0.05); }

        @keyframes flashSync { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        .sync-active { animation: flashSync 0.8s ease-in-out; }
    </style>
</head>
<body class="flex flex-col h-screen">

    <!-- INI BRANKAS RAHASIA UNTUK NYIMPAN DATA WAJAH -->
    <script id="brankas-data-wajah" type="application/json">
        {!! $json_wajah_murni !!}
    </script>

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.body.classList.add('dark-mode');
        }
    </script>

    <!-- HEADER -->
    <div class="w-full max-w-[1400px] mx-auto flex justify-between items-center px-4 mt-2 mb-2 flex-shrink-0">
        <a href="/dashboard" class="btn-kembali">⬅️ Kembali</a>
        <div class="flex gap-2">
            <span id="liveSyncIndicator" class="text-[10px] font-bold px-2 py-1 rounded-full text-emerald-600 bg-emerald-100 border border-emerald-300 shadow-sm flex items-center gap-1" class="dark:bg-emerald-900/50 dark:text-emerald-400 dark:border-emerald-700">
                <i class="fas fa-satellite-dish animate-pulse"></i> LIVE
            </span>
            <button id="fullscreenToggle" class="btn-kembali" style="gap: 4px;"><span id="fsIcon">🔲</span> <span id="fsText">Penuh</span></button>
            <button id="themeToggle" class="btn-kembali" style="gap: 4px;"><span id="themeIcon">☀️</span> <span id="themeText">Tema</span></button>
        </div>
    </div>

    <!-- MAIN AREA -->
    <div class="w-full max-w-[1400px] mx-auto px-4 flex flex-col md:flex-row gap-2 pb-3 items-stretch flex-1 overflow-hidden">
        
        <!-- KOLOM KIRI: SCANNER -->
        <div class="w-full md:w-5/12 lg:w-4/12 scanner-container flex flex-col">
            <h2 class="title-scanner">⚡ HYBRID SCANNER</h2>
            <p class="subtitle-scanner">Deteksi QR Code & Wajah Otomatis.</p>
            
            <div class="mb-3 w-full border rounded-lg py-1.5 px-3 flex items-center justify-between shadow-sm transition-colors" 
                 id="boxInfoJam" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);"
                 class="dark:bg-blue-900/20 dark:border-blue-700/50">
                <span class="text-[10px] md:text-xs font-bold uppercase tracking-wider" id="teksInfoJam" style="color: #2563EB;" class="dark:text-blue-400">
                    <i class="fas fa-business-time mr-1"></i> Jam Pulang:
                </span>
                <span id="displayJamPulang" class="text-[10px] md:text-xs font-extrabold px-2 py-0.5 rounded border" 
                      style="background: #DBEAFE; color: #1E3A8A; border-color: #BFDBFE;"
                      class="dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700">
                    Memuat Jadwal...
                </span>
            </div>
            
            <div class="camera-wrapper flex-1" id="videoBox">
                <div id="reader"></div> 
                <canvas id="overlay"></canvas>
                <div id="scanLine" class="scan-line"></div>
                <div id="loadingText" class="loader-overlay">⏳ Menyiapkan Sistem...</div>
            </div>

            <div id="statusBox" class="status-box">
                Menunggu QR Code / Wajah... 👁️
            </div>

            <div class="manual-container">
                <h4 class="manual-title">⌨️ Input Manual / Override Status</h4>
                <form id="formManual" style="display: flex; gap: 4px; justify-content: center; flex-wrap: wrap;">
                    <input type="text" id="inputNisManual" class="input-manual" style="flex: 1; min-width: 80px;" placeholder="NIS..." autocomplete="off">
                    
                    <select id="inputStatusManual" class="input-manual" style="flex: 1; min-width: 80px; cursor: pointer;">
                        <option value="Otomatis">Otomatis</option>
                        <option value="Hadir Pagi">Pagi</option>
                        <option value="Pulang">Pulang</option>
                    </select>

                    <button type="submit" class="btn-manual">Absen</button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: DAFTAR ABSEN -->
        <div class="w-full md:w-7/12 lg:w-8/12 flex flex-col gap-2">
            
            <!-- PANEL KENDALI MODE GURU (PINTAR!) -->
            <div class="w-full bg-white rounded-xl p-3 border-2 border-emerald-300 shadow flex justify-between items-center" class="dark:bg-slate-800 dark:border-slate-600">
                <span class="text-xs md:text-sm font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                    <i class="fas fa-filter text-lg"></i> MODE SCANNER:
                </span>
                
                @if($is_admin || $is_mesin)
                    <select id="modeFilterKelas" onchange="terapkanFilterAdmin()" class="text-xs font-bold bg-emerald-50 border border-emerald-400 text-emerald-800 rounded-lg px-3 py-1.5 outline-none cursor-pointer shadow-inner dark:bg-slate-700 dark:text-white dark:border-slate-500 w-1/2 md:w-2/3">
                        <option value="ALL">🌍 Gerbang Utama (Semua Kelas)</option>
                        @foreach($semua_kelas as $k)
                            <option value="{{ strtoupper($k) }}">🏫 Kelas {{ strtoupper($k) }}</option>
                        @endforeach
                    </select>
                @else
                    <select id="modeFilterKelas" onchange="gantiModeGuru()" class="text-xs font-bold bg-emerald-50 border border-emerald-400 text-emerald-800 rounded-lg px-3 py-1.5 outline-none cursor-pointer shadow-inner dark:bg-slate-700 dark:text-white dark:border-slate-500 w-1/2 md:w-2/3">
                        <option value="ALL">🌅 Jam Pagi Gerbang (Semua Kelas)</option>
                        <option value="KELASKU">🏫 Jam Masuk Kelas (Hanya Kelasku)</option>
                    </select>
                @endif
            </div>

            <!-- KOTAK LIST DATA -->
            <div class="grid grid-cols-2 gap-2 flex-1" id="areaDaftarSiswa" style="min-height: 0;">
                
                <!-- KOLOM BELUM HADIR -->
                <div class="list-card">
                    <h3 class="list-header" style="color: #DC2626;"><i class="fas fa-user-clock"></i> Belum</h3>
                    <div class="scroll-list" id="list-belum-absen">
                        @forelse($grouped_belum as $kelas => $murids)
                            <div class="class-group-belum" data-kelas="{{ $kelas }}">
                                <div class="class-divider">
                                    <i class="fas fa-users mr-1"></i> KELAS {{ $kelas }}
                                </div>
                                @foreach($murids as $m)
                                    @php 
                                        $id_guru_login = (string) (auth()->id() ?? '0');
                                        $nama_guru_login = auth()->check() ? strtolower(trim(auth()->user()->name)) : 'xxx_no_name_xxx';
                                        $milik_saya = 'false';
                                        
                                        if ($m) {
                                            foreach($m->getAttributes() as $col => $val) {
                                                $val_str = strtolower(trim((string)$val));
                                                if (in_array(strtolower($col), ['id', 'nis', 'status', 'kelas', 'created_at', 'updated_at'])) continue;
                                                
                                                if($val_str === $id_guru_login || $val_str === $nama_guru_login) {
                                                    $milik_saya = 'true';
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp

                                    <div class="student-row row-belum-absen" id="row-belum-{{ $m->nis }}" data-kelas="{{ $kelas }}" data-milik-saya="{{ $milik_saya }}">
                                        <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                            <div class="avatar-bulat">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="student-info">
                                                <h4>{{ strtoupper($m->nama_lengkap) }}</h4>
                                                <p>{{ $m->nis }}</p>
                                            </div>
                                        </div>
                                        @if($is_tanggal_merah || $hari_angka >= 6)
                                            <span class="badge-libur">LIBUR</span>
                                        @else
                                            <span class="badge-waiting">BELUM</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="text-center text-gray-500 mt-4" id="empty-belum-absen">
                                <i class="fas fa-check-circle text-xl text-green-500 mb-1"></i>
                                <p class="text-[10px] font-bold">Semua hadir!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- KOLOM SUDAH HADIR -->
                <div class="list-card">
                    <h3 class="list-header" style="color: #059669;"><i class="fas fa-clipboard-check"></i> Masuk</h3>
                    <div class="scroll-list" id="list-sudah-absen">
                        @forelse($absen_masuk as $a)
                            @php 
                                $kls_masuk = empty($a->kelas) ? 'TANPA KELAS' : strtoupper($a->kelas); 
                                
                                $milik_saya_masuk = 'false';
                                if ($a) {
                                    foreach($a->getAttributes() as $col => $val) {
                                        $val_str = strtolower(trim((string)$val));
                                        if (in_array(strtolower($col), ['id', 'nis', 'status', 'kelas', 'waktu', 'tanggal', 'created_at', 'updated_at'])) continue;
                                        
                                        if($val_str === $id_guru_login || $val_str === $nama_guru_login) {
                                            $milik_saya_masuk = 'true';
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <div class="student-row row-sudah-absen" data-kelas="{{ $kls_masuk }}" data-milik-saya="{{ $milik_saya_masuk }}">
                                <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                    <div class="avatar-bulat" style="background: #D1FAE5; color: #059669; border-color: #A7F3D0;" class="dark:bg-emerald-900/50 dark:text-emerald-400">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="student-info">
                                        <h4>{{ strtoupper($a->nama_lengkap) }}</h4>
                                        <p>{{ $a->nis }} • {{ $a->kelas ?? 'KOSONG' }} • {{ strtoupper($a->status) }}</p>
                                    </div>
                                </div>
                                <span class="badge-time">{{ substr($a->waktu, 0, 5) }}</span>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 mt-4" id="empty-sudah-absen">
                                <i class="fas fa-hourglass-half text-xl text-yellow-500 mb-1"></i>
                                <p class="text-[10px] font-bold">Belum ada.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- SCRIPT LOGIKA -->
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const hari_angka_php = {{ $hari_angka }};
            const is_tanggal_merah = {{ $is_tanggal_merah ? 'true' : 'false' }};
            
            // =========================================================
            // SCRIPT FILTER KELAS
            // =========================================================
            window.terapkanFilterKelasUI = function(targetMode) {
                
                if(targetMode !== 'KELASKU') {
                    document.querySelectorAll('.class-divider').forEach(div => div.style.display = 'block');

                    document.querySelectorAll('.class-group-belum').forEach(group => {
                        if (targetMode === 'ALL' || group.getAttribute('data-kelas') === targetMode) {
                            group.style.display = 'block';
                            group.querySelectorAll('.row-belum-absen').forEach(row => row.style.display = 'flex');
                        } else {
                            group.style.display = 'none';
                        }
                    });

                    document.querySelectorAll('.row-sudah-absen').forEach(row => {
                        if (targetMode === 'ALL' || row.getAttribute('data-kelas') === targetMode) {
                            row.style.display = 'flex';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                } 
                else {
                    document.querySelectorAll('.class-divider').forEach(div => div.style.display = 'none');
                    document.querySelectorAll('.class-group-belum').forEach(group => {
                        let adaAnakMilikSaya = false;
                        
                        group.querySelectorAll('.row-belum-absen').forEach(row => {
                            if (row.getAttribute('data-milik-saya') === 'true') {
                                row.style.display = 'flex';
                                adaAnakMilikSaya = true;
                            } else {
                                row.style.display = 'none';
                            }
                        });
                        
                        if(adaAnakMilikSaya) {
                            group.style.display = 'block';
                            let divider = group.querySelector('.class-divider');
                            if(divider) divider.style.display = 'block';
                        } else {
                            group.style.display = 'none';
                        }
                    });

                    document.querySelectorAll('.row-sudah-absen').forEach(row => {
                        if (row.getAttribute('data-milik-saya') === 'true') {
                            row.style.display = 'flex';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
            };

            window.terapkanFilterAdmin = function() {
                let val = document.getElementById('modeFilterKelas').value;
                localStorage.setItem('modeScannerKelasAdmin', val);
                terapkanFilterKelasUI(val);
            };

            window.gantiModeGuru = function() {
                let mode = document.getElementById('modeFilterKelas').value;
                localStorage.setItem('modeScannerKelasGuru', mode);
                terapkanFilterKelasUI(mode); 
            };

            // LOAD PENGATURAN TERAKHIR SAAT HALAMAN DIBUKA
            @if($is_admin || $is_mesin)
                let savedModeAdmin = localStorage.getItem('modeScannerKelasAdmin') || 'ALL';
                if(document.querySelector(`#modeFilterKelas option[value="${savedModeAdmin}"]`)) {
                    document.getElementById('modeFilterKelas').value = savedModeAdmin;
                }
                terapkanFilterKelasUI(savedModeAdmin);
            @else
                let savedModeGuru = localStorage.getItem('modeScannerKelasGuru') || 'ALL';
                if(savedModeGuru === 'ALL') {
                    document.getElementById('modeFilterKelas').value = 'ALL';
                    terapkanFilterKelasUI('ALL');
                } else {
                    document.getElementById('modeFilterKelas').value = 'KELASKU';
                    terapkanFilterKelasUI('KELASKU');
                }
            @endif


            let jadwalMingguan = JSON.parse(localStorage.getItem('jadwalSekolah'));
            let tampilanJadwal = document.getElementById('displayJamPulang');
            let boxJadwal = document.getElementById('boxInfoJam');
            let teksJadwal = document.getElementById('teksInfoJam');
            let savedOverride = localStorage.getItem('settingJamPulang');

            if(tampilanJadwal) {
                if(savedOverride && !savedOverride.includes('Otomatis') && !savedOverride.includes('Normal')) {
                     tampilanJadwal.innerText = savedOverride;
                     if(savedOverride.includes('DITUTUP')) {
                         boxJadwal.style.background = '#FEE2E2'; boxJadwal.style.borderColor = '#FCA5A5';
                         teksJadwal.style.color = '#B91C1C';
                         tampilanJadwal.style.background = '#FECACA'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                     } else if (savedOverride.includes('DIBUKA')) {
                         boxJadwal.style.background = '#D1FAE5'; boxJadwal.style.borderColor = '#6EE7B7';
                         teksJadwal.style.color = '#047857';
                         tampilanJadwal.style.background = '#A7F3D0'; tampilanJadwal.style.color = '#065F46'; tampilanJadwal.style.borderColor = '#34D399';
                     } else {
                         boxJadwal.style.background = '#FEF3C7'; boxJadwal.style.borderColor = '#FCD34D';
                         teksJadwal.style.color = '#92400E';
                         tampilanJadwal.style.background = '#FDE68A'; tampilanJadwal.style.color = '#78350F'; tampilanJadwal.style.borderColor = '#F59E0B';
                     }
                     
                     document.querySelectorAll('.badge-libur').forEach(el => {
                         el.className = 'badge-waiting';
                         el.innerText = 'BELUM';
                     });

                } else if(is_tanggal_merah) {
                    tampilanJadwal.innerText = "TANGGAL MERAH (LIBUR NASIONAL)";
                    boxJadwal.style.background = '#FEE2E2'; boxJadwal.style.borderColor = '#FCA5A5';
                    teksJadwal.style.color = '#B91C1C';
                    tampilanJadwal.style.background = '#FECACA'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                } else {
                    if(jadwalMingguan && jadwalMingguan[hari_angka_php]) {
                        let jadwalHariIni = jadwalMingguan[hari_angka_php];
                        if(jadwalHariIni.status === 'libur') {
                            tampilanJadwal.innerText = "HARI LIBUR";
                            boxJadwal.style.background = '#FEE2E2'; boxJadwal.style.borderColor = '#FCA5A5'; teksJadwal.style.color = '#B91C1C';
                            tampilanJadwal.style.background = '#FECACA'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                        } else {
                            tampilanJadwal.innerText = `Sesuai Jadwal (${jadwalHariIni.waktu} WIB)`;
                        }
                    } else {
                        let batas = (hari_angka_php == 5) ? '11:00' : '13:30';
                        if(hari_angka_php >= 6) {
                            tampilanJadwal.innerText = "HARI LIBUR";
                            boxJadwal.style.background = '#FEE2E2'; boxJadwal.style.borderColor = '#FCA5A5'; teksJadwal.style.color = '#B91C1C';
                            tampilanJadwal.style.background = '#FECACA'; tampilanJadwal.style.color = '#991B1B'; tampilanJadwal.style.borderColor = '#F87171';
                        } else {
                            tampilanJadwal.innerText = `Sesuai Jadwal (${batas} WIB)`;
                        }
                    }
                }
            }

            let isScanning = false; 
            let faceMatcher = null; 
            let html5QrcodeScanner = null;
            let videoElement = null; 
            let canvas = document.getElementById('overlay');

            try {
                const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                
                loadingText.innerText = "🧠 Memuat AI...";
                
                const dbWajahStr = document.getElementById('brankas-data-wajah').textContent;
                const databaseWajah = JSON.parse(dbWajahStr || "[]");

                if(databaseWajah && databaseWajah.length > 0) {
                    const labeledDescriptors = [];
                    for (const murid of databaseWajah) {
                        try {
                            const faceDataArray = JSON.parse(murid.face_data);
                            const float32Array = new Float32Array(faceDataArray);
                            const label = `${murid.nis}_${murid.nama_lengkap}`;
                            labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(label, [float32Array]));
                        } catch (e) {}
                    }
                    if(labeledDescriptors.length > 0) {
                        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.55); 
                    }
                }
                
                loadingText.innerText = "📷 Membuka Kamera...";
                mulaiScannerQR(); 

            } catch (err) {
                loadingText.innerText = "❌ Gagal memuat AI atau Kamera!";
                mulaiScannerQR();
            }

            async function mulaiScannerQR() {
                try {
                    html5QrcodeScanner = new Html5Qrcode("reader");
                    const config = { fps: 10, qrbox: { width: 150, height: 150 } };
                    
                    // Langsung paksa minta izin dan buka kamera menghadap depan (user/environment)
                    await html5QrcodeScanner.start(
                        { facingMode: "user" }, 
                        config, 
                        onQRSuccess,
                        (errorMessage) => { /* Abaikan error frame kecil */ }
                    );

                    loadingText.style.display = 'none'; 
                    scanLine.style.display = 'block'; 
                    mulaiDeteksiWajah();

                } catch(e) {
                    // Fallback jika kamera depan gagal, coba kamera belakang/default
                    try {
                        await html5QrcodeScanner.start(
                            { facingMode: "environment" }, 
                            { fps: 10, qrbox: { width: 150, height: 150 } }, 
                            onQRSuccess
                        );
                        loadingText.style.display = 'none'; 
                        scanLine.style.display = 'block'; 
                        mulaiDeteksiWajah();
                    } catch(err2) {
                        loadingText.innerText = "❌ Kamera Gagal Diakses! Pastikan izin HTTPS / Browser aktif.";
                    }
                }
            }

                    setTimeout(() => {
                        if(!document.querySelector('#reader video')) {
                            loadingText.innerHTML = "👇 TAP tombol biru di bawah 👇<br><span style='font-size:9px; color:white; font-weight:normal; margin-top:4px;'>(Request Camera Permissions)</span>";
                            loadingText.style.background = "transparent"; 
                        }
                    }, 1500);

                    let checkVideoExist = setInterval(() => {
                        videoElement = document.querySelector('#reader video');
                        if(videoElement) {
                            clearInterval(checkVideoExist);
                            loadingText.style.display = 'none'; 
                            scanLine.style.display = 'block'; 
                            
                            mulaiDeteksiWajah(); 
                        }
                    }, 500);
                } catch(e) {
                    loadingText.innerText = "❌ Kamera Gagal Diakses!";
                }
            }

            function onQRSuccess(decodedText, decodedResult) {
                if (isScanning) return; 
                isScanning = true; 
                html5QrcodeScanner.pause();
                
                let statusTerpilih = document.getElementById('inputStatusManual').value;
                
                updateStatusBox(`QR Code Dikenali! Mengirim... ⏳`, "normal");
                prosesAbsen(decodedText, "QR Code", 'qr', statusTerpilih);
            }

            function mulaiDeteksiWajah() {
                function gambarLabelAntiMirror(box, teks, warna) {
                    const ctx = canvas.getContext('2d');
                    const drawBox = new faceapi.draw.DrawBox(box, { label: '', lineWidth: 3, boxColor: warna });
                    drawBox.draw(canvas);

                    ctx.save();
                    ctx.scale(-1, 1); 
                    ctx.font = 'bold 12px Arial';
                    
                    const textWidth = ctx.measureText(teks).width;
                    const xPosisi = -(box.x + box.width); 
                    const yPosisi = box.y;
                    
                    ctx.fillStyle = warna;
                    ctx.fillRect(xPosisi, yPosisi - 20, textWidth + 10, 20);
                    
                    ctx.fillStyle = 'white';
                    ctx.fillText(teks, xPosisi + 5, yPosisi - 5);
                    
                    ctx.restore(); 
                }

                setInterval(async () => {
                    videoElement = document.querySelector('#reader video');
                    if (isScanning || !videoElement || videoElement.paused || videoElement.readyState !== 4) return;

                    // PENJAGA CANVAS: Kalau kotak QR merusak canvas, kita masukkan lagi ke dalam video region
                    const scanRegion = document.getElementById('reader__scan_region');
                    if (scanRegion && !scanRegion.contains(canvas)) {
                        scanRegion.appendChild(canvas);
                    }

                    const displaySize = { width: videoElement.clientWidth, height: videoElement.clientHeight };
                    if(displaySize.width === 0) return;
                    faceapi.matchDimensions(canvas, displaySize);

                    try {
                        // KUNCI UTAMA: Kodingan AI disamakan persis 1 banding 1 dengan halaman Rekam Wajah (Resolusi 416 Bawaan FaceAPI)
                        const detections = await faceapi.detectSingleFace(videoElement, new faceapi.TinyFaceDetectorOptions())
                            .withFaceLandmarks().withFaceDescriptor();

                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                        if (detections) {
                            const resizedDetections = faceapi.resizeResults(detections, displaySize);
                            const box = resizedDetections.detection.box;
                            
                            if (faceMatcher) {
                                const bestMatch = faceMatcher.findBestMatch(detections.descriptor);
                                
                                if (bestMatch.label !== 'unknown' && bestMatch.distance < 0.55) {
                                    isScanning = true; 
                                    html5QrcodeScanner.pause(); 
                                    
                                    const nisMurid = bestMatch.label.split('_')[0];
                                    const namaMurid = bestMatch.label.split('_')[1];

                                    let statusTerpilih = document.getElementById('inputStatusManual').value;

                                    gambarLabelAntiMirror(box, `${namaMurid}`, '#10B981');
                                    updateStatusBox(`Wajah ${namaMurid} Dikenali! Mengirim... ⏳`, "normal");
                                    prosesAbsen(nisMurid, namaMurid, 'wajah', statusTerpilih);
                                } else {
                                    gambarLabelAntiMirror(box, 'Tidak Dikenal', '#EF4444');
                                    updateStatusBox("Wajah tidak terdaftar di sistem.", "error");
                                }
                            } else {
                                gambarLabelAntiMirror(box, 'Belum Ada Data', '#F59E0B');
                                updateStatusBox("Database Wajah Masih Kosong!", "error");
                            }
                        } else {
                            updateStatusBox("Menunggu QR Code / Wajah... 👁️", "normal");
                        }
                    } catch (error) {}
                }, 600);
            }

            function prosesAbsen(nis, nama, sumber, statusManual = 'Otomatis') {
                scanLine.style.animationPlayState = 'paused';

                fetch('/scan/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ nis: nis, sumber: sumber, status_manual: statusManual })
                })
                .then(response => response.json())
                .then(data => {
                    const isDark = document.body.classList.contains('dark-mode');
                    
                    if (data.success) {
                        playSuksesSound(); 
                        bacakanPesan(`Berhasil! ${data.nama} absen ${data.status}`);
                        
                        document.getElementById('inputStatusManual').value = 'Otomatis';
                        
                        manipulasiDaftarAbsenLive(nis, data.nama, data.status, data.waktu);

                        Swal.fire({
                            title: 'BERHASIL!',
                            text: `Absen ${data.status} untuk ${data.nama}`, 
                            icon: 'success',
                            timer: 3000, 
                            showConfirmButton: false, 
                            timerProgressBar: true,
                            background: isDark ? '#1E293B' : '#FFFFFF', 
                            color: isDark ? '#F8FAFC' : '#065F46', 
                            iconColor: '#10B981', 
                            customClass: {
                                popup: 'border-2 border-emerald-500 rounded-2xl shadow-[0_0_30px_rgba(16,185,129,0.4)]',
                                title: 'text-emerald-500 font-extrabold text-xl tracking-wide',
                            }
                        }).then(() => { resetScanner(); });
                    } else {
                        playErrorSound();
                        bacakanPesan(data.pesan);
                        
                        let warnaBorder = data.tipe_error === 'dobel' ? 'border-yellow-500' : 'border-red-500';
                        let warnaGlow = data.tipe_error === 'dobel' ? 'rgba(234,179,8,0.4)' : 'rgba(239,68,68,0.4)';
                        let warnaTeksTitle = data.tipe_error === 'dobel' ? 'text-yellow-500' : 'text-red-500';

                        Swal.fire({
                            title: data.tipe_error === 'dobel' ? 'INFO' : 'GAGAL',
                            text: data.pesan,
                            icon: data.tipe_error === 'dobel' ? 'warning' : 'error',
                            timer: 3500, 
                            showConfirmButton: false,
                            timerProgressBar: true,
                            background: isDark ? '#1E293B' : '#FFFFFF',
                            color: isDark ? '#F8FAFC' : '#1F2937',
                            customClass: {
                                popup: `border-2 ${warnaBorder} rounded-2xl shadow-[0_0_30px_${warnaGlow}]`,
                                title: `${warnaTeksTitle} font-extrabold text-xl tracking-wide`,
                            }
                        }).then(() => { resetScanner(); });
                    }
                })
                .catch(error => {
                    bacakanPesan("Maaf, koneksi internet terputus.");
                    resetScanner();
                });
            }

            function manipulasiDaftarAbsenLive(nis, nama, status, waktuFull) {
                let kelasSiswa = 'TANPA KELAS';
                let milikSiswa = 'false';

                const rowBelum = document.getElementById('row-belum-' + nis);
                if(rowBelum) {
                    kelasSiswa = rowBelum.getAttribute('data-kelas') || 'TANPA KELAS';
                    milikSiswa = rowBelum.getAttribute('data-milik-saya') || 'false';
                    rowBelum.remove();
                }

                const emptySudah = document.getElementById('empty-sudah-absen');
                if(emptySudah) emptySudah.remove();

                const listSudah = document.getElementById('list-sudah-absen');
                const newRow = document.createElement('div');
                newRow.className = 'student-row row-sudah-absen';
                newRow.setAttribute('data-kelas', kelasSiswa); 
                newRow.setAttribute('data-milik-saya', milikSiswa); 
                
                const jamAbsen = waktuFull.split(' - ')[1] || 'Barusan';

                newRow.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <div class="avatar-bulat" style="background: #D1FAE5; color: #059669; border-color: #A7F3D0;" class="dark:bg-emerald-900/50 dark:text-emerald-400">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="student-info">
                            <h4>${nama.toUpperCase()}</h4>
                            <p>${nis} • ${kelasSiswa} • ${status.toUpperCase()}</p>
                        </div>
                    </div>
                    <span class="badge-time">${jamAbsen}</span>
                `;
                listSudah.insertBefore(newRow, listSudah.firstChild);
                
                @if($is_admin || $is_mesin)
                    terapkanFilterKelasUI(document.getElementById('modeFilterKelas').value);
                @else
                    terapkanFilterKelasUI(document.getElementById('modeFilterKelas').value);
                @endif
            }

            function resetScanner() {
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                scanLine.style.animationPlayState = 'running';
                updateStatusBox("Menunggu QR Code / Wajah... 👁️", "normal");
                html5QrcodeScanner.resume();
                setTimeout(() => { isScanning = false; }, 1000);
            }

            function updateStatusBox(text, type) {
                statusBox.innerText = text;
                statusBox.className = 'status-box'; 
                if(type === 'success') statusBox.classList.add('status-success');
                if(type === 'error') statusBox.classList.add('status-error');
            }

            function playSuksesSound() {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2013/2013-preview.mp3');
                audio.play().catch(e => {});
            }
            function playErrorSound() {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3');
                audio.play().catch(e => {});
            }

            const formManual = document.getElementById('formManual');
            const inputNisManual = document.getElementById('inputNisManual');
            const inputStatusManual = document.getElementById('inputStatusManual');

            if (formManual) {
                formManual.addEventListener('submit', function(e) {
                    e.preventDefault(); 
                    const nisManual = inputNisManual.value.trim();
                    const statusManual = inputStatusManual.value; 

                    if (nisManual) {
                        updateStatusBox(`Memproses Input Manual... ⏳`, "normal");
                        prosesAbsen(nisManual, "Manual Input", 'manual', statusManual);
                        
                        document.getElementById('inputStatusManual').value = 'Otomatis';
                        inputNisManual.value = '';
                    }
                });
            }

            async function jalankanSinkronisasiGaib() {
                try {
                    const respons = await fetch(window.location.href);
                    const htmlBaru = await respons.text();
                    
                    const parser = new DOMParser();
                    const dokumenBaru = parser.parseFromString(htmlBaru, 'text/html');

                    const daftarBelumBaru = dokumenBaru.getElementById('list-belum-absen');
                    const daftarSudahBaru = dokumenBaru.getElementById('list-sudah-absen');

                    if(daftarBelumBaru && daftarSudahBaru) {
                        document.getElementById('list-belum-absen').innerHTML = daftarBelumBaru.innerHTML;
                        document.getElementById('list-sudah-absen').innerHTML = daftarSudahBaru.innerHTML;
                        
                        @if($is_admin || $is_mesin)
                            terapkanFilterKelasUI(document.getElementById('modeFilterKelas').value);
                        @else
                            terapkanFilterKelasUI(document.getElementById('modeFilterKelas').value);
                        @endif
                    }
                    
                    const indikatorLive = document.getElementById('liveSyncIndicator');
                    indikatorLive.classList.add('sync-active');
                    setTimeout(() => { indikatorLive.classList.remove('sync-active'); }, 800);
                    
                } catch (error) {}
            }
            
            setInterval(jalankanSinkronisasiGaib, 4000);
        });
        
        function bacakanPesan(teks) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel(); 
                const suara = new SpeechSynthesisUtterance(teks);
                suara.lang = 'id-ID'; suara.rate = 0.9; suara.pitch = 1;      
                window.speechSynthesis.speak(suara);
            }
        }

        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');

        function updateToggleButton() {
            if (document.body.classList.contains('dark-mode')) {
                themeIcon.innerText = '🌙';
                themeText.innerText = 'Gelap';
            } else {
                themeIcon.innerText = '☀️';
                themeText.innerText = 'Terang';
            }
        }
        updateToggleButton();
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            updateToggleButton();
        });

        const fullscreenToggle = document.getElementById('fullscreenToggle');
        const fsIcon = document.getElementById('fsIcon');
        const fsText = document.getElementById('fsText');

        fullscreenToggle.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch((err) => {});
                fsIcon.innerText = '✖️';
                fsText.innerText = 'Tutup';
            } else {
                document.exitFullscreen();
                fsIcon.innerText = '🔲';
                fsText.innerText = 'Penuh';
            }
        });

        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                fsIcon.innerText = '🔲';
                fsText.innerText = 'Penuh';
            }
        });
    </script>
</body>
</html>