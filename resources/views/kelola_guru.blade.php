<x-app-layout>
    <x-slot name="header">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <h2 class="font-bold text-2xl leading-tight" style="color: #4F46E5;">
            <i class="fas fa-user-minus mr-2"></i> {{ __('Kelola Data Guru') }}
        </h2>
    </x-slot>

    <style>
        /* GRID LEBIH KECIL & RAPAT */
        .grid-guru {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .guru-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .guru-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        
        /* WARNA TEMA TERANG */
        .card-aktif { border-color: #3B82F6 !important; background-color: #EFF6FF !important; }
        .card-nonaktif { border-color: #D1D5DB !important; background-color: #F9FAFB !important; filter: grayscale(100%); opacity: 0.9; }
        .text-nama-aktif { color: #1E3A8A !important; }
        .text-nama-nonaktif { color: #374151 !important; } 
        .badge-aktif { background-color: #DBEAFE !important; color: #1D4ED8 !important; }
        .badge-tidak-aktif { background-color: #E5E7EB !important; color: #4B5563 !important; }

        /* WARNA TEMA GELAP (DARK MODE) */
        body.dark-mode .bg-white { background-color: #1E293B !important; border-color: #334155 !important;}
        body.dark-mode .text-gray-900 { color: #F8FAFC !important; }
        body.dark-mode .text-gray-800 { color: #F1F5F9 !important; }
        body.dark-mode .text-gray-600, body.dark-mode .text-gray-500 { color: #9CA3AF !important; }
        body.dark-mode .border-gray-200 { border-color: #334155 !important; }
        
        body.dark-mode .card-aktif { border-color: #3B82F6 !important; background-color: rgba(59, 130, 246, 0.1) !important; filter: none; opacity: 1;}
        body.dark-mode .card-nonaktif { border-color: #475569 !important; background-color: #0F172A !important; filter: grayscale(100%); opacity: 0.85;}
        body.dark-mode .text-nama-aktif { color: #93C5FD !important; }
        body.dark-mode .text-nama-nonaktif { color: #D1D5DB !important; } 
        body.dark-mode .badge-aktif { background-color: rgba(59, 130, 246, 0.2) !important; color: #60A5FA !important; }
        body.dark-mode .badge-tidak-aktif { background-color: #334155 !important; color: #9CA3AF !important; } 
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- TOMBOL KEMBALI -->
            <div class="mb-5">
                <a href="/dashboard" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white text-sm font-bold rounded-lg transition-all shadow-md" style="text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>

            @if(session('success'))
                <div class="mb-5 bg-green-100 border-l-4 border-green-500 text-green-700 p-3 rounded-lg shadow-sm" role="alert">
                    <p class="font-bold text-sm"><i class="fas fa-check-circle mr-2"></i> BERHASIL: {{ session('success') }}</p>
                </div>
            @endif

            <!-- KOTAK UTAMA -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-6 border-b border-gray-200 pb-4">
                        <h3 class="text-lg font-extrabold text-gray-800 mb-1">📋 Atur Status Aktif Guru</h3>
                        <p class="text-[13px] text-gray-600 leading-relaxed">
                            Centang kotak untuk guru <b>AKTIF</b>. Hilangkan centang untuk guru <b>Pensiun/Keluar</b> agar otomatis disembunyikan.
                        </p>
                    </div>

                    <form action="{{ route('kelola.guru.update') }}" method="POST">
                        @csrf
                        
                        <!-- GRID KARTU GURU (MUNGIL & RAPI) -->
                        <div class="grid-guru">
                            @foreach($semua_guru as $g)
                                @php
                                    $is_aktif = ($g->status_aktif == 1 || is_null($g->status_aktif));
                                @endphp
                                
                                <label class="guru-card relative p-3 rounded-xl border cursor-pointer flex items-center gap-3 {{ $is_aktif ? 'card-aktif' : 'card-nonaktif' }}" id="label-{{ $g->nip }}">
                                    
                                    <!-- FOTO GURU (DI KIRI, UKURAN LEBIH KECIL) -->
                                    <div class="flex-shrink-0 relative">
                                        <!-- DISINI MANTRANYA: background=random -->
                                        <img src="{{ asset('wajah/GURU_'.$g->nip.'.jpg') }}" 
                                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($g->nama_guru) }}&background=random&color=fff&bold=true&rounded=true'" 
                                             class="w-10 h-10 rounded-full object-cover border-2 transition-all duration-300 {{ $is_aktif ? 'border-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.4)] filter-none' : 'border-gray-400 grayscale opacity-70' }}" 
                                             id="img-{{ $g->nip }}" alt="Foto" style="border-radius: 50%;">
                                    </div>

                                    <!-- INFO GURU (DI TENGAH, TEKS LEBIH PADAT) -->
                                    <div class="flex-1 min-w-0">
                                        <p class="font-extrabold text-[13px] truncate leading-tight {{ $is_aktif ? 'text-nama-aktif' : 'text-nama-nonaktif' }}" id="nama-{{ $g->nip }}">
                                            {{ strtoupper($g->nama_guru) }}
                                        </p>
                                        <p class="text-[10px] text-gray-500 font-mono mt-0.5 mb-1 font-bold">NIP: {{ $g->nip }}</p>
                                        
                                        <!-- BADGE LEBIH KECIL -->
                                        <div class="inline-block px-1.5 py-0.5 text-[9px] font-extrabold rounded {{ $is_aktif ? 'badge-aktif' : 'badge-tidak-aktif' }}" id="badge-{{ $g->nip }}">
                                            @if($is_aktif)
                                                <i class="fas fa-check-circle"></i> AKTIF
                                            @else
                                                <i class="fas fa-times-circle"></i> PENSIUN
                                            @endif
                                        </div>
                                    </div>

                                    <!-- CHECKBOX (DI KANAN, UKURAN SEDANG) -->
                                    <div class="flex-shrink-0 ml-auto pl-1">
                                        <input type="checkbox" name="guru_pilihan[]" value="{{ $g->nip }}" 
                                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                                            {{ $is_aktif ? 'checked' : '' }}
                                            onchange="updateStyle(this, '{{ $g->nip }}')">
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <!-- TOMBOL SIMPAN -->
                        <div class="flex justify-end border-t border-gray-200 pt-5">
                            <button type="submit" style="background-color: #2563EB !important; color: #FFFFFF !important; width: 100%; border-radius: 10px; font-weight: 800; font-size: 14px; padding: 12px; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3); transition: 0.3s;" onmouseover="this.style.backgroundColor='#1D4ED8' !important;" onmouseout="this.style.backgroundColor='#2563EB' !important;">
                                <i class="fas fa-save text-lg mr-2"></i> SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT UNTUK ANIMASI KOTAK -->
    <script>
        function updateStyle(checkbox, nip) {
            const label = document.getElementById('label-' + nip);
            const nama = document.getElementById('nama-' + nip);
            const badge = document.getElementById('badge-' + nip);
            const img = document.getElementById('img-' + nip);
            
            if (checkbox.checked) {
                label.className = 'guru-card relative p-3 rounded-xl border cursor-pointer flex items-center gap-3 card-aktif';
                nama.className = 'font-extrabold text-[13px] truncate leading-tight text-nama-aktif';
                
                badge.className = 'inline-block px-1.5 py-0.5 text-[9px] font-extrabold rounded badge-aktif';
                badge.innerHTML = '<i class="fas fa-check-circle"></i> AKTIF';
                
                img.className = 'w-10 h-10 rounded-full object-cover border-2 transition-all duration-300 border-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.4)] filter-none opacity-100';
            } else {
                label.className = 'guru-card relative p-3 rounded-xl border cursor-pointer flex items-center gap-3 card-nonaktif';
                nama.className = 'font-extrabold text-[13px] truncate leading-tight text-nama-nonaktif';
                
                badge.className = 'inline-block px-1.5 py-0.5 text-[9px] font-extrabold rounded badge-tidak-aktif';
                badge.innerHTML = '<i class="fas fa-times-circle"></i> PENSIUN';
                
                img.className = 'w-10 h-10 rounded-full object-cover border-2 transition-all duration-300 border-gray-400 grayscale opacity-70';
            }
        }
    </script>
</x-app-layout>