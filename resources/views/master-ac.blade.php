<x-guest-layout>
    <div x-data="{ 
        {{-- State Modal & Mode --}}
        openAcModal: false,
        openDetailModal: false,
        isEditingAc: false,
        selectedAc: {},

        {{-- State Filter --}}
        searchQuery: '',
        filterStatus: 'all',
        filterBuilding: 'all',
        filterBrand: 'all',

        {{-- State Dependent Dropdown Modal Tambah --}}
        selectedBuilding: '',
        selectedFloor: '',
        selectedRoom: '',

        {{-- Data Gedung dari Controller --}}
        buildings: {{ $buildings->toJson() }},

        {{-- Logic Dropdown Berjenjang --}}
        get filteredFloors() {
            if (!this.selectedBuilding) return [];
            const b = this.buildings.find(b => b.id == this.selectedBuilding);
            return b ? b.floors : [];
        },
        get filteredRooms() {
            if (!this.selectedFloor) return [];
            const f = this.filteredFloors.find(f => f.id == this.selectedFloor);
            return f ? f.rooms : [];
        },

        {{-- Logic Filter Pencarian & Dropdown --}}
        shouldShow(ac) {
            const matchesSearch = ac.brand.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                 ac.indoor_sn.toLowerCase().includes(this.searchQuery.toLowerCase());
            const matchesStatus = this.filterStatus === 'all' || ac.status === this.filterStatus;
            const matchesBuilding = this.filterBuilding === 'all' || ac.building_id == this.filterBuilding;
            const matchesBrand = this.filterBrand === 'all' || ac.brand === this.filterBrand;

            return matchesSearch && matchesStatus && matchesBuilding && matchesBrand;
        }
    }" class="max-w-7xl mx-auto p-6 bg-gray-50 min-h-screen">

        {{-- Header --}}
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Master Data <span class="text-indigo-600">AC</span></h1>
            <a href="{{ route('location.index') }}" class="bg-white border text-gray-400 hover:text-red-500 w-10 h-10 flex items-center justify-center rounded-xl font-bold transition">✕</a>
        </div>

        {{-- Search & Action Bar --}}
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="relative flex-grow">
                <input x-model="searchQuery" type="text" placeholder="Cari Brand atau Serial Number..." class="w-full border-none shadow-sm rounded-2xl px-12 py-3 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
                <span class="absolute left-4 top-3 text-slate-400">🔍</span>
            </div>
            <button @click="openAcModal = true" class="bg-slate-800 text-white px-8 py-3 rounded-2xl font-bold text-sm hover:bg-slate-700 transition shadow-lg flex items-center gap-2">
                <span>+</span> Tambah Inventaris AC
            </button>
        </div>

        {{-- Filter Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <select x-model="filterBuilding" class="border-none shadow-sm rounded-xl py-2.5 text-sm focus:ring-indigo-500">
                <option value="all">Semua Gedung</option>
                @foreach($buildings as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
            <select x-model="filterStatus" class="border-none shadow-sm rounded-xl py-2.5 text-sm focus:ring-indigo-500">
                <option value="all">Semua Status</option>
                <option value="baik">✅ Baik</option>
                <option value="rusak">❌ Rusak</option>
            </select>
            <select x-model="filterBrand" class="border-none shadow-sm rounded-xl py-2.5 text-sm focus:ring-indigo-500">
                <option value="all">Semua Brand</option>
                @foreach($all_acs->pluck('brand')->unique() as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
            <button @click="searchQuery = ''; filterStatus = 'all'; filterBuilding = 'all'; filterBrand = 'all'" class="text-gray-400 text-xs font-bold hover:underline text-left md:text-center">Reset Filter</button>
        </div>

        {{-- Grid Content --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($all_acs as $ac)
                <div x-show="shouldShow({
                    brand: '{{ $ac->brand }}',
                    status: '{{ $ac->status }}',
                    building_id: '{{ $ac->room->floor->building_id }}',
                    indoor_sn: '{{ $ac->indoor_sn }}'
                })" class="animate-fadeIn">
                    @include('partials.ac-card', ['ac' => $ac])
                </div>
            @endforeach
        </div>

        {{-- ========================================== --}}
        {{-- MODAL DETAIL AC (Sesuai Referensi Gambar) --}}
        {{-- ========================================== --}}
        <div x-show="openDetailModal" class="fixed inset-0 z-[150] overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openDetailModal = false; isEditingAc = false" class="bg-white rounded-[3rem] max-w-5xl w-full overflow-hidden shadow-2xl animate-zoomIn">
                    
                    <form :action="`/ac/${selectedAc.id}`" method="POST" enctype="multipart/form-data">
                        @csrf @method('PATCH')
                        
                        <div class="flex flex-col md:flex-row max-h-[90vh]">
                            {{-- Sisi Kiri: Foto --}}
                            <div class="md:w-[35%] bg-gray-50 p-8 flex flex-col gap-6 border-r border-gray-100 overflow-y-auto">
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Foto Indoor</label>
                                    <div class="relative group">
                                        <img :src="selectedAc.image_indoor_url || 'https://via.placeholder.com/400x400?text=No+Photo'" class="w-full aspect-square object-cover rounded-[2rem] shadow-md border-4 border-white">
                                        <template x-if="isEditingAc">
                                            <input type="file" name="image_indoor" class="mt-2 text-[10px] w-full file:rounded-full file:border-0 file:bg-indigo-50 file:px-4 file:py-2">
                                        </template>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Foto Outdoor</label>
                                    <div class="relative group">
                                        <img :src="selectedAc.image_outdoor_url || 'https://via.placeholder.com/400x300?text=No+Photo'" class="w-full aspect-video object-cover rounded-[2rem] shadow-md border-4 border-white">
                                        <template x-if="isEditingAc">
                                            <input type="file" name="image_outdoor" class="mt-2 text-[10px] w-full file:rounded-full file:border-0 file:bg-indigo-50 file:px-4 file:py-2">
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Sisi Kanan: Konten --}}
                            <div class="flex-grow p-10 flex flex-col overflow-y-auto">
                                <div class="flex justify-between items-start mb-8">
                                    <div>
                                        <span class="px-3 py-1 rounded-md text-[10px] font-black uppercase tracking-tighter mb-3 inline-block"
                                            :class="selectedAc.status === 'baik' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                                            x-text="selectedAc.status">
                                        </span>
                                        <h2 class="text-4xl font-black text-slate-800 uppercase tracking-tighter" x-text="`${selectedAc.brand} ${selectedAc.model}`"></h2>
                                        <p class="text-indigo-600 font-bold text-sm mt-1" x-text="`${selectedAc.building_name} • ${selectedAc.floor_name} • ${selectedAc.room_name}`"></p>
                                    </div>
                                    <button type="button" @click="openDetailModal = false; isEditingAc = false" class="text-3xl text-slate-300 hover:text-slate-800 transition">✕</button>
                                </div>

                                <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="col-span-2 p-4 bg-slate-50 rounded-2xl border border-slate-100" x-show="isEditingAc">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Relokasi Unit</label>
                                        <select name="room_id" class="w-full border-gray-200 rounded-xl text-sm font-bold" x-model="selectedAc.room_id">
                                            @foreach($buildings as $b)
                                                <optgroup label="🏢 {{ $b->name }}">
                                                    @foreach($b->floors as $f)
                                                        @foreach($f->rooms as $r)
                                                            <option value="{{ $r->id }}">{{ $f->name }} - {{ $r->name }}</option>
                                                        @endforeach
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Jenis AC (PK)</label>
                                        <p x-show="!isEditingAc" class="text-lg font-bold text-slate-700" x-text="selectedAc.ac_type"></p>
                                        <input x-show="isEditingAc" type="text" name="ac_type" x-model="selectedAc.ac_type" class="w-full border-gray-200 rounded-xl font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tipe Unit</label>
                                        <p x-show="!isEditingAc" class="text-lg font-bold text-slate-700 uppercase" x-text="selectedAc.model_type"></p>
                                        <input x-show="isEditingAc" type="text" name="model_type" x-model="selectedAc.model_type" class="w-full border-gray-200 rounded-xl font-bold uppercase">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">SN Indoor</label>
                                        <p x-show="!isEditingAc" class="text-lg font-mono font-bold text-indigo-600" x-text="selectedAc.indoor_sn"></p>
                                        <input x-show="isEditingAc" type="text" name="indoor_sn" x-model="selectedAc.indoor_sn" class="w-full border-gray-200 rounded-xl font-mono text-indigo-600">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">SN Outdoor</label>
                                        <p x-show="!isEditingAc" class="text-lg font-mono font-bold text-indigo-600" x-text="selectedAc.outdoor_sn || '-'"></p>
                                        <input x-show="isEditingAc" type="text" name="outdoor_sn" x-model="selectedAc.outdoor_sn" class="w-full border-gray-200 rounded-xl font-mono text-indigo-600">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Spesifikasi / Kapasitas</label>
                                        <p x-show="!isEditingAc" class="text-lg font-bold text-slate-700" x-text="selectedAc.specifications || '-'"></p>
                                        <input x-show="isEditingAc" type="text" name="specifications" x-model="selectedAc.specifications" class="w-full border-gray-200 rounded-xl font-bold">
                                    </div>
                                </div>

                                {{-- Action Footer --}}
                                <div class="mt-auto pt-10 flex justify-between items-center">
                                    <template x-if="isEditingAc">
                                        <button type="button" @click="$refs.masterDeleteForm.submit()" class="text-red-500 text-xs font-black uppercase hover:underline">Hapus Unit</button>
                                    </template>
                                    <div class="flex gap-4 ml-auto">
                                        <button x-show="!isEditingAc" type="button" @click="isEditingAc = true" class="bg-indigo-600 text-white px-10 py-3 rounded-2xl font-black text-xs uppercase shadow-lg hover:bg-indigo-700 transition">Edit Data</button>
                                        <button x-show="isEditingAc" type="button" @click="isEditingAc = false" class="border px-6 py-3 rounded-2xl font-black text-xs uppercase">Batal</button>
                                        <button x-show="isEditingAc" type="submit" class="bg-slate-800 text-white px-10 py-3 rounded-2xl font-black text-xs uppercase shadow-lg hover:bg-slate-900 transition">Simpan Perubahan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    {{-- Form Hapus Hidden --}}
                    <form x-ref="masterDeleteForm" :action="`/ac/${selectedAc.id}`" method="POST" class="hidden" onsubmit="return confirm('Hapus permanen unit ini?')">
                        @csrf @method('DELETE')
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL TAMBAH AC (Logic Dependent Dropdown) --}}
        {{-- Sesuai Screenshot 2025-12-22 090235.png --}}
        <div x-show="openAcModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openAcModal = false" class="bg-white rounded-[2.5rem] max-w-3xl w-full p-10 shadow-2xl animate-zoomIn">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Tambah Inventaris AC</h2>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Sistem Manajemen Aset</p>
                        </div>
                        <button @click="openAcModal = false" class="text-3xl text-gray-300 hover:text-gray-600 transition">&times;</button>
                    </div>

                    <form action="{{ route('ac.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Dropdown Lokasi Berjenjang --}}
                            <div class="md:col-span-2 grid grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Gedung</label>
                                    <select x-model="selectedBuilding" @change="selectedFloor = ''; selectedRoom = ''" class="w-full border-none shadow-sm rounded-xl text-sm font-bold focus:ring-indigo-500" required>
                                        <option value="">-- Pilih Gedung --</option>
                                        <template x-for="b in buildings" :key="b.id">
                                            <option :value="b.id" x-text="b.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Lantai</label>
                                    <select x-model="selectedFloor" @change="selectedRoom = ''" :disabled="!selectedBuilding" class="w-full border-none shadow-sm rounded-xl text-sm font-bold focus:ring-indigo-500 disabled:opacity-50" required>
                                        <option value="">-- Pilih Lantai --</option>
                                        <template x-for="f in filteredFloors" :key="f.id">
                                            <option :value="f.id" x-text="f.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Ruangan</label>
                                    <select name="room_id" x-model="selectedRoom" :disabled="!selectedFloor" class="w-full border-none shadow-sm rounded-xl text-sm font-bold focus:ring-indigo-500 disabled:opacity-50" required>
                                        <option value="">-- Pilih Ruangan --</option>
                                        <template x-for="r in filteredRooms" :key="r.id">
                                            <option :value="r.id" x-text="r.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            {{-- Input Data AC --}}
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Jenis AC</label>
                                <input type="text" name="ac_type" placeholder="Contoh: AC-2 PK" class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-bold" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Tipe AC</label>
                                <input type="text" name="model_type" placeholder="Contoh: Split Wall" class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-bold" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Brand</label>
                                <input type="text" name="brand" placeholder="Contoh: LG" class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-bold" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Model Unit</label>
                                <input type="text" name="model" placeholder="Contoh: CU-PN18SKP" class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-bold" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">SN Indoor</label>
                                <input type="text" name="indoor_sn" placeholder="SN E- 030..." class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-mono font-bold" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">SN Outdoor</label>
                                <input type="text" name="outdoor_sn" placeholder="SN E- 028..." class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-mono font-bold">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Spesifikasi</label>
                                <input type="text" name="specifications" placeholder="Contoh: 18.000 Btu/h" class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-bold">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Kondisi</label>
                                <select name="status" class="w-full border-gray-100 bg-slate-50 rounded-xl text-sm font-black">
                                    <option value="baik">✅ BAIK</option>
                                    <option value="rusak">❌ RUSAK</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Foto Indoor</label>
                                <input type="file" name="image_indoor" class="w-full text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-2">Foto Outdoor</label>
                                <input type="file" name="image_outdoor" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-black mt-8 hover:bg-slate-900 transition shadow-xl uppercase tracking-widest text-xs">
                            Simpan Data AC
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-guest-layout>

<style>
    [x-cloak] { display: none !important; }
    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    .animate-zoomIn { animation: zoomIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>