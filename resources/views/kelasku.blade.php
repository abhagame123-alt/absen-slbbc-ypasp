<x-app-layout>
    <x-slot name="header">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- HEADER DENGAN TOMBOL KEMBALI DI ATAS -->
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-white leading-tight">
                <i class="fas fa-users-cog text-blue-500 mr-2"></i> {{ __('Kelola Kelasku') }}
            </h2>
            
            <a href="{{ route('dashboard') }}" style="background: #374151; color: white; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: bold; text-decoration: none; transition: 0.3s; border: 1px solid #4B5563; display: flex; align-items: center;" onmouseover="this.style.background='#4B5563'" onmouseout="this.style.background='#374151'">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </x-slot>

    <!-- CSS SUPER PREMIUM UNTUK DARK MODE -->
    <style>
        .premium-container { background-color: #1E293B; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid #334155; padding: 30px; }
        
        .murid-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-bottom: 30px; }

        .chk-box {
            appearance: none; -webkit-appearance: none; width: 24px; height: 24px; border: 2px solid #475569; border-radius: 6px; outline: none; cursor: pointer; transition: 0.2s; position: relative; flex-shrink: 0; background-color: #0F172A;
        }
        .chk-box:checked { background-color: #3B82F6; border-color: #3B82F6; }
        .chk-box:checked::after {
            content: ''; position: absolute; left: 7px; top: 3px; width: 6px; height: 12px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
        }
        .chk-box:disabled { border-color: #334155; background-color: #1E293B; cursor: not-allowed; }

        .student-card {
            display: flex; align-items: center; padding: 16px; background-color: #0F172A; border: 2px solid #334155; border-radius: 12px; cursor: pointer; transition: all 0.2s;
        }
        .student-card:hover:not(.disabled) { border-color: #64748B; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3);}
        
        .student-card.selected { background-color: rgba(59, 130, 246, 0.1); border-color: #3B82F6; }
        .student-card.disabled { opacity: 0.5; cursor: not-allowed; background-color: #0B1120; border-color: #1E293B; }

        .avatar {
            width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 18px; color: white; margin: 0 15px; transition: 0.3s; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); border: 2px solid transparent;
        }
        .student-card.selected .avatar { border-color: white; box-shadow: 0 0 12px rgba(255,255,255,0.4); transform: scale(1.05); }
        .student-card.disabled .avatar { filter: grayscale(100%); opacity: 0.4; }

        .btn-save {
            background: linear-gradient(135deg, #3B82F6, #2563EB); color: white; padding: 12px 35px; border-radius: 8px; font-weight: 800; font-size: 16px; transition: 0.3s; border: none; cursor: pointer;
        }
        .btn-save:hover { box-shadow: 0 0 15px rgba(59,130,246,0.6); transform: scale(1.02); }

        /* CSS KOLOM PENCARIAN */
        .search-box {
            background-color: #0F172A; border: 1px solid #334155; color: white; border-radius: 10px; padding: 12px 15px 12px 45px; width: 100%; transition: 0.3s;
        }
        .search-box:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); outline: none; }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div style="background-color: #064E3B; border: 1px solid #10B981; color: #34D399; padding: 15px; border-radius: 10px; margin-bottom: 25px; font-weight: bold; display: flex; align-items: center;">
                    <i class="fas fa-check-circle mr-3 text-xl"></i> {{ session('success') }}
                </div>
            @endif

            <div class="premium-container">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 20px; margin-bottom: 20px;">
                    <div>
                        <h3 class="text-xl font-bold text-white mb-1">Pilih Siswa Kelas Anda</h3>
                        <p class="text-gray-400 text-sm">Centang siswa yang masuk ke dalam kelas Anda.</p>
                    </div>
                    <div style="background-color: #0F172A; padding: 8px 15px; border-radius: 8px; border: 1px solid #334155;">
                        <span class="text-sm text-gray-400">Login sebagai:</span>
                        <span class="font-bold text-blue-400 ml-1">{{ strtoupper($nama_guru) }}</span>
                    </div>
                </div>

                <!-- 🔍 KOLOM PENCARIAN CERDAS 🔍 -->
                <div style="position: relative; margin-bottom: 25px;">
                    <div style="position: absolute; inset-y: 0; left: 0; display: flex; align-items: center; padding-left: 15px; pointer-events: none;">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="inputCari" class="search-box" placeholder="Ketik nama siswa untuk mencari dengan cepat...">
                </div>

                <form action="{{ route('kelasku.update') }}" method="POST">
                    @csrf
                    
                    <div class="murid-grid" id="wadahKartu">
                        @foreach($semua_murid as $m)
                            @php 
                                $milik_saya = ($m->wali_kelas == $nama_guru);
                                $milik_orang = (!empty($m->wali_kelas) && $m->wali_kelas != $nama_guru);
                                
                                $card_class = 'student-card kartu-siswa'; // Tambahan class kartu-siswa untuk pencarian
                                if($milik_saya) $card_class .= ' selected';
                                if($milik_orang) $card_class .= ' disabled';
                                
                                $inisial = substr($m->nama_lengkap, 0, 1);
                                
                                // MESIN PEMBERI WARNA OTOMATIS
                                $palet_warna = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#14B8A6', '#F43F5E', '#84CC16', '#0EA5E9'];
                                $angka_unik = abs(crc32($m->nama_lengkap)); 
                                $warna_avatar = $palet_warna[$angka_unik % count($palet_warna)];
                            @endphp
                            
                            <label class="{{ $card_class }}">
                                <input type="checkbox" name="murid_pilihan[]" value="{{ $m->nis }}" class="chk-box"
                                    {{ $milik_saya ? 'checked' : '' }} 
                                    {{ $milik_orang ? 'disabled' : '' }}>
                                    
                                <div class="avatar" style="background-color: {{ $warna_avatar }};">
                                    {{ strtoupper($inisial) }}
                                </div>
                                
                                <div style="flex-grow: 1; overflow: hidden;">
                                    <!-- Tambahan class nama-target agar mesin pencari tahu mana yang mau dibaca -->
                                    <div class="font-bold text-white nama-target" style="font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ strtoupper($m->nama_lengkap) }}</div>
                                    <div class="text-xs text-gray-400 mt-1">NIS: {{ $m->nis }}</div>
                                </div>
                                
                                @if($milik_orang)
                                    <span style="font-size: 10px; font-weight: bold; background: rgba(220, 38, 38, 0.2); color: #FCA5A5; border: 1px solid #991B1B; padding: 4px 8px; border-radius: 6px; margin-left: 10px; white-space: nowrap;">
                                        Milik {{ ucfirst($m->wali_kelas) }}
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #334155; padding-top: 25px;">
                        <a href="{{ route('dashboard') }}" style="color: #94A3B8; text-decoration: none; font-weight: bold; padding: 10px 15px; border-radius: 8px; transition: 0.3s;" onmouseover="this.style.color='#F8FAFC'; this.style.backgroundColor='#334155'" onmouseout="this.style.color='#94A3B8'; this.style.backgroundColor='transparent'">
                            <i class="fas fa-arrow-left mr-2"></i> Batal / Kembali
                        </a>
                        
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save mr-2"></i> Simpan Kelasku
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT GABUNGAN -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. ANIMASI KLIK CHECKBOX
            const checkboxes = document.querySelectorAll('.chk-box:not(:disabled)');
            checkboxes.forEach(box => {
                box.addEventListener('change', function() {
                    const card = this.closest('.student-card');
                    if(this.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                });
            });

            // 2. MESIN PENCARI (LIVE FILTER)
            const inputCari = document.getElementById('inputCari');
            const daftarKartu = document.querySelectorAll('.kartu-siswa');

            inputCari.addEventListener('keyup', function() {
                // Ambil kata yang diketik, jadikan huruf besar semua
                let filter = this.value.toUpperCase(); 

                // Cek satu-satu semua kartu murid
                daftarKartu.forEach(function(kartu) {
                    let nama = kartu.querySelector('.nama-target').textContent;
                    
                    // Kalau namanya cocok dengan ketikan, tampilkan. Kalau tidak, sembunyikan!
                    if (nama.toUpperCase().indexOf(filter) > -1) {
                        kartu.style.display = ""; 
                    } else {
                        kartu.style.display = "none";
                    }
                });
            });

        });
    </script>
</x-app-layout>