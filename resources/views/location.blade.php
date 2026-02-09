
<style>
    [x-cloak] { display: none !important; }
</style>

<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-cyan-100/50 py-12 px-4 sm:px-6 lg:px-8">
    
        <div class="fixed top-[-15%] right-[-15%] w-[50vw] h-[50vw] bg-[#2da2ad]/70 rounded-full blur-[60px] animate-blob"></div>
        <div class="fixed bottom-[-15%] left-[-15%] w-[55vw] h-[55vw] bg-[#D1FADF]/90 rounded-full blur-[70px] animate-blob animation-delay-2000"></div>

        <div x-data="{ openModal: false, search:'' }" class="max-w-7xl mx-auto ">
            
            {{-- Wrapper Putih untuk Konten --}}
            <div class="bg-white/70 backdrop-blur-sm rounded-[3rem] p-8 md:p-12 shadow-2xl border border-white relative">
    
    <a href="{{ url(path: '/dashboard') }}" class="absolute top-8 right-8 text-gray-400 hover:text-red-500 transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </a>
                
                {{-- Header: Judul di Kiri, Search & Button di Tengah --}}
                <div class="flex flex-col mb-12">
                    <h1 class="text-4xl font-black text-slate-800 tracking-tight mb-8">Location</h1>
                    
                    <div class="flex flex-col md:flex-row gap-3 w-full max-w-2xl mx-auto justify-center">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input 
                                x-model="search" 
                                type="text" 
                                placeholder="Cari gedung..." 
                                class="w-full border border-gray-200 rounded-full pl-12 pr-6 py-4 focus:ring-2 focus:ring-indigo-500 outline-none transition shadow-inner bg-white/80 text-lg">
                        </div>
                        <button @click="openModal = true" 
                                class="bg-slate-900 text-white px-8 py-4 rounded-full font-bold hover:bg-slate-800 transition shadow-lg flex items-center justify-center gap-2 whitespace-nowrap">
                            <span class="text-xl">+</span> Tambah Gedung
                        </button>
                    </div>
                </div>

                {{-- Grid Gedung --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-10">
                    @forelse($buildings as $b)
                    <div 
                        x-show="'{{ strtolower($b->name) }}'.includes(search.toLowerCase())"
                        class="relative overflow-hidden rounded-[2.5rem] group h-80 shadow-2xl ring-8 ring-white/50">
                            {{-- Image --}}
                            <img src="{{ asset('storage/' . $b->image) }}" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                            
                            {{-- Overlay Default --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent flex items-end p-8 transition-opacity duration-500 group-hover:opacity-0">
                                <h2 class="text-white text-xl font-semibold leading-tight">{{ $b->name }}</h2>
                            </div>

                            {{-- Hover Menu --}}
                            <div class="absolute inset-0 bg-slate-900/80 opacity-0 group-hover:opacity-100 transition-all duration-500 flex flex-col items-center justify-center p-6 backdrop-blur-sm">
                                <h2 class="text-white text-lg font-bold mb-6 translate-y-4 group-hover:translate-y-0 transition duration-500">{{ $b->name }}</h2>
                                
                                <div class="flex flex-col w-full max-w-xs gap-3 translate-y-8 group-hover:translate-y-0 transition duration-500 delay-75">
                                    <a href="{{ route('location.show', $b->id) }}" 
                                       class="w-full bg-white text-slate-900 py-3 rounded-2xl font-bold text-center hover:bg-slate-100 transition shadow-xl">
                                        📦 Data Inventaris
                                    </a>
                                    <a href="{{ route('buildings.floorplan', $b->id) }}" 
                                       class="w-full bg-cyan-500 text-white py-3 rounded-2xl font-bold text-center hover:bg-cyan-400 transition shadow-xl">
                                        📍 Lihat Denah
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-32 text-center border-4 border-dashed rounded-[3rem] text-gray-400 bg-white/50">
                            <p class="text-xl font-medium">Belum ada data gedung.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Modal Tambah Gedung --}}
            <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
                <div class="fixed inset-0 bg-black/40 backdrop-blur-md"></div>
                <div class="relative min-h-screen flex items-center justify-center p-4">
                    <div @click.away="openModal = false" class="bg-white rounded-[2.5rem] max-w-md w-full p-10 shadow-2xl">
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-2xl font-black text-slate-800">Gedung Baru</h2>
                            <button @click="openModal = false" class="text-gray-400 hover:text-red-500 transition text-3xl">&times;</button>
                        </div>

                        <form action="{{ route('buildings.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Nama Gedung</label>
                                    <input type="text" name="name" required class="w-full border-gray-200 rounded-2xl p-3 focus:ring-slate-800">
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Foto Utama</label>
                                    <input type="file" name="image" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 transition">
                                </div>

                                <div x-data="{ floorCount: 1 }">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Jumlah Lantai</label>
                                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto pr-2 mb-4">
                                        <template x-for="i in floorCount" :key="i">
                                            <div class="flex items-center bg-gray-50 p-2 rounded-xl border border-gray-100">
                                                <input type="text" :name="'floors[]'" :value="'Lantai ' + i" readonly class="bg-transparent border-none p-0 text-sm font-bold text-slate-600 w-full focus:ring-0">
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="floorCount++" class="flex-1 bg-indigo-50 text-indigo-600 py-2 rounded-xl text-xs font-bold hover:bg-indigo-100 transition">+ Tambah</button>
                                        <button type="button" @click="if(floorCount > 1) floorCount--" class="flex-1 bg-red-50 text-red-600 py-2 rounded-xl text-xs font-bold hover:bg-red-100 transition">- Kurang</button>
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-bold shadow-xl hover:bg-slate-900 transition">
                                    Simpan Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>