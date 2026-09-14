<x-app-layout>
    <x-slot name="header">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- HEADER DENGAN TOMBOL KEMBALI DI KANAN ATAS -->
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-header-title leading-tight transition-colors duration-300">
                <i class="fas fa-book-open text-emerald-500 mr-2"></i> {{ __('Buku Rekap Absensi') }}
            </h2>
            
            <a href="{{ route('dashboard') }}" class="btn-kembali">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <style>
        /* ==============================================================
           TEMA HIJAU SEGAR (LIGHT MODE - DEFAULT)
           ============================================================== */
        .text-header-title { color: #065F46; } /* Hijau Tua */
        .btn-kembali { background: #F3F4F6; color: #374151; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: bold; text-decoration: none; transition: 0.3s; border: 1px solid #D1D5DB; display: flex; align-items: center; }
        .btn-kembali:hover { background: #E5E7EB; color: #111827; }

        .premium-container { background-color: #FFFFFF; border-radius: 16px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.08); border: 1px solid #A7F3D0; padding: 30px; margin-bottom: 30px; transition: all 0.3s; }
        .judul-kotak { color: #059669; }
        
        .label-input { color: #374151; font-weight: 700; font-size: 14px; margin-bottom: 8px; display: block; }
        .input-premium { background-color: #F9FAFB; border: 1px solid #D1D5DB; color: #1F2937; border-radius: 8px; padding: 10px 15px; width: 100%; transition: 0.3s; }
        .input-premium:focus { border-color: #10B981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); outline: none; }
        
        .btn-simpan { background: linear-gradient(135deg, #10B981, #059669); color: white; padding: 10px 25px; border-radius: 8px; font-weight: bold; transition: 0.3s; border: none; cursor: pointer; height: 100%; width: 100%; }
        .btn-simpan:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        
        .btn-cetak { background: #10B981; color: white; padding: 8px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; transition: 0.3s; border: none; cursor: pointer; }
        .btn-cetak:hover { background: #059669; transform: translateY(-2px); }
        
        /* Filter Tanggal */
        .filter-box { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; background: #ECFDF5; padding: 10px; border-radius: 8px; border: 1px solid #D1FAE5; transition: all 0.3s;}
        .filter-label { font-size: 11px; color: #065F46; font-weight: bold; margin-bottom: 2px; }
        .filter-input { background: #FFFFFF; color: #1F2937; border: 1px solid #A7F3D0; border-radius: 6px; padding: 5px 10px; font-size: 13px; outline: none; transition: 0.3s;}
        .filter-input:focus { border-color: #10B981; }

        /* Tabel */
        .table-premium { width: 100%; text-align: left; border-collapse: collapse; margin-top: 15px; }
        .table-premium th { background-color: #F0FDF4; color: #065F46; font-weight: bold; padding: 15px; font-size: 14px; border-bottom: 2px solid #6EE7B7; }
        .table-premium td { padding: 15px; border-bottom: 1px solid #E5E7EB; color: #374151; font-size: 14px; }
        .table-premium tr:hover { background-color: #F9FAFB; }
        
        .btn-batal { background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; transition: 0.3s; cursor: pointer; }
        .btn-batal:hover { background: #EF4444; color: white; }

        /* Status Warna */
        .status-hadir { color: #059669; font-weight: bold; }
        .status-terlambat { color: #DC2626; font-weight: bold; }
        .status-sakit { color: #EA580C; font-weight: bold; }
        .status-izin { color: #7C3AED; font-weight: bold; }
        .status-default { color: #6B7280; font-weight: bold; }

        /* ==============================================================
           TEMA GELAP ELEGAN (DARK MODE)
           ============================================================== */
        body.dark-mode .text-header-title { color: #F8FAFC; }
        body.dark-mode .btn-kembali { background: #374151; color: white; border-color: #4B5563; }
        body.dark-mode .btn-kembali:hover { background: #4B5563; }

        body.dark-mode .premium-container { background-color: #1E293B; border-color: #064E3B; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        body.dark-mode .judul-kotak { color: #34D399; }
        
        body.dark-mode .label-input { color: #9CA3AF; }
        body.dark-mode .input-premium { background-color: #0F172A; border-color: #334155; color: white; }
        
        body.dark-mode .filter-box { background: #0F172A; border-color: #064E3B; }
        body.dark-mode .filter-label { color: #A7F3D0; }
        body.dark-mode .filter-input { background: #1E293B; color: white; border-color: #064E3B; }

        body.dark-mode .table-premium th { background-color: #0F172A; color: #34D399; border-bottom-color: #064E3B; }
        body.dark-mode .table-premium td { border-bottom-color: #334155; color: #F1F5F9; }
        body.dark-mode .table-premium tr:hover { background-color: rgba(16, 185, 129, 0.05); }

        body.dark-mode .status-hadir { color: #34D399; }
        body.dark-mode .status-terlambat { color: #F87171; }
        body.dark-mode .status-sakit { color: #FBBF24; }
        body.dark-mode .status-izin { color: #A78BFA; }
        body.dark-mode .status-default { color: #9CA3AF; }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div style="background-color: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 15px; border-radius: 10px; margin-bottom: 25px; font-weight: bold;" class="dark:bg-emerald-900 dark:border-emerald-500 dark:text-emerald-200">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="background-color: #FEF2F2; border: 1px solid #EF4444; color: #991B1B; padding: 15px; border-radius: 10px; margin-bottom: 25px; font-weight: bold;" class="dark:bg-red-900 dark:border-red-500 dark:text-red-200">
                    <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                </div>
            @endif

            <!-- KOTAK INPUT MANUAL (SEKARANG PAKAI DROPDOWN NAMA) -->
            <div class="premium-container">
                <h3 class="font-bold text-lg mb-4 judul-kotak"><i class="fas fa-edit mr-2"></i> Input Absen Manual</h3>
                
                <form action="{{ route('rekap.manual') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-1">
                            <label class="label-input">Pilih Nama Siswa</label>
                            <select name="nis" class="input-premium" required>
                                <option value="">-- Pilih Siswa --</option>
                                @foreach($daftar_murid as $m)
                                    <option value="{{ $m->nis }}">{{ $m->nis }} - {{ strtoupper($m->nama_lengkap) }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="label-input">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="input-premium" required>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="label-input">Status Kehadiran</label>
                            <select name="status" class="input-premium" required>
                                <option value="Sakit">Sakit 🤒</option>
                                <option value="Izin">Izin 📩</option>
                                <!-- TAMBAHAN OPSI BARU -->
                                <option value="Terlambat">Terlambat ⏰</option>
                                <option value="Hadir">Hadir (Lupa Scan) ✅</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-1" style="height: 46px;">
                            <button type="submit" class="btn-simpan">
                                <i class="fas fa-plus mr-2"></i> Simpan Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- KOTAK RIWAYAT MURID -->
            <div class="premium-container">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <h3 class="font-bold text-lg judul-kotak"><i class="fas fa-user-graduate mr-2"></i> Riwayat Kehadiran MURID</h3>
                    
                    <!-- TOMBOL CETAK MURID DENGAN FILTER TANGGAL -->
                    <form action="{{ route('rekap.export', 'murid') }}" method="GET" class="filter-box">
                        <div style="display: flex; flex-direction: column;">
                            <label class="filter-label">Dari Tanggal:</label>
                            <input type="date" name="tgl_awal" value="{{ date('Y-m-01') }}" required class="filter-input">
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label class="filter-label">Sampai Tanggal:</label>
                            <input type="date" name="tgl_akhir" value="{{ date('Y-m-t') }}" required class="filter-input">
                        </div>
                        <button type="submit" class="btn-cetak">
                            <i class="fas fa-file-excel mr-2"></i> Cetak Excel
                        </button>
                    </form>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Jam Hadir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($absen_murid as $i => $a)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $a->nis }}</td>
                                <td class="font-bold">{{ strtoupper($a->nama) }}</td>
                                <td>{{ $a->kelas }}</td>
                                <td>
                                    @if(str_contains(strtolower($a->status), 'hadir'))
                                        <span class="status-hadir"><i class="fas fa-check-circle mr-1"></i> {{ $a->status }}</span>
                                    @elseif(str_contains(strtolower($a->status), 'terlambat'))
                                        <span class="status-terlambat"><i class="fas fa-clock mr-1"></i> {{ $a->status }}</span>
                                    @elseif(str_contains(strtolower($a->status), 'sakit'))
                                        <span class="status-sakit"><i class="fas fa-briefcase-medical mr-1"></i> {{ $a->status }}</span>
                                    @elseif(str_contains(strtolower($a->status), 'izin'))
                                        <span class="status-izin"><i class="fas fa-envelope mr-1"></i> {{ $a->status }}</span>
                                    @else
                                        <span class="status-default">{{ $a->status }}</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($a->tanggal)->translatedFormat('d F Y') }}</td>
                                <td>{{ $a->waktu }} WIB</td>
                                <td>
                                    <form action="{{ route('rekap.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan absen ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-batal">Batalkan</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @if(count($absen_murid) == 0)
                                <tr><td colspan="8" style="text-align: center; padding: 30px;" class="text-gray-500">Belum ada riwayat absensi murid.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- KOTAK RIWAYAT GURU (HANYA MUNCUL JIKA ADMIN) -->
            @if($is_admin)
            <div class="premium-container">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <h3 class="font-bold text-lg judul-kotak"><i class="fas fa-chalkboard-teacher mr-2"></i> Riwayat Kehadiran GURU</h3>
                    
                    <!-- TOMBOL CETAK GURU DENGAN FILTER TANGGAL -->
                    <form action="{{ route('rekap.export', 'guru') }}" method="GET" class="filter-box">
                        <div style="display: flex; flex-direction: column;">
                            <label class="filter-label">Dari Tanggal:</label>
                            <input type="date" name="tgl_awal" value="{{ date('Y-m-01') }}" required class="filter-input">
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label class="filter-label">Sampai Tanggal:</label>
                            <input type="date" name="tgl_akhir" value="{{ date('Y-m-t') }}" required class="filter-input">
                        </div>
                        <button type="submit" class="btn-cetak">
                            <i class="fas fa-file-excel mr-2"></i> Cetak Excel
                        </button>
                    </form>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama Guru</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Jam Hadir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($absen_guru as $i => $a)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $a->nis }}</td>
                                <td class="font-bold">{{ strtoupper($a->nama) }}</td>
                                <td>
                                    @if(str_contains(strtolower($a->status), 'hadir'))
                                        <span class="status-hadir"><i class="fas fa-check-circle mr-1"></i> {{ $a->status }}</span>
                                    @elseif(str_contains(strtolower($a->status), 'terlambat'))
                                        <span class="status-terlambat"><i class="fas fa-clock mr-1"></i> {{ $a->status }}</span>
                                    @else
                                        <span class="status-sakit">{{ $a->status }}</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($a->tanggal)->translatedFormat('d F Y') }}</td>
                                <td>{{ $a->waktu }} WIB</td>
                                <td>
                                    <form action="{{ route('rekap.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Yakin membatalkan absen guru ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-batal">Batalkan</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @if(count($absen_guru) == 0)
                                <tr><td colspan="7" style="text-align: center; padding: 30px;" class="text-gray-500">Belum ada riwayat absensi guru.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>