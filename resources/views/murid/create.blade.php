<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-white leading-tight">
            <i class="fas fa-user-plus text-blue-500 mr-2"></i> Tambah Murid Baru (VERSI BARU)
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div style="background-color: #1E293B; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; border: 1px solid #334155;">
                
                <form action="{{ route('murid.store') }}" method="POST">
                    @csrf

                    @php
                        $nomor_urut = isset($nis_otomatis) ? substr($nis_otomatis, -3) : '001'; 
                        $tahun_bulan = date('ym'); 
                    @endphp

                    <div style="margin-bottom: 20px;">
                        <label style="color: white; font-size: 14px; font-weight: bold; display: block; margin-bottom: 8px;">NIS & Pilihan SLB</label>
                        
                        <div style="display: flex; gap: 15px; align-items: stretch;">
                            <div style="flex: 1.5; position: relative;">
                                <input type="text" id="nis_preview" name="nis" readonly required
                                       style="width: 100%; height: 100%; padding: 12px; border-radius: 8px; border: 1px solid #475569; background: #0F172A; color: #EF4444; font-weight: bold; font-size: 16px; letter-spacing: 1px; outline: none; cursor: not-allowed;"
                                       value="PILIH SLB DAHULU">
                            </div>

                            <div style="flex: 2;">
                                <select id="jenis_slb" required onchange="generateNIS()"
                                        style="width: 100%; height: 100%; padding: 12px; border-radius: 8px; border: 1px solid #475569; background: #1E293B; color: white; outline: none; cursor: pointer; transition: 0.3s;"
                                        onfocus="this.style.borderColor='#3B82F6'" onblur="this.style.borderColor='#475569'">
                                    <option value="" disabled selected style="color: #9CA3AF;">-- Pilih Jenis SLB --</option>
                                    <option value="1" style="color: black; background: white;">SLB A (Tunanetra)</option>
                                    <option value="2" style="color: black; background: white;">SLB B (Tunarungu)</option>
                                    <option value="3" style="color: black; background: white;">SLB C (Tunagrahita)</option>
                                    <option value="4" style="color: black; background: white;">SLB D (Tunadaksa)</option>
                                    <option value="5" style="color: black; background: white;">SLB E (Tunalaras)</option>
                                    <option value="6" style="color: black; background: white;">SLB G (Tunaganda)</option>
                                </select>
                            </div>
                        </div>
                        <p style="font-size: 11px; color: #9CA3AF; margin-top: 8px;">
                            <i>*Format: TahunBulan <strong>({{ $tahun_bulan }})</strong> + Kode SLB + No. Urut <strong>({{ $nomor_urut }})</strong></i>
                        </p>
                    </div>

                    <script>
                        const tahunBulan = "{{ $tahun_bulan }}";
                        const nomorUrut = "{{ $nomor_urut }}";

                        function generateNIS() {
                            const dropdownSLB = document.getElementById('jenis_slb');
                            const kolomNIS = document.getElementById('nis_preview');
                            const kodeSLB = dropdownSLB.value;
                            
                            if (kodeSLB !== "") {
                                kolomNIS.value = tahunBulan + kodeSLB + nomorUrut;
                                kolomNIS.style.color = '#10B981'; 
                            } else {
                                kolomNIS.value = "PILIH SLB DAHULU";
                                kolomNIS.style.color = '#EF4444'; 
                            }
                        }
                        
                        document.addEventListener('DOMContentLoaded', generateNIS);
                    </script>

                    <div style="margin-bottom: 20px;">
                        <label style="color: white; font-weight: bold; display: block; margin-bottom: 8px;">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required placeholder="Nama lengkap siswa..." 
                               style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #475569; background: #334155; color: white; outline: none;" onfocus="this.style.borderColor='#3B82F6'" onblur="this.style.borderColor='#475569'">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="color: white; font-weight: bold; display: block; margin-bottom: 8px;">Kelas</label>
                        <input type="text" name="kelas" required placeholder="Contoh: 1-B" 
                               style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #475569; background: #334155; color: white; outline: none;" onfocus="this.style.borderColor='#3B82F6'" onblur="this.style.borderColor='#475569'">
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="color: white; font-weight: bold; display: block; margin-bottom: 8px;">No. WhatsApp Orang Tua</label>
                        <div style="display: flex;">
                            <span style="background: #0F172A; border: 1px solid #475569; color: white; padding: 12px 15px; border-radius: 8px 0 0 8px; border-right: none;">+62</span>
                            <input type="number" name="no_wa_ortu" placeholder="81234567890" 
                                   style="width: 100%; padding: 12px; border-radius: 0 8px 8px 0; border: 1px solid #475569; background: #334155; color: white; outline: none;" onfocus="this.style.borderColor='#3B82F6'" onblur="this.style.borderColor='#475569'">
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" style="background: #3B82F6; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                            <i class="fas fa-save mr-2"></i> Simpan Data
                        </button>
                        <a href="{{ route('murid.index') }}" style="background: #64748B; color: white; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; display: inline-flex; align-items: center;">
                            Batal
                        </a>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
</x-app-layout>