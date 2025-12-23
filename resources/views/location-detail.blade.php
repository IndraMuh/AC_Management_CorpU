<x-guest-layout>
    {{-- Inisialisasi state Alpine.js dengan penambahan fitur Sortir --}}
    <div x-data="{ 
        activeTab: 'all', 
        openAcModal: false, 
        editMode: false,
        openEditBuildingModal: false,
        openEditFloorModal: false,
        openDetailModal: false,
        isEditingAc: false,
        selectedFloorName: '',
        selectedFloorId: null,
        selectedAc: {},
        openHistoryModal: false,
historyLoading: false,
acHistoryData: { history: [] },

fetchHistory(id) {
    this.historyLoading = true;
    this.openHistoryModal = true;
    this.acHistoryData = { history: [] }; // Reset data lama

    fetch(`/ac-history/${id}`)
        .then(res => {
            if (!res.ok) throw new Error('Route tidak ditemukan');
            return res.json();
        })
        .then(data => {
            this.acHistoryData = data;
            this.historyLoading = false;
        })
        .catch(err => {
            console.error(err);
            this.historyLoading = false;
            alert('Gagal mengambil data: Pastikan Route sudah terdaftar.');
        });
},
        // State Filter & Search
        searchQuery: '',
        filterStatus: 'all',
        filterRoom: 'all',
        filterBrand: 'all',

        // Fungsi Filter Logic
        shouldShow(ac) {
            const matchesSearch = ac.brand.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                 ac.indoor_sn.toLowerCase().includes(this.searchQuery.toLowerCase());
            const matchesStatus = this.filterStatus === 'all' || ac.status === this.filterStatus;
            const matchesRoom = this.filterRoom === 'all' || ac.room_id == this.filterRoom;
            const matchesBrand = this.filterBrand === 'all' || ac.brand === this.filterBrand;
            const matchesTab = this.activeTab === 'all' || 'floor-' + ac.floor_id === this.activeTab;

            return matchesSearch && matchesStatus && matchesRoom && matchesBrand && matchesTab;
        }
    }" class="max-w-7xl mx-auto p-6 bg-gray-50 min-h-screen">
        
        {{-- Header & Breadcrumb --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('location.index') }}" class="hover:underline">Location</a> / {{ $building->name }}
                </nav>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <span x-text="editMode ? '⚙️ Mode Edit: ' + '{{ $building->name }}' : '{{ $building->name }}'"></span>
                </h1>
            </div>
            <div class="flex gap-3">
                <button @click="editMode = !editMode" 
                        :class="editMode ? 'bg-amber-500 text-white shadow-lg scale-105' : 'bg-white text-slate-700 border'"
                        class="px-4 py-2 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                    <span x-text="editMode ? 'Selesai Edit' : '🔧 Aktifkan Mode Edit'"></span>
                </button>
                <a href="{{ route('location.index') }}" class="bg-white border text-gray-400 hover:text-red-500 w-10 h-10 flex items-center justify-center rounded-xl font-bold transition">✕</a>
            </div>
        </div>

        {{-- Statistik Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-teal-500 text-white p-6 rounded-3xl shadow-sm">
                <p class="text-lg opacity-80">Total Lantai</p>
                <h3 class="text-4xl font-bold">{{ $building->floors->count() }} <span class="text-sm font-normal">Lantai</span></h3>
            </div>
            <div class="bg-red-100 text-red-600 p-6 rounded-3xl shadow-sm border border-red-200">
                <p class="text-lg opacity-80">Total AC Rusak</p>
                <h3 class="text-4xl font-bold">
                    {{ $building->floors->flatMap->rooms->flatMap->acs->where('status','rusak')->count() }} 
                    <span class="text-sm font-normal">unit</span>
                </h3>
            </div>
            <div class="bg-green-100 text-green-600 p-6 rounded-3xl shadow-sm border border-green-200">
                <p class="text-lg opacity-80">Total AC</p>
                <h3 class="text-4xl font-bold">
                    {{ $building->floors->flatMap->rooms->flatMap->acs->count() }} 
                    <span class="text-sm font-normal">unit</span>
                </h3>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex gap-6 border-b mb-6 font-semibold text-slate-500 overflow-x-auto scrollbar-hide">
            <button @click="activeTab = 'all'; filterRoom = 'all'" 
                :class="activeTab === 'all' ? 'text-slate-800 border-b-2 border-slate-800 pb-2' : 'hover:text-slate-800 whitespace-nowrap pb-2'">
                Semua Unit AC
            </button>
            @foreach($building->floors as $floor)
                <div class="relative group pb-2 flex items-center">
                    <button @click="activeTab = 'floor-{{ $floor->id }}'; filterRoom = 'all'" 
                        :class="activeTab === 'floor-{{ $floor->id }}' ? 'text-slate-800 border-b-2 border-slate-800' : 'hover:text-slate-800 whitespace-nowrap'">
                        {{ $floor->name }}
                    </button>
                    <button x-show="editMode" @click="openEditFloorModal = true; selectedFloorId = '{{ $floor->id }}'; selectedFloorName = '{{ $floor->name }}'" 
                            class="ml-2 text-indigo-500 hover:text-indigo-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Actions & FILTERS Bar --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-8 items-end">
            {{-- Search --}}
            <div class="lg:col-span-1">
                <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Cari Unit</label>
                <input x-model="searchQuery" type="text" placeholder="Brand atau SN..." class="w-full border-gray-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-slate-800 outline-none transition text-sm">
            </div>

            {{-- Filter Status --}}
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Status</label>
                <select x-model="filterStatus" class="w-full border-gray-200 rounded-xl py-2 text-sm focus:ring-slate-800">
                    <option value="all">Semua Kondisi</option>
                    <option value="baik">✅ Baik</option>
                    <option value="rusak">❌ Rusak</option>
                </select>
            </div>

            {{-- Filter Ruangan --}}
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Ruangan</label>
                <select x-model="filterRoom" class="w-full border-gray-200 rounded-xl py-2 text-sm focus:ring-slate-800">
                    <option value="all">Semua Ruangan</option>
                    @foreach($building->floors as $floor)
                        <optgroup label="🏢 {{ $floor->name }}">
                            @foreach($floor->rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            {{-- Filter Brand --}}
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Brand</label>
                <select x-model="filterBrand" class="w-full border-gray-200 rounded-xl py-2 text-sm focus:ring-slate-800">
                    <option value="all">Semua Brand</option>
                    @php
                        $brands = $building->floors->flatMap->rooms->flatMap->acs->pluck('brand')->unique()->sort();
                    @endphp
                    @foreach($brands as $brand)
                        <option value="{{ $brand }}">{{ $brand }}</option>
                    @endforeach
                </select>
            </div>
        </div>

       {{-- Tombol Utama --}}
<div class="flex flex-wrap items-center justify-end gap-4 mb-6">
    {{-- Tombol Reset Filter diletakkan paling kiri dalam grup kanan ini --}}
    <button @click="searchQuery = ''; filterStatus = 'all'; filterRoom = 'all'; filterBrand = 'all'" 
            class="text-gray-400 text-sm hover:underline mr-2">
        Reset Filter
    </button>

    <button @click="openAcModal = true" 
            class="bg-slate-800 text-white px-5 py-2.5 rounded-xl hover:bg-slate-700 transition flex items-center gap-2 font-semibold text-sm">
        <span>+</span> Tambah Unit AC
    </button>

    <button @click="openEditBuildingModal = true" 
            :disabled="!editMode" 
            :class="editMode ? 'bg-indigo-600' : 'bg-gray-300'" 
            class="text-white px-5 py-2.5 rounded-xl transition font-semibold text-sm">
        Edit Gedung
    </button>

    <form action="{{ route('buildings.destroy', $building->id) }}" method="POST" onsubmit="return confirm('Hapus gedung?')">
        @csrf @method('DELETE')
        <button type="submit" 
                :disabled="!editMode" 
                :class="editMode ? 'bg-red-600' : 'bg-gray-300'" 
                class="text-white px-5 py-2.5 rounded-xl transition font-semibold text-sm">
            Hapus Gedung
        </button>
    </form>
</div>

        {{-- GRID KONTEN --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($building->floors->flatMap->rooms->flatMap->acs as $ac)
                {{-- Kita bungkus card dengan div yang dikontrol Alpine --}}
                <div x-show="shouldShow({
                    id: '{{ $ac->id }}',
                    brand: '{{ $ac->brand }}',
                    status: '{{ $ac->status }}',
                    room_id: '{{ $ac->room_id }}',
                    floor_id: '{{ $ac->room->floor_id }}',
                    indoor_sn: '{{ $ac->indoor_sn }}'
                })" x-cloak class="animate-fadeIn">
                    @include('partials.ac-card', ['ac' => $ac])
                </div>
            @endforeach
        </div>

        {{-- Script untuk Empty State (Jika hasil filter kosong) --}}
        <div x-show="false" class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed mt-6">
             <p class="text-gray-400 font-medium">Tidak ada unit AC yang sesuai dengan filter.</p>
        </div>

        {{-- MODAL DETAIL AC (VIEW & EDIT) --}}
        {{-- (Tetap sama seperti kode Anda sebelumnya) --}}
        <div x-show="openDetailModal" class="fixed inset-0 z-[150] overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openDetailModal = false; isEditingAc = false" class="bg-white rounded-3xl max-w-4xl w-full overflow-hidden shadow-2xl animate-zoomIn">
                    
                    <form :action="`/ac/${selectedAc.id}`" method="POST" enctype="multipart/form-data">
                        @csrf @method('PATCH')
                        
                        <div class="flex flex-col md:flex-row h-full max-h-[90vh]">
                            {{-- Sisi Kiri: Foto --}}
                            <div class="md:w-1/3 bg-gray-50 p-6 flex flex-col gap-6 border-r border-gray-100 overflow-y-auto">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Foto Indoor</label>
                                    <img :src="selectedAc.image_indoor_url || 'https://via.placeholder.com/400x300?text=No+Photo'" class="w-full aspect-square object-cover rounded-2xl shadow-md border-4 border-white">
                                    <template x-if="isEditingAc">
                                        <input type="file" name="image_indoor" class="text-[10px] mt-2 w-full file:rounded-lg file:border-0 file:bg-indigo-50 file:px-2 file:py-1">
                                    </template>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Foto Outdoor</label>
                                    <img :src="selectedAc.image_outdoor_url || 'https://via.placeholder.com/400x300?text=No+Photo'" class="w-full aspect-square object-cover rounded-2xl shadow-md border-4 border-white">
                                    <template x-if="isEditingAc">
                                        <input type="file" name="image_outdoor" class="text-[10px] mt-2 w-full file:rounded-lg file:border-0 file:bg-indigo-50 file:px-2 file:py-1">
                                    </template>
                                </div>
                            </div>

                            {{-- Sisi Kanan: Form Data --}}
                            <div class="flex-grow p-8 flex flex-col overflow-y-auto">
                                <div class="flex justify-between items-start mb-6">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                                :class="selectedAc.status === 'baik' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                                                x-text="selectedAc.status">
                                            </span>
                                        </div>
                                        <h2 class="text-2xl font-black text-slate-800 uppercase" x-text="`${selectedAc.brand} ${selectedAc.model}`"></h2>
                                        <p class="text-sm text-indigo-600 font-semibold" x-text="`${selectedAc.building_name} • ${selectedAc.floor_name} • ${selectedAc.room_name}`"></p>
                                    </div>
                                    <button type="button" @click="openDetailModal = false; isEditingAc = false" class="text-2xl text-gray-300 hover:text-gray-500 transition">✕</button>
                                </div>

                                <div class="grid grid-cols-2 gap-x-6 gap-y-5 flex-grow">
                                    <div class="col-span-2" x-show="isEditingAc">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pindah Ruangan</label>
                                        <select name="room_id" class="w-full border-gray-200 rounded-xl text-sm" x-model="selectedAc.room_id">
                                            @foreach($building->floors as $floor)
                                                <optgroup label="🏢 {{ $floor->name }}">
                                                    @foreach($floor->rooms as $room)
                                                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jenis AC (PK)</label>
                                        <p x-show="!isEditingAc" class="font-bold text-slate-700" x-text="selectedAc.ac_type"></p>
                                        <input x-show="isEditingAc" type="text" name="ac_type" x-model="selectedAc.ac_type" class="w-full border-gray-200 rounded-xl text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tipe Unit</label>
                                        <p x-show="!isEditingAc" class="font-bold text-slate-700" x-text="selectedAc.model_type"></p>
                                        <input x-show="isEditingAc" type="text" name="model_type" x-model="selectedAc.model_type" class="w-full border-gray-200 rounded-xl text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">SN Indoor</label>
                                        <p x-show="!isEditingAc" class="font-mono text-indigo-600 font-bold" x-text="selectedAc.indoor_sn"></p>
                                        <input x-show="isEditingAc" type="text" name="indoor_sn" x-model="selectedAc.indoor_sn" class="w-full border-gray-200 rounded-xl text-sm font-mono text-indigo-600">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">SN Outdoor</label>
                                        <p x-show="!isEditingAc" class="font-mono text-indigo-600 font-bold" x-text="selectedAc.outdoor_sn || '-'"></p>
                                        <input x-show="isEditingAc" type="text" name="outdoor_sn" x-model="selectedAc.outdoor_sn" class="w-full border-gray-200 rounded-xl text-sm font-mono text-indigo-600">
                                    </div>
                                    <div :class="isEditingAc ? 'col-span-1' : 'col-span-2'">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Spesifikasi/Kapasitas</label>
                                        <p x-show="!isEditingAc" class="font-bold text-slate-700" x-text="selectedAc.specifications || '-'"></p>
                                        <input x-show="isEditingAc" type="text" name="specifications" x-model="selectedAc.specifications" class="w-full border-gray-200 rounded-xl text-sm">
                                    </div>

                                    <div x-show="isEditingAc">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kondisi</label>
                                        <select name="status" x-model="selectedAc.status" class="w-full border-gray-200 rounded-xl text-sm font-bold">
                                            <option value="baik">✅ BAIK</option>
                                            <option value="rusak">❌ RUSAK</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Footer Action Buttons --}}
                                <div class="mt-10 pt-6 border-t border-gray-100 flex justify-between items-center">
                                    <div>
                                        <template x-if="isEditingAc">
                                            <button type="button" @click="$refs.deleteFormDetail.submit()" class="text-red-500 text-xs font-bold hover:underline flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Hapus Unit
                                            </button>
                                        </template>
                                    </div>

                                    <div class="flex gap-3">
{{-- Ganti tombol lama dengan ini --}}
<button type="button" @click="fetchHistory(selectedAc.id)" 
    class="px-6 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition text-sm flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Riwayat Servis
</button>
                                        <button x-show="isEditingAc" type="button" @click="isEditingAc = false" class="px-6 py-2 rounded-xl border border-gray-200 font-bold text-gray-500 hover:bg-gray-50 transition text-sm">Batal</button>
                                        <button x-show="!isEditingAc" type="button" @click="isEditingAc = true" class="px-8 py-2 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-lg transition text-sm flex items-center gap-2">Edit Data</button>
                                        <button x-show="isEditingAc" type="submit" class="px-8 py-2 rounded-xl bg-slate-800 text-white font-bold hover:bg-slate-900 shadow-lg transition text-sm">Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <form x-ref="deleteFormDetail" :action="`/ac/${selectedAc.id}`" method="POST" class="hidden" onsubmit="return confirm('Hapus permanen?')">
                        @csrf @method('DELETE')
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL TAMBAH AC --}}
        <div x-show="openAcModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openAcModal = false" class="bg-white rounded-3xl max-w-3xl w-full p-8 shadow-2xl animate-zoomIn">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Tambah Inventaris AC</h2>
                            <p class="text-xs text-gray-500">Gedung: {{ $building->name }}</p>
                        </div>
                        <button @click="openAcModal = false" class="text-2xl text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <form action="{{ route('ac.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Lantai & Ruangan</label>
                                <select name="room_id" required class="w-full border-gray-200 rounded-xl focus:ring-slate-800 transition">
                                    <option value="">-- Pilih Lokasi Ruangan --</option>
                                    @foreach($building->floors as $floor)
                                        <optgroup label="🏢 Lantai: {{ $floor->name }}">
                                            @foreach($floor->rooms as $room)
                                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jenis AC</label>
                                <input type="text" name="ac_type" placeholder="Contoh: AC-2 PK" class="w-full border-gray-200 rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Tipe AC</label>
                                <input type="text" name="model_type" placeholder="Contoh: Split Wall" class="w-full border-gray-200 rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Brand</label>
                                <input type="text" name="brand" placeholder="Contoh: LG" class="w-full border-gray-200 rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Model Unit</label>
                                <input type="text" name="model" placeholder="Contoh: CU-PN18SKP" class="w-full border-gray-200 rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">SN Indoor</label>
                                <input type="text" name="indoor_sn" placeholder="SN E- 030..." class="w-full border-gray-200 rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">SN Outdoor</label>
                                <input type="text" name="outdoor_sn" placeholder="SN E- 028..." class="w-full border-gray-200 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Spesifikasi</label>
                                <input type="text" name="specifications" placeholder="Contoh: 18.000 Btu/h" class="w-full border-gray-200 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Kondisi</label>
                                <select name="status" class="w-full border-gray-200 rounded-xl font-bold">
                                    <option value="baik" class="text-green-600">✅ BAIK</option>
                                    <option value="rusak" class="text-red-600">❌ RUSAK</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Foto Indoor</label>
                                <input type="file" name="image_indoor" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Foto Outdoor</label>
                                <input type="file" name="image_outdoor" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-bold mt-8 hover:bg-slate-900 transition shadow-lg">
                            Simpan Data AC
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL EDIT GEDUNG --}}
        <div x-show="openEditBuildingModal" class="fixed inset-0 z-[110] overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openEditBuildingModal = false" class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl">
                    <h2 class="text-xl font-bold mb-4">Edit Gedung: {{ $building->name }}</h2>
                    <form action="{{ route('buildings.update', $building->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Nama Gedung</label>
                                <input type="text" name="name" value="{{ $building->name }}" class="w-full border-gray-200 rounded-xl mt-1 focus:ring-slate-800" required>
                            </div>
                            
                            <div x-data="{ newFloors: [] }">
                                <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Tambah Lantai Baru</label>
                                <template x-for="(f, index) in newFloors" :key="index">
                                    <div class="flex gap-2 mb-2">
                                        <input type="text" name="new_floors[]" placeholder="Contoh: Lantai 4" class="flex-grow border-gray-200 rounded-xl text-sm" required>
                                        <button type="button" @click="newFloors.splice(index, 1)" class="text-red-500">✕</button>
                                    </div>
                                </template>
                                <button type="button" @click="newFloors.push('')" class="text-xs text-indigo-600 font-bold hover:underline">+ Tambah Input Lantai</button>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button type="button" @click="openEditBuildingModal = false" class="flex-1 bg-gray-100 py-3 rounded-xl font-bold text-gray-600 text-sm">Batal</button>
                                <button type="submit" class="flex-1 bg-slate-800 text-white py-3 rounded-xl font-bold shadow-lg text-sm">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL EDIT LANTAI & RUANGAN --}}
        <div x-show="openEditFloorModal" class="fixed inset-0 z-[110] overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openEditFloorModal = false" class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl">
                    <h2 class="text-xl font-bold mb-1">Edit <span x-text="selectedFloorName" class="text-indigo-600"></span></h2>
                    <p class="text-xs text-gray-500 mb-6 font-medium">Kelola ruangan di lantai ini</p>
                    
                    <form action="{{ route('rooms.store') }}" method="POST" class="mb-6 pb-6 border-b border-gray-100">
                        @csrf
                        <input type="hidden" name="floor_id" :value="selectedFloorId">
                        <label class="text-xs font-bold text-gray-400 uppercase mb-1 block">Tambah Ruangan Baru</label>
                        <div class="flex gap-2">
                            <input type="text" name="name" placeholder="Nama Ruang (Contoh: R. Lobby)" class="flex-grow border-gray-200 rounded-xl text-sm" required>
                            <button type="submit" class="bg-indigo-600 text-white px-6 rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Tambah</button>
                        </div>
                    </form>

                    <div class="mb-6">
                        <label class="text-xs font-bold text-gray-400 uppercase mb-2 block">Daftar Ruangan Saat Ini</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto pr-2 custom-scroll">
                            @foreach($building->floors as $fl)
                                <template x-if="selectedFloorId == {{ $fl->id }}">
                                    <div class="space-y-2">
                                        @forelse($fl->rooms as $rm)
                                            <div class="flex justify-between items-center bg-gray-50 px-4 py-2 rounded-xl border border-gray-100">
                                                <span class="text-sm font-medium">{{ $rm->name }}</span>
                                                <form action="{{ route('rooms.destroy', $rm->id) }}" method="POST" onsubmit="return confirm('Hapus ruangan ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-400 hover:text-red-600 font-bold text-sm">✕</button>
                                                </form>
                                            </div>
                                        @empty
                                            <p class="text-gray-400 text-xs italic">Belum ada ruangan.</p>
                                        @endforelse
                                    </div>
                                </template>
                            @endforeach
                        </div>
                    </div>

                    <button @click="openEditFloorModal = false" class="w-full bg-slate-100 text-slate-800 py-3 rounded-xl font-bold hover:bg-slate-200 transition text-sm">Tutup</button>
                </div>
            </div>
        </div>

        {{-- MODAL RIWAYAT POP-UP --}}
<div x-show="openHistoryModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openHistoryModal = false"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl font-black text-slate-800 uppercase">Riwayat Servis</h3>
                    <p class="text-xs text-indigo-600 font-bold" x-text="acHistoryData.brand + ' - ' + acHistoryData.sn"></p>
                </div>
                <button @click="openHistoryModal = false" class="text-2xl text-gray-300 hover:text-gray-500">✕</button>
            </div>

            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 custom-scroll">
                <template x-if="historyLoading">
                    <div class="text-center py-10 text-gray-400 font-bold animate-pulse">Memuat riwayat...</div>
                </template>

                <template x-for="item in acHistoryData.history" :key="item.date">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex justify-between items-center">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase" x-text="item.date"></p>
                            <p class="text-sm font-bold text-slate-700 uppercase" x-text="item.name"></p>
                        </div>
                        <span :class="{
                            'bg-green-100 text-green-600': item.status === 'selesai',
                            'bg-yellow-100 text-yellow-600': item.status === 'proses',
                            'bg-red-100 text-red-600': item.status === 'belum'
                        }" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase" x-text="item.status"></span>
                    </div>
                </template>

                <template x-if="!historyLoading && acHistoryData.history.length === 0">
                    <div class="text-center py-10 text-gray-400 italic text-sm">Belum ada catatan servis.</div>
                </template>
            </div>
        </div>
    </div>
</div>

    </div>
</x-guest-layout>

<style>
    [x-cloak] { display: none !important; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    .animate-zoomIn { animation: zoomIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>