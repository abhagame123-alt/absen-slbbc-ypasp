<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Data Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" style="max-width: 600px; margin: 0 auto; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                
                <div class="p-6 text-gray-900" style="background-color: #1F2937;">
                    
                    <h3 class="text-lg font-bold mb-6" style="color: #F59E0B; border-bottom: 2px solid #374151; padding-bottom: 10px;">
                        Ubah Data Guru
                    </h3>
                    
                    <!-- MATIKAN AUTOCOMPLETE AGAR BROWSER TIDAK MENGISI PASSWORD SENDIRI -->
                    <form action="{{ route('guru.update', $guru->id) }}" method="POST" autocomplete="off">
                        @csrf
                        @method('PUT')
                        
                        <!-- KOLOM NIP -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #D1D5DB;">NIP / Kode Guru</label>
                            <input type="text" name="nip" value="{{ old('nip', $guru->nip) }}" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #4B5563; background-color: #374151; color: white; outline: none;">
                        </div>

                        <!-- KOLOM NAMA -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #D1D5DB;">Nama Lengkap Guru</label>
                            <input type="text" name="nama_guru" value="{{ old('nama_guru', $guru->nama_guru) }}" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #4B5563; background-color: #374151; color: white; outline: none;">
                        </div>

                        <!-- KOLOM JABATAN DROPDOWN -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #D1D5DB;">Jabatan</label>
                            
                            <select name="jabatan" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #4B5563; background-color: #374151; color: white; outline: none; cursor: pointer;">
                                <option value="" {{ (old('jabatan', $guru->jabatan) == '') ? 'selected' : '' }}>-- Tanpa Jabatan --</option>
                                <option value="Wali Kelas" {{ (old('jabatan', $guru->jabatan) == 'Wali Kelas') ? 'selected' : '' }}>Wali Kelas</option>
                                <option value="Guru Mapel" {{ (old('jabatan', $guru->jabatan) == 'Guru Mapel') ? 'selected' : '' }}>Guru Mapel</option>
                                <option value="Kepala Sekolah" {{ (old('jabatan', $guru->jabatan) == 'Kepala Sekolah') ? 'selected' : '' }}>Kepala Sekolah</option>
                                <option value="Staf Tata Usaha" {{ (old('jabatan', $guru->jabatan) == 'Staf Tata Usaha') ? 'selected' : '' }}>Staf Tata Usaha</option>
                                <option value="Operator" {{ (old('jabatan', $guru->jabatan) == 'Operator') ? 'selected' : '' }}>Operator</option>
                            </select>
                            
                            <p style="font-size: 11px; color: #9CA3AF; margin-top: 5px;"><i>Jabatan saat ini: {{ $guru->jabatan ?? 'Kosong' }}</i></p>
                        </div>

                        <!-- ========================================================= -->
                        <!-- ZONA BAHAYA: FITUR RESET AKUN UNTUK ADMIN -->
                        <!-- ========================================================= -->
                        <div style="background-color: #374151; padding: 15px; border-radius: 8px; border: 1px dashed #6B7280; margin-bottom: 30px;">
                            <h4 style="color: #EF4444; font-weight: bold; margin-bottom: 10px; font-size: 14px;">
                                ⚠️ Reset Akun Darurat (Hanya Diisi Jika Lupa Sandi)
                            </h4>
                            <p style="font-size: 12px; color: #9CA3AF; margin-bottom: 15px;">Biarkan kosong jika tidak ingin mengubah Email atau Password login guru ini.</p>

                            @php
                                // 🔥 KITA PAKAI DETEKTIF NAMA BUAT NYARI EMAILNYA 🔥
                                $akun_login = \App\Models\User::where('name', $guru->nama_guru)->first();
                                $email_aktif = $akun_login ? $akun_login->email : '';
                            @endphp

                            <!-- KOLOM EDIT EMAIL -->
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #D1D5DB; font-size: 13px;">Ubah Email Login (Opsional)</label>
                                <!-- Sekarang otomatis ngisi email lama kalau ada! -->
                                <input type="email" name="email" value="{{ old('email', $email_aktif) }}" autocomplete="new-password" placeholder="Email Baru..." style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #4B5563; background-color: #1F2937; color: white; outline: none;">
                            </div>

                            <!-- KOLOM EDIT PASSWORD -->
                            <div>
                                <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #D1D5DB; font-size: 13px;">Reset Password Baru (Opsional)</label>
                                <div style="position: relative;">
                                    <input type="password" id="inputSandiBaru" name="password" placeholder="Ketik Password Baru..." autocomplete="new-password" style="width: 100%; padding: 8px; padding-right: 35px; border-radius: 4px; border: 1px solid #4B5563; background-color: #1F2937; color: white; outline: none;">
                                    <span onclick="lihatSandiBaru()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none;" title="Tampilkan Sandi">👁️</span>
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL AKSI -->
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button type="submit" style="background-color: #10B981; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; transition: 0.3s;">
                                Simpan Perubahan
                            </button>
                            
                            <a href="{{ route('guru.index') }}" style="color: #9CA3AF; text-decoration: none; font-weight: bold; padding: 10px;">
                                Batal
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT LIHAT SANDI BARU -->
    <script>
        function lihatSandiBaru() {
            var x = document.getElementById("inputSandiBaru");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</x-app-layout>