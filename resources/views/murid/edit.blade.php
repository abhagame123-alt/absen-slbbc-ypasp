<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Data Murid') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form action="{{ route('murid.update', $murid->id) }}" method="POST">
                        @csrf
                        @method('PUT') <!-- Ini sihir penanda kalau form ini untuk UPDATE -->
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: bold;">NIS</label>
                            <input type="text" name="nis" value="{{ $murid->nis }}" required style="width: 100%; border: 1px solid #ccc; padding: 8px; border-radius: 4px;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: bold;">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="{{ $murid->nama_lengkap }}" required style="width: 100%; border: 1px solid #ccc; padding: 8px; border-radius: 4px;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: bold;">Kelas</label>
                            <input type="text" name="kelas" value="{{ $murid->kelas }}" required style="width: 100%; border: 1px solid #ccc; padding: 8px; border-radius: 4px;">
                        </div>

                        <!-- KOTAK ISIAN NOMOR WA BARU -->
                        <div style="margin-bottom: 25px;">
                            <label style="display: block; font-weight: bold;">Nomor WA Orang Tua</label>
                            <input type="text" name="no_wa_ortu" value="{{ $murid->no_wa_ortu }}" placeholder="Contoh: 6281234567890" style="width: 100%; border: 1px solid #ccc; padding: 8px; border-radius: 4px;">
                            <small style="color: #6B7280; font-size: 12px;">* Wajib diawali dengan 62 (Tanpa tanda + atau 0 di depan). Contoh: 6281234567890</small>
                        </div>

                        <button type="submit" style="background-color: #F59E0B; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;">
                            Update Data
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>