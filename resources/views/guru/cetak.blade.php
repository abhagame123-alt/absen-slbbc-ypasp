<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak ID Card Guru - SLB BC YPASP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* MANTRA AJAIB AGAR WARNA & TEKSTUR TETAP DICETAK */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #E2E8F0;
            margin: 0;
            padding: 20px;
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important;
        }

        .info-box {
            background-color: #EFF6FF; border-left: 5px solid #3B82F6;
            padding: 15px 20px; border-radius: 8px; max-width: 800px; margin: 0 auto 20px auto;
            color: #1E3A8A; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .info-box h4 { margin: 0 0 10px 0; display: flex; align-items: center; font-size: 16px; color: #1D4ED8; }
        .info-box ul { margin: 0; padding-left: 20px; }
        .info-box li { margin-bottom: 5px; }

        .header-print { text-align: center; margin-bottom: 30px; }
        .btn-print {
            background: linear-gradient(135deg, #3B82F6, #2563EB); color: white; border: none;
            padding: 15px 30px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); transition: 0.3s;
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.6); }

        .card-container {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(53.98mm, 1fr));
            gap: 15px; justify-items: center; max-width: 1000px; margin: 0 auto;
        }

        /* ========================================================
           DESAIN ID CARD GURU (TEMA BIRU NAVY)
           ======================================================== */
        .id-card-wrapper {
            width: 53.98mm !important; 
            height: 85.60mm !important; 
            box-sizing: border-box; 
            background: white; border-radius: 2.5mm; overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15); border: 1px solid #94A3B8;
            display: flex; flex-direction: column; position: relative;
            font-family: 'Segoe UI', Arial, sans-serif; 
        }
        
        .id-card-header {
            background: #0F172A !important; /* Biru Navy Sangat Gelap (Resmi) */
            color: white !important;
            padding: 5mm 2mm 4mm 2mm; 
            border-bottom: 2mm solid #3B82F6 !important; /* Garis Biru Terang */
            display: flex; align-items: center; justify-content: center; gap: 2.5mm;
            position: relative; z-index: 2;
        }
        
        .logo-sekolah-card { width: 11mm; height: 11mm; object-fit: contain; filter: drop-shadow(0px 1px 2px rgba(0,0,0,0.4)); }
        
        .header-text { text-align: left; }
        .header-text h3 { margin: 0; font-size: 13px; font-weight: 800; line-height: 1; letter-spacing: 0.2px; color: white !important;}
        .header-text p { margin: 2px 0 0 0; font-size: 6px; font-weight: 600; opacity: 0.9; letter-spacing: 0.8px; color: white !important;}
        
        .id-card-body {
            flex-grow: 1; padding: 2mm; text-align: center; background-color: #ffffff !important;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            position: relative; z-index: 2;
        }

        .watermark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -45%);
            width: 45mm; height: 45mm; opacity: 0.08; z-index: 1; pointer-events: none;
        }

        .qr-box {
            background: white !important; padding: 2mm; border-radius: 2mm; border: none; 
            display: inline-block; margin-bottom: 3mm; margin-top: -3mm; 
            box-shadow: 0 3px 6px rgba(0,0,0,0.1), inset 0 0 0 1px rgba(0,0,0,0.05) !important;
            position: relative; z-index: 3;
        }

        .student-name { font-size: 14px; font-weight: 800; color: #0F172A !important; margin: 0; line-height: 1.1; z-index: 3; position: relative;}
        .student-nis { font-size: 9px; font-weight: 700; color: #DC2626 !important; margin: 2px 0 3px 0; letter-spacing: 0.5px; z-index: 3; position: relative;}
        
        /* DESAIN KAPSUL GURU (Warna Biru) */
        .student-class { 
            display: inline-block;
            font-size: 8px; 
            font-weight: 800; 
            color: #1D4ED8 !important; /* Teks Biru Gelap */
            background: #DBEAFE !important; /* Background Biru Muda */
            border-radius: 10mm; 
            border: 1px solid #3B82F6 !important; /* Border Biru Terang */
            margin: 0 auto;
            
            /* Jurus Padding Asimetris Bos Hakim */
            padding: 0.5mm 4mm 3mm 4mm; 
            
            line-height: 1.2; 
            white-space: nowrap;
        }
        
        .id-card-footer {
            background: #0F172A !important; color: white !important; text-align: center;
            padding: 3mm 2mm; font-size: 6.5px; font-weight: 700; letter-spacing: 0.5px;
            position: relative; overflow: hidden; z-index: 2;
        }

        .microtext {
            position: absolute; top: 0; left: 0; width: 100%; font-size: 3px; 
            color: rgba(255,255,255,0.08) !important; white-space: nowrap; font-weight: normal;
        }

        /* --- PENGATURAN CETAK (KERTAS A4, 3 KOLOM RAPI) --- */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background-color: white !important; padding: 0; }
            .header-print, .info-box { display: none !important; }
            .card-container {
                display: grid !important; 
                grid-template-columns: repeat(3, 53.98mm) !important; 
                gap: 5mm !important; 
                justify-content: center !important;
            }
            .id-card-wrapper {
                box-shadow: none !important; border: 0.5px dashed #94A3B8 !important; page-break-inside: avoid !important; 
            }
        }
    </style>
</head>
<body>

    <div class="info-box">
        <h4><i class="fas fa-exclamation-triangle mr-2"></i> PENTING: Panduan Cetak ID Card Guru</h4>
        <ul>
            <li>Pastikan Anda menggunakan kertas berukuran <strong>A4</strong> (disarankan kertas PVC/Tebal).</li>
            <li>Centang opsi <strong>"Background Graphics"</strong> (Grafik Latar Belakang) agar warna biru elegan dan fitur keamanan tercetak sempurna.</li>
            <li>Ubah opsi <strong>"Scale"</strong> (Skala) menjadi <strong>Default / 100%</strong> agar ukuran kartu mutlak standar CR80 (53.98mm x 85.60mm).</li>
        </ul>
    </div>

    <div class="header-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print mr-2"></i> PRINT ID CARD GURU
        </button>
    </div>

    <div class="card-container">
        @foreach($guru as $g) <!-- Asumsi variabel lemparan dari controller adalah $guru -->
            <div class="id-card-wrapper">
                
                <div class="id-card-header">
                    <img src="{{ asset('logo.png') }}" alt="Logo" class="logo-sekolah-card" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Tut_Wuri_Handayani.svg/1200px-Tut_Wuri_Handayani.svg.png'">
                    <div class="header-text">
                        <h3>SLB BC YPASP</h3>
                        <p>KARTU IDENTITAS RESMI</p>
                    </div>
                </div>
                
                <div class="id-card-body">
                    <!-- WATERMARK -->
                    <img src="{{ asset('logo.png') }}" alt="Watermark" class="watermark" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Tut_Wuri_Handayani.svg/1200px-Tut_Wuri_Handayani.svg.png'">

                    <div class="qr-box">
                        <!-- Asumsi NIP/Kode guru dipakai untuk QR Code -->
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(80)->generate($g->nip) !!}
                    </div>
                    
                    <h2 class="student-name">{{ strtoupper($g->nama_guru) }}</h2>
                    <p class="student-nis">NIP: {{ $g->nip }}</p>
                    
                    <!-- KAPSUL JABATAN GURU -->
                    <span class="student-class">{{ strtoupper($g->jabatan ?? 'GURU') }}</span>
                </div>
                
                <div class="id-card-footer">
                    <div class="microtext">SLBBCYPASP-SLBBCYPASP-SLBBCYPASP-SLBBCYPASP-SLBBCYPASP-SLBBCYPASP</div>
                    HARAP DIBAWA SETIAP HARI
                </div>
            </div>
        @endforeach
    </div>

</body>
</html>