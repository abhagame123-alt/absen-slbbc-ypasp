<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data Guru SLB BC YPASP') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                <h3 class="text-lg font-bold mb-4" style="color: #2563EB;">Tambah Data Guru Baru & Buat Akun</h3>
                
                <!-- Tambah autocomplete="off" agar browser tidak sok tahu -->
                <form action="{{ route('guru.store') }}" method="POST" autocomplete="off" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    @csrf
                    <!-- Data Profil -->
                    <input type="text" name="nip" placeholder="NIP / Kode Guru" required autocomplete="off" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f9fafb;">
                    
                    <input type="text" name="nama_guru" placeholder="Nama Lengkap Guru" required autocomplete="off" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1; background-color: #f9fafb;">
                    
                    <!-- KOTAK JABATAN DIUBAH JADI DROPDOWN -->
                    <select name="jabatan" style="padding: 8px 10px; border-radius: 4px; border: 1px solid #ccc; background-color: #f9fafb; outline: none; cursor: pointer; height: 42px; min-width: 150px;">
                        <option value="">-- Tanpa Jabatan --</option>
                        <option value="Wali Kelas">Wali Kelas</option>
                        <option value="Guru Mapel">Guru Mapel</option>
                        <option value="Kepala Sekolah">Kepala Sekolah</option>
                        <option value="Staf Tata Usaha">Staf Tata Usaha</option>
                        <option value="Operator">Operator</option>
                    </select>
                    
                    <!-- Data Akun Login (Anti Auto-Fill) -->
                    <input type="email" name="email" placeholder="Email Login Guru" required autocomplete="new-password" style="padding: 8px; border: 1px solid #2563EB; border-radius: 4px; background-color: #f9fafb;">
                    
                    <!-- Fitur Lihat Password dengan Ikon Mata -->
                    <div style="position: relative; display: inline-block;">
                        <input type="password" id="inputSandi" name="password" placeholder="Password (Min. 8 Huruf)" required minlength="8" autocomplete="new-password" style="padding: 8px; padding-right: 35px; border: 1px solid #2563EB; border-radius: 4px; background-color: #f9fafb;">
                        <span onclick="lihatSandi()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none;" title="Tampilkan Sandi">👁️</span>
                    </div>
                    
                    <button type="submit" style="background-color: #2563EB; color: white; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; height: 42px;">Simpan & Buat Akun</button>
                </form>

                <!-- Mesin Kecil untuk Buka/Tutup Password -->
                <script>
                    function lihatSandi() {
                        var x = document.getElementById("inputSandi");
                        if (x.type === "password") {
                            x.type = "text";
                        } else {
                            x.type = "password";
                        }
                    }
                </script>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <!-- PESAN ERROR JIKA LUPA CENTANG -->
                @if (session('error'))
                    <div style="background-color: #FEE2E2; color: #B91C1C; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #EF4444;">
                        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                    </div>
                @endif
                
                <!-- FORM RAHASIA UNTUK CETAK TERPILIH -->
                <form id="formCetakGuruTerpilih" action="{{ route('guru.cetak.terpilih') }}" method="POST" target="_blank">
                    @csrf
                </form>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;">
                    <!-- TOMBOL CETAK SEMUA (Bawaan) -->
                    <a href="{{ route('guru.cetak') }}" target="_blank" style="background-color: #10B981; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; display: flex; align-items: center;">
                        🖨️ Cetak Semua QR Code Guru
                    </a>
                    
                    <!-- TOMBOL BARU: CETAK TERPILIH (Warna Oranye) -->
                    <button type="submit" form="formCetakGuruTerpilih" style="background-color: #F59E0B; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; display: flex; align-items: center;">
                        🖨️ Cetak Terpilih
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; color: white;">
                        <thead>
                            <tr style="background-color: #374151; border-bottom: 2px solid #4B5563;">
                                <!-- CHECKBOX PILIH SEMUA -->
                                <th style="padding: 12px; width: 40px; text-align: center;">
                                    <input type="checkbox" id="check-all-guru" style="width: 16px; height: 16px; cursor: pointer;">
                                </th>
                                <th style="padding: 12px;">No</th>
                                <th style="padding: 12px;">NIP</th>
                                <th style="padding: 12px;">Nama Guru</th>
                                <th style="padding: 12px;">Jabatan</th>
                                <!-- 🔥 KOLOM BARU: EMAIL LOGIN 🔥 -->
                                <th style="padding: 12px;">Email Login</th>
                                <th style="padding: 12px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gurus as $index => $guru)
                            
                            <!-- 🔥 DETEKTIF PENCARI EMAIL 🔥 -->
                            @php
                                $akun = \App\Models\User::where('name', $guru->nama_guru)->first();
                                $email_guru = $akun ? $akun->email : '-';
                            @endphp

                            <tr style="border-bottom: 1px solid #4B5563;">
                                <!-- CHECKBOX UNTUK MASING-MASING GURU -->
                                <td style="padding: 12px; text-align: center;">
                                    <input type="checkbox" name="ids[]" value="{{ $guru->id }}" class="check-item-guru" form="formCetakGuruTerpilih" style="width: 16px; height: 16px; cursor: pointer;">
                                </td>
                                
                                <td style="padding: 12px;">{{ $index + 1 }}</td>
                                <td style="padding: 12px; font-weight: bold;">{{ $guru->nip }}</td>
                                <td style="padding: 12px; text-transform: uppercase;">{{ $guru->nama_guru }}</td>
                                <td style="padding: 12px;">{{ $guru->jabatan ?? '-' }}</td>
                                
                                <!-- 🔥 TAMPILAN EMAIL LOGIN 🔥 -->
                                <td style="padding: 12px; color: #9CA3AF;">{{ $email_guru }}</td>

                                <td style="padding: 12px; text-align: center;">
                                    <a href="{{ route('guru.profil', $guru->nip) }}" style="background-color: #3B82F6; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 5px;">👤 Profil</a>

                                    <a href="{{ route('guru.edit', $guru->id) }}" style="background-color: #F59E0B; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 5px;">Edit</a>
                                    
                                    <!-- FORM HAPUS -->
                                    <form action="{{ route('guru.destroy', $guru->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Yakin ingin menghapus data guru ini?')" style="background-color: #EF4444; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;">Hapus</button>
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
    
    <!-- SCRIPT CHECK ALL GURU -->
    <script>
        document.getElementById('check-all-guru').addEventListener('change', function() {
            let checkboxes = document.querySelectorAll('.check-item-guru');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    </script>
</x-app-layout>