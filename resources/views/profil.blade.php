<x-app-layout>
    <x-slot name="header">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Tambahkan SweetAlert untuk Notifikasi -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-white leading-tight">
                <i class="fas fa-user-graduate text-blue-500 mr-2"></i> {{ __('Profil & Raport Siswa') }}
            </h2>
            
            <a href="{{ route('dashboard') }}" style="background: #374151; color: white; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: bold; text-decoration: none; transition: 0.3s; border: 1px solid #4B5563; display: flex; align-items: center;" onmouseover="this.style.background='#4B5563'" onmouseout="this.style.background='#374151'">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <!-- CSS KHUSUS ID CARD & LAYOUT -->
    <style>
        .premium-container { background-color: #1E293B; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid #334155; padding: 30px; height: 100%;}
        
        .profil-split { display: flex; flex-direction: column; gap: 24px; }
        @media(min-width: 992px) {
            .profil-split { display: grid; grid-template-columns: 35% 1fr; align-items: start; }
        }

        .stat-grid-3 { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
        @media(min-width: 768px) {
            .stat-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); }
        }

        /* --- DESAIN ID CARD "KELAS BERAT" --- */
        .id-card-wrapper {
            width: 53.98mm; 
            height: 85.60mm;
            box-sizing: border-box;
            background: white;
            border-radius: 2.5mm;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border: 1px solid #94A3B8;
            display: flex;
            flex-direction: column;
            position: relative;
            margin: 0 auto;
            font-family: 'Segoe UI', Arial, sans-serif; 
        }
        
        .id-card-header {
            background: #00563B; 
            color: white;
            padding: 5mm 2mm 4mm 2mm; 
            border-bottom: 2mm solid #F59E0B; 
            display: flex; align-items: center; justify-content: center; gap: 2.5mm;
            position: relative; z-index: 2;
        }
        
        .logo-sekolah-card { width: 11mm; height: 11mm; object-fit: contain; filter: drop-shadow(0px 1px 2px rgba(0,0,0,0.4)); }
        
        .header-text { text-align: left; }
        .header-text h3 { margin: 0; font-size: 13px; font-weight: 800; line-height: 1; letter-spacing: 0.2px; color: white;}
        .header-text p { margin: 2px 0 0 0; font-size: 6px; font-weight: 600; opacity: 0.9; letter-spacing: 0.8px; color: white;}
        
        .id-card-body {
            flex-grow: 1; padding: 2mm; text-align: center; background-color: #ffffff;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            position: relative; z-index: 2;
        }

        .watermark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -45%);
            width: 45mm; height: 45mm; opacity: 0.08; z-index: 1; pointer-events: none;
        }

        .qr-box {
            background: white; padding: 2mm; border-radius: 2mm; border: none; 
            display: inline-block; margin-bottom: 3mm; margin-top: -3mm; 
            box-shadow: 0 3px 6px rgba(0,0,0,0.1), inset 0 0 0 1px rgba(0,0,0,0.05);
            position: relative; z-index: 3;
        }

        .student-name { font-size: 14px; font-weight: 800; color: #0F172A; margin: 0; line-height: 1.1; z-index: 3; position: relative;}
        .student-nis { font-size: 9px; font-weight: 700; color: #DC2626; margin: 2px 0 3px 0; letter-spacing: 0.5px; z-index: 3; position: relative;}
        
        .class-wrapper {
            width: 100%;
            text-align: center;
            margin-top: 2px;
            z-index: 3;
            position: relative;
        }

        .student-class { 
            display: inline-block;
            font-size: 8px; 
            font-weight: 800; 
            color: #047857 !important; 
            background: #D1FAE5 !important; 
            border-radius: 10mm; 
            border: 1px solid #10B981 !important;
            margin: 0 auto;
            padding: 0.5mm 4mm 3mm 4mm; 
            line-height: 1.2; 
            white-space: nowrap;
        }
        
        .id-card-footer {
            background: #0F172A; color: white; text-align: center;
            padding: 3mm 2mm; font-size: 6.5px; font-weight: 700; letter-spacing: 0.5px;
            position: relative; overflow: hidden; z-index: 2;
        }

        .microtext {
            position: absolute; top: 0; left: 0; width: 100%; font-size: 3px; 
            color: rgba(255,255,255,0.08); white-space: nowrap; font-weight: normal;
        }
        
        /* --- TOMBOL DOWNLOAD --- */
        .btn-download-id {
            background: linear-gradient(135deg, #10B981, #059669); 
            color: white; width: 100%; padding: 12px; border-radius: 8px;
            font-weight: bold; border: none; cursor: pointer; transition: 0.3s;
            margin-top: 20px; display: flex; justify-content: center; align-items: center;
        }
        .btn-download-id:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }

        .stat-box-small { border-radius: 12px; padding: 20px; text-align: center; color: #334155; border: 2px solid transparent;}

        /* CSS KHUSUS DROPDOWN FORM MANUAL BIAR KELIHATAN DI DARK MODE */
        .form-select-manual option {
            background-color: #1E293B;
            color: #F8FAFC;
        }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="profil-split">
                
                <!-- KOLOM KIRI: ID CARD MAHASISWA -->
                <div class="premium-container" style="display: flex; flex-direction: column; align-items: center;">
                    
                    <!-- AREA YANG AKAN DIDOWNLOAD -->
                    <div id="capture-area" style="padding: 10px; background-color: transparent;">
                        
                        <!-- ID CARD "KELAS BERAT" -->
                        <div class="id-card-wrapper" id="printable-id-card">
                            <div class="id-card-header">
                                <img src="{{ asset('logo.png') }}" alt="Logo" class="logo-sekolah-card" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Tut_Wuri_Handayani.svg/1200px-Tut_Wuri_Handayani.svg.png'">
                                <div class="header-text">
                                    <h3>SLB BC YPASP</h3>
                                    <p>KARTU IDENTITAS RESMI</p>
                                </div>
                            </div>
                            
                            <div class="id-card-body">
                                <img src="{{ asset('logo.png') }}" alt="Watermark" class="watermark" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Tut_Wuri_Handayani.svg/1200px-Tut_Wuri_Handayani.svg.png'">
                                <div class="qr-box">
                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(80)->generate($murid->nis) !!}
                                </div>
                                <h2 class="student-name">{{ strtoupper($murid->nama_lengkap) }}</h2>
                                <p class="student-nis">NIS: {{ $murid->nis }}</p>
                                
                                <div class="class-wrapper">
                                    <span class="student-class">KELAS: {{ strtoupper($murid->kelas) }}</span>
                                </div>
                            </div>
                            
                            <div class="id-card-footer">
                                <div class="microtext">SLBBCYPASP-SLBBCYPASP-SLBBCYPASP-SLBBCYPASP-SLBBCYPASP-SLBBCYPASP</div>
                                HARAP DIBAWA SETIAP HARI
                            </div>
                        </div>

                    </div>

                    <!-- TOMBOL DOWNLOAD -->
                    <button onclick="downloadIDCard()" class="btn-download-id">
                        <i class="fas fa-download mr-2"></i> Download ID Card (PNG)
                    </button>

                    <!-- TABUNGAN -->
                    <div style="background: linear-gradient(135deg, #3B82F6, #2563EB); color: white; width: 100%; border-radius: 12px; padding: 20px; text-align: center; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <p style="font-size: 12px; font-weight: bold; opacity: 0.9; margin-bottom: 5px;">Total Tabungan Saat Ini</p>
                        <h3 style="font-size: 24px; font-weight: 900; margin: 0;">Rp {{ number_format($saldo ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>

                <!-- KOLOM KANAN: STATISTIK KEHADIRAN & FORM MANUAL -->
                <div class="premium-container">
                    <h3 class="font-bold text-lg text-white mb-6" style="border-bottom: 1px solid #334155; padding-bottom: 15px;">
                        <i class="fas fa-chart-bar text-blue-400 mr-2"></i> Rekap Kehadiran Selama Ini
                    </h3>
                    
                    <div class="stat-grid-3">
                        <div class="stat-box-small" style="background-color: #D1FAE5; border-color: #10B981;">
                            <h4 style="font-size: 13px; font-weight: bold; color: #059669; margin-bottom: 10px;">Hadir / Telat</h4>
                            <p style="font-size: 32px; font-weight: 900; color: #047857; margin: 0; line-height: 1;">{{ $hadir ?? 0 }}</p>
                        </div>
                        
                        <div class="stat-box-small" style="background-color: #FEF3C7; border-color: #F59E0B;">
                            <h4 style="font-size: 13px; font-weight: bold; color: #D97706; margin-bottom: 10px;">Sakit</h4>
                            <p style="font-size: 32px; font-weight: 900; color: #B45309; margin: 0; line-height: 1;">{{ $sakit ?? 0 }}</p>
                        </div>
                        
                        <div class="stat-box-small" style="background-color: #F3E8FF; border-color: #8B5CF6;">
                            <h4 style="font-size: 13px; font-weight: bold; color: #7C3AED; margin-bottom: 10px;">Izin</h4>
                            <p style="font-size: 32px; font-weight: 900; color: #6D28D9; margin: 0; line-height: 1;">{{ $izin ?? 0 }}</p>
                        </div>
                    </div>

                    <div style="background-color: #EFF6FF; border-left: 4px solid #3B82F6; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                        <p style="font-size: 12px; color: #1E3A8A; margin: 0;">
                            <i class="fas fa-info-circle mr-2 text-blue-500"></i> 
                            <strong>Informasi:</strong> Data di atas adalah akumulasi dari seluruh riwayat absensi dan tabungan atas nama <strong>{{ strtoupper($murid->nama_lengkap) }}</strong>.
                        </p>
                    </div>

                    <!-- ============================================== -->
                    <!-- FORM INPUT MANUAL CEPAT DARI PROFIL            -->
                    <!-- ============================================== -->
                    <div style="background: #0F172A; border-radius: 15px; padding: 25px; border: 1px solid #334155; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="color: #60A5FA; font-weight: bold; font-size: 16px; margin-bottom: 15px; border-bottom: 1px dashed #334155; padding-bottom: 10px;">
                            <i class="fas fa-edit mr-2"></i> Input Absen Manual Cepat
                        </h3>
                        
                        <form action="{{ route('rekap.manual') }}" method="POST"> 
                            @csrf
                            <!-- NIS otomatis di-hidden -->
                            <input type="hidden" name="nis" value="{{ $murid->nis }}">

                            <div style="margin-bottom: 15px;">
                                <label style="color: #9CA3AF; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Tanggal Absen</label>
                                <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required
                                       style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #475569; background: #1E293B; color: white; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='#3B82F6'" onblur="this.style.borderColor='#475569'">
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="color: #9CA3AF; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Status Kehadiran</label>
                                <select name="status" required class="form-select-manual"
                                        style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #475569; background: #1E293B; color: white; outline: none; cursor: pointer; transition: 0.3s;" onfocus="this.style.borderColor='#3B82F6'" onblur="this.style.borderColor='#475569'">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Alpha">Alpha / Bolos</option>
                                    <option value="Hadir Pagi">Hadir Pagi (Lupa Scan)</option>
                                </select>
                            </div>

                            <button type="submit" style="width: 100%; padding: 12px; background: #3B82F6; color: white; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" onmouseover="this.style.background='#2563EB'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='#3B82F6'; this.style.transform='translateY(0)';">
                                <i class="fas fa-save mr-2"></i> Simpan Status
                            </button>
                        </form>
                    </div>
                    <!-- ============================================== -->

                </div>

            </div>
        </div>
    </div>

    <!-- SCRIPT AJAIB UNTUK MENGUBAH HTML MENJADI GAMBAR PNG -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function downloadIDCard() {
            const btn = document.querySelector('.btn-download-id');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sedang Memproses...';
            btn.disabled = true;

            const cardArea = document.getElementById('printable-id-card');

            html2canvas(cardArea, {
                scale: 4, 
                useCORS: true, 
                backgroundColor: null 
            }).then(canvas => {
                const image = canvas.toDataURL("image/png");
                const link = document.createElement('a');
                link.download = "ID_CARD_{{ strtoupper($murid->nama_lengkap) }}.png";
                link.href = image;
                link.click();
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(err => {
                alert("Gagal mengunduh ID Card. Silakan coba lagi.");
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    </script>
</x-app-layout>