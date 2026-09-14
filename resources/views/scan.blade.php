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
        
        .loader-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 30; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #FBBF24; font-weight: bold; font-size: 14px; text-align: center; padding: 0 15px;}
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

    <!-- BRANKAS DATA WAJAH -->
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
            <span id="liveSyncIndicator" class="text-[10px] font-bold px-2 py-1 rounded-full text-emerald-600 bg-emerald-100 border border-emerald-300 shadow-sm flex items-center gap-1">
                LIVE
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
                 id="boxInfoJam" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                <span class="text-[10px] md:text-xs font-bold uppercase tracking-wider" id="teksInfoJam" style="color: #2563EB;">
                    Jam Pulang:
                </span>
                <span id="displayJamPulang" class="text-[10px] md:text-xs font-extrabold px-2 py-0.5 rounded border" 
                      style="background: #DBEAFE; color: #1E3A8A; border-color: #BFDBFE;">
                    Memuat Jadwal...
                </span>
            </div>
            
            <div class="camera-wrapper flex-1" id="videoBox">
                <div id="reader"></div> 
                <canvas id="overlay"></canvas>
                <div id="scanLine" class="scan-line"></div>
                <div id="loadingText" class="loader-overlay">📷 Membuka Kamera Secara Instan...</div>
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
            <div class="w-full bg-white rounded-xl p-3 border-2 border-emerald-300 shadow flex justify-between items-center">
                <span class="text-xs md:text-sm font-bold text-emerald-700 flex items-center gap-2">
                    MODE SCANNER:
                </span>
                @if($is_admin || $is_mesin)
                    <select id="modeFilterKelas" onchange="terapkanFilterAdmin()" class="text-xs font-bold bg-emerald-50 border border-emerald-400 text-emerald-800 rounded-lg px-3 py-1.5 outline-none cursor-pointer w-1/2 md:w-2/3">
                        <option value="ALL">🌍 Gerbang Utama (Semua Kelas)</option>
                        @foreach($semua_kelas as $k)
                            <option value="{{ strtoupper($k) }}">🏫 Kelas {{ strtoupper($k) }}</option>
                        @endforeach
                    </select>
                @else
                    <select id="modeFilterKelas" onchange="gantiModeGuru()" class="text-xs font-bold bg-emerald-50 border border-emerald-400 text-emerald-800 rounded-lg px-3 py-1.5 outline-none cursor-pointer w-1/2 md:w-2/3">
                        <option value="ALL">🌅 Jam Pagi Gerbang (Semua Kelas)</option>
                        <option value="KELASKU">🏫 Jam Masuk Kelas (Hanya Kelasku)</option>
                    </select>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-2 flex-1" id="areaDaftarSiswa" style="min-height: 0;">
                <!-- KOLOM BELUM HADIR -->
                <div class="list-card">
                    <h3 class="list-header" style="color: #DC2626;">Belum</h3>
                    <div class="scroll-list" id="list-belum-absen">
                        @forelse($grouped_belum as $kelas => $murids)
                            <div class="class-group-belum" data-kelas="{{ $kelas }}">
                                <div class="class-divider">KELAS {{ $kelas }}</div>
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
                                            <div class="avatar-bulat">👤</div>
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
                                <p class="text-[10px] font-bold">Semua hadir!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- KOLOM SUDAH HADIR -->
                <div class="list-card">
                    <h3 class="list-header" style="color: #059669;">Masuk</h3>
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
                                    <div class="avatar-bulat" style="background: #D1FAE5; color: #059669; border-color: #A7F3D0;">✓</div>
                                    <div class="student-info">
                                        <h4>{{ strtoupper($a->nama_lengkap) }}</h4>
                                        <p>{{ $a->nis }} • {{ $a->kelas ?? 'KOSONG' }} • {{ strtoupper($a->status) }}</p>
                                    </div>
                                </div>
                                <span class="badge-time">{{ substr($a->waktu, 0, 5) }}</span>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 mt-4" id="empty-sudah-absen">
                                <p class="text-[10px] font-bold">Belum ada.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT LOGIKA UTAMA -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const loadingText = document.getElementById('loadingText');
            const scanLine = document.getElementById('scanLine');
            const statusBox = document.getElementById('statusBox');
            let isScanning = false;
            let html5QrcodeScanner = null;

            // LANGSUNG BUKA KAMERA TANPA MENUNGGU AI
            try {
                html5QrcodeScanner = new Html5Qrcode("reader");
                
                // Cari kamera yang tersedia di perangkat (HP / Laptop)
                const cameras = await Html5Qrcode.getCameras();
                let selectedCameraId = { facingMode: "user" }; // Default depan

                if (cameras && cameras.length > 0) {
                    // Coba cari kamera belakang (environment / back)
                    let backCamera = cameras.find(cam => 
                        cam.label.toLowerCase().find('back') || 
                        cam.label.toLowerCase().find('rear') || 
                        cam.label.toLowerCase().find('belakang')
                    );
                    
                    // Jika ada kamera belakang, jadikan pilihan prioritas atau sediakan opsi
                    // Kita gunakan kamera terakhir (biasanya kamera belakang di HP) atau biarkan user pilih
                    selectedCameraId = cameras.length > 1 ? cameras[cameras.length - 1].id : cameras[0].id;
                }

                await html5QrcodeScanner.start(
                    selectedCameraId,
                    { fps: 10, qrbox: { width: 150, height: 150 } },
                    onQRSuccess
                );
                
                loadingText.style.display = 'none';
                scanLine.style.display = 'block';
            } catch(e) {
                // Fallback aman jika gagal
                try {
                    await html5QrcodeScanner.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 150, height: 150 } },
                        onQRSuccess
                    );
                    loadingText.style.display = 'none';
                    scanLine.style.display = 'block';
                } catch(err2) {
                    loadingText.innerText = "❌ Gagal membuka kamera. Izinkan akses kamera di browser.";
                }
            }
                loadingText.style.display = 'none';
                scanLine.style.display = 'block';
            } catch(e) {
                try {
                    await html5QrcodeScanner.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 150, height: 150 } },
                        onQRSuccess
                    );
                    loadingText.style.display = 'none';
                    scanLine.style.display = 'block';
                } catch(err2) {
                    loadingText.innerText = "❌ Gagal membuka kamera. Izinkan akses kamera di browser.";
                }
            }

            function onQRSuccess(decodedText) {
                if (isScanning) return;
                isScanning = true;
                html5QrcodeScanner.pause(true);
                
                let statusTerpilih = document.getElementById('inputStatusManual').value;
                updateStatusBox("QR Code Dikenali! Mengirim... ⏳", "normal");
                prosesAbsen(decodedText, "QR Code", 'qr', statusTerpilih);
            }

            function prosesAbsen(nis, nama, sumber, statusManual) {
                fetch('/scan/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ nis: nis, sumber: sumber, status_manual: statusManual })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        playSuksesSound();
                        bacakanPesan(`Berhasil! ${data.nama} absen ${data.status}`);
                        Swal.fire({
                            title: 'BERHASIL!',
                            text: `Absen ${data.status} untuk ${data.nama}`,
                            icon: 'success',
                            timer: 2500,
                            showConfirmButton: false
                        }).then(() => { resetScanner(); });
                    } else {
                        playErrorSound();
                        bacakanPesan(data.pesan);
                        Swal.fire({
                            title: 'GAGAL',
                            text: data.pesan,
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        }).then(() => { resetScanner(); });
                    }
                }).catch(() => { resetScanner(); });
            }

            function resetScanner() {
                scanLine.style.animationPlayState = 'running';
                updateStatusBox("Menunggu QR Code / Wajah... 👁️", "normal");
                if(html5QrcodeScanner) {
                    html5QrcodeScanner.resume();
                }
                setTimeout(() => { isScanning = false; }, 1000);
            }

            function updateStatusBox(text, type) {
                statusBox.innerText = text;
                statusBox.className = 'status-box';
                if(type === 'success') statusBox.classList.add('status-success');
                if(type === 'error') statusBox.classList.add('status-error');
            }

            function playSuksesSound() {
                new Audio('https://assets.mixkit.co/active_storage/sfx/2013/2013-preview.mp3').play().catch(()=>{});
            }
            function playErrorSound() {
                new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3').play().catch(()=>{});
            }
            function bacakanPesan(teks) {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    const s = new SpeechSynthesisUtterance(teks);
                    s.lang = 'id-ID';
                    window.speechSynthesis.speak(s);
                }
            }

            // FORM INPUT MANUAL
            document.getElementById('formManual').addEventListener('submit', function(e) {
                e.preventDefault();
                let nis = document.getElementById('inputNisManual').value.trim();
                let status = document.getElementById('inputStatusManual').value;
                if(nis) {
                    updateStatusBox("Memproses Input Manual... ⏳", "normal");
                    prosesAbsen(nis, "Manual", 'manual', status);
                    document.getElementById('inputNisManual').value = '';
                }
            });

            // SINKRONISASI LIVE BACKGROUND
            setInterval(async () => {
                try {
                    let res = await fetch(window.location.href);
                    let html = await res.text();
                    let doc = new DOMParser().parseFromString(html, 'text/html');
                    if(doc.getElementById('list-belum-absen')) {
                        document.getElementById('list-belum-absen').innerHTML = doc.getElementById('list-belum-absen').innerHTML;
                        document.getElementById('list-sudah-absen').innerHTML = doc.getElementById('list-sudah-absen').innerHTML;
                    }
                } catch(e) {}
            }, 4000);
        });

        // FULLSCREEN & TEMA
        document.getElementById('themeToggle').addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
        document.getElementById('fullscreenToggle').addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(()=>{});
            } else {
                document.exitFullscreen();
            }
        });
    </script>
</body>
</html>