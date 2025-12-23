<x-guest-layout>
    <div x-data="{ 
        viewMode: 'table',
        schedules_table: {{ json_encode($schedules_table ?? []) }},
    
// --- STATE FILTER & SEARCH ---
        searchQuery: '',
        filterBuilding: '',
        filterStatus: '',
        filterStartDate: '', // Tambahan filter tanggal mulai
        filterEndDate: '',   // Tambahan filter tanggal selesai

        // --- LOGIKA FILTERING ---
        get filteredSchedules() {
            return this.schedules_table.filter(item => {
                // 1. Search Nama
                const matchSearch = item.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                
                // 2. Filter Gedung
                const matchBuilding = this.filterBuilding === '' || item.acs.some(ac => 
                    ac.room?.floor?.building?.name === this.filterBuilding
                );

                // 3. Filter Status
                const matchStatus = this.filterStatus === '' || item.status === this.filterStatus;

                // 4. Filter Rentang Tanggal
                // Jika filter diisi, bandingkan dengan start_date jadwal
                const itemDate = item.start_date; 
                const matchDateStart = this.filterStartDate === '' || itemDate >= this.filterStartDate;
                const matchDateEnd = this.filterEndDate === '' || itemDate <= this.filterEndDate;

                return matchSearch && matchBuilding && matchStatus && matchDateStart && matchDateEnd;
            });
        },

        month: new Date().getMonth(), 
        year: new Date().getFullYear(),
        openAddModal: false,
        openEditModal: false,
        openDetailModal: false,
        openDayModal: false,
        
        selectedSchedule: { name: '', start_date: '', end_date: '', status: '', note: '', acs: [] },
        selectedStartDate: '',
        currentDayString: '',
        selectedDateEvents: [],
        
        buildings: {{ json_encode($buildings) }},
        schedules_calendar: {{ json_encode($schedules_calendar) }},

        selectedBuildingIds: [], 
        selectedFloorIds: [],
        selectedRoomId: null,
        selectedAcIds: [],

        toggleSelectAll(action) {
            const visibleAcIds = this.filteredAcs.map(ac => ac.id.toString());
            if(action === 'select') {
                this.selectedAcIds = [...new Set([...this.selectedAcIds, ...visibleAcIds])];
            } else {
                this.selectedAcIds = this.selectedAcIds.filter(id => !visibleAcIds.includes(id));
            }
        },

        showDayDetails(date, month, year) {
            this.currentDayString = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
            this.selectedStartDate = this.currentDayString;
            this.selectedDateEvents = this.schedules_calendar.filter(e => 
                e.day === date && e.month === month && e.year === year
            );
            this.openDayModal = true;
        },

        // Di dalam x-data file schedule.blade.php
// Ganti fungsi openDetail lama dengan ini
prepareLocations(schedule) {
    if (!schedule.locations && schedule.acs) {
        const grouped = {};
        schedule.acs.forEach(ac => {
            const bName = ac.room?.floor?.building?.name || 'Tanpa Gedung';
            const fName = ac.room?.floor?.name || 'Tanpa Lantai';
            const rName = ac.room?.name || 'Tanpa Ruangan';

            if (!grouped[bName]) grouped[bName] = { name: bName, floors: {} };
            if (!grouped[bName].floors[fName]) grouped[bName].floors[fName] = { name: fName, rooms: {} };
            if (!grouped[bName].floors[fName].rooms[rName]) {
                grouped[bName].floors[fName].rooms[rName] = { name: rName, acs: [] };
            }
            
            // KRUSIAL: Mapping ulang semua data agar tersedia di Modal
            grouped[bName].floors[fName].rooms[rName].acs.push({
                ...ac, // Ini akan mengambil semua data asli termasuk brand, model, dll
                id: ac.id,
                next_service_date: ac.next_service_date,
                // Pastikan nama properti di kiri (JavaScript) 
                // sama dengan nama properti di kanan (Data dari Database/Controller)
                sn_indoor: ac.sn_indoor || '-', 
                sn_outdoor: ac.sn_outdoor || '-',
                image: ac.image || ac.ac_image || null, // Cek apakah di database namanya 'image' atau 'ac_image'
                status: ac.status
            });
        });

        schedule.locations = Object.values(grouped).map(b => ({
            ...b,
            floors: Object.values(b.floors).map(f => ({
                ...f,
                rooms: Object.values(f.rooms)
            }))
        }));
    }
    return schedule;
},

    // 2. FUNGSI OPEN DETAIL (DIPERBAIKI)
    openDetail(item) {
        // Jangan gunakan stringify jika data object sudah bersih, atau pastikan re-mapping
        let cloned = JSON.parse(JSON.stringify(item));
        this.selectedSchedule = this.prepareLocations(cloned);
        
        // Sinkronisasi ID untuk checkbox
        this.selectedAcIds = item.acs ? item.acs.map(ac => ac.id.toString()) : [];
        
        this.openDayModal = false;
        this.openDetailModal = true;
    },

    // 3. FUNGSI OPEN EDIT (DIPERBAIKI)
    openEdit(item) {
        let cloned = JSON.parse(JSON.stringify(item));
        this.selectedSchedule = this.prepareLocations(cloned);
        
        this.selectedStartDate = item.start_date;
        this.selectedAcIds = item.acs ? item.acs.map(ac => ac.id.toString()) : [];
        
        this.openEditModal = true;
        this.openDetailModal = false;
        this.openDayModal = false;
    },

        toggleAcStatus(acId) {
            this.selectedSchedule.locations.forEach(b => {
                b.floors.forEach(f => {
                    f.rooms.forEach(r => {
                        r.acs.forEach(ac => {
                            if(ac.id === acId) {
                                ac.status = (ac.status === 'selesai') ? 'belum' : 'selesai';
                            }
                        });
                    });
                });
            });

            const targetAc = this.selectedSchedule.acs.find(a => a.id === acId);
            if (targetAc) targetAc.status = (targetAc.status === 'selesai') ? 'belum' : 'selesai';

            const totalAc = this.selectedSchedule.acs.length;
            const finishedAc = this.selectedSchedule.acs.filter(ac => ac.status === 'selesai').length;

            if (finishedAc === totalAc) {
                this.selectedSchedule.status = 'selesai';
            } else if (finishedAc > 0) {
                this.selectedSchedule.status = 'proses';
            } else {
                this.selectedSchedule.status = 'belum';
            }
        },

        get filteredFloors() {
            if (this.selectedBuildingIds.length === 0) return [];
            return this.buildings
                .filter(b => this.selectedBuildingIds.includes(b.id.toString()))
                .flatMap(b => b.floors);
        },

        get filteredRooms() {
            if (this.selectedFloorIds.length === 0) return [];
            return this.filteredFloors
                .filter(f => this.selectedFloorIds.includes(f.id.toString()))
                .flatMap(f => f.rooms);
        },

        get filteredAcs() {
            if (this.selectedFloorIds.length === 0) return [];
            let rooms = this.filteredRooms;
            if (this.selectedRoomId) {
                rooms = rooms.filter(r => r.id == this.selectedRoomId);
            }
            return rooms.flatMap(r => r.acs.map(ac => ({...ac, room_name: r.name})));
        }
    }" class="max-w-7xl mx-auto p-6 bg-gray-50 min-h-screen">

        {{-- Main Container --}}
{{-- HEADER TETAP MUNCUL --}}
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-[#2D365E] uppercase tracking-tighter">Jadwal Maintenance</h1>
            <button @click="openAddModal = true" class="bg-[#2D365E] text-white px-6 py-3 rounded-2xl font-bold transition-all shadow-lg active:scale-95">
                + Tambah Jadwal
            </button>
        </div>

        {{-- KONTINER FILTER: DIBUNGKUS x-show AGAR HILANG SAAT MODE CALENDAR --}}
        <div class="space-y-4 mb-8" x-show="viewMode === 'table'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-2">
            {{-- Baris 1: Search, Gedung, Status --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-grow md:max-w-xs">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama jadwal..." 
                           class="w-full pl-5 pr-4 py-3 bg-white border-none rounded-2xl text-xs font-bold shadow-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="bg-white px-4 py-1.5 rounded-2xl border border-slate-100 shadow-sm flex items-center min-w-[150px]">
                    <span class="text-[9px] font-black text-slate-400 uppercase mr-2">Gedung:</span>
                    <select x-model="filterBuilding" class="border-none bg-transparent text-xs font-black text-[#2D365E] focus:ring-0 w-full cursor-pointer uppercase">
                        <option value="">Semua</option>
                        <template x-for="b in buildings" :key="b.id">
                            <option :value="b.name" x-text="b.name"></option>
                        </template>
                    </select>
                </div>

                <div class="bg-white px-4 py-1.5 rounded-2xl border border-slate-100 shadow-sm flex items-center min-w-[140px]">
                    <span class="text-[9px] font-black text-slate-400 uppercase mr-2">Status:</span>
                    <select x-model="filterStatus" class="border-none bg-transparent text-xs font-black text-[#2D365E] focus:ring-0 w-full cursor-pointer uppercase">
                        <option value="">Semua</option>
                        <option value="belum">Belum</option>
                        <option value="proses">Proses</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
            </div>

            {{-- Baris 2: Tanggal & Reset --}}
            <div class="flex flex-wrap items-center gap-3 text-slate-400">
                <div class="bg-white px-4 py-1.5 rounded-2xl border border-slate-100 shadow-sm flex items-center">
                    <span class="text-[9px] font-black uppercase mr-2 text-slate-400">Dari:</span>
                    <input type="date" x-model="filterStartDate" class="border-none bg-transparent text-xs font-black text-[#2D365E] focus:ring-0">
                </div>
                
                <div class="bg-white px-4 py-1.5 rounded-2xl border border-slate-100 shadow-sm flex items-center">
                    <span class="text-[9px] font-black uppercase mr-2 text-slate-400">Hingga:</span>
                    <input type="date" x-model="filterEndDate" class="border-none bg-transparent text-xs font-black text-[#2D365E] focus:ring-0">
                </div>

                <button @click="searchQuery = ''; filterBuilding = ''; filterStatus = ''; filterStartDate = ''; filterEndDate = ''" 
                        class="text-[10px] font-black uppercase tracking-widest hover:text-red-500 transition-colors ml-2">
                    Reset Filter
                </button>
            </div>
        </div>

        {{-- TOMBOL SWITCHER: TARUH DI LUAR KONTINER FILTER AGAR TETAP MUNCUL --}}
        <div class="flex justify-end mb-6">
            <div class="flex bg-white p-1 rounded-2xl border border-slate-100 shadow-sm">
                <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-[#2D365E] text-white shadow-md' : 'text-slate-400'" class="px-5 py-2 rounded-xl text-xs font-black transition-all">TABLE</button>
                <button @click="viewMode = 'calendar'" :class="viewMode === 'calendar' ? 'bg-[#2D365E] text-white shadow-md' : 'text-slate-400'" class="px-5 py-2 rounded-xl text-xs font-black transition-all">CALENDAR</button>
            </div>
        </div>

        {{-- AREA KONTEN --}}
        <div x-show="viewMode === 'table'" x-cloak>
            @include('partials.schedule-table')
        </div>

        <div x-show="viewMode === 'calendar'" x-cloak>
            @include('partials.schedule-calendar')
        </div>

{{-- MODAL ADD SCHEDULE --}}
<div x-show="openAddModal" class="fixed inset-0 z-[150] overflow-y-auto" x-cloak>
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div @click.away="openAddModal = false" class="bg-white rounded-[3rem] max-w-2xl w-full p-10 shadow-2xl animate-zoomIn overflow-y-auto max-h-[90vh] custom-scrollbar">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800 tracking-tight">Add Schedule</h2>
                <button @click="openAddModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form action="{{ route('schedules.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Schedule Name</label>
                    <input type="text" name="name" required placeholder="Contoh: Service AC Rutin" class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 focus:ring-2 focus:ring-[#86D052] outline-none transition-all">
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Start Date</label>
                    <input type="date" name="start_date" x-model="selectedStartDate" required class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052]">
                </div>

                {{-- Step 1 --}}
                <div class="space-y-3">
                    <label class="block text-lg font-bold text-slate-800">1. Pilih Gedung:</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <template x-for="b in buildings" :key="b.id">
                            <button type="button" 
                                @click="selectedBuildingIds.includes(b.id.toString()) ? selectedBuildingIds = selectedBuildingIds.filter(id => id !== b.id.toString()) : selectedBuildingIds.push(b.id.toString())"
                                :class="selectedBuildingIds.includes(b.id.toString()) ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                                class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]" x-text="b.name"></button>
                        </template>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div x-show="selectedBuildingIds.length > 0" class="space-y-4 animate-fadeIn">
                    <label class="block text-lg font-bold text-slate-800">2. Pilih Lantai:</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <template x-for="f in filteredFloors" :key="f.id">
                            <button type="button" 
                                @click="selectedFloorIds.includes(f.id.toString()) ? selectedFloorIds = selectedFloorIds.filter(id => id !== f.id.toString()) : selectedFloorIds.push(f.id.toString())"
                                :class="selectedFloorIds.includes(f.id.toString()) ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                                class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]" x-text="f.name"></button>
                        </template>
                    </div>
                </div>

                {{-- Step 3: Pemilihan AC Detail --}}
                <div x-show="selectedFloorIds.length > 0" class="space-y-4 animate-fadeIn">
                    <label class="block text-lg font-bold text-slate-800">3. Pilih Unit AC:</label>
                    
                    {{-- Filter Ruangan & Action Buttons --}}
                    <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100 space-y-4">
                        <select x-model="selectedRoomId" class="w-full border-slate-200 rounded-xl py-3 px-4 text-sm outline-none focus:ring-2 focus:ring-[#86D052]">
                            <option value="">- Semua Ruangan -</option>
                            <template x-for="r in filteredRooms" :key="r.id">
                                <option :value="r.id" x-text="r.name"></option>
                            </template>
                        </select>
                        <div class="flex gap-3">
                            <button type="button" @click="toggleSelectAll('select')" class="flex-1 bg-slate-200 text-slate-700 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-slate-300 transition-all">Select All</button>
                            <button type="button" @click="toggleSelectAll('deselect')" class="flex-1 bg-red-50 text-red-500 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-red-100 transition-all">Deselect All</button>
                        </div>
                    </div>

                    {{-- List AC dengan Detail (Card Style) --}}
                    <div class="grid grid-cols-1 gap-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <template x-for="ac in filteredAcs" :key="ac.id">
                            <label class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 group"
                                   :class="selectedAcIds.includes(ac.id.toString()) ? 'border-indigo-500 bg-indigo-50/30' : 'border-slate-100 bg-white hover:border-slate-200'">
                                
                                <input type="checkbox" name="ac_ids[]" :value="ac.id" 
                                       :checked="selectedAcIds.includes(ac.id.toString())"
                                       @change="selectedAcIds.includes(ac.id.toString()) ? selectedAcIds = selectedAcIds.filter(id => id !== ac.id.toString()) : selectedAcIds.push(ac.id.toString())"
                                       class="hidden">

                                <div class="flex items-center gap-4 w-full">
                                    {{-- Thumbnail --}}
                                    <div class="w-14 h-14 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                                        <template x-if="ac.image_indoor">
                                            <img :src="`/storage/${ac.image_indoor}`" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!ac.image_indoor">
                                            <div class="w-full h-full flex items-center justify-center text-slate-300">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <h4 class="text-xs font-black text-[#2D365E] uppercase truncate" x-text="ac.brand || 'Unit AC'"></h4>
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                                                 :class="selectedAcIds.includes(ac.id.toString()) ? 'border-indigo-500 bg-indigo-500' : 'border-slate-200'">
                                                <svg x-show="selectedAcIds.includes(ac.id.toString())" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5" x-text="ac.room_name"></p>
                                        <div class="flex gap-2 mt-1">
                                            <span class="text-[8px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded uppercase" x-text="'Model: ' + (ac.model || '-')"></span>
                                            <span class="text-[8px] font-black bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded uppercase" x-text="'SN: ' + (ac.indoor_sn || '-')"></span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Catatan</label>
                    <textarea name="notes" placeholder="Contoh: Ganti filter atau cek tekanan freon..." class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052] min-h-[100px] transition-all"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-[#86D052] text-white py-4 rounded-2xl font-bold text-lg shadow-lg shadow-green-200 hover:scale-[1.01] active:scale-95 transition-all">
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL LIST HARIAN --}}
<div x-show="openDayModal" class="fixed inset-0 z-[140] overflow-y-auto" x-cloak>
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div @click.away="openDayModal = false" class="bg-white rounded-[2rem] max-w-2xl w-full p-8 shadow-2xl animate-zoomIn">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-black text-slate-800 tracking-tight">
                    Jadwal Tanggal <span x-text="currentDayString"></span>
                </h3>
                <button @click="openDayModal = false" class="text-3xl font-bold text-slate-400">✕</button>
            </div>
            
            <button @click="openDayModal = false; openAddModal = true" class="bg-[#2D365E] text-white px-6 py-3 rounded-xl font-bold text-sm mb-8">+ Tambah Jadwal</button>
            
            <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-4 custom-scrollbar">
                <template x-for="event in selectedDateEvents" :key="event.id">
                    <div class="border border-slate-100 rounded-[2.5rem] p-6 bg-white shadow-sm ring-1 ring-slate-50">
                        {{-- Header Jadwal --}}
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h4 class="text-xl font-black text-slate-800 uppercase leading-none mb-1" x-text="event.name"></h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Daftar Pemeliharaan Unit</p>
                            </div>
                            <span :class="{
                                'bg-[#E2F7E3] text-[#4CAF50]': event.status === 'selesai', 
                                'bg-[#FFF4DE] text-[#FFA800]': event.status === 'proses', 
                                'bg-[#FFE2E5] text-[#F64E60]': event.status === 'belum'
                            }" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase" x-text="event.status"></span>
                        </div>

                        {{-- Isi Detail Lokasi & AC --}}
                        <div class="space-y-4 mb-6">
                            <template x-for="building in (event.locations || [])" :key="building.name">
                                <div class="bg-slate-50/50 rounded-2xl p-4 border border-slate-50">
                                    {{-- Nama Gedung --}}
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-sm">🏢</span>
                                        <span class="text-xs font-black text-[#2D365E] uppercase" x-text="building.name"></span>
                                    </div>

                                    <template x-for="floor in building.floors" :key="floor.name">
                                        <div class="ml-4 mb-3 last:mb-0">
                                            {{-- Nama Lantai --}}
                                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-2 border-l-2 border-slate-200 pl-2" x-text="floor.name"></p>
                                            
                                            <template x-for="room in floor.rooms" :key="room.name">
                                                <div class="mb-4 last:mb-0">
                                                    {{-- Nama Ruangan --}}
                                                    <p class="text-[10px] font-black text-slate-700 mb-2 ml-2" x-text="'📍 ' + room.name"></p>
                                                    
                                                    {{-- Grid Daftar AC --}}
                                                    <div class="grid grid-cols-1 gap-2 ml-2">
                                                        <template x-for="ac in room.acs" :key="ac.id">
                                                            <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                                                <div class="flex flex-col">
                                                                    {{-- Tampilkan Brand dan AC Type --}}
                                                                    <div class="flex items-center gap-1">
                                                                        <span class="text-[10px] font-black text-slate-800" x-text="ac.brand ? ac.brand.toUpperCase() : 'UNIT'"></span>
                                                                        <span class="text-[10px] font-bold text-blue-600" x-text="ac.ac_type ? ' - ' + ac.ac_type : ''"></span>
                                                                    </div>
                                                                    {{-- Tampilkan Model --}}
                                                                    <span class="text-[9px] text-slate-500 italic font-medium" x-text="'Model: ' + (ac.model || '-')"></span>
                                                                </div>

                                                                {{-- Status Per Unit: Hijau jika selesai, Merah jika belum --}}
                                                                <div class="flex items-center gap-2 bg-slate-50 px-2 py-1 rounded-lg">
                                                                    <span :class="ac.status === 'selesai' ? 'bg-green-500 shadow-[0_0_5px_rgba(34,197,94,0.4)]' : 'bg-red-500 shadow-[0_0_5px_rgba(239,68,68,0.4)]'" 
                                                                          class="w-2 h-2 rounded-full"></span>
                                                                    <span :class="ac.status === 'selesai' ? 'text-green-600' : 'text-red-600'" 
                                                                          class="text-[9px] font-black uppercase" 
                                                                          x-text="ac.status === 'selesai' ? 'Selesai' : 'Belum'"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <button @click="openDetail(event)" class="w-full bg-[#2D365E] text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg active:scale-95">
                            Update Status Pekerjaan
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL: DETAIL & STATUS UPDATE --}}
{{-- ========================================== --}}
<div x-show="openDetailModal" class="fixed inset-0 z-[170] overflow-y-auto" x-cloak>
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div @click.away="openDetailModal = false" 
             class="bg-white rounded-[2rem] max-w-2xl w-full p-8 shadow-2xl animate-zoomIn overflow-y-auto max-h-[95vh] custom-scrollbar">
            
            {{-- Tombol Tutup --}}
            <div class="flex justify-end">
                <button @click="openDetailModal = false" class="text-2xl font-bold text-slate-400 hover:text-slate-600">✕</button>
            </div>

            {{-- Header Informasi --}}
            <div class="mb-8">
                <h2 class="text-2xl font-black text-[#2D365E] mb-4">Detail Jadwal: <span x-text="selectedSchedule?.name"></span></h2>
                
                <div class="grid grid-cols-1 gap-1 text-sm">
                    <div class="flex">
                        <span class="w-32 font-bold text-slate-700">Nama:</span>
                        <span class="font-black text-slate-900" x-text="selectedSchedule?.name"></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-bold text-slate-700">Tanggal Mulai:</span>
                        <span class="text-slate-600" x-text="selectedSchedule?.start_date"></span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-bold text-slate-700">Status:</span>
                        <span :class="{
                            'bg-[#E2F7E3] text-[#4CAF50]': selectedSchedule?.status === 'selesai',
                            'bg-[#FFF4DE] text-[#FFA800]': selectedSchedule?.status === 'proses',
                            'bg-[#FFE2E5] text-[#F64E60]': selectedSchedule?.status === 'belum'
                        }" class="px-3 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-tighter" x-text="selectedSchedule?.status"></span>
                    </div>
                    <div class="flex mt-2">
                        <span class="w-32 font-bold text-slate-700">Catatan:</span>
                        <span class="text-slate-500 italic" x-text="selectedSchedule?.note || '-'"></span>
                    </div>
                </div>

                {{-- Statistik Progres --}}
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <p class="text-xs font-black text-[#2D365E] uppercase tracking-widest">
                        Status Pengerjaan AC (Selesai: 
                        <span class="text-green-600" x-text="selectedSchedule?.acs.filter(a => a.status === 'selesai').length"></span>/
                        <span x-text="selectedSchedule?.acs.length"></span>)
                    </p>
                </div>
            </div>

            {{-- Iterasi Gedung -> Lantai -> Ruangan --}}
            <div class="space-y-8">
                <template x-for="building in (selectedSchedule?.locations || [])" :key="building.name">
                    <div class="space-y-4">
                        <h3 class="flex items-center gap-2 text-md font-black text-[#4A90E2] uppercase">
                            🏢 <span x-text="building.name"></span>
                        </h3>

                        <template x-for="floor in building.floors" :key="floor.name">
                            <div class="ml-4 space-y-4">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]" x-text="floor.name"></p>
                                
                                <template x-for="room in floor.rooms" :key="room.name">
                                    <div class="ml-2 space-y-4">
                                        <p class="text-xs font-black text-slate-700" x-text="`Ruangan: ${room.name}`"></p>
                                        
                                        {{-- List Kartu AC --}}
                                        <div class="space-y-4">
                                            <template x-for="ac in room.acs" :key="ac.id">
                                                <div class="group">
                                                    <label class="flex items-center gap-3 mb-2 cursor-pointer">
                                                        <input type="checkbox" 
                                                               :checked="ac.status === 'selesai'" 
                                                               @change="toggleAcStatus(ac.id)" 
                                                               class="w-5 h-5 rounded border-slate-300 text-[#2D365E] focus:ring-[#2D365E]">
                                                        <span class="text-xs font-black text-slate-600 group-hover:text-slate-900" 
                                                              x-text="`${ac.brand} - ${ac.ac_type || ''}`"></span>
                                                    </label>

                                                    <div class="flex gap-4 bg-white border border-slate-100 p-4 rounded-[1.5rem] shadow-sm ring-1 ring-slate-50">
                                                        <div class="w-24 h-24 bg-slate-100 rounded-2xl flex-shrink-0 overflow-hidden border border-slate-50">
                                                            <img :src="ac.image ? `/storage/${ac.image}` : '/images/placeholder-ac.jpg'" 
                                                                 class="w-full h-full object-cover">
                                                        </div>

                                                        <div class="flex flex-col justify-center space-y-1.5">
                                                            <p class="text-xs font-black text-slate-800 uppercase tracking-tight">
                                                                Model: <span class="text-slate-600" x-text="ac.model || '-'"></span>
                                                            </p>
                                                            <p class="text-[10px] text-slate-400 font-bold italic tracking-tight">
                                                                SN: <span x-text="ac.sn_indoor || '-'"></span> / <span x-text="ac.sn_outdoor || '-'"></span>
                                                            </p>
                                                            <p class="text-[10px] text-slate-500 font-medium">
                                                                Area: <span class="font-bold" x-text="building.name"></span> | 
                                                                <span x-text="floor.name"></span> | 
                                                                <span x-text="room.name"></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Section Tombol: Simpan & Edit --}}
            <div class="mt-10 space-y-4">
                {{-- Form Simpan Perubahan --}}
                <form :action="`/schedules/${selectedSchedule?.id}/update-status`" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="space-y-3">
    <template x-for="ac in selectedSchedule?.acs" :key="ac.id">
        <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-slate-100">
            <span class="text-xs font-bold text-slate-700" x-text="ac.brand"></span>
            <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase"
                  :class="ac.status === 'selesai' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                  x-text="ac.status"></span>
        </div>
    </template>
</div>

<div class="mt-4 p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
    <h4 class="text-[10px] font-black text-slate-400 uppercase mb-2 tracking-widest">Estimasi Service Berikutnya (6 Bulan)</h4>
    <template x-for="ac in selectedSchedule?.acs">
        <div class="flex justify-between items-center mb-1">
            <span class="text-[11px] font-bold text-slate-600" x-text="ac.brand"></span>
            <span class="text-[11px] font-black text-indigo-600" 
                  x-text="ac.next_service_date ? new Date(ac.next_service_date).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'}) : '-'"></span>
        </div>
    </template>
</div>
                    
                    <input type="hidden" name="status" :value="selectedSchedule?.acs.every(a => a.status === 'selesai') ? 'selesai' : (selectedSchedule?.acs.some(a => a.status === 'selesai') ? 'proses' : 'belum')">
                    
                    <button type="submit" 
                            class="w-full bg-[#2D365E] text-white py-4 rounded-2xl font-black text-sm uppercase tracking-[0.2em] shadow-lg hover:bg-[#1a203a] transition-all active:scale-95">
                        Simpan Perubahan
                    </button>
                </form>

                {{-- Tombol Edit Jadwal (Desain Sama dengan Simpan) --}}
                <button @click="openDetailModal = false; openEditModal = true" 
                        class="w-full bg-amber-500 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-[0.2em] shadow-lg shadow-amber-200 hover:bg-amber-600 transition-all active:scale-95">
                    Edit Detail Jadwal
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL: EDIT JADWAL (Tampilan Konsisten dengan Add) --}}
{{-- ========================================== --}}
<div x-show="openEditModal" class="fixed inset-0 z-[180] overflow-y-auto" x-cloak>
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div @click.away="openEditModal = false" 
             class="bg-white rounded-[3rem] max-w-2xl w-full p-10 shadow-2xl animate-zoomIn overflow-y-auto max-h-[90vh] custom-scrollbar relative">
            
            {{-- Tombol Batal (Silang di Pojok) --}}
            <button @click="openEditModal = false" class="absolute top-8 right-8 w-10 h-10 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
                <span class="text-xl font-bold">✕</span>
            </button>

            <h2 class="text-3xl font-bold text-slate-800 mb-8 tracking-tight">Edit Schedule</h2>

            <form :action="`/schedules/${selectedSchedule?.id}`" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Nama Jadwal --}}
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Schedule Name</label>
                    <input type="text" name="name" x-model="selectedSchedule.name" required 
                           class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 focus:ring-2 focus:ring-[#86D052] outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Tanggal Mulai --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700 ml-1">Start Date</label>
                        <input type="date" name="start_date" x-model="selectedSchedule.start_date" required 
                               class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052]">
                    </div>

                    {{-- Tanggal Selesai --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700 ml-1">End Date</label>
                        <input type="date" name="end_date" x-model="selectedSchedule.end_date" 
                               class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052]">
                    </div>
                </div>

                {{-- Status Utama --}}
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Status Utama</label>
                    <select name="status" x-model="selectedSchedule.status" 
                            class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 text-sm outline-none focus:ring-2 focus:ring-[#86D052] font-bold text-slate-600">
                        <option value="belum">BELUM DIKERJAKAN</option>
                        <option value="proses">DALAM PROSES</option>
                        <option value="selesai">SELESAI</option>
                    </select>
                </div>

                {{-- UNIT AC SECTION (Style Mirip Step di Modal Add) --}}
                <div class="space-y-6 pt-4 border-t border-slate-100">
                    {{-- Step 1: Pilih Gedung --}}
                    <div class="space-y-3">
                        <label class="block text-lg font-bold text-slate-800">1. Edit Gedung:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <template x-for="b in buildings" :key="'eb-'+b.id">
                                <button type="button" 
                                    @click="selectedBuildingIds.includes(b.id.toString()) ? selectedBuildingIds = selectedBuildingIds.filter(id => id !== b.id.toString()) : selectedBuildingIds.push(b.id.toString())"
                                    :class="selectedBuildingIds.includes(b.id.toString()) ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                                    class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]" x-text="b.name"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Step 2: Pilih Lantai --}}
                    <div x-show="selectedBuildingIds.length > 0" class="space-y-3 animate-fadeIn">
                        <label class="block text-lg font-bold text-slate-800">2. Edit Lantai:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <template x-for="f in filteredFloors" :key="'ef-'+f.id">
                                <button type="button" 
                                    @click="selectedFloorIds.includes(f.id.toString()) ? selectedFloorIds = selectedFloorIds.filter(id => id !== f.id.toString()) : selectedFloorIds.push(f.id.toString())"
                                    :class="selectedFloorIds.includes(f.id.toString()) ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                                    class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]" x-text="f.name"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Step 3: Pilih AC --}}
                    <div x-show="selectedFloorIds.length > 0" class="space-y-4 animate-fadeIn">
                        <div class="flex justify-between items-center">
                            <label class="block text-lg font-bold text-slate-800">3. Edit Unit AC:</label>
                            <span class="text-[10px] font-black text-white bg-[#86D052] px-3 py-1 rounded-full" x-text="selectedAcIds.length + ' TERPILIH'"></span>
                        </div>
                        
                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 space-y-4">
                            <select x-model="selectedRoomId" class="w-full border-slate-200 rounded-xl py-3 px-4 text-sm outline-none focus:ring-2 focus:ring-[#86D052]">
                                <option value="">-Tampilkan Semua Ruangan-</option>
                                <template x-for="r in filteredRooms" :key="'er-'+r.id">
                                    <option :value="r.id" x-text="r.name"></option>
                                </template>
                            </select>
                            <div class="flex gap-3">
                                <button type="button" @click="toggleSelectAll('select')" class="flex-1 bg-[#86D052] text-white py-2 rounded-lg text-xs font-bold shadow-sm active:scale-95 transition-all">Select All</button>
                                <button type="button" @click="toggleSelectAll('deselect')" class="flex-1 bg-[#FF6B6B] text-white py-2 rounded-lg text-xs font-bold shadow-sm active:scale-95 transition-all">Deselect All</button>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                            <template x-for="ac in filteredAcs" :key="'eac-'+ac.id">
                                <label class="flex items-center gap-3 bg-white p-3 rounded-xl cursor-pointer group border-2 transition-all"
                                       :class="selectedAcIds.includes(ac.id.toString()) ? 'border-[#86D052]' : 'border-transparent'">
                                    <input type="checkbox" name="ac_ids[]" :value="ac.id" x-model="selectedAcIds" class="w-5 h-5 rounded border-slate-300 text-[#86D052] focus:ring-0">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700 uppercase" x-text="ac.ac_type + ' ' + ac.brand"></span>
                                        <span class="text-[10px] font-bold text-slate-400" x-text="ac.room_name"></span>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Catatan</label>
                    <input type="text" name="note" x-model="selectedSchedule.note" placeholder="Catatan tambahan..." 
                           class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052]">
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col gap-3 pt-6">
                    <button type="submit" class="w-full bg-[#86D052] text-white py-4 rounded-2xl font-bold text-lg shadow-lg hover:scale-[1.01] transition-all">
                        Update Schedule
                    </button>
                    
                    <button type="button" 
                            @click="if(confirm('Hapus jadwal ini?')) { document.getElementById('delete-form-' + selectedSchedule.id).submit(); }"
                            class="w-full bg-red-50 text-red-500 py-4 rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">
                        Delete Schedule
                    </button>
                </div>
            </form>

            {{-- Form Hapus Tersembunyi --}}
            <template x-if="selectedSchedule">
                <form :id="'delete-form-' + selectedSchedule.id" :action="`/schedules/${selectedSchedule.id}`" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </template>
        </div>
    </div>
</div>

    </div>
</x-guest-layout>

<style>
    [x-cloak] { display: none !important; }
    .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
    .animate-zoomIn { animation: zoomIn 0.2s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>