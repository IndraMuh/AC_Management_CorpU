<x-guest-layout>
    <div x-data="{ openModal: false }" class="max-w-7xl mx-auto p-6">
        
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold italic text-slate-800">Location & Maps</h1>
            <div class="flex gap-2">
                <input type="text" placeholder="Cari gedung..." class="border border-gray-200 rounded-xl px-4 py-2 w-64 focus:ring-2 focus:ring-slate-800 outline-none transition shadow-sm">
                <button @click="openModal = true" class="bg-slate-800 text-white px-5 py-2 rounded-xl font-bold hover:bg-slate-700 transition shadow-lg">
                    + Gedung
                </button>
            </div>
        </div>

        {{-- Grid Gedung --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($buildings as $b)
                <div class="relative overflow-hidden rounded-[2rem] group h-72 shadow-xl border-4 border-white">
                    {{-- Image --}}
                    <img src="{{ asset('storage/' . $b->image) }}" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                    
                    {{-- Overlay Default --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent flex items-end p-8 transition-opacity duration-500 group-hover:opacity-0">
                        <h2 class="text-white text-2xl font-black tracking-tight">{{ strtoupper($b->name) }}</h2>
                    </div>

                    {{-- Hover Menu --}}
                    <div class="absolute inset-0 bg-slate-900/80 opacity-0 group-hover:opacity-100 transition-all duration-500 flex flex-col items-center justify-center p-6 backdrop-blur-sm">
                        <h2 class="text-white text-lg font-bold mb-6 translate-y-4 group-hover:translate-y-0 transition duration-500">{{ $b->name }}</h2>
                        
                        <div class="flex flex-col w-full gap-3 translate-y-8 group-hover:translate-y-0 transition duration-500 delay-75">
                            <a href="{{ route('location.show', $b->id) }}" 
                               class="w-full bg-white text-slate-900 py-3 rounded-2xl font-bold text-center hover:bg-slate-100 transition shadow-xl">
                                📦 Data Inventaris
                            </a>
                            <a href="{{ route('buildings.floorplan', $b->id) }}" 
                               class="w-full bg-teal-500 text-white py-3 rounded-2xl font-bold text-center hover:bg-teal-400 transition shadow-xl">
                                📍 Lihat Denah
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-32 text-center border-4 border-dashed rounded-[3rem] text-gray-400 bg-gray-50">
                    <p class="text-xl font-medium">Belum ada data gedung.</p>
                </div>
            @endforelse
        </div>

        {{-- Modal Tambah Gedung --}}
        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openModal = false" class="bg-white rounded-[2.5rem] max-w-md w-full p-10 shadow-2xl animate-zoomIn">
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

                            <button type="submit" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-bold shadow-xl hover:bg-slate-900 transition translate-y-2">
                                Simpan Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>