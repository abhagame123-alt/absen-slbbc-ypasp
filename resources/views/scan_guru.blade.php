@php
    date_default_timezone_set('Asia/Jakarta');
    $hari_ini = date('Y-m-d');
    $tanggal_tampil = date('d M Y');

    // CEK SIAPA YANG LOGIN!
    $user_email = auth()->check() ? auth()->user()->email : '';
    $is_mesin = ($user_email == 'mesinabsen@gmail.com' || $user_email == 'abhaadmin234@gmail.com');

    // KODE RAHASIA QR HARI INI
    $qr_code_data = "ABSEN_GURU_SLB_" . date('Ymd');
    
    // AMBIL NIP GURU JIKA YANG LOGIN ADALAH GURU (BUKAN MESIN)
    $nip_guru_login = '';
    if (!$is_mesin && auth()->check()) {
        $nama_user = auth()->user()->name; 
        $guru_login = \App\Models\Guru::where('nama_guru', $nama_user)->first();
        if($guru_login) {
            $nip_guru_login = $guru_login->nip;
        }
    }

    // SIAPKAN BRANKAS DATA WAJAH
    $json_wajah_murni = json_encode($guru_dengan_wajah ?? []);

    // HANYA TARIK DATA LIST KALAU YANG LOGIN MESIN/ADMIN
    if($is_mesin) {
        $guru_sudah = \App\Models\Absensi::join('gurus', 'absensis.nis', '=', 'gurus.nip')
            ->select('absensis.*', 'gurus.nama_guru')
            ->where('absensis.tanggal', $hari_ini)
            ->orderBy('absensis.waktu', 'desc')
            ->get();
            
        $nip_sudah = $guru_sudah->pluck('nis')->toArray();

        $guru_belum = \App\Models\Guru::whereNotIn('nip', $nip_sudah)
            ->where('nip', '!=', 'MESIN-01')
            ->where(function($q) {
                $q->where('status_aktif', 1)->orWhereNull('status_aktif');
            })
            ->orderBy('nama_guru', 'asc')
            ->get();
    }
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SCANNER GURU - SLB BC YPASP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        body { background-color: #0F172A; color: white; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; min-height: 100vh; display: flex; flex-direction: column;}
        
        @media (min-width: 1024px) { body { height: 100vh; overflow: hidden; } }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1E293B; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748B; }

        .glass-panel { background: #1E293B; border: 1px solid #334155; border-radius: 12px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column;}
        
        .clock-glow { text-shadow: 0 0 10px rgba(52, 211, 153, 0.6), 0 0 20px rgba(52, 211, 153, 0.4); }

        #reader { width: 100%; border-radius: 8px; overflow: hidden; border: 2px solid #3B82F6; box-shadow: 0 0 15px rgba(59, 130, 246, 0.3); position: relative;}
        #reader__scan_region { background: black !important; min-height: 250px !important;}
        
        #reader video { object-fit: cover !important; width: 100% !important; height: 100% !important; position: absolute; top:0; left:0;}
        #overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 20; pointer-events: none;}
        
        #reader select { background-color: #1E293B !important; color: #FFFFFF !important; border: 1px solid #475569 !important; padding: 6px !important; border-radius: 6px !important; outline: none !important; margin-bottom: 5px; width: auto; max-width: 100%; font-size: 11px;}
        #reader button { background-color: #3B82F6 !important; color: white !important; border: none !important; padding: 6px 12px !important; border-radius: 6px !important; font-weight: bold !important; cursor: pointer !important; margin-top: 5px !important; font-size: 11px; transition: 0.3s;}
        #reader button:hover { background-color: #2563EB !important; }
        #reader a { color: #10B981 !important; text-decoration: none !important; font-weight: bold !important; font-size: 11px;}
        #reader__dashboard_section_csr span { color: #9CA3AF !important; font-size: 11px !important;}
        
        /* UKURAN QR CODE JUMBO */
        .qr-wrapper { background: white; padding: 15px; border-radius: 16px; box-shadow: 0 0 20px rgba(16,185,129,0.3); display: inline-flex; justify-content: center; align-items: center; margin-bottom: 1.5rem; }
        #qrcode { width: 280px; height: 280px; overflow: hidden; }
        #qrcode img, #qrcode canvas { width: 280px !important; height: 280px !important; margin: 0 auto !important; }

        .status-box { background: #334155; color: white; padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 13px; margin-top: 15px; border: 1px solid #475569; transition: 0.3s;}
        .status-success { background: #059669; border-color: #10B981; }
        .status-error { background: #DC2626; border-color: #EF4444; }

        .input-manual { background: #0F172A; border: 1px solid #475569; color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; outline: none; transition: 0.3s;}
        .input-manual:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.3); }

        .list-item { background: #0F172A; border: 1px solid #334155; border-radius: 8px; padding: 12px 15px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;}
        .list-item h4 { font-size: 13px; font-weight: 700; color: #F8FAFC; margin: 0;}
        .list-item p { font-size: 10px; color: #9CA3AF; margin: 0; font-family: monospace;}
        .badge-waktu { background: rgba(16, 185, 129, 0.2); color: #34D399; border: 1px solid #059669; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold;}
    </style>
</head>
<body>

    <script id="brankas-data-wajah" type="application/json">
        {!! $json_wajah_murni !!}
    </script>

    <div class="w-full flex flex-col md:flex-row justify-between items-center px-6 py-4 border-b border-slate-700 bg-slate-800/50 flex-shrink-0 gap-4">
        <a href="/dashboard" class="bg-slate-700 hover:bg-slate-600 text-gray-200 py-2 px-4 rounded-lg font-bold text-sm transition flex items-center gap-2 border border-slate-600">
            ⬅️ Kembali ke Dashboard
        </a>
        
        <div class="text-center md:text-right flex items-center justify-center gap-4">
            <span id="liveSyncIndicator" class="text-[10px] font-bold px-2 py-1 rounded-full text-emerald-400 bg-emerald-900/50 border border-emerald-700 shadow-sm flex items-center gap-1 hidden md:flex">
                <i class="fas fa-satellite-dish animate-pulse"></i> LIVE
            </span>
            <div>
                <div id="liveClock" class="text-3xl md:text-4xl font-extrabold text-emerald-400 tracking-widest font-mono clock-glow">
                    00:00:00
                </div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">
                    {{ date('l, d F Y') }}
                </div>
            </div>
        </div>
    </div>

    @if($is_mesin)
    <div class="w-full max-w-[1500px] mx-auto p-4 grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 lg:overflow-hidden">
    @else
    <div class="w-full max-w-xl mx-auto p-4 flex flex-col gap-4 flex-1 justify-center">
    @endif
        
        <!-- KOLOM 1: KAMERA AI & SCANNER QR -->
        <div class="glass-panel" style="border-color: #3B82F6;">
            <div class="text-center mb-4">
                <h3 class="text-blue-400 font-bold text-lg"><i class="fas fa-id-badge mr-2"></i> SCANNER GURU</h3>
                @if($is_mesin)
                    <p class="text-xs text-gray-400">Mesin Utama: Arahkan Wajah / ID Card QR ke kamera.</p>
                @else
                    <p class="text-xs text-emerald-400 font-bold">Arahkan Kamera ke QR Code di Layar Laptop Mesin!</p>
                @endif
            </div>

            <div id="reader" class="flex-1 min-h-[250px] lg:min-h-0 relative">
                <canvas id="overlay"></canvas>
            </div>

            <div id="statusBox" class="status-box">
                Memuat Kamera... ⏳
            </div>

            @if($is_mesin)
            <div class="mt-4 bg-slate-800/50 p-3 rounded-lg border border-slate-700 cursor-text" onclick="document.getElementById('inputNisManual').focus();">
                <p class="text-center text-[10px] font-bold text-yellow-500 mb-2">⌨️ Input NIP Manual (Tembak Barcode / Ketik)</p>
                <form id="formManual" class="flex gap-2">
                    <div class="flex items-center bg-slate-900 border border-slate-600 rounded-lg px-2 flex-1 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/30 transition-all">
                        <span class="text-xs font-bold text-gray-400 pr-2 border-r border-slate-600">G-</span>
                        <input type="text" id="inputNisManual" autofocus class="bg-transparent border-none text-white text-xs w-full p-2 outline-none" placeholder="Ketik Angka & Enter..." autocomplete="off">
                    </div>
                    <select id="inputStatusManual" class="input-manual cursor-pointer">
                        <option value="Otomatis">Otomatis</option>
                        <option value="Hadir Pagi">Pagi</option>
                        <option value="Pulang">Pulang</option>
                    </select>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold text-xs px-4 py-2 rounded-lg transition shadow-lg hidden md:block">Absen</button>
                </form>
            </div>
            @endif
        </div>

        @if($is_mesin)
        <!-- KOLOM 2: QR CODE MESIN -->
        <div class="glass-panel" style="border-color: #10B981;">
            <div class="text-center mb-6">
                <h3 class="text-emerald-400 font-bold text-lg"><i class="fas fa-qrcode mr-2"></i> SCAN QR INI DARI HP ANDA</h3>
                <p class="text-xs text-gray-400">Buka menu 'Scan Guru' di HP Anda dan arahkan ke sini.</p>
            </div>

            <div class="flex-1 flex flex-col items-center justify-center py-6">
                <div class="qr-wrapper">
                    <div id="qrcode"></div>
                </div>

                <div class="w-full max-w-[250px] bg-slate-800/80 border border-slate-600 rounded-lg p-3 text-center mt-4">
                    <p class="text-sm font-bold text-yellow-400 mb-1">Sandi Berganti Tiap Hari</p>
                    <p class="text-[10px] text-gray-400">Valid khusus: <span class="text-white font-bold">{{ $tanggal_tampil }}</span></p>
                </div>
            </div>
        </div>

        <!-- KOLOM 3: DAFTAR HADIR -->
        <div class="flex flex-col gap-4 overflow-visible lg:overflow-hidden h-auto lg:h-full">
            <div class="glass-panel flex-1 flex flex-col min-h-[250px]" style="border-color: #10B981;">
                <h3 class="text-center text-sm font-bold text-emerald-400 mb-3 border-b border-slate-700 pb-2">
                    <i class="fas fa-check-square mr-1"></i> SUDAH ABSEN HARI INI
                </h3>
                <div class="overflow-y-auto flex-1 pr-1" id="list-sudah-absen">
                    @forelse($guru_sudah as $g)
                        <div class="list-item row-sudah" id="row-sudah-{{ $g->nis }}">
                            <div>
                                <h4>{{ strtoupper($g->nama_guru) }}</h4>
                                <p>NIP: {{ $g->nis }} • {{ strtoupper($g->status) }}</p>
                            </div>
                            <span class="badge-waktu">{{ substr($g->waktu, 0, 5) }}</span>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 text-xs mt-6" id="empty-sudah">Belum ada yang hadir.</div>
                    @endforelse
                </div>
            </div>

            <div class="glass-panel flex-1 flex flex-col min-h-[250px]" style="border-color: #F59E0B;">
                <h3 class="text-center text-sm font-bold text-yellow-500 mb-3 border-b border-slate-700 pb-2">
                    <i class="fas fa-hourglass-half mr-1"></i> BELUM ABSEN
                </h3>
                <div class="overflow-y-auto flex-1 pr-1" id="list-belum-absen">
                    @forelse($guru_belum as $g)
                        <div class="list-item row-belum" id="row-belum-{{ $g->nip }}">
                            <div>
                                <h4 class="text-gray-300">{{ strtoupper($g->nama_guru) }}</h4>
                                <p>NIP: {{ $g->nip }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 text-xs mt-6" id="empty-belum">Semua Guru Sudah Hadir!</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            
            let is_mesin_js = {{ $is_mesin ? 'true' : 'false' }};

            const inputField = document.getElementById('inputNisManual');
            if(inputField) {
                inputField.focus();
                document.addEventListener('keydown', function(e) {
                    if(e.key !== 'Enter' && e.key !== 'Tab' && document.activeElement.id !== 'inputNisManual' && document.activeElement.tagName !== 'SELECT') {
                        inputField.focus();
                    }
                });
            }

            function updateClock() {
                const now = new Date();
                const h = String(now.getHours()).padStart(2, '0');
                const m = String(now.getMinutes()).padStart(2, '0');
                const s = String(now.getSeconds()).padStart(2, '0');
                document.getElementById('liveClock').textContent = `${h}:${m}:${s}`;
            }
            setInterval(updateClock, 1000);
            updateClock();

            const qrcodeElement = document.getElementById("qrcode");
            if(qrcodeElement) {
                const rahasiaSandi = "{{ $qr_code_data ?? '' }}";
                new QRCode(qrcodeElement, {
                    text: rahasiaSandi, 
                    width: 280,   
                    height: 280,  
                    colorDark : "#0F172A",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            }

            let isScanning = false; 
            let faceMatcher = null; 
            let html5QrcodeScanner = null;
            let videoElement = null; 
            let canvas = document.getElementById('overlay');
            let statusBox = document.getElementById('statusBox');

            if (is_mesin_js) {
                try {
                    const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
                    statusBox.innerText = "🧠 Memuat Data AI Wajah...";
                    
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                    ]);
                    
                    const dbWajahStr = document.getElementById('brankas-data-wajah').textContent;
                    const databaseWajah = JSON.parse(dbWajahStr || "[]");

                    if(databaseWajah && databaseWajah.length > 0) {
                        const labeledDescriptors = [];
                        for (const guru of databaseWajah) {
                            try {
                                const faceDataArray = JSON.parse(guru.face_data);
                                const float32Array = new Float32Array(faceDataArray);
                                const label = `${guru.nis}_${guru.nama_lengkap}`;
                                labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(label, [float32Array]));
                            } catch (e) {}
                        }
                        if(labeledDescriptors.length > 0) {
                            faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.55); 
                        }
                    }
                    
                    statusBox.innerText = "📷 Membuka Kamera...";
                    mulaiScannerQR(); 

                } catch (err) {
                    statusBox.innerText = "❌ Gagal memuat AI!";
                    mulaiScannerQR();
                }
            } else {
                statusBox.innerText = "📷 Membuka Kamera Scanner...";
                mulaiScannerQR();
            }

            function mulaiScannerQR() {
                try {
                    // 🔥 KITA KEMBALIKAN KOTAK PUTIHNYA BIAR IPHONE NGGAK NGELAG 🔥
                    // Kita pakai ukuran kotak 250px x 250px untuk semua perangkat biar enteng!
                    let qrConfig = { 
                        fps: 10, 
                        qrbox: {width: 250, height: 250} 
                    };

                    html5QrcodeScanner = new Html5QrcodeScanner("reader", qrConfig, false);
                    html5QrcodeScanner.render(onQRSuccess);

                    let checkVideoExist = setInterval(() => {
                        videoElement = document.querySelector('#reader video');
                        if(videoElement) {
                            clearInterval(checkVideoExist);
                            
                            if (is_mesin_js) {
                                statusBox.innerText = "Silakan tatap kamera / tapkan ID Card... 👁️";
                                const scanRegion = document.getElementById('reader__scan_region');
                                if (scanRegion) scanRegion.appendChild(canvas);
                                mulaiDeteksiWajah(); 
                            } else {
                                statusBox.innerText = "Arahkan kamera ke layar laptop... 📷";
                            }
                        }
                    }, 500);
                } catch(e) {
                    statusBox.innerText = "❌ Kamera Gagal Diakses!";
                }
            }

            function onQRSuccess(decodedText, decodedResult) {
                if (isScanning) return; 
                isScanning = true; 
                html5QrcodeScanner.pause();
                
                let statusTerpilih = document.getElementById('inputStatusManual') ? document.getElementById('inputStatusManual').value : 'Otomatis';
                const tokenHarian = "{{ $qr_code_data ?? '' }}";
                
                let nipKirim = decodedText;
                let namaKirim = "QR Card";
                let sumberKirim = "qr";

                if (!is_mesin_js) {
                    if(decodedText === tokenHarian) {
                        nipKirim = "{{ $nip_guru_login ?? '' }}"; 
                        namaKirim = "{{ Auth::user()->name ?? 'Saya' }}";
                        sumberKirim = "qr_layar_mesin";
                        updateStatusBox(`Sandi Cocok! Mengirim Absen... ⏳`, "success");
                    } else {
                        updateStatusBox(`QR Code Tidak Dikenali! ❌`, "error");
                    }
                } else {
                    updateStatusBox(`QR Card Dikenali! Mengirim... ⏳`, "normal");
                }

                prosesAbsen(nipKirim, namaKirim, sumberKirim, statusTerpilih);
            }

            function mulaiDeteksiWajah() {
                function gambarLabelNormal(box, teks, warna) {
                    const ctx = canvas.getContext('2d');
                    const drawBox = new faceapi.draw.DrawBox(box, { label: '', lineWidth: 3, boxColor: warna });
                    drawBox.draw(canvas);

                    ctx.font = 'bold 12px Arial';
                    const textWidth = ctx.measureText(teks).width;
                    const xPosisi = box.x; 
                    const yPosisi = box.y;
                    
                    ctx.fillStyle = warna;
                    ctx.fillRect(xPosisi, yPosisi - 20, textWidth + 10, 20);
                    ctx.fillStyle = 'white';
                    ctx.fillText(teks, xPosisi + 5, yPosisi - 5);
                }

                setInterval(async () => {
                    videoElement = document.querySelector('#reader video');
                    if (isScanning || !videoElement || videoElement.paused || videoElement.readyState !== 4) return;

                    const displaySize = { width: videoElement.clientWidth, height: videoElement.clientHeight };
                    if(displaySize.width === 0) return;
                    faceapi.matchDimensions(canvas, displaySize);

                    try {
                        const detections = await faceapi.detectSingleFace(videoElement, new faceapi.TinyFaceDetectorOptions({ 
                            inputSize: 224, scoreThreshold: 0.5 
                        })).withFaceLandmarks().withFaceDescriptor();

                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                        if (detections) {
                            const resizedDetections = faceapi.resizeResults(detections, displaySize);
                            const box = resizedDetections.detection.box;
                            
                            if (faceMatcher) {
                                const bestMatch = faceMatcher.findBestMatch(detections.descriptor);
                                if (bestMatch.label !== 'unknown' && bestMatch.distance < 0.55) {
                                    isScanning = true; 
                                    html5QrcodeScanner.pause(); 
                                    
                                    const nisGuru = bestMatch.label.split('_')[0];
                                    const namaGuru = bestMatch.label.split('_')[1];
                                    let statusTerpilih = document.getElementById('inputStatusManual') ? document.getElementById('inputStatusManual').value : 'Otomatis';

                                    gambarLabelNormal(box, `${namaGuru}`, '#10B981');
                                    updateStatusBox(`Wajah ${namaGuru} Dikenali! Mengirim... ⏳`, "normal");
                                    prosesAbsen(nisGuru, namaGuru, 'wajah', statusTerpilih);
                                } else {
                                    gambarLabelNormal(box, 'Tidak Dikenal', '#EF4444');
                                }
                            } else {
                                gambarLabelNormal(box, 'Belum Ada Data', '#F59E0B');
                            }
                        }
                    } catch (error) {}
                }, 600);
            }

            function prosesAbsen(nis, nama, sumber, statusManual = 'Otomatis') {
                if(sumber === 'manual' && !nis.toUpperCase().startsWith('G-')) { nis = 'G-' + nis; }

                fetch('/scan/store', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify({ nis: nis, sumber: sumber, status_manual: statusManual, jenis_mesin: 'guru' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        playSuksesSound(); 
                        if(document.getElementById('inputStatusManual')) document.getElementById('inputStatusManual').value = 'Otomatis';
                        manipulasiDaftarAbsenLive(nis, data.nama, data.status, data.waktu);

                        Swal.fire({
                            title: 'BERHASIL!', text: `Absen ${data.status} untuk Guru ${data.nama}`, icon: 'success',
                            timer: 3000, showConfirmButton: false, background: '#1E293B', color: '#F8FAFC', iconColor: '#10B981', 
                            customClass: { popup: 'border-2 border-emerald-500 rounded-2xl' }
                        }).then(() => { resetScanner(); if(inputField) inputField.focus(); });
                    } else {
                        playErrorSound();
                        let warnaBorder = data.tipe_error === 'dobel' ? 'border-yellow-500' : 'border-red-500';
                        Swal.fire({
                            title: data.tipe_error === 'dobel' ? 'INFO' : 'GAGAL', text: data.pesan, icon: data.tipe_error === 'dobel' ? 'warning' : 'error',
                            timer: 3500, showConfirmButton: false, background: '#1E293B', color: '#F8FAFC', customClass: { popup: `border-2 ${warnaBorder} rounded-2xl` }
                        }).then(() => { resetScanner(); if(inputField) inputField.focus(); });
                    }
                }).catch(error => { resetScanner(); if(inputField) inputField.focus(); });
            }

            function manipulasiDaftarAbsenLive(nis, nama, status, waktuFull) {
                const listSudah = document.getElementById('list-sudah-absen');
                if(!listSudah) return; 

                const rowBelum = document.getElementById('row-belum-' + nis);
                if(rowBelum) rowBelum.remove();
                const emptySudah = document.getElementById('empty-sudah');
                if(emptySudah) emptySudah.remove();

                const newRow = document.createElement('div');
                newRow.className = 'list-item row-sudah';
                const jamAbsen = waktuFull.split(' - ')[1] || 'Barusan';

                newRow.innerHTML = `<div><h4>${nama.toUpperCase()}</h4><p>NIP: ${nis} • ${status.toUpperCase()}</p></div><span class="badge-waktu">${jamAbsen}</span>`;
                listSudah.insertBefore(newRow, listSudah.firstChild);
            }

            function resetScanner() {
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                if (is_mesin_js) {
                    updateStatusBox("Silakan tatap kamera / tapkan ID Card... 👁️", "normal");
                } else {
                    updateStatusBox("Arahkan kamera ke layar laptop... 📷", "normal");
                }
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
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2013/2013-preview.mp3'); audio.play().catch(e => {});
            }
            function playErrorSound() {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3'); audio.play().catch(e => {});
            }

            const formManual = document.getElementById('formManual');
            if (formManual) {
                const inputStatusManual = document.getElementById('inputStatusManual');
                formManual.addEventListener('submit', function(e) {
                    e.preventDefault(); 
                    const nisManual = inputField.value.trim();
                    const statusManual = inputStatusManual.value; 

                    if (nisManual) {
                        updateStatusBox(`Memproses Input Manual... ⏳`, "normal");
                        prosesAbsen(nisManual, "Manual Input", 'manual', statusManual);
                        inputField.value = '';
                    }
                });
            }
            
            async function jalankanSinkronisasiGaib() {
                if(!document.getElementById('list-belum-absen')) return;
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
                    }
                } catch (error) {}
            }
            setInterval(jalankanSinkronisasiGaib, 5000);
        });
    </script>
</body>
</html>