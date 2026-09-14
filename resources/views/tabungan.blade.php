<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight header-title">
                {{ __('Buku Tabungan Siswa') }} 💰
            </h2>
        </div>
    </x-slot>

    <!-- CSS UNTUK TEMA GELAP -->
    <style>
        body, .bg-white, nav, header, .stat-box, .bg-gray-100 { transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease; }
        body.dark-mode .bg-gray-100 { background-color: #111827 !important; }
        body.dark-mode .bg-white, body.dark-mode nav, body.dark-mode header { background-color: #1F2937 !important; border-color: #374151 !important; }
        body.dark-mode .text-gray-900, body.dark-mode .text-gray-800, body.dark-mode .header-title { color: #F9FAFB !important; }
        body.dark-mode .input-field { background-color: #374151 !important; color: white !important; border-color: #4B5563 !important; }
        body.dark-mode th { background-color: #374151 !important; color: white !important; }
        body.dark-mode td { border-bottom-color: #374151 !important; }
        
        body.dark-mode .admin-filter-box { background-color: #1F2937 !important; border-color: #374151 !important; }
        body.dark-mode .admin-filter-text { color: #9CA3AF !important; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Alert Sukses -->
            @if(session('success'))
                <div style="background-color: #D1FAE5; color: #065F46; padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: bold; border-left: 5px solid #10B981;">
                    ✅ {{ session('success') }}
                </div>
            @endif

            <!-- ========================================================== -->
            <!-- SUPER FILTER: TANGGAL & GURU (MUNCUL UNTUK SEMUA) -->
            <!-- ========================================================== -->
            <div class="admin-filter-box bg-white overflow-hidden shadow-sm sm:rounded-lg" style="padding: 20px; border-radius: 15px; margin-bottom: 20px; border: 1px solid #E5E7EB;">
                <div style="margin-bottom: 15px;">
                    <h3 class="font-bold header-title" style="color: #10B981; font-size: 16px;">🔍 Saring Data Tabungan</h3>
                    <p class="text-sm admin-filter-text" style="color: #6B7280;">Cari riwayat transaksi berdasarkan periode waktu tertentu dan kelas.</p>
                </div>
                
                <form action="{{ route('tabungan') }}" method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <!-- Filter Tanggal Awal -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;" class="header-title">Tanggal Awal</label>
                        <input type="date" name="tgl_awal" value="{{ request('tgl_awal') }}" class="input-field" style="border: 1px solid #ccc; border-radius: 5px; padding: 10px;">
                    </div>
                    <!-- Filter Tanggal Akhir -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;" class="header-title">Tanggal Akhir</label>
                        <input type="date" name="tgl_akhir" value="{{ request('tgl_akhir') }}" class="input-field" style="border: 1px solid #ccc; border-radius: 5px; padding: 10px;">
                    </div>
                    
                    <!-- KOTAK PENCARIAN NAMA / NIS (DENGAN FITUR SELECT2) -->
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;" class="header-title">Cari Siswa</label>
                        <!-- Tambahkan id="cari-siswa" agar bisa dihubungkan dengan Select2 -->
                        <select name="nama_siswa" id="cari-siswa" class="input-field" style="border: 1px solid #ccc; border-radius: 5px; padding: 10px; min-width: 250px;">
                            <option value="">-- Semua Siswa --</option>
                            @foreach($daftar_murid as $m)
                                <!-- Value-nya kita isi NIS, teksnya kita tampilkan NIS & Nama -->
                                <option value="{{ $m->nis }}" {{ request('nama_siswa') == $m->nis ? 'selected' : '' }}>
                                    {{ $m->nis }} - {{ strtoupper($m->nama_lengkap) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Filter Guru (CUMA ADMIN YANG BISA LIHAT KOTAK INI) -->
                    @if($is_admin)
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;" class="header-title">Wali Kelas</label>
                        <select name="filter_guru" class="input-field" style="border: 1px solid #ccc; border-radius: 5px; padding: 10px; min-width: 150px;">
                            <option value="semua">-- Semua Kelas --</option>
                            @foreach($daftar_wali_kelas as $wali)
                                <option value="{{ $wali }}" {{ request('filter_guru') == $wali ? 'selected' : '' }}>
                                    Guru {{ ucwords($wali) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" style="background-color: #4F46E5; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; border: none; height: 42px;">
                            Cari Data
                        </button>
                        <a href="{{ route('tabungan') }}" style="background-color: #6B7280; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; text-decoration: none; display: flex; align-items: center; height: 42px;">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
            <!-- ========================================================== -->

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                
                <!-- KARTU SALDO TOTAL -->
                <div class="stat-box" style="background: linear-gradient(135deg, #4F46E5, #7C3AED); padding: 30px; border-radius: 15px; color: white; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                    <h3 style="font-size: 16px; font-weight: normal; opacity: 0.9;">{{ $judul_saldo }}</h3>
                    <p style="font-size: 36px; font-weight: bold; margin-top: 10px;">
                        Rp {{ number_format($total_saldo, 0, ',', '.') }}
                    </p>
                    <div style="margin-top: 20px; font-size: 14px; opacity: 0.8;">
                        {{ $sub_judul }}
                    </div>
                </div>

                <!-- FORM TRANSAKSI -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg stat-box" style="padding: 25px; border-radius: 15px;">
                    <h3 class="text-lg font-bold mb-4 header-title" style="color: #1F2937; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px;">➕ Transaksi Baru</h3>
                    <form action="{{ route('tabungan.store') }}" method="POST">
                        @csrf
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 5px;" class="header-title">Nama Siswa</label>
                                <select name="nis" id="pilih-siswa" required class="input-field" style="width: 100%; border: 1px solid #ccc; border-radius: 5px; padding: 10px;">                                    <option value="">-- Pilih Siswa --</option>
                                   @foreach($daftar_murid as $m)
                                        <option value="{{ $m->nis }}">{{ $m->nis }} - {{ strtoupper($m->nama_lengkap) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 5px;" class="header-title">Tanggal</label>
                                <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}" class="input-field" style="width: 100%; border: 1px solid #ccc; border-radius: 5px; padding: 10px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 5px;" class="header-title">Jenis</label>
                                <select name="jenis" required class="input-field" style="width: 100%; border: 1px solid #ccc; border-radius: 5px; padding: 10px;">
                                    <option value="Setor">🟢 Setor (Menabung)</option>
                                    <option value="Tarik">🔴 Tarik (Ambil Uang)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 5px;" class="header-title">Nominal (Rp)</label>
                                <input type="number" name="nominal" required placeholder="Contoh: 5000" class="input-field" style="width: 100%; border: 1px solid #ccc; border-radius: 5px; padding: 10px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 14px; font-weight: bold; margin-bottom: 5px;" class="header-title">Keterangan (Opsional)</label>
                            <input type="text" name="keterangan" placeholder="Contoh: Tabungan sisa jajan" class="input-field" style="width: 100%; border: 1px solid #ccc; border-radius: 5px; padding: 10px;">
                        </div>
                        <button type="submit" style="width: 100%; background-color: #4F46E5; color: white; padding: 12px; border-radius: 5px; font-weight: bold; cursor: pointer; border: none; font-size: 16px;">
                            Simpan Transaksi
                        </button>
                    </form>
                </div>

            </div>

            <!-- TABEL RIWAYAT -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg stat-box">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4 header-title" style="color: #1F2937;">📋 Riwayat Transaksi Terbaru</h3>
                    
                    <!-- TOMBOL CETAK EXCEL PINTAR (MENGIKUTI FILTER) -->
                    <div style="margin-bottom: 20px; text-align: left;">
                        <a href="{{ route('tabungan.export', request()->query()) }}" style="background-color: #10B981; color: white; padding: 8px 15px; border-radius: 5px; font-weight: bold; text-decoration: none; font-size: 14px;">
                            📥 Download Laporan (Excel)
                        </a>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background-color: #F3F4F6;">
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Tgl</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Nama Siswa</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Wali Kelas</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Jenis</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Nominal</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Keterangan</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($riwayat_tabungan as $t)
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 12px;">{{ \Carbon\Carbon::parse($t->tanggal)->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; font-weight: bold; text-transform: uppercase;">{{ $t->nama }}</td>
                                    <td style="padding: 12px; color: #4F46E5; font-weight: bold;">{{ ucwords($t->wali_kelas ?? '-') }}</td>
                                    <td style="padding: 12px; font-weight: bold; color: {{ $t->jenis == 'Setor' ? '#10B981' : '#EF4444' }};">
                                        {{ $t->jenis == 'Setor' ? '🟢 Setor' : '🔴 Tarik' }}
                                    </td>
                                    <td style="padding: 12px; font-weight: bold;">Rp {{ number_format($t->nominal, 0, ',', '.') }}</td>
                                    <td style="padding: 12px;">{{ $t->keterangan }}</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <form action="{{ route('tabungan.destroy', $t->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Hapus transaksi ini? Saldo akan otomatis menyesuaikan.')" style="background-color: #EF4444; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- SCRIPT TEMA GELAP -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggle = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            const body = document.body;

            if (localStorage.getItem('tema_aplikasi') === 'gelap') {
                body.classList.add('dark-mode');
                themeIcon.textContent = '☀️';
                themeText.textContent = 'Mode Terang';
                if(btnToggle) btnToggle.style.backgroundColor = '#F59E0B'; 
            }

            if(btnToggle) {
                btnToggle.addEventListener('click', () => {
                    body.classList.toggle('dark-mode');
                    if (body.classList.contains('dark-mode')) {
                        localStorage.setItem('tema_aplikasi', 'gelap');
                        themeIcon.textContent = '☀️';
                        themeText.textContent = 'Mode Terang';
                        btnToggle.style.backgroundColor = '#F59E0B';
                    } else {
                        localStorage.setItem('tema_aplikasi', 'terang');
                        themeIcon.textContent = '🌙';
                        themeText.textContent = 'Mode Gelap';
                        btnToggle.style.backgroundColor = '#374151';
                    }
                });
            }
        });
    </script>
    
    <!-- MESIN SELECT2 (UNTUK FITUR KETIK & CARI NAMA SISWA) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#pilih-siswa').select2({
                placeholder: "Ketik Nama atau NIS...",
                width: '100%'
            });
        });
    </script>

    <style>
        .select2-container--default .select2-selection--single { height: 42px !important; display: flex; align-items: center; border: 1px solid #ccc; border-radius: 5px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; }
        
        body.dark-mode .select2-container--default .select2-selection--single { background-color: #374151 !important; border-color: #4B5563 !important; }
        body.dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered { color: white !important; }
        body.dark-mode .select2-dropdown { background-color: #374151 !important; border-color: #4B5563 !important; color: white !important; }
        body.dark-mode .select2-search__field { background-color: #1F2937 !important; color: white !important; border: 1px solid #4B5563 !important; }
        body.dark-mode .select2-results__option--highlighted[aria-selected] { background-color: #4F46E5 !important; }
    </style>
</x-app-layout>