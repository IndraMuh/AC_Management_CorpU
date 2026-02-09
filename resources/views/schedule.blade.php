<x-guest-layout>
    {{-- --- BACKGROUND BLOB (Sama dengan edit.blade) --- --}}
    <div class="fixed top-[-15%] right-[-15%] w-[50vw] h-[50vw] bg-[#2da2ad]/70 rounded-full blur-[60px] animate-blob -z-10"></div>
    <div class="fixed bottom-[-15%] left-[-15%] w-[55vw] h-[55vw] bg-[#D1FADF]/90 rounded-full blur-[70px] animate-blob animation-delay-2000 -z-10"></div>
    {{-- Ganti bg-gray-50 menjadi bg-white/70 dan tambahkan backdrop-blur --}}

    <div x-data="{ 

toggleAllAcStatus() {
    if (!this.selectedSchedule) return;

    // Cek apakah sekarang semuanya sudah selesai
    const isAllSelected = this.selectedSchedule.acs.every(ac => ac.status === 'selesai');

    // Jika semua sudah selesai, ubah semua jadi 'belum'
    // Jika belum semua selesai, ubah semua jadi 'selesai'
    const newStatus = isAllSelected ? 'belum' : 'selesai';

    // Update status di lokasi (UI)
    this.selectedSchedule.locations.forEach(b => {
        b.floors.forEach(f => {
            f.rooms.forEach(r => {
                r.acs.forEach(ac => {
                    ac.status = newStatus;
                });
            });
        });
    });

    // Update status di array flat acs (Data)
    this.selectedSchedule.acs.forEach(ac => {
        ac.status = newStatus;
    });

    // Update status utama jadwal
    this.selectedSchedule.status = newStatus;
},
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

        selectedBuildingIds: '',
        selectedFloorIds: '',
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
    // 1. Update status di struktur locations (UI)
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

    // 2. Update status di array flat acs (Data)
    const targetAc = this.selectedSchedule.acs.find(a => a.id === acId);
    if (targetAc) targetAc.status = (targetAc.status === 'selesai') ? 'belum' : 'selesai';

    // 3. LOGIKA PROSES: Hitung progres untuk menentukan status Jadwal Utama
    const totalAc = this.selectedSchedule.acs.length;
    const finishedAc = this.selectedSchedule.acs.filter(ac => ac.status === 'selesai').length;

    if (finishedAc === totalAc) {
        this.selectedSchedule.status = 'selesai';
    } else if (finishedAc > 0) {
        this.selectedSchedule.status = 'proses'; // <--- Ini status prosesnya
    } else {
        this.selectedSchedule.status = 'belum';
    }
},


get filteredFloors() {
    if (!this.selectedBuildingIds) return [];
    // Filter gedung berdasarkan ID tunggal
    const building = this.buildings.find(b => b.id.toString() === this.selectedBuildingIds);
    return building ? building.floors : [];
},

get filteredRooms() {
    if (!this.selectedFloorIds) return [];
    // Filter lantai berdasarkan ID tunggal dari gedung yang terpilih
    const floor = this.filteredFloors.find(f => f.id.toString() === this.selectedFloorIds);
    return floor ? floor.rooms : [];
},
        get filteredAcs() {
            if (this.selectedFloorIds.length === 0) return [];
            let rooms = this.filteredRooms;
            if (this.selectedRoomId) {
                rooms = rooms.filter(r => r.id == this.selectedRoomId);
            }
            return rooms.flatMap(r => r.acs.map(ac => ({...ac, room_name: r.name})));
        }
    }" class="max-w-7xl mx-auto p-6 bg-white/70 backdrop-blur-md min-h-screen rounded-3xl shadow-xl border border-white/20">
{{-- 1. TOMBOL NAVIGASI UTAMA (BACK TO DASHBOARD) --}}
<a href="{{ url('/dashboard') }}" 
   class="absolute top-6 right-6 md:top-8 md:right-8 text-gray-400 hover:text-red-500 transition-all duration-300 z-50">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 md:h-10 md:w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
</a>

{{-- 2. MAIN HEADER WRAPPER --}}
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pr-12 md:pr-16 gap-4">
    
    {{-- Branding, Title & Notification Bell --}}
    <div class="flex items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-[#2D365E] uppercase tracking-tighter">Schedule</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Manajemen Pemeliharaan Unit</p>
        </div>

{{-- NOTIFIKASI DROPDOWN --}}
<div x-data="{ open: false, activeTab: 'upcoming' }" class="static md:relative">
    
    {{-- TOMBOL LONCENG --}}
    <button @click="open = !open" 
        class="relative p-3 bg-white rounded-2xl shadow-sm border border-slate-100 hover:bg-slate-50 transition-all active:scale-95 mt-1 group">
        
        {{-- Animasi Lonceng --}}
        <div class="{{ ($upcomingServices->count() > 0 || $overdueAcs->count() > 0) ? 'animate-bell-ring' : '' }} inline-block">
            <span class="text-xl group-hover:scale-110 transition-transform inline-block">🔔</span>
        </div>

        {{-- DOT MERAH (BADGE) --}}
        @if($upcomingServices->count() > 0 || $overdueAcs->count() > 0)
            <span class="absolute -top-1 -right-1 flex h-4 w-4 z-10">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-gradient-to-tr from-red-500 to-pink-600 border-2 border-white shadow-sm"></span>
            </span>
        @endif
    </button>

    {{-- ISI DROPDOWN --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         @click.away="open = false" 
         x-cloak
         class="absolute md:fixed top-24 left-4 right-4 md:left-auto md:right-10 md:w-[40vw] max-w-[550px] bg-white/90 backdrop-blur-xl rounded-[2.5rem] shadow-[0_25px_80px_-15px_rgba(45,54,94,0.2)] border border-white/50 z-[100] overflow-hidden">
        
        {{-- Header --}}
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-slate-50/50 to-white">
            <div class="flex justify-between items-center mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-6 bg-indigo-600 rounded-full"></div>
                    <h3 class="text-xs font-black text-[#2D365E] uppercase tracking-widest">Pusat Notifikasi</h3>
                </div>
                <button @click="open = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="flex bg-slate-100 p-1.5 rounded-2xl w-full border border-slate-200/50">
                <button @click="activeTab = 'upcoming'" 
                        :class="activeTab === 'upcoming' ? 'bg-white text-indigo-600 shadow-md scale-[1.02]' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                    📅 Mendatang ({{ $upcomingServices->count() }})
                </button>
                <button @click="activeTab = 'overdue'" 
                        :class="activeTab === 'overdue' ? 'bg-white text-orange-600 shadow-md scale-[1.02]' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                    ⚠️ Terlambat ({{ $overdueAcs->count() }})
                </button>
            </div>
        </div>
        
        <div class="max-h-[50vh] overflow-y-auto custom-scrollbar p-6 bg-white/50">
            {{-- TAB MENDATANG --}}
            <div x-show="activeTab === 'upcoming'" x-transition:enter="transition duration-300" class="grid grid-cols-1 gap-4">
                @forelse($upcomingServices as $service)
                    <div class="group bg-gradient-to-br from-blue-50 to-indigo-50/30 p-5 rounded-[1.8rem] border border-blue-100/50 flex justify-between items-center hover:shadow-lg hover:shadow-indigo-500/10 transition-all border-l-4 border-l-indigo-500">
                        <div class="pr-3">
                            <p class="text-[12px] font-black text-slate-800 uppercase tracking-tight group-hover:text-indigo-700 transition-colors">{{ $service->name }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-[9px] bg-white/80 text-indigo-600 font-black px-2.5 py-1 rounded-lg border border-indigo-100 shadow-sm">
                                    {{ \Carbon\Carbon::parse($service->start_date)->translatedFormat('d M Y') }}
                                </span>
                                <span class="text-[8px] text-slate-400 font-bold uppercase">Jadwal Mulai</span>
                            </div>
                        </div>
                        <div class="bg-white p-3 rounded-2xl shadow-sm text-xl group-hover:rotate-12 transition-transform">🗓️</div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-20">
                        <div class="text-4xl mb-3 opacity-50">☕</div>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Tidak ada jadwal layanan.</p>
                    </div>
                @endforelse
            </div>

            {{-- TAB TERLAMBAT / OVERDUE --}}
            <div x-show="activeTab === 'overdue'" x-transition:enter="transition duration-300" class="grid grid-cols-1 gap-2">
                @forelse($overdueAcs as $ac)
                    <div class="group bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-3 hover:border-orange-200 transition-all border-l-4 border-l-orange-500 relative">
                        
                        {{-- Foto AC --}}
                        <div class="relative w-14 h-14 flex-shrink-0">
                            @if($ac->image_indoor)
                                <img src="{{ asset('storage/' . $ac->image_indoor) }}" class="w-full h-full object-cover rounded-xl shadow-sm" alt="Foto Unit">
                            @else
                                <div class="w-full h-full bg-slate-50 rounded-xl flex items-center justify-center border border-dashed border-slate-200 text-lg">
                                    ❄️
                                </div>
                            @endif
                        </div>

                        {{-- Konten Informasi --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <h4 class="text-[10px] font-black text-slate-800 uppercase truncate">
                                    {{ $ac->brand }} <span class="text-slate-400 font-medium">- {{ $ac->model }}</span>
                                </h4>
                                <span class="text-[7px] font-black text-orange-600 bg-orange-50 px-1 rounded uppercase tracking-tighter">Lewat Jadwal</span>
                            </div>

                            {{-- Lokasi --}}
                            <div class="flex items-center gap-2 mt-0.5">
                                <p class="text-[9px] text-slate-500 font-bold truncate">
                                    <span class="opacity-60">📍</span> {{ $ac->room->name }} 
                                    <span class="text-[8px] font-normal text-slate-400">({{ $ac->room->floor->building->name }})</span>
                                </p>
                            </div>

                            {{-- Teknis --}}
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[8px] text-indigo-600 font-bold uppercase">{{ $ac->ac_type }}</span>
                                <span class="text-[8px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-mono font-bold border border-slate-200/50">
                                    No. Seri: {{ $ac->indoor_sn }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-10">
                        <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Semua unit sudah diservis.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
    </div>

    {{-- Right Side Actions (Hanya Add Button) --}}
    <div class="flex items-center gap-4">
        <button @click="openAddModal = true" 
            class="bg-[#2D365E] text-white px-6 py-3 rounded-2xl font-bold transition-all shadow-lg active:scale-95 whitespace-nowrap hover:bg-[#3d487a]">
            + Tambah Jadwal
        </button>
    </div>
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
            
<form action="{{ route('schedules.store') }}" method="POST" class="space-y-6"
      @submit.prevent="
        Swal.fire({
            title: 'Simpan Jadwal?',
            text: 'Pastikan data yang diisi sudah benar.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2D365E',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $el.submit();
            }
        })
      ">
    @csrf
    {{-- Hidden Inputs agar semua pilihan AC dari gedung berbeda tetap terkirim --}}
    <template x-for="id in selectedAcIds" :key="id">
        <input type="hidden" name="ac_ids[]" :value="id">
    </template>
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Schedule Name</label>
                    <input type="text" name="name" required placeholder="Contoh: Service AC Rutin" class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 focus:ring-2 focus:ring-[#86D052] outline-none transition-all">
                </div>

                                <div class="space-y-2">
    <label class="block text-sm font-bold text-slate-700 ml-1">Pekerjaan Dikerjakan Oleh</label>
    <input type="text" name="worker_name" placeholder="Nama Teknisi atau Vendor" 
           class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 focus:ring-2 focus:ring-[#86D052] outline-none transition-all">
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
                                @click="selectedBuildingIds = b.id.toString(); selectedFloorIds = ''; selectedRoomId = '';"
                                :class="selectedBuildingIds === b.id.toString() ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                                class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]"
                                x-text="b.name">
                            </button>                        
                        </template>
                    </div>
                </div>

{{-- Step 2 --}}
<div x-show="selectedBuildingIds !== ''" class="space-y-4 animate-fadeIn">
    <label class="block text-lg font-bold text-slate-800">2. Pilih Lantai:</label>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <template x-for="f in filteredFloors" :key="f.id">
            <button type="button" 
                @click="selectedFloorIds = f.id.toString(); selectedRoomId = '';"
                :class="selectedFloorIds === f.id.toString() ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]" 
                x-text="f.name">
            </button>
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

{{-- List AC dengan Detail --}}
<div class="grid grid-cols-1 gap-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
    <template x-for="ac in filteredAcs" :key="ac.id">
        <label class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 group"
               :class="selectedAcIds.includes(ac.id.toString()) ? 'border-indigo-500 bg-indigo-50/30' : 'border-slate-100 bg-white hover:border-slate-200'">
            
            {{-- HAPUS name="ac_ids[]" di sini karena sudah ditangani oleh hidden input di atas --}}
            <input type="checkbox" 
                   :value="ac.id.toString()" 
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
                        {{-- Indikator Centang Visual --}}
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                             :class="selectedAcIds.includes(ac.id.toString()) ? 'border-indigo-500 bg-indigo-500' : 'border-slate-200'">
                            <svg x-show="selectedAcIds.includes(ac.id.toString())" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
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
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    {{-- Kolom Kiri: Teks Informasi --}}
                    <div class="space-y-2">
                        <div class="flex">
                            <span class="w-32 font-bold text-slate-700">Nama:</span>
                            <span class="font-black text-slate-900" x-text="selectedSchedule?.name"></span>
                        </div>
                        <div class="flex">
                            <span class="w-32 font-bold text-slate-700">Dikerjakan Oleh:</span>
                            <span class="font-black text-slate-900" x-text="selectedSchedule?.worker_name || '-'"></span>
                        </div>
                        <div class="flex">
                            <span class="w-32 font-bold text-slate-700">Tanggal Mulai:</span>
                            <span class="text-slate-600" x-text="selectedSchedule?.start_date"></span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-32 font-bold text-slate-700">Status:</span>
                            <span :class="{
                                'bg-[#E2F7E3] text-[#4CAF50]': selectedSchedule?.status === 'selesai',
                                'bg-[#FFF4DE] text-[#FFA800]': selectedSchedule?.status === 'proses',
                                'bg-[#FFE2E5] text-[#F64E60]': selectedSchedule?.status === 'belum'
                            }" class="px-3 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-tighter" x-text="selectedSchedule?.status"></span>
                        </div>
                        <div class="flex">
                            <span class="w-32 font-bold text-slate-700">Catatan:</span>
                            <span class="text-slate-500 italic" x-text="selectedSchedule?.note || '-'"></span>
                        </div>
                    </div>

{{-- ========================================== --}}
{{-- KOLOM KANAN: TAMPILAN BUKTI GAMBAR (MULTIPLE) --}}
{{-- ========================================== --}}
<div class="space-y-2">
    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Bukti Pekerjaan:</span>
    
    <div class="grid grid-cols-2 gap-2">
        <template x-if="selectedSchedule?.proof_image && selectedSchedule.proof_image.length > 0">
            <template x-for="(img, index) in selectedSchedule.proof_image" :key="index">
                <div class="group relative overflow-hidden rounded-xl border-2 border-slate-50 shadow-sm aspect-video">
                    <img :src="'/storage/' + img" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    
                    {{-- Overlay Zoom --}}
                    <a :href="'/storage/' + img" target="_blank" 
                       class="absolute inset-0 bg-slate-900/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-white text-[8px] font-bold bg-white/20 backdrop-blur-md px-2 py-1 rounded-full border border-white/30 uppercase">Lihat</span>
                    </a>
                </div>
            </template>
        </template>

        {{-- Tampilan jika kosong --}}
        <template x-if="!selectedSchedule?.proof_image || selectedSchedule.proof_image.length === 0">
            <div class="col-span-2 h-24 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/50">
                <span class="text-[20px] mb-1">📷</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase">Belum ada bukti</span>
            </div>
        </template>
    </div>
</div>
                </div>

                {{-- Statistik Progres --}}
                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center">
                    <p class="text-xs font-black text-[#2D365E] uppercase tracking-widest">
                        Status Pengerjaan AC (Selesai: 
                        <span class="text-green-600" x-text="selectedSchedule?.acs.filter(a => a.status === 'selesai').length"></span>/
                        <span x-text="selectedSchedule?.acs.length"></span>)
                    </p>
                    
                    <button type="button" 
                            @click="toggleAllAcStatus()" 
                            class="text-[10px] font-black px-4 py-2 rounded-xl transition-all active:scale-95 border"
                            :class="selectedSchedule?.acs.every(a => a.status === 'selesai') 
                                    ? 'bg-red-50 text-red-500 border-red-100' 
                                    : 'bg-indigo-50 text-[#2D365E] border-indigo-100'">
                        <span x-text="selectedSchedule?.acs.every(a => a.status === 'selesai') ? 'BATALKAN SEMUA' : 'TANDAI SEMUA SELESAI'"></span>
                    </button>
                </div>
            </div>

            {{-- Iterasi Gedung -> Lantai -> Ruangan (Sama seperti sebelumnya) --}}
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
                <form :action="`/schedules/${selectedSchedule?.id}/update-status`" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <template x-for="ac in selectedSchedule?.acs" :key="'hidden-status-'+ac.id">
                        <input type="hidden" :name="'ac_statuses[' + ac.id + ']'" :value="ac.status">
                    </template>

                    {{-- Daftar visual status --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="ac in selectedSchedule?.acs" :key="'view-'+ac.id">
                            <div class="flex items-center justify-between p-3 bg-slate-50/50 rounded-xl border border-slate-100">
                                <span class="text-[10px] font-bold text-slate-700 truncate mr-2" x-text="ac.brand"></span>
                                <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase transition-colors"
                                      :class="ac.status === 'selesai' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                                      x-text="ac.status"></span>
                            </div>
                        </template>
                    </div>

                    <input type="hidden" name="status" :value="selectedSchedule?.status">
                    
                    <button type="submit" 
                            class="w-full mt-6 bg-[#2D365E] text-white py-4 rounded-2xl font-black text-sm uppercase tracking-[0.2em] shadow-lg hover:bg-[#1a203a] transition-all active:scale-95">
                        Simpan Perubahan
                    </button>
                </form>

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
            
            <button @click="openEditModal = false" class="absolute top-8 right-8 w-10 h-10 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
                <span class="text-xl font-bold">✕</span>
            </button>

            <h2 class="text-3xl font-bold text-slate-800 mb-8 tracking-tight">Edit Schedule</h2>

            <form :action="'/schedules/' + selectedSchedule.id" method="POST" enctype="multipart/form-data"
                  @submit.prevent="
                    Swal.fire({
                        title: 'Simpan Perubahan?',
                        text: 'Data jadwal akan diperbarui.',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#2D365E',
                        confirmButtonText: 'Ya, Update!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $el.submit();
                        }
                    })
                  ">
                @csrf
                @method('PUT')

                {{-- Hidden inputs untuk AC --}}
                <template x-for="id in selectedAcIds" :key="'hidden-ac-'+id">
                    <input type="hidden" name="ac_ids[]" :value="id">
                </template>

                {{-- Informasi Dasar --}}
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700 ml-1">Schedule Name</label>
                        <input type="text" name="name" x-model="selectedSchedule.name" required 
                               class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 focus:ring-2 focus:ring-[#86D052] outline-none transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700 ml-1">Pekerjaan Dikerjakan Oleh</label>
                        <input type="text" name="worker_name" x-model="selectedSchedule.worker_name" placeholder="Nama Teknisi atau Vendor" 
                               class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 focus:ring-2 focus:ring-[#86D052] outline-none transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700 ml-1">Start Date</label>
                            <input type="date" name="start_date" x-model="selectedSchedule.start_date" required 
                                   class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052]">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700 ml-1">End Date</label>
                            <input type="date" name="end_date" x-model="selectedSchedule.end_date" 
                                   class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052]">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700 ml-1">Status Utama</label>
                        <select name="status" x-model="selectedSchedule.status" 
                                class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 text-sm outline-none focus:ring-2 focus:ring-[#86D052] font-bold text-slate-600">
                            <option value="belum">BELUM DIKERJAKAN</option>
                            <option value="proses">DALAM PROSES</option>
                            <option value="selesai">SELESAI</option>
                        </select>
                    </div>

                    {{-- ========================================== --}}
                    {{-- INPUT BANYAK GAMBAR + FITUR HAPUS --}}
                    {{-- ========================================== --}}
                    <div class="space-y-3 p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <label class="block text-sm font-bold text-slate-700 ml-1">Bukti Pekerjaan</label>
                        
{{-- Preview Gambar yang sudah ada dengan Tombol Hapus --}}
<div class="grid grid-cols-4 gap-4 mb-3" x-show="selectedSchedule.proof_image && selectedSchedule.proof_image.length > 0">
    <template x-for="(img, index) in selectedSchedule.proof_image" :key="index">
        {{-- Container Gambar (Harus Relative) --}}
        <div class="relative aspect-square">
            <img :src="'/storage/' + img" 
                 class="w-full h-full object-cover rounded-xl border-2 border-slate-100 shadow-sm">
            
            {{-- Input Hidden --}}
            <input type="hidden" name="existing_images[]" :value="img">
            
            {{-- Tombol Hapus (Absolute di pojok kanan atas) --}}
            <button type="button" 
                @click="selectedSchedule.proof_image.splice(index, 1)"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center shadow-lg hover:bg-red-600 hover:scale-110 active:scale-90 transition-all z-10 border-2 border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Overlay Label (Opsional) --}}
            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
                <span class="text-[8px] bg-black/50 text-white px-2 py-0.5 rounded-full backdrop-blur-sm">LAMA</span>
            </div>
        </div>
    </template>
</div>

                        {{-- Input Upload Baru --}}
                        <div class="space-y-2">
                            <span class="text-[10px] font-bold text-slate-400 block ml-1 uppercase">Tambah Foto Baru:</span>
                            <input type="file" name="proof_images[]" accept="image/*" multiple
                                   class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#2D365E] file:text-white hover:file:bg-slate-700 transition-all">
                            <p class="text-[10px] text-slate-400 ml-1 italic">*Tahan Ctrl untuk memilih banyak. Gambar yang dihapus (X) tidak akan tersimpan.</p>
                        </div>
                    </div>
                </div>

                {{-- UNIT AC SECTION --}}
                <div class="space-y-6 pt-6 mt-6 border-t border-slate-100">
                    {{-- ... (Bagian Gedung, Lantai, AC tetap sama seperti kode Anda) ... --}}
                    <div class="space-y-3">
                        <label class="block text-lg font-bold text-slate-800">1. Edit Gedung:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <template x-for="b in buildings" :key="'eb-'+b.id">
                                <button type="button" 
                                    @click="selectedBuildingIds = b.id.toString(); selectedFloorIds = ''; selectedRoomId = '';"
                                    :class="selectedBuildingIds === b.id.toString() ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                                    class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]" 
                                    x-text="b.name"></button>
                            </template>
                        </div>
                    </div>

                    <div x-show="selectedBuildingIds !== ''" class="space-y-3 animate-fadeIn">
                        <label class="block text-lg font-bold text-slate-800">2. Edit Lantai:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <template x-for="f in filteredFloors" :key="'ef-'+f.id">
                                <button type="button" 
                                    @click="selectedFloorIds = f.id.toString(); selectedRoomId = '';"
                                    :class="selectedFloorIds === f.id.toString() ? 'bg-[#2D365E] text-white border-[#2D365E]' : 'bg-white text-slate-500 border-slate-200'"
                                    class="py-3 px-2 rounded-xl text-xs font-bold border transition-all hover:border-[#2D365E]" 
                                    x-text="f.name"></button>
                            </template>
                        </div>
                    </div>

                    <div x-show="selectedFloorIds !== ''" class="space-y-4 animate-fadeIn">
                        <div class="flex justify-between items-center bg-indigo-50 p-3 rounded-2xl border border-indigo-100">
                            <label class="text-sm font-bold text-indigo-900">3. Edit Unit AC:</label>
                            <span class="text-[10px] font-black text-white bg-indigo-500 px-3 py-1 rounded-full" x-text="selectedAcIds.length + ' TERPILIH TOTAL'"></span>
                        </div>
                        
                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 space-y-4">
                            <select x-model="selectedRoomId" class="w-full border-slate-200 rounded-xl py-3 px-4 text-sm outline-none focus:ring-2 focus:ring-[#86D052]">
                                <option value="">-Tampilkan Semua Ruangan-</option>
                                <template x-for="r in filteredRooms" :key="'er-'+r.id">
                                    <option :value="r.id" x-text="r.name"></option>
                                </template>
                            </select>
                            <div class="flex gap-3">
                                <button type="button" @click="toggleSelectAll('select')" class="flex-1 bg-slate-200 text-slate-700 py-2 rounded-lg text-xs font-bold hover:bg-slate-300 transition-all">Select All</button>
                                <button type="button" @click="toggleSelectAll('deselect')" class="flex-1 bg-red-50 text-red-500 py-2 rounded-lg text-xs font-bold hover:bg-red-100 transition-all">Deselect All</button>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                            <template x-for="ac in filteredAcs" :key="'eac-'+ac.id">
                                <label class="flex items-center gap-3 bg-white p-3 rounded-xl cursor-pointer group border-2 transition-all"
                                       :class="selectedAcIds.includes(ac.id.toString()) ? 'border-[#86D052] bg-green-50/30' : 'border-slate-100'">
                                    
                                    <input type="checkbox" 
                                           :value="ac.id.toString()" 
                                           :checked="selectedAcIds.includes(ac.id.toString())"
                                           @change="selectedAcIds.includes(ac.id.toString()) ? 
                                                    selectedAcIds = selectedAcIds.filter(id => id !== ac.id.toString()) : 
                                                    selectedAcIds.push(ac.id.toString())"
                                           class="w-5 h-5 rounded border-slate-300 text-[#86D052] focus:ring-0">
                                    
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-[#2D365E] uppercase" x-text="ac.brand"></span>
                                        <span class="text-[10px] font-bold text-slate-400" x-text="ac.room_name + ' | SN: ' + (ac.indoor_sn || '-')"></span>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 mt-6">
                    <label class="block text-sm font-bold text-slate-700 ml-1">Catatan</label>
                    <textarea name="note" x-model="selectedSchedule.note" placeholder="Catatan tambahan..." 
                              class="w-full border-slate-200 bg-white rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-[#86D052] min-h-[80px] transition-all"></textarea>
                </div>

                <div class="flex flex-col gap-3 pt-6">
                    <button type="submit" class="w-full bg-[#2D365E] text-white py-4 rounded-2xl font-bold text-lg shadow-lg hover:bg-[#3d4a82] active:scale-95 transition-all">
                        Update Schedule
                    </button>
                    
                    <button type="button" 
                        @click="
                            Swal.fire({
                                title: 'Apakah Anda yakin?',
                                text: 'Data jadwal ini akan dihapus permanen!',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#ef4444',
                                cancelButtonColor: '#64748b',
                                confirmButtonText: 'Ya, Hapus!',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    document.getElementById('delete-form-' + selectedSchedule.id).submit();
                                }
                            })
                        "
                        class="w-full bg-[#F95454] border-none text-white py-4 rounded-2xl font-bold text-lg shadow-lg hover:bg-[#D44848] active:scale-95 transition-all">
                        Delete Schedule
                    </button>
                </div>
            </form>
            <form :id="'delete-form-' + selectedSchedule.id" 
                  :action="'/schedules/' + selectedSchedule.id" 
                  method="POST" 
                  style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: "{{ session('success') }}",
        timer: 3000,
        showConfirmButton: false
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: "{{ session('error') }}"
    });
</script>
@endif
    
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

<style>
    @keyframes bell-ring {
        0%, 100% { transform: rotate(0); }
        20% { transform: rotate(15deg); }
        40% { transform: rotate(-15deg); }
        60% { transform: rotate(10deg); }
        80% { transform: rotate(-10deg); }
    }
    .animate-bell-ring {
        animation: bell-ring 2s ease-in-out infinite;
        transform-origin: top center;
    }
</style>