<x-guest-layout>
    {{-- Background Glow Effects --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-200/40 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-blue-200/40 blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto p-6 min-h-screen" 
x-data="{ 
    // Fungsi pembantu untuk ambil parameter URL
    getParam(name) {
        return new URLSearchParams(window.location.search).get(name) || '';
    },

    search: '',
    filterStatus: '',
    filterBuilding: '',
    filterBrand: '',
    filterDate: '',
    filterAction: '',

    init() {
        // Otomatis isi filter dari URL saat halaman dibuka
        this.search = this.getParam('search');
        this.filterBuilding = this.getParam('building');
        this.filterBrand = this.getParam('brand');
        // Catatan: filterRoom jika ada bisa ditambahkan di sini
    },           
            // Modal States
            showModal: false,
            selectedAcHistory: [],
            selectedAcName: '',

            // Data mapping dari PHP ke JSON (Client-side Data)
allRecords: {{ json_encode($schedules->flatMap(function($s) {
    return $s->acs->map(function($ac) use ($s) {
        return [
            'id' => $ac->id,
            'date' => $s->start_date ? $s->start_date->format('Y-m-d') : '',
            'schedule_name' => $s->name,
            'brand' => $ac->brand,
            'ac_type' => $ac->ac_type,
            'sn' => $ac->indoor_sn,
            'room' => $ac->room->name ?? 'N/A',
            'building' => $ac->room->floor->building->name ?? 'N/A',
            
            // PERUBAHAN DI SINI:
            // Kita ambil status dari JADWAL ($s), bukan dari pivot per AC
            'status' => $s->status ?? 'belum', 
        ];
    });
})) }},

            // Fungsi untuk membuka modal detail riwayat spesifik 1 AC
            openModalDetail(acId) {
                const acData = this.allRecords.find(item => item.id === acId);
                // Filter semua record yang ID AC-nya sama untuk melihat riwayat lengkapnya
                this.selectedAcHistory = this.allRecords.filter(item => item.id === acId)
                                            .sort((a, b) => new Date(b.date) - new Date(a.date));
                this.selectedAcName = acData.brand + ' ' + acData.ac_type + ' (SN: ' + acData.sn + ')';
                this.showModal = true;
            },

            // Logika Multifilter Utama
            get filteredRecords() {
                return this.allRecords.filter(item => {
                    const matchSearch = this.search === '' || 
                                      item.schedule_name.toLowerCase().includes(this.search.toLowerCase()) || 
                                      item.sn.toLowerCase().includes(this.search.toLowerCase());
                    const matchStatus = this.filterStatus === '' || item.status === this.filterStatus;
                    const matchBuilding = this.filterBuilding === '' || item.building === this.filterBuilding;
                    const matchBrand = this.filterBrand === '' || item.brand === this.filterBrand;
                    const matchDate = this.filterDate === '' || item.date === this.filterDate;
                    const matchAction = this.filterAction === '' || item.schedule_name.toLowerCase().includes(this.filterAction.toLowerCase());

                    return matchSearch && matchStatus && matchBuilding && matchBrand && matchDate && matchAction;
                });
            }
         }">
        
        {{-- Header Section --}}
        <div class="mb-6 p-8 rounded-[2.5rem] bg-white/40 backdrop-blur-xl border border-white/60 shadow-xl flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-3xl font-black text-[#2D365E] uppercase tracking-[0.3em]">History Maintenance</h1>
                <p class="text-slate-600 text-xs font-bold uppercase tracking-widest opacity-70">Sistem Pencatatan Riwayat Unit AC</p>
            </div>
            
            <div class="relative w-full md:w-80 group">
                <input type="text" x-model="search" placeholder="Cari SN atau Nama Jadwal..." 
                       class="w-full pl-12 pr-6 py-4 bg-white/50 border-2 border-white rounded-2xl text-sm font-bold shadow-inner focus:border-indigo-400 focus:ring-0 transition-all">
                <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        {{-- Filter Row --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <select x-model="filterStatus" class="filter-dropdown">
                <option value="">Semua Status</option>
                <option value="selesai">Selesai</option>
                <option value="proses">Proses</option>
                <option value="belum">Belum</option>
            </select>

            <select x-model="filterBuilding" class="filter-dropdown">
                <option value="">Semua Gedung</option>
                @foreach($schedules->flatMap->acs->pluck('room.floor.building.name')->unique() as $bName)
                    <option value="{{ $bName }}">{{ $bName }}</option>
                @endforeach
            </select>

            <select x-model="filterBrand" class="filter-dropdown">
                <option value="">Semua Brand</option>
                @foreach($schedules->flatMap->acs->pluck('brand')->unique() as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>

<select x-model="filterAction" class="filter-dropdown">
    <option value="">Semua Tindakan</option>
    {{-- Mengambil nama unik dari semua jadwal yang ada dalam data $schedules --}}
    @foreach($schedules->pluck('name')->unique() as $actionName)
        <option value="{{ $actionName }}">{{ $actionName }}</option>
    @endforeach
</select>

            <input type="date" x-model="filterDate" class="filter-dropdown px-4">
        </div>

        {{-- Table Section --}}
        <div class="overflow-hidden rounded-[3rem] border border-white/60 shadow-2xl bg-white/30 backdrop-blur-md">
            <table class="w-full text-left border-collapse">
                <thead class="bg-[#2D365E] text-white">
                    <tr>
                        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-widest">Waktu</th>
                        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-widest">Unit AC</th>
                        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-widest text-center">Status</th>
                        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/20">
                    <template x-for="(item, index) in filteredRecords" :key="index">
                        <tr class="hover:bg-white/40 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-[#2D365E] uppercase" x-text="new Date(item.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'})"></span>
                                    <span class="text-[9px] font-bold text-slate-500" x-text="'Ref: ' + item.schedule_name"></span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-indigo-700 uppercase" x-text="item.brand + ' - ' + item.ac_type"></span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase" x-text="item.room + ' | SN: ' + item.sn"></span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span :class="{
                                    'bg-green-100 text-green-600 border-green-200': item.status === 'selesai',
                                    'bg-yellow-100 text-yellow-600 border-yellow-200': item.status === 'proses',
                                    'bg-red-100 text-red-600 border-red-200': item.status === 'belum'
                                }" class="border px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm" x-text="item.status"></span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <button @click="openModalDetail(item.id)" 
                                        class="bg-white/80 border border-white hover:bg-[#2D365E] hover:text-white transition-all px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- MODAL DETAIL LOG --}}
        <div x-show="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak x-transition>
            <div @click.away="showModal = false" class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden border border-white">
                <div class="bg-[#2D365E] p-8 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black uppercase tracking-widest" x-text="selectedAcName"></h3>
                        <p class="text-[9px] font-bold text-indigo-200 uppercase tracking-widest mt-1">Seluruh Riwayat Maintenance</p>
                    </div>
                    <button @click="showModal = false" class="text-white/50 hover:text-white"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="p-8 max-h-[60vh] overflow-y-auto space-y-4">
                    <template x-for="hist in selectedAcHistory" :key="hist.date + hist.schedule_name">
                        <div class="flex items-center gap-6 p-5 rounded-3xl bg-slate-50 border border-slate-100 transition-hover hover:border-indigo-200">
                            <div class="flex flex-col min-w-[100px]">
                                <span class="text-xs font-black text-[#2D365E]" x-text="new Date(hist.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'})"></span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-black text-slate-700 uppercase" x-text="hist.schedule_name"></h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase" x-text="hist.room + ' - ' + hist.building"></p>
                            </div>
                            <span :class="{'text-green-600': hist.status === 'selesai', 'text-yellow-600': hist.status === 'proses', 'text-red-600': hist.status === 'belum'}" 
                                  class="text-[9px] font-black uppercase tracking-widest" x-text="hist.status"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .filter-dropdown { @apply bg-white/60 backdrop-blur-md border-white rounded-2xl text-[10px] font-black uppercase tracking-widest text-[#2D365E] py-3 shadow-sm focus:ring-indigo-500 focus:border-indigo-500; }
        body { background-color: #f8fafc; }
    </style>
</x-guest-layout>