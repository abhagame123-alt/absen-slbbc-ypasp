<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center space-x-6">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-4 sm:flex items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('dashboard') ? 'border-indigo-400 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('Dashboard') }}
                    </a>
                    
                    <!-- MENU SCANNER PINTAR -->
                    <a href="{{ auth()->user()->email == 'mesinabsen@gmail.com' ? route('scan.guru') : route('scan') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('scan') || request()->routeIs('scan.guru') ? 'border-indigo-400 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('Scanner Kamera') }}
                    </a>

                    <!-- HANYA ADMIN -->
                    @if(auth()->user()->email == 'abhaadmin234@gmail.com')
                        <a href="{{ route('murid.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('murid.*') ? 'border-indigo-400 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                            {{ __('Data Murid') }}
                        </a>
                        
                        <a href="{{ route('tabungan') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('tabungan') ? 'border-indigo-400 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                            {{ __('Tabungan Siswa') }}
                        </a>
                        
                        <a href="{{ route('rekap') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('rekap') ? 'border-indigo-400 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                            {{ __('Buku Rekap') }}
                        </a>
                        
                        <a href="{{ route('guru.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('guru.*') ? 'border-indigo-400 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                            {{ __('Data Guru') }}
                        </a>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                <!-- TOMBOL TEMA GLOBAL -->
                <button onclick="toggleGlobalTheme()" class="g-btn bg-gray-700 text-white px-3 py-1.5 rounded-md text-sm font-bold cursor-pointer border-none flex items-center gap-1.5 shadow">
                    <span class="g-icon">🌙</span> <span class="g-text">Gelap</span>
                </button>

                <div class="relative" x-data="{ dropdownOpen: false }">
                    <button @click="dropdownOpen = !dropdownOpen" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                        <div>{{ Auth::user()->name }}</div>
                        <div class="ms-1">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    <div x-show="dropdownOpen" @click.away="dropdownOpen = false" class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1 z-50">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden px-4 pt-2 pb-4 space-y-1">
        <a href="{{ route('dashboard') }}" class="block py-2 text-base font-medium text-gray-700">Dashboard</a>
        <a href="{{ auth()->user()->email == 'mesinabsen@gmail.com' ? route('scan.guru') : route('scan') }}" class="block py-2 text-base font-medium text-gray-700">Scanner Kamera</a>
        @if(auth()->user()->email == 'abhaadmin234@gmail.com')
            <a href="{{ route('murid.index') }}" class="block py-2 text-base font-medium text-gray-700">Data Murid</a>
            <a href="{{ route('tabungan') }}" class="block py-2 text-base font-medium text-gray-700">Tabungan Siswa</a>
            <a href="{{ route('rekap') }}" class="block py-2 text-base font-medium text-gray-700">Buku Rekap</a>
            <a href="{{ route('guru.index') }}" class="block py-2 text-base font-medium text-gray-700">Data Guru</a>
        @endif
        <div class="border-t border-gray-200 pt-4 mt-4">
            <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
            <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" class="block py-2 text-sm text-gray-600">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left py-2 text-sm text-gray-600">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</nav>