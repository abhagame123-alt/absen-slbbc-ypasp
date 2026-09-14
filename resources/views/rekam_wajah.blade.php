<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rekam Wajah AI - SLB BC YPASP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* ==============================================================
           TEMA HIJAU SEGAR (LIGHT MODE - DEFAULT)
           ============================================================== */
        body { background-color: #F0FDF4; color: #1F2937; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: background-color 0.3s; padding: 20px; }
        
        .main-container { width: 100%; max-width: 1000px; margin: 0 auto; }
        
        /* Tombol Navigasi Header */
        .header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-kembali { background: #FFFFFF; color: #059669; border: 1px solid #10B981; padding: 8px 16px; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(16,185,129,0.1); transition: 0.3s; cursor: pointer; }
        .btn-kembali:hover { background: #ECFDF5; color: #047857; }

        /* Kotak Utama */
        .glass-panel { background: #FFFFFF; border-radius: 16px; padding: 25px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.1); border: 2px solid #6EE7B7; margin-bottom: 25px; transition: all 0.3s; }
        .title-text { color: #059669; font-weight: bold; font-size: 1.5rem; text-align: center; margin-bottom: 20px; }

        /* AREA KAMERA */
        .camera-wrapper { position: relative; width: 100%; aspect-ratio: 16 / 9; background: black; border-radius: 12px; overflow: hidden; border: 4px solid #10B981; box-shadow: 0 0 15px rgba(16, 185, 129, 0.2); display: flex; justify-content: center; align-items: center;}
        video, canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); z-index: 2; pointer-events: none;}
        
        /* Loader Overlay (Background Hitam Transparan) */
        .loader-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10; display: flex; justify-content: center; align-items: center; color: #FBBF24; font-weight: bold; font-size: 18px; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.05); } 100% { opacity: 1; transform: scale(1); } }

        /* Panel Input & Tombol Simpan */
        .input-panel { background: #ECFDF5; padding: 24px; border-radius: 12px; border: 1px solid #A7F3D0; height: 100%; display: flex; flex-direction: column; justify-content: center; transition: 0.3s;}
        .input-label { color: #065F46; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block; }
        
        .input-field { width: 100%; padding: 12px; background: #FFFFFF; border: 1px solid #10B981; border-radius: 8px; color: #1F2937; margin-bottom: 20px; outline: none; transition: 0.3s; font-weight: 500;}
        .input-field:focus { box-shadow: 0 0 0 3px rgba(16,185,129,0.3); border-color: #059669; }

        .btn-simpan { background: linear-gradient(135deg, #10B981, #059669); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: bold; width: 100%; cursor: pointer; transition: 0.3s; box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }
        .btn-simpan:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); }
        .btn-simpan:disabled { background: #D1D5DB; color: #6B7280; box-shadow: none; cursor: not-allowed; transform: none; }

        /* Grid Daftar Wajah Tersimpan */
        .face-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        @media (min-width: 768px) { .face-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        
        .face-card { background: #FFFFFF; border: 1px solid #A7F3D0; border-radius: 12px; padding: 15px; text-align: center; box-shadow: 0 4px 6px rgba(16,185,129,0.05); transition: 0.3s; display: flex; flex-direction: column; justify-content: space-between;}
        .face-card:hover { transform: translateY(-3px); border-color: #10B981; box-shadow: 0 8px 15px rgba(16,185,129,0.15); }
        .face-card img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 10px; border: 3px solid #10B981; }
        .face-name { font-size: 12px; font-weight: 800; color: #1F2937; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .btn-hapus { background: #EF4444; color: white; border: none; padding: 8px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; width: 100%; cursor: pointer; transition: 0.3s; }
        .btn-hapus:hover { background: #DC2626; }

        /* ==============================================================
           TEMA GELAP ELEGAN (DARK MODE)
           ============================================================== */
        body.dark-mode { background-color: #0F172A; color: white; }
        
        body.dark-mode .btn-kembali { background: #1E293B; color: #9CA3AF; border: 1px solid #475569; box-shadow: none; }
        body.dark-mode .btn-kembali:hover { color: white; background: #334155; }

        body.dark-mode .glass-panel { background: #1E293B; border-color: #334155; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        body.dark-mode .title-text { color: #60A5FA; }

        body.dark-mode .camera-wrapper { border-color: #3B82F6; box-shadow: 0 0 15px rgba(59, 130, 246, 0.3); }
        
        body.dark-mode .input-panel { background: #283548; border-color: #475569; }
        body.dark-mode .input-label { color: #9CA3AF; }
        body.dark-mode .input-field { background: #0F172A; border-color: #475569; color: white; }
        body.dark-mode .input-field:focus { box-shadow: 0 0 0 3px rgba(59,130,246,0.3); border-color: #3B82F6; }
        
        body.dark-mode .btn-simpan { background: linear-gradient(135deg, #3B82F6, #1D4ED8); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3); }
        body.dark-mode .btn-simpan:hover { box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4); }
        body.dark-mode .btn-simpan:disabled { background: #475569; color: #9CA3AF; box-shadow: none; }

        body.dark-mode .face-card { background: #283548; border-color: #475569; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        body.dark-mode .face-card:hover { border-color: #3B82F6; }
        body.dark-mode .face-card img { border-color: #3B82F6; }
        body.dark-mode .face-name { color: #F8FAFC; }
    </style>
</head>
<body>

    <!-- Skrip untuk mendeteksi Tema awal -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.body.classList.add('dark-mode');
        }
    </script>

    <div class="main-container">
        
        <!-- HEADER TOMBOL (KEMBALI & TOGGLE TEMA) -->
        <div class="header-nav">
            <a href="/dashboard" class="btn-kembali">
                ⬅️ Kembali ke Dashboard
            </a>
            <button id="themeToggle" class="btn-kembali" style="gap: 8px;">
                <span id="themeIcon">☀️</span> <span id="themeText">Terang</span>
            </button>
        </div>

        <!-- BAGIAN ATAS: REKAMAN WAJAH -->
        <div class="glass-panel">
            <h2 class="title-text">🤖 AI Face Enrollment (Rekam Wajah)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- BAGIAN KIRI: KAMERA -->
                <div class="flex flex-col items-center w-full">
                    <div class="camera-wrapper">
                        <video id="videoElement" autoplay muted playsinline></video>
                        <canvas id="overlay"></canvas>
                        
                        <div id="loadingText" class="loader-overlay">
                            Memuat AI Camera...
                        </div>
                    </div>
                </div>

                <!-- BAGIAN KANAN: FORM PILIH MURID -->
                <div class="input-panel">
                    <label class="input-label">Pilih/Ketik Nama Murid (Yang Belum Rekam):</label>
                
                    <!-- Input box datalist -->
                    <input list="daftar_murid" id="nisMurid_input" placeholder="🔍 Ketik nama murid di sini..." class="input-field">
                    
                    <datalist id="daftar_murid">
                        @foreach($murid_belum_rekam as $m)
                            <option value="{{ $m->nis }}">{{ strtoupper($m->nama_lengkap) }} (Kelas {{ $m->kelas }})</option>
                        @endforeach
                    </datalist>

                    <input type="hidden" id="nisMurid" value="">

                    <button id="btnRekam" class="btn-simpan" disabled>
                        📸 SIMPAN WAJAH SEKARANG
                    </button>
                    
                    <p class="text-xs text-gray-500 mt-4 text-center dark:text-gray-400">
                        Sistem akan memilih otomatis wajah anak yang paling jelas dalam 3 detik.
                    </p>
                </div>
            </div>
        </div>

        <!-- BAGIAN BAWAH: GALERI WAJAH -->
        <div class="glass-panel">
            <h3 class="text-xl font-bold mb-6 text-emerald-500 dark:text-green-400">✅ Daftar Wajah Tersimpan</h3>
            
            <div class="face-grid">
                @foreach($murid_sudah_rekam as $m)
                <div class="face-card">
                    <img src="{{ asset('wajah/'.$m->nis.'.jpg') }}" onerror="this.src='https://ui-avatars.com/api/?name={{$m->nama_lengkap}}&background=random'" alt="Foto">
                    <p class="face-name" title="{{ $m->nama_lengkap }}">{{ strtoupper($m->nama_lengkap) }}</p>
                    <button onclick="hapusWajah('{{ $m->nis }}')" class="btn-hapus">
                        🔄 ULANGI
                    </button>
                </div>
                @endforeach
            </div>
            
            @if(count($murid_sudah_rekam) == 0)
                <p class="text-gray-400 text-center text-sm py-4">Belum ada wajah murid yang terdaftar.</p>
            @endif
        </div>
    </div>

    <!-- SCRIPT TEMA -->
    <script>
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
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
            updateToggleButton();
        });
    </script>

    <!-- SCRIPT AI -->
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const video = document.getElementById('videoElement');
            const loadingText = document.getElementById('loadingText');
            const btnRekam = document.getElementById('btnRekam');
            const canvas = document.getElementById('overlay');
            
            const inputMurid = document.getElementById('nisMurid_input');
            const hiddenNis = document.getElementById('nisMurid');
            const dataListOpts = document.getElementById('daftar_murid').options;
            
            let bestFaceDescriptor = null; 
            let isRecording = false;       
            let bestScore = 0;             
            let fotoSnapshot = null; 

            loadingText.innerText = "⏳ Sedang mengunduh Otak AI...";
            try {
                const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                startVideo();
            } catch (err) {
                loadingText.innerText = "❌ Gagal memuat AI. Pastikan internet menyala!";
                loadingText.style.color = "#EF4444";
            }

            function startVideo() {
                navigator.mediaDevices.getUserMedia({ video: {} }).then(stream => {
                        video.srcObject = stream;
                        loadingText.style.display = 'none'; 
                });
            }

            // FUNGSI AJAIB ANTI-MIRROR TEKS
            function gambarLabelAntiMirror(box, teks, warna) {
                const ctx = canvas.getContext('2d');
                
                // Gambar kotak wajahnya saja
                const drawBox = new faceapi.draw.DrawBox(box, { label: '', lineWidth: 3, boxColor: warna });
                drawBox.draw(canvas);

                // Mulai gambar teks dengan rumus cermin
                ctx.save();
                ctx.scale(-1, 1); // Membalikkan kuas agar teks ditulis normal
                ctx.font = 'bold 14px Arial';
                
                const textWidth = ctx.measureText(teks).width;
                const xPosisi = -(box.x + box.width); 
                const yPosisi = box.y;
                
                // Gambar background teks
                ctx.fillStyle = warna;
                ctx.fillRect(xPosisi, yPosisi - 25, textWidth + 14, 25);
                
                // Tulis teksnya
                ctx.fillStyle = 'white';
                ctx.fillText(teks, xPosisi + 7, yPosisi - 8);
                
                ctx.restore(); // Kembalikan kuas ke normal
            }

            video.addEventListener('play', () => {
                const displaySize = { width: video.clientWidth, height: video.clientHeight };
                faceapi.matchDimensions(canvas, displaySize);

                setInterval(async () => {
                    const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                    if (detections) {
                        const resizedDetections = faceapi.resizeResults(detections, displaySize);
                        const box = resizedDetections.detection.box;
                        
                        if (!isRecording) {
                            const isDark = document.body.classList.contains('dark-mode');
                            const boxColor = isDark ? '#3B82F6' : '#10B981'; 
                            
                            // Gunakan fungsi ajaib agar teks normal
                            gambarLabelAntiMirror(box, 'Wajah Terdeteksi', boxColor);
                        }

                        if (isRecording) {
                            const score = detections.detection.score; 
                            if (score > bestScore) {
                                bestScore = score;
                                bestFaceDescriptor = detections.descriptor;
                                
                                const snapCanvas = document.createElement('canvas');
                                snapCanvas.width = video.videoWidth;
                                snapCanvas.height = video.videoHeight;
                                snapCanvas.getContext('2d').drawImage(video, 0, 0);
                                fotoSnapshot = snapCanvas.toDataURL('image/jpeg');

                                // Gunakan fungsi ajaib agar teks normal
                                gambarLabelAntiMirror(box, 'MENANGKAP FOTO...', '#F59E0B');
                            }
                        }
                    }
                }, 100); 
            });

            // Pantau saat guru mengetik atau memilih nama di datalist
            inputMurid.addEventListener('input', () => {
                let foundNis = "";
                
                for (let i = 0; i < dataListOpts.length; i++) {
                    if (dataListOpts[i].value === inputMurid.value || dataListOpts[i].text === inputMurid.value) {
                        foundNis = dataListOpts[i].value; 
                        break;
                    }
                }

                hiddenNis.value = foundNis; 

                if (hiddenNis.value !== "" && !btnRekam.innerText.includes("Merekam")) {
                    btnRekam.disabled = false;
                } else {
                    btnRekam.disabled = true;
                }
            });

            btnRekam.addEventListener('click', () => {
                if (hiddenNis.value === "") return;
                bestFaceDescriptor = null;
                bestScore = 0;
                fotoSnapshot = null;
                isRecording = true; 

                btnRekam.innerHTML = "📸 Merekam Wajah (Tunggu 3 Detik)...";
                btnRekam.style.background = "#D97706"; 
                btnRekam.disabled = true;
                inputMurid.disabled = true; 

                setTimeout(() => {
                    isRecording = false; 
                    if (bestFaceDescriptor && fotoSnapshot) {
                         btnRekam.innerHTML = "⏳ Menyimpan Foto ke Server...";
                         btnRekam.style.background = "#059669"; 
                         simpanKeServer(bestFaceDescriptor, hiddenNis.value, fotoSnapshot);
                    } else {
                         const isDark = document.body.classList.contains('dark-mode');
                         Swal.fire({
                             title: 'GAGAL!', 
                             text: 'Wajah tidak terdeteksi dengan jelas. Coba lagi.', 
                             icon: 'warning',
                             background: isDark ? '#1E293B' : '#FFFFFF',
                             color: isDark ? '#F8FAFC' : '#1F2937'
                         });
                         resetTombol();
                    }
                }, 3000); 
            });

            function simpanKeServer(descriptor, nis, fotoB64) {
                const faceDataArray = Array.from(descriptor); 
                fetch('/simpan-wajah', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ nis: nis, face_data: JSON.stringify(faceDataArray), foto: fotoB64 })
                })
                .then(res => res.json())
                .then(data => {
                    const isDark = document.body.classList.contains('dark-mode');
                    if (data.success) {
                        Swal.fire({
                            title: 'BERHASIL!', 
                            text: data.pesan, 
                            icon: 'success',
                            background: isDark ? '#1E293B' : '#FFFFFF',
                            color: isDark ? '#F8FAFC' : '#065F46',
                            confirmButtonColor: '#10B981'
                        }).then(() => { window.location.reload(); });
                    } else {
                        Swal.fire({
                            title: 'GAGAL!', 
                            text: data.pesan, 
                            icon: 'error',
                            background: isDark ? '#1E293B' : '#FFFFFF',
                            color: isDark ? '#F8FAFC' : '#991B1B'
                        });
                        resetTombol();
                    }
                });
            }

            function resetTombol() {
                btnRekam.innerHTML = "📸 SIMPAN WAJAH SEKARANG";
                btnRekam.style.background = ""; 
                btnRekam.disabled = false;
                inputMurid.disabled = false;
            }
        });

        // FUNGSI UNTUK TOMBOL ULANGI WAJAH
        function hapusWajah(nisMurid) {
            const isDark = document.body.classList.contains('dark-mode');
            
            Swal.fire({
                title: 'Ulangi Wajah?',
                text: "Foto wajah saat ini akan dihapus dan murid harus scan ulang.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: isDark ? '#475569' : '#9CA3AF',
                confirmButtonText: 'Ya, Hapus & Ulangi!',
                background: isDark ? '#1E293B' : '#FFFFFF',
                color: isDark ? '#F8FAFC' : '#1F2937'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/hapus-wajah', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ nis: nisMurid })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) { window.location.reload(); }
                    });
                }
            });
        }
    </script>
</body>
</html>