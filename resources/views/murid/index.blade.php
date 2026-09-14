<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Murid') }}
        </h2>
        
        <!-- TAMBAHKAN BARIS INI UNTUK MEMANGGIL IKON -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg stat-box">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4 header-title">Data Murid SLB BC YPASP</h3>

                    <!-- PESAN ERROR JIKA LUPA CENTANG -->
                    @if (session('error'))
                        <div style="background-color: #FEE2E2; color: #B91C1C; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #EF4444;">
                            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                        </div>
                    @endif
                    
                    <!-- FORM CETAK (DITARUH DI LUAR TABEL BIAR GAK BENTROK) -->
                    <form id="formCetakTerpilih" action="{{ route('murid.cetak.terpilih') }}" method="POST" target="_blank">
                        @csrf
                    </form>

                    <!-- CONTAINER TOMBOL AKSI & KOTAK PENCARIAN -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                        
                        <!-- DERETAN TOMBOL KIRI -->
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="{{ route('murid.create') }}" style="background-color: #10B981; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; display: flex; align-items: center;">
                                + Tambah Murid
                            </a>
                            
                            <a href="{{ route('murid.cetak') }}" target="_blank" style="background-color: #3B82F6; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; display: flex; align-items: center;">
                                🖨️ Cetak Semua Kartu
                            </a>

                            <button type="submit" form="formCetakTerpilih" style="background-color: #F59E0B; color: white; padding: 8px 16px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; display: flex; align-items: center;">
                                🖨️ Cetak Terpilih
                            </button>
                        </div>

                        <!-- KOTAK PENCARIAN KANAN -->
                        <div style="position: relative; width: 100%; max-width: 300px;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9CA3AF;">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="kotakCari" placeholder="Cari Nama atau NIS Siswa..." 
                                   style="width: 100%; padding: 10px 10px 10px 35px; border-radius: 8px; border: 1px solid #4B5563; background-color: #1F2937; color: white; outline: none; transition: 0.3s;"
                                   onfocus="this.style.borderColor='#3B82F6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.3)';"
                                   onblur="this.style.borderColor='#4B5563'; this.style.boxShadow='none';">
                        </div>

                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background-color: #f3f4f6;">
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd; width: 40px; text-align: center;">
                                        <input type="checkbox" id="check-all" style="width: 16px; height: 16px; cursor: pointer;">
                                    </th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">No</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">NIS</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Nama Lengkap</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Kelas</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">No. WA Ortu</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">QR Code</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #ddd; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($murids as $index => $murid)
                                <tr style="border-bottom: 1px solid #ddd;" class="baris-murid">
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" name="ids[]" value="{{ $murid->id }}" class="check-item" form="formCetakTerpilih" style="width: 16px; height: 16px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 12px;">{{ $index + 1 }}</td>
                                    <td style="padding: 12px;">{{ $murid->nis }}</td>
                                    <td style="padding: 12px; text-transform: uppercase;">{{ $murid->nama_lengkap }}</td>
                                    <td style="padding: 12px;">{{ $murid->kelas }}</td>
                                    <td style="padding: 12px; font-weight: bold; color: #059669;">{{ $murid->no_wa_ortu ?? '-' }}</td>
                                    <td style="padding: 12px;">
                                        {!! QrCode::size(60)->generate($murid->nis) !!}
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        
                                        <a href="{{ route('murid.profil', $murid->nis) }}" style="background-color: #3B82F6; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; margin-right: 5px;">👤 Profil</a>
                                        
                                        <a href="{{ route('murid.edit', $murid->id) }}" style="background-color: #F59E0B; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; margin-right: 5px;">Edit</a>
                                        
                                        <form action="{{ route('murid.destroy', $murid->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="background-color: #EF4444; color: white; padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 14px;" onclick="return confirm('Yakin ingin menghapus murid ini?')">Hapus</button>
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

    <!-- SCRIPT CHECK ALL -->
    <script>
        document.getElementById('check-all').addEventListener('change', function() {
            let checkboxes = document.querySelectorAll('.check-item');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    </script>

    <!-- SCRIPT PENCARIAN REAL-TIME -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kotakCari = document.getElementById('kotakCari');
            const barisMurid = document.querySelectorAll('.baris-murid');

            if(kotakCari && barisMurid.length > 0) {
                kotakCari.addEventListener('keyup', function() {
                    const kataKunci = this.value.toLowerCase();

                    barisMurid.forEach(baris => {
                        const isiTeksBaris = baris.textContent.toLowerCase();
                        
                        // Cek apakah teks di baris ini mengandung kata kunci yang diketik
                        if (isiTeksBaris.includes(kataKunci)) {
                            baris.style.display = ''; // Tampilkan
                        } else {
                            baris.style.display = 'none'; // Sembunyikan
                        }
                    });
                });
            }
        });
    </script>
</x-app-layout>