<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- ======================================================= -->
        <!-- SWEETALERT GLOBAL (Dipanggil sekali untuk semua halaman)-->
        <!-- ======================================================= -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- ======================================================= -->
        <!-- MESIN NOTIFIKASI PINTAR AUTO-CLOSE (GLOBAL)             -->
        <!-- ======================================================= -->
        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'BERHASIL!',
                        text: "{!! session('success') !!}",
                        showConfirmButton: false, // Tombol OK dihilangkan
                        timer: 2500, // Hilang dalam 2.5 detik
                        timerProgressBar: true
                    });
                });
            </script>
        @endif

        @if(session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'GAGAL!',
                        text: "{!! session('error') !!}",
                        showConfirmButton: false, // Tombol OK dihilangkan juga
                        timer: 3000, // Hilang dalam 3 detik (agak lama biar bisa dibaca)
                        timerProgressBar: true
                    });
                });
            </script>
        @endif
        <!-- ======================================================= -->

    </body>
    
    <!-- ======================================================= -->
    <!-- MESIN TEMA GELAP GLOBAL (BERLAKU UNTUK SEMUA HALAMAN)   -->
    <!-- ======================================================= -->
    <style>
        /* Transisi halus saat ganti tema */
        body, .bg-white, .bg-gray-100, nav, header { transition: background-color 0.4s ease, color 0.4s ease; }
        
        /* Warna Dasar Mode Gelap */
        body.dark-mode { background-color: #111827 !important; color: #F3F4F6 !important; }
        body.dark-mode .bg-gray-100 { background-color: #111827 !important; }
        body.dark-mode .bg-white, body.dark-mode nav.bg-white { background-color: #1F2937 !important; border-color: #374151 !important; }
        body.dark-mode .text-gray-900, body.dark-mode .text-gray-800, body.dark-mode .text-gray-500 { color: #F9FAFB !important; }
        
        /* Paksa Semua Tabel menjadi Gelap (Menimpa warna bawaan) */
        body.dark-mode table tr { background-color: transparent !important; }
        body.dark-mode table thead tr, body.dark-mode table th { background-color: #374151 !important; color: #FFF !important; border-color: #4B5563 !important; }
        body.dark-mode table td { color: #F3F4F6 !important; border-color: #374151 !important; }
        
        /* Paksa Input Form menjadi Gelap */
        body.dark-mode input, body.dark-mode select { background-color: #374151 !important; color: white !important; border: 1px solid #4B5563 !important; }
    </style>

    <script>
        // 1. Cek memori browser saat pindah-pindah halaman
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('tema_aplikasi') === 'gelap') {
                document.body.classList.add('dark-mode');
                updateTombolGlobal('gelap');
            }
        });

        // 2. Fungsi saklar (dipanggil saat tombol diklik)
        function toggleGlobalTheme() {
            const body = document.body;
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('tema_aplikasi', 'gelap');
                updateTombolGlobal('gelap');
            } else {
                localStorage.setItem('tema_aplikasi', 'terang');
                updateTombolGlobal('terang');
            }
        }

        // 3. Sinkronisasi warna & teks tombol di menu atas
        function updateTombolGlobal(mode) {
            const icons = document.querySelectorAll('.g-icon');
            const texts = document.querySelectorAll('.g-text');
            const btns = document.querySelectorAll('.g-btn');
            
            if (mode === 'gelap') {
                icons.forEach(el => el.textContent = '☀️');
                texts.forEach(el => el.textContent = 'Terang');
                btns.forEach(el => el.style.backgroundColor = '#F59E0B');
            } else {
                icons.forEach(el => el.textContent = '🌙');
                texts.forEach(el => el.textContent = 'Gelap');
                btns.forEach(el => el.style.backgroundColor = '#374151');
            }
        }
    </script>
</html>