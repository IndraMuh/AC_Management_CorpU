{{-- Letakkan di bagian atas dashboard.blade.php --}}
@push('head')
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
@endpush

<x-app-layout>
    <div class="min-h-screen w-full bg-[#F3F4F6] relative overflow-y-auto flex items-start justify-center p-2 md:p-4 pt-1 lg:pt-5">
        
        <div class="fixed top-[-15%] right-[-15%] w-[50vw] h-[50vw] bg-[#2da2ad]/70 rounded-full blur-[60px] animate-blob"></div>
        <div class="fixed bottom-[-15%] left-[-15%] w-[55vw] h-[55vw] bg-[#D1FADF]/90 rounded-full blur-[70px] animate-blob animation-delay-2000"></div>

        <style>
            @keyframes blob {
                0% { transform: translate(0px, 0px) scale(1); }
                33% { transform: translate(50px, -70px) scale(1.2); }
                66% { transform: translate(-40px, 40px) scale(0.8); }
                100% { transform: translate(0px, 0px) scale(1); }
            }
            .animate-blob { animation: blob 10s infinite alternate ease-in-out; }
            .animation-delay-2000 { animation-delay: 2s; }
            
            /* Sembunyikan scrollbar */
            .hide-scrollbar::-webkit-scrollbar { display: none; }
            .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

            /* Smooth Transition Helper */
            [x-cloak] { display: none !important; }
        </style>

        <div class="max-w-7xl w-full mx-auto relative z-10">
            <div class="bg-white/80 backdrop-blur-md shadow-2xl rounded-[3rem] p-6 lg:p-12 relative border border-white/50">
                
                <div class="absolute top-8 right-10">
                    <img src="{{ asset('images/logo.png') }}" alt="Telkom CorpU" class="h-10 lg:h-20" loading="lazy">
                </div>

                <div class="mb-6 lg:mb-8">
                    <h1 class="text-4xl lg:text-6xl font-extrabold text-gray-900 tracking-tight leading-tight">Hello</h1>
                    <h2 class="text-3xl lg:text-5xl font-bold text-gray-800">{{ Auth::user()->name }}.</h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-stretch">
                    
<div class="lg:col-span-4 flex flex-col justify-start space-y-4">
    {{-- TOTAL AC --}}
    <div class="bg-[#2da2ad] rounded-[2rem] px-6 py-5 lg:pl-10 lg:py-6 text-white shadow-xl flex flex-col justify-center min-h-[100px] lg:min-h-[130px]">
        <p class="text-sm lg:text-lg font-medium opacity-90 leading-tight">Total AC</p>
        <h3 class="text-4xl lg:text-5xl font-black mt-1">
            {{ $totalAc }} <span class="text-lg font-light">unit</span>
        </h3>
    </div>

    {{-- TOTAL AC BAIK --}}
    <div class="bg-[#D1FADF] rounded-[2rem] px-6 py-5 lg:pl-10 lg:py-6 text-[#039855] shadow-xl flex flex-col justify-center min-h-[100px] lg:min-h-[130px]">
        <p class="text-sm lg:text-lg font-medium opacity-80 leading-tight">Total AC Baik</p>
        <h3 class="text-4xl lg:text-5xl font-black mt-1">
            {{ $totalAcBaik }} <span class="text-lg font-light">unit</span>
        </h3>
    </div>

    <div class="grid grid-cols-2 gap-4">
        {{-- TOTAL AC PROSES (Sedang Diperbaiki) --}}
        <div class="bg-[#FEF0C7] rounded-[2.5rem] p-6 text-[#B54708] shadow-md flex flex-col justify-between min-h-[160px]">
            <p class="text-base lg:text-lg font-semibold leading-tight w-3/4">Total AC Proses</p>
            <h3 class="text-5xl lg:text-6xl font-black flex items-baseline">
                {{ $totalAcProses }}<span class="text-xl lg:text-2xl font-normal ml-1">unit</span>
            </h3>
        </div>

        {{-- TOTAL AC RUSAK --}}
        <div class="bg-[#FEE4E2] rounded-[2.5rem] p-6 text-[#D92D20] shadow-md flex flex-col justify-between min-h-[160px]">
            <p class="text-base lg:text-lg font-semibold leading-tight w-3/4">Total AC Rusak</p>
            <h3 class="text-5xl lg:text-6xl font-black flex items-baseline">
                {{ $totalAcRusak }}<span class="text-xl lg:text-2xl font-normal ml-1">unit</span>
            </h3>
        </div>
    </div>
</div>

<div class="lg:col-span-8 flex flex-col -mt-4 lg:-mt-10" 
     x-data="{ 
        currentIndex: 0,
        menus: [
            { title: 'SCHEDULE', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', route: 'schedules' },
            { title: 'LOCATION', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', route: 'location' },
            { title: 'MASTER DATA AC', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', route: 'master-ac' },
            { title: 'HISTORY', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', route: 'history' },
            { title: 'PROFILE', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', route: '{{ route('profile.edit') }}' }
        ],
        next() { this.currentIndex = (this.currentIndex + 1) % this.menus.length },
        prev() { this.currentIndex = (this.currentIndex - 1 + this.menus.length) % this.menus.length }
     }">
                        
                        <h3 class="text-2xl lg:text-4xl font-bold mb-3 text-gray-800 ml-4 leading-none">Menu_</h3>
                        
                        <div class="bg-[#2da2ad] rounded-[2.5rem] flex items-center justify-between px-6 lg:px-10 relative overflow-hidden shadow-2xl border-4 border-white/20 min-h-[350px] lg:min-h-[480px] group hover:bg-[#268d96] hover:shadow-[0_20px_50px_rgba(45,162,173,0.4)] transition-all duration-500">
                            
                            <button @click="prev()" class="z-20 text-white/60 hover:text-white transition-all transform hover:scale-125 focus:outline-none active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 lg:h-20 lg:w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div class="flex-1 flex flex-col items-center justify-center text-white py-8 relative">
                                <template x-for="(menu, index) in menus" :key="index">
                                    <div x-show="currentIndex === index" 
                                         class="flex flex-col items-center text-center absolute"
                                         x-transition:enter="transition ease-out duration-500"
                                         x-transition:enter-start="opacity-0 scale-75 translate-y-8"
                                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-300"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-110">
                                        
                                        <div class="mb-4 lg:mb-8 group-hover:rotate-6 transition-transform duration-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32 lg:h-56 lg:w-56 opacity-95 drop-shadow-2xl" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" :d="menu.icon" />
                                            </svg>
                                        </div>

                                        <a :href="menu.route" class="text-3xl lg:text-5xl font-black tracking-widest uppercase drop-shadow-lg hover:tracking-[0.2em] transition-all duration-300">
                                            <span x-text="menu.title"></span>
                                        </a>
                                    </div>
                                </template>
                            </div>

                            <button @click="next()" class="z-20 text-white/60 hover:text-white transition-all transform hover:scale-125 focus:outline-none active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 lg:h-20 lg:w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="group flex items-center text-gray-400 hover:text-red-600 font-bold uppercase tracking-widest text-xs transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>