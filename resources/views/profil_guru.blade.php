<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight header-title">
                👨‍🏫 Profil & Raport Kehadiran Guru
            </h2>
            <a href="{{ route('guru.index') }}" style="background-color: #374151; color: white; padding: 8px 15px; border-radius: 8px; font-weight: bold; text-decoration: none;">
                ⬅️ Kembali ke Data Guru
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                
                <!-- BAGIAN KIRI: FOTO & KARTU GURU -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg stat-box" style="padding: 30px; text-align: center;">
                    
                    <!-- Menampilkan QR Code -->
                    <div id="qr-container" style="background-color: white; padding: 15px; border-radius: 10px; display: inline-block; margin-bottom: 10px; border: 2px solid #E5E7EB;">
                        {!! QrCode::size(150)->generate($guru->nip) !!}
                    </div>
                    
                    <!-- TOMBOL DOWNLOAD -->
                    <div style="margin-bottom: 20px;">
                        <button onclick="downloadQR()" style="background-color: #F59E0B; color: white; padding: 6px 15px; border-radius: 5px; border: none; font-weight: bold; cursor: pointer; font-size: 14px;">
                            📥 Download QR (PNG)
                        </button>
                    </div>

                    <h3 style="font-size: 24px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;" class="header-title">{{ $guru->nama_guru }}</h3>
                    <p style="font-size: 16px; color: #6B7280; font-weight: bold;">NIP: {{ $guru->nip }} | Jabatan: {{ $guru->jabatan ?? 'Guru' }}</p>
                </div>

                <!-- BAGIAN KANAN: STATISTIK ABSENSI -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg stat-box" style="padding: 30px;">
                    <h3 class="text-lg font-bold mb-6 header-title" style="border-bottom: 2px solid #E5E7EB; padding-bottom: 10px;">📊 Rekap Kehadiran Selama Ini</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        
                        <div style="background-color: #D1FAE5; padding: 20px; border-radius: 10px; border: 1px solid #A7F3D0; text-align: center;">
                            <h4 style="color: #059669; font-weight: bold; font-size: 16px; margin:0;">Hadir / Telat</h4>
                            <p style="font-size: 36px; font-weight: bold; color: #064E3B; margin: 10px 0 0 0;">{{ $hadir }}</p>
                        </div>

                        <div style="background-color: #FEF3C7; padding: 20px; border-radius: 10px; border: 1px solid #FDE68A; text-align: center;">
                            <h4 style="color: #D97706; font-weight: bold; font-size: 16px; margin:0;">Sakit</h4>
                            <p style="font-size: 36px; font-weight: bold; color: #92400E; margin: 10px 0 0 0;">{{ $sakit }}</p>
                        </div>

                        <div style="background-color: #F3E8FF; padding: 20px; border-radius: 10px; border: 1px solid #E9D5FF; text-align: center;">
                            <h4 style="color: #7E22CE; font-weight: bold; font-size: 16px; margin:0;">Izin</h4>
                            <p style="font-size: 36px; font-weight: bold; color: #5B21B6; margin: 10px 0 0 0;">{{ $izin }}</p>
                        </div>

                    </div>
                    
                    <div style="margin-top: 30px; padding: 15px; background-color: #EFF6FF; border-radius: 10px; border-left: 5px solid #3B82F6;">
                        <p style="color: #1E3A8A; font-size: 14px; margin: 0;">
                            💡 <b>Informasi:</b> Data di atas adalah akumulasi dari seluruh riwayat absensi atas nama <b>Bpk/Ibu {{ strtoupper($guru->nama_guru) }}</b> yang tercatat di sistem SLB BC YPASP.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MESIN DOWNLOAD QR CODE GURU -->
    <script>
        function downloadQR() {
            const svg = document.querySelector('#qr-container svg');
            if (!svg) { alert("QR Code belum termuat sempurna!"); return; }
            const svgData = new XMLSerializer().serializeToString(svg);
            
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            
            const img = new Image();
            img.onload = function() {
                canvas.width = img.width + 40; 
                canvas.height = img.height + 40;
                ctx.fillStyle = "white"; 
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 20, 20);
                
                const pngFile = canvas.toDataURL("image/png");
                const downloadLink = document.createElement("a");
                downloadLink.download = "QR_Code_Guru_{{ $guru->nama_guru }}.png";
                downloadLink.href = pngFile;
                downloadLink.click();
            };
            img.src = "data:image/svg+xml;base64," + btoa(unescape(encodeURIComponent(svgData)));
        }
    </script>
</x-app-layout>