<nav class="bg-white border-b border-gray-200 shadow-sm" style="background-color: #ffffff; border-bottom: 1px solid #e5e7eb; padding: 10px 20px;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
        
        <!-- Bagian Kiri: Logo & Menu Utama -->
        <div style="display: flex; align-items: center; gap: 25px;">
            <a href="{{ route('dashboard') }}" style="font-weight: bold; color: #1f2937; text-decoration: none; font-size: 18px;">
                🏫 Absen SLB
            </a>
            
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="{{ route('dashboard') }}" style="color: #4b5563; text-decoration: none; font-size: 14px; font-weight: 500;">Dashboard</a>
                
                <a href="{{ auth()->user()->email == 'mesinabsen@gmail.com' ? route('scan.guru') : route('scan') }}" style="color: #4b5563; text-decoration: none; font-size: 14px; font-weight: 500;">Scanner Kamera</a>

                @if(auth()->user()->email == 'abhaadmin234@gmail.com')
                    <a href="{{ route('murid.index') }}" style="color: #4b5563; text-decoration: none; font-size: 14px; font-weight: 500;">Data Murid</a>
                    <a href="{{ route('tabungan') }}" style="color: #4b5563; text-decoration: none; font-size: 14px; font-weight: 500;">Tabungan Siswa</a>
                    <a href="{{ route('rekap') }}" style="color: #4b5563; text-decoration: none; font-size: 14px; font-weight: 500;">Buku Rekap</a>
                    <a href="{{ route('guru.index') }}" style="color: #4b5563; text-decoration: none; font-size: 14px; font-weight: 500;">Data Guru</a>
                @endif
            </div>
        </div>

        <!-- Bagian Kanan: Tombol Tema & Profil -->
        <div style="display: flex; align-items: center; gap: 15px;">
            <button onclick="toggleGlobalTheme()" class="g-btn" style="background-color: #374151; color: white; padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; display: flex; align-items: center; gap: 5px;">
                <span class="g-icon">🌙</span> <span class="g-text">Gelap</span>
            </button>

            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 14px; font-weight: 600; color: #374151;">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" style="background-color: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer;">Log Out</button>
                </form>
            </div>
        </div>

    </div>
</nav>