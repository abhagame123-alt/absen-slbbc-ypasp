<x-app-layout>
    <x-slot name="header">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-white leading-tight">
                <i class="fas fa-list-alt text-blue-500 mr-2"></i> {{ $judul }}
            </h2>
            
            <!-- TOMBOL CETAK & KEMBALI -->
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" style="background: #10B981; color: white; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: bold; transition: 0.3s; border: none; cursor: pointer; display: flex; align-items: center;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10B981'">
                    <i class="fas fa-print mr-2"></i> Cetak Data
                </button>
                <a href="{{ route('dashboard') }}" style="background: #374151; color: white; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: bold; text-decoration: none; transition: 0.3s; border: 1px solid #4B5563; display: flex; align-items: center;" onmouseover="this.style.background='#4B5563'" onmouseout="this.style.background='#374151'">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <!-- CSS PREMIUM & MODE CETAK KERTAS -->
    <style>
        .premium-container { background-color: #1E293B; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid #334155; padding: 30px; }
        .table-premium { width: 100%; text-align: left; border-collapse: collapse; }
        .table-premium th { background-color: #0F172A; color: #94A3B8; font-weight: bold; padding: 15px; font-size: 14px; border-bottom: 2px solid #334155; }
        .table-premium td { padding: 15px; border-bottom: 1px solid #334155; color: #F1F5F9; font-size: 14px; }
        
        /* Efek Link Profil Interaktif */
        .nama-link { color: #60A5FA; text-decoration: none; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 6px; }
        .nama-link:hover { background-color: rgba(59, 130, 246, 0.1); color: #93C5FD; text-shadow: 0 0 10px rgba(96, 165, 250, 0.3); }
        .nama-link i { font-size: 10px; margin-left: 8px; opacity: 0; transition: 0.3s; transform: translateX(-5px); }
        .nama-link:hover i { opacity: 1; transform: translateX(0); }
        
        /* SETTINGAN KHUSUS PRINTER (Biar kalau dicetak hitam putih rapi) */
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; color: black !important; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; }
            .premium-container { background: white !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
            .table-premium th { background-color: #f3f4f6 !important; border-bottom: 2px solid #000 !important; }
            .table-premium td { border-bottom: 1px solid #ddd !important; }
            .nama-link i { display: none !important; }
            .print-header { display: block !important; margin-bottom: 20px; text-align: center; font-size: 20px; font-weight: bold; text-transform: uppercase; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="premium-container print-area">
                
                <!-- Judul ini hanya muncul kalau dicetak di kertas -->
                <div class="print-header" style="display: none;">
                    Laporan {{ $judul }}<br>
                    <span style="font-size: 12px; font-weight: normal;">Dicetak pada: {{ date('d F Y - H:i') }}</span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>{{ $tipe == 'murid' ? 'NIS' : 'NIP' }}</th>
                                <th>Nama Lengkap</th>
                                <th>Status Kehadiran</th>
                                <th>Waktu Scan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $i => $d)
                                @php
                                    // Antisipasi nama variabel dari database
                                    $nama = $d->nama ?? ($d->nama_lengkap ?? ($d->nama_guru ?? '-'));
                                    $kode = $d->nis ?? ($d->nip ?? '-');
                                    
                                    // Pewarnaan Badge Status Otomatis
                                    $status_text = strtoupper($d->status ?? '-');
                                    $status_color = 'text-gray-400';
                                    if (str_contains(strtolower($status_text), 'hadir')) $status_color = 'text-green-400';
                                    elseif (str_contains(strtolower($status_text), 'alpha') || str_contains(strtolower($status_text), 'bolos')) $status_color = 'text-red-400';
                                    elseif (str_contains(strtolower($status_text), 'sakit')) $status_color = 'text-orange-400';
                                    elseif (str_contains(strtolower($status_text), 'izin')) $status_color = 'text-yellow-400';
                                @endphp
                                <tr style="transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(59, 130, 246, 0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $kode }}</td>
                                    <td>
                                        @if($tipe == 'murid')
                                            <!-- LINK AJAIB KE HALAMAN PROFIL SISWA -->
                                            <a href="{{ route('murid.profil', $kode) }}" class="nama-link">
                                                {{ strtoupper($nama) }}
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        @else
                                            <span class="font-bold text-white">{{ strtoupper($nama) }}</span>
                                        @endif
                                    </td>
                                    <td class="font-bold {{ $status_color }}">{{ $status_text }}</td>
                                    <td>{{ $d->waktu ?? '-' }}</td>
                                </tr>
                            @endforeach
                            
                            @if(count($data) == 0)
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #64748B;">
                                        <i class="fas fa-box-open text-4xl mb-3"></i><br>
                                        Tidak ada data yang ditemukan.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>