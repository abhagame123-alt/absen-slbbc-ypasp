<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rekam Wajah AI Guru - SLB BC YPASP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-900 text-white p-8">

    <div class="max-w-5xl mx-auto">
        <!-- TOMBOL KEMBALI -->
        <a href="/dashboard" class="inline-block mb-6 text-gray-400 hover:text-blue-400 font-bold transition-all">
            ⬅️ Kembali ke Dashboard
        </a>
        
        <!-- BAGIAN ATAS: REKAMAN WAJAH GURU -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 mb-8">
            <h2 class="text-2xl font-bold text-center mb-6 text-pink-400">🤖 AI Face Enrollment (Rekam Wajah Guru)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- BAGIAN KIRI: KAMERA -->
                <div class="flex flex-col items-center">
                    <div class="relative bg-black rounded-lg overflow-hidden border-4 border-gray-600 w-full aspect-video flex justify-center items-center">
                        <video id="videoElement" class="w-full h-full object-cover" autoplay muted playsinline></video>
                        <canvas id="overlay" class="absolute top-0 left-0 w-full h-full"></canvas>
                        
                        <div id="loadingText" class="absolute text-yellow-400 font-bold animate-pulse">
                            Memuat AI Camera...
                        </div>
                    </div>
                </div>

                <!-- BAGIAN KANAN: FORM PILIH GURU -->
                <div class="flex flex-col justify-center bg-gray-700 p-6 rounded-lg">
                    <label class="text-sm text-gray-300 mb-2">Pilih/Ketik Nama Guru (Yang Belum Rekam):</label>
                
                    <!-- Input box untuk Guru -->
                    <input list="daftar_guru" id="nipGuru_input" placeholder="🔍 Ketik nama guru di sini..." class="w-full p-3 bg-gray-900 border border-gray-600 rounded-lg text-white mb-6 focus:outline-none focus:border-pink-500 placeholder-gray-500">
                    
                    <!-- Datalist dari variabel $guru_belum_rekam -->
                    <datalist id="daftar_guru">
                        @foreach($guru_belum_rekam as $g)
                            <option value="{{ $g->nip }}">{{ strtoupper($g->nama_guru) }}</option>
                        @endforeach
                    </datalist>

                    <!-- Input tersembunyi untuk menyimpan NIP -->
                    <input type="hidden" id="nipGuru" value="">

                    <button id="btnRekam" class="w-full bg-pink-600 hover:bg-pink-500 text-white font-bold py-4 px-4 rounded-lg shadow-lg opacity-50 cursor-not-allowed transition-all" disabled>
                        📸 SIMPAN WAJAH SEKARANG
                    </button>
                    
                    <p class="text-xs text-gray-400 mt-4 text-center">
                        Sistem akan memilih otomatis wajah guru yang paling jelas dalam 3 detik.
                    </p>
                </div>
            </div>
        </div>

        <!-- BAGIAN BAWAH: GALERI WAJAH GURU YANG SUDAH DIREKAM -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-green-400">✅ Daftar Wajah Guru Tersimpan</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach($guru_sudah_rekam as $g)
                <div class="bg-gray-700 p-3 rounded-lg border border-gray-600 text-center flex flex-col justify-between">
                    <!-- Path gambar ditambahkan prefix 'GURU_' sesuai controller -->
                    <img src="{{ asset('wajah/GURU_'.$g->nip.'.jpg') }}" onerror="this.src='https://ui-avatars.com/api/?name={{$g->nama_guru}}&background=random'" class="w-20 h-20 rounded-full mx-auto mb-2 object-cover border-2 border-gray-500">
                    <p class="font-bold text-xs truncate mb-2" title="{{ $g->nama_guru }}">{{ strtoupper($g->nama_guru) }}</p>
                    <p class="text-[10px] text-gray-400 mb-2">{{ $g->nip }}</p>
                    
                    <!-- Tombol Ulangi Wajah -->
                    <button onclick="hapusWajah('{{ $g->nip }}')" class="w-full bg-red-600 hover:bg-red-500 text-white text-xs py-2 px-2 rounded font-bold shadow">
                        🔄 ULANGI
                    </button>
                </div>
                @endforeach
            </div>
            
            @if(count($guru_sudah_rekam) == 0)
                <p class="text-gray-400 text-center text-sm">Belum ada wajah guru yang terdaftar.</p>
            @endif
        </div>
    </div>

    <!-- SCRIPT AI -->
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const video = document.getElementById('videoElement');
            const loadingText = document.getElementById('loadingText');
            const btnRekam = document.getElementById('btnRekam');
            const canvas = document.getElementById('overlay');
            
            const inputGuru = document.getElementById('nipGuru_input');
            const hiddenNip = document.getElementById('nipGuru');
            const dataListOpts = document.getElementById('daftar_guru').options;
            
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
            }

            function startVideo() {
                navigator.mediaDevices.getUserMedia({ video: {} }).then(stream => {
                        video.srcObject = stream;
                        loadingText.style.display = 'none'; 
                });
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
                        
                        if (!isRecording) {
                             faceapi.draw.drawDetections(canvas, resizedDetections);
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

                                const box = resizedDetections.detection.box;
                                const drawBox = new faceapi.draw.DrawBox(box, { label: 'MENANGKAP FOTO...', lineWidth: 4, boxColor: 'pink' });
                                drawBox.draw(canvas);
                            }
                        }
                    }
                }, 100); 
            });

            // Pantau saat mengetik atau memilih nama guru
            inputGuru.addEventListener('input', () => {
                let foundNip = "";
                
                for (let i = 0; i < dataListOpts.length; i++) {
                    if (dataListOpts[i].value === inputGuru.value || dataListOpts[i].text === inputGuru.value) {
                        foundNip = dataListOpts[i].value; 
                        break;
                    }
                }

                hiddenNip.value = foundNip; 

                if (hiddenNip.value !== "" && !btnRekam.innerText.includes("Merekam")) {
                    btnRekam.disabled = false;
                    btnRekam.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    btnRekam.disabled = true;
                    btnRekam.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });

            btnRekam.addEventListener('click', () => {
                if (hiddenNip.value === "") return;
                bestFaceDescriptor = null;
                bestScore = 0;
                fotoSnapshot = null;
                isRecording = true; 

                btnRekam.innerHTML = "📸 Merekam Wajah (Tunggu 3 Detik)...";
                btnRekam.classList.add('animate-pulse', 'bg-yellow-600');
                btnRekam.classList.remove('bg-pink-600');
                btnRekam.disabled = true;
                inputGuru.disabled = true;

                setTimeout(() => {
                    isRecording = false; 
                    if (bestFaceDescriptor && fotoSnapshot) {
                         btnRekam.innerHTML = "⏳ Menyimpan Foto ke Server...";
                         btnRekam.classList.remove('animate-pulse', 'bg-yellow-600');
                         btnRekam.classList.add('bg-green-600');
                         simpanKeServer(bestFaceDescriptor, hiddenNip.value, fotoSnapshot);
                    } else {
                         Swal.fire('GAGAL!', 'Wajah tidak terdeteksi dengan jelas. Coba lagi.', 'warning');
                         resetTombol();
                    }
                }, 3000); 
            });

            function simpanKeServer(descriptor, nip, fotoB64) {
                const faceDataArray = Array.from(descriptor); 
                // DIARAHKAN KE RUTE BARU KHUSUS GURU
                fetch('/simpan-wajah-guru', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    // Controller kita tetap menangkap parameter 'nis', jadi kita kirim 'nis' berisi NIP guru
                    body: JSON.stringify({ nis: nip, face_data: JSON.stringify(faceDataArray), foto: fotoB64 })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('BERHASIL!', data.pesan, 'success').then(() => { window.location.reload(); });
                    } else {
                        Swal.fire('GAGAL!', data.pesan, 'error');
                        resetTombol();
                    }
                });
            }

            function resetTombol() {
                btnRekam.innerHTML = "📸 SIMPAN WAJAH SEKARANG";
                btnRekam.classList.remove('bg-green-600', 'bg-yellow-600');
                btnRekam.classList.add('bg-pink-600');
                btnRekam.disabled = false;
                inputGuru.disabled = false;
            }
        });

        // DIARAHKAN KE RUTE HAPUS KHUSUS GURU
        function hapusWajah(nipGuru) {
            Swal.fire({
                title: 'Ulangi Wajah?',
                text: "Foto wajah guru saat ini akan dihapus dan harus scan ulang.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus & Ulangi!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/hapus-wajah-guru', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ nis: nipGuru }) // Tetap pakai 'nis' agar sesuai backend controller
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