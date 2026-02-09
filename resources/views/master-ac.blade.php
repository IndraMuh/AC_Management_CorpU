<x-guest-layout>
<div x-data="{ 

handleFileSelect(event, index, type) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        // Update Preview untuk UI (sudah benar di blade Anda)
        this.importData[index][`preview_${type}`] = e.target.result;
        
        // PENTING: Update data yang akan dikirim ke backend
        // Controller bulkStore membaca key 'image_indoor' dan 'image_outdoor'
        if (type === 'indoor') {
            this.importData[index].image_indoor = e.target.result;
        } else if (type === 'outdoor') {
            this.importData[index].image_outdoor = e.target.result;
        }
    };
    reader.readAsDataURL(file);
},
    {{-- State Modal & Mode --}}
    openAcModal: false,
    openDetailModal: false,
    openHistoryModal: false,
    isEditingAc: false,
    selectedAc: {},

    {{-- State History --}}
    historyLoading: false,
    acHistoryData: { history: [] },

    {{-- State Import --}}
    loadingImport: false,
    showReviewModal: false,
    importData: [],
    loadingZip: false,

    {{-- State Filter --}}
    searchQuery: '',
    filterStatus: 'all',
    filterBuilding: 'all',
    filterFloor: 'all',
    filterRoom: 'all',
    filterBrand: 'all',

    {{-- State Dependent Dropdown --}}
    selectedBuilding: '',
    selectedFloor: '',
    selectedRoom: '',

    buildings: {{ $buildings->toJson() }},

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

    {{-- --- ACTION: FETCH HISTORY --- --}}
    fetchHistory(id) {
        this.historyLoading = true;
        this.openHistoryModal = true;
        this.acHistoryData = { history: [] };
        
        fetch(`/ac-history/${id}`)
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => { 
                this.acHistoryData = data; 
                this.historyLoading = false; 
            })
            .catch(err => {
                this.historyLoading = false;
                this.openHistoryModal = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memuat',
                    text: 'Terjadi kesalahan saat mengambil riwayat servis.',
                    confirmButtonColor: '#ef4444'
                });
            });
    },

    {{-- --- ACTION: IMPORT EXCEL --- --}}
    handleUpload() {
        const fileInput = document.getElementById('file_excel');
        if (!fileInput.files[0]) {
            return Swal.fire({
                icon: 'warning',
                title: 'File Belum Dipilih',
                text: 'Silakan pilih file Excel terlebih dahulu.',
                confirmButtonColor: '#4f46e5'
            });
        }

        Swal.fire({
            title: 'Mengimpor Data...',
            text: 'Harap tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        let formData = new FormData();
        formData.append('file_excel', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        this.loadingImport = true;
        fetch('{{ route('ac.import') }}', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                this.importData = res.data.map(item => ({
                    ...item, image_indoor: null, image_outdoor: null, preview_indoor: null, preview_outdoor: null
                }));
                this.showReviewModal = true;
                this.loadingImport = false;
                Swal.close();
            })
            .catch(err => {
                this.loadingImport = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Sistem',
                    text: 'Gagal memproses file Excel. Pastikan format file benar.',
                    confirmButtonColor: '#ef4444'
                });
            });
    },

    {{-- --- ACTION: BULK PHOTO UPLOAD (ZIP/MULTIPLE) --- --}}
    async handleBulkPhotoUpload(event) {
        const files = Array.from(event.target.files);
        if (files.length === 0) return;

        Swal.fire({
            title: 'Memproses Foto...',
            text: 'Mencocokkan Serial Number dengan data Excel',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        this.loadingZip = true;
        let stats = { total: 0, matched: 0, skipped: 0 };

        try {
            for (const file of files) {
                if (file.type === 'application/zip' || file.name.endsWith('.zip')) {
                    const zip = new JSZip();
                    const contents = await zip.loadAsync(file);
                    for (const filename of Object.keys(contents.files)) {
                        const fileData = contents.files[filename];
                        if (fileData.dir || filename.includes('__MACOSX')) continue;
                        const base64 = await fileData.async('base64');
                        const ext = filename.split('.').pop().toLowerCase();
                        stats.total++;
                        if (this.doMatch(filename, `data:image/${ext};base64,${base64}`)) stats.matched++;
                        else stats.skipped++;
                    }
                } else if (file.type.startsWith('image/')) {
                    const base64 = await new Promise(resolve => {
                        const reader = new FileReader();
                        reader.onload = (e) => resolve(e.target.result);
                        reader.readAsDataURL(file);
                    });
                    stats.total++;
                    if (this.doMatch(file.name, base64)) stats.matched++;
                    else stats.skipped++;
                }
            }

            Swal.fire({
                icon: stats.matched > 0 ? 'success' : 'info',
                title: 'Sinkronisasi Selesai',
                html: `<div class='text-left text-sm mt-2 p-3 bg-slate-50 rounded-xl'>
                    <p>📸 Total Foto: <b>${stats.total}</b></p>
                    <p class='text-green-600'>✅ Berhasil Cocok: <b>${stats.matched}</b></p>
                    <p class='text-red-500'>❌ Tidak Cocok: <b>${stats.skipped}</b></p>
                </div>`,
                confirmButtonColor: '#4f46e5'
            });
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        } finally {
            this.loadingZip = false;
            event.target.value = '';
        }
    },

    {{-- --- ACTION: DO MATCHING LOGIC --- --}}
    doMatch(filename, base64) {
        let found = false;
        const name = filename.toLowerCase();
        this.importData.forEach((item, index) => {
            const sn = (item.indoor_sn || '').toLowerCase();
            if (sn && name.includes(sn)) {
                if (name.includes('indoor') && !this.importData[index].preview_indoor) {
                    this.importData[index].preview_indoor = base64;
                    this.importData[index].image_indoor = base64;
                    found = true;
                } else if (name.includes('outdoor') && !this.importData[index].preview_outdoor) {
                    this.importData[index].preview_outdoor = base64;
                    this.importData[index].image_outdoor = base64;
                    found = true;
                }
            }
        });
        return found;
    },

{{-- --- ACTION: CONFIRM SAVE DATA (DENGAN CHUNKING) --- --}}
    async confirmSave() {
        const totalData = this.importData.length;
        if (totalData === 0) return;

        const result = await Swal.fire({
            title: 'Simpan Data?',
            text: `Anda akan menyimpan ${totalData} data AC. Data akan dikirim secara bertahap untuk mencegah error sistem.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        });

        if (!result.isConfirmed) return;

        // Tampilkan loading dengan indikator progres
        Swal.fire({
            title: 'Menyimpan...',
            html: `Sedang memproses: <b id='chunk-progress'>0</b> dari <b>${totalData}</b> data.`,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const batchSize = 5; // Jumlah data per pengiriman. Turunkan ke 2 atau 3 jika gambar sangat besar.
        let successCount = 0;
        let hasError = false;

        try {
            // Loop untuk mengirim data per kelompok (chunk)
            for (let i = 0; i < totalData; i += batchSize) {
                const chunk = this.importData.slice(i, i + batchSize);
                
                const response = await fetch('{{ route('ac.bulk-store') }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    // Hanya melakukan stringify pada 5 data, bukan seluruhnya
                    body: JSON.stringify({ acs: chunk })
                });

                const res = await response.json();

                if (!response.ok || !res.success) {
                    throw new Error(res.message || 'Terjadi kesalahan saat menyimpan batch.');
                }

                successCount += chunk.length;
                
                // Update teks progres di SweetAlert
                const progressEl = document.getElementById('chunk-progress');
                if (progressEl) progressEl.innerText = successCount;
            }

            // Jika semua batch berhasil
            await Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: `Semua (${successCount}) data AC telah disimpan.`,
                timer: 2000,
                showConfirmButton: false
            });
            
            window.location.reload();

        } catch (err) {
            console.error('Bulk Store Error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: 'Pesan Error: ' + err.message + '. Beberapa data mungkin sudah tersimpan.',
                confirmButtonColor: '#ef4444'
            });
        }
    },

    {{-- --- ACTION: FILTER LOGIC --- --}}
    shouldShow(ac) {
        const matchesSearch = ac.brand.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                             ac.indoor_sn.toLowerCase().includes(this.searchQuery.toLowerCase());
        const matchesStatus = this.filterStatus === 'all' || ac.status === this.filterStatus;
        const matchesBuilding = this.filterBuilding === 'all' || ac.building_id == this.filterBuilding;
        const matchesFloor = this.filterFloor === 'all' || ac.floor_id == this.filterFloor;
        const matchesRoom = this.filterRoom === 'all' || ac.room_id == this.filterRoom;
        const matchesBrand = this.filterBrand === 'all' || ac.brand === this.filterBrand;
        return matchesSearch && matchesStatus && matchesBuilding && matchesFloor && matchesRoom && matchesBrand;
    },

    {{-- --- ACTION: DELETE UNIT --- --}}
    confirmDelete() {
        Swal.fire({
            title: 'Hapus Unit AC?',
            text: 'Tindakan ini tidak dapat dibatalkan dan akan menghapus semua riwayat servis terkait!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                this.$refs.masterDeleteForm.submit();
            }
        });
    },

    {{-- --- ACTION: UPDATE UNIT --- --}}
    confirmUpdate(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Simpan Perubahan?',
            text: 'Pastikan data unit sudah benar.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Update!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memperbarui...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                event.target.closest('form').submit();
            }
        });
    },

    {{-- --- ACTION: CREATE UNIT --- --}}
    confirmCreate(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Tambah Unit?',
            text: 'Unit baru akan ditambahkan ke sistem.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Tambahkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                event.target.submit();
            }
        });
    }
}" class="min-h-screen w-full bg-[#F3F4F6] relative overflow-y-auto p-8">
        
        {{-- Script for handling Backend Sessions --}}
        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: "{{ session('success') }}",
                        timer: 2500,
                        confirmButtonColor: '#4f46e5'
                    });
                });
            </script>
        @endif

        @if(session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: "{{ session('error') }}",
                        confirmButtonColor: '#ef4444'
                    });
                });
            </script>
        @endif

        {{-- Background Blobs --}}
        <div class="fixed top-[-15%] right-[-15%] w-[50vw] h-[50vw] bg-[#2da2ad]/70 rounded-full blur-[60px] animate-blob"></div>
        <div class="fixed bottom-[-15%] left-[-15%] w-[55vw] h-[55vw] bg-[#D1FADF]/90 rounded-full blur-[70px] animate-blob animation-delay-2000"></div>

        <style>
            @keyframes blob { 0% { transform: translate(0px, 0px) scale(1); } 33% { transform: translate(50px, -70px) scale(1.2); } 66% { transform: translate(-40px, 40px) scale(0.8); } 100% { transform: translate(0px, 0px) scale(1); } }
            .animate-blob { animation: blob 10s infinite alternate ease-in-out; }
            .animation-delay-2000 { animation-delay: 2s; }
            [x-cloak] { display: none !important; }
        </style>

        <div class="max-w-6xl mx-auto bg-white/50 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/30 relative z-10">
            
            {{-- Header Section --}}
            <div class="bg-white/25 backdrop-blur-sm px-8 py-6 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="{{ url('/dashboard') }}" class="absolute top-8 right-8 text-gray-400 hover:text-red-500 transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>      
                    <h1 class="text-xl font-semibold text-gray-800">Master Data AC</h1>
                </div>
            </div>

            {{-- Top Toolbar --}}
            <div class="bg-white/25 backdrop-blur-sm px-8 py-4">
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <div class="relative flex-grow">
                        <input x-model="searchQuery" type="text" placeholder="Cari Brand atau Serial Number..." class="w-full border-none shadow-sm rounded-2xl px-12 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                        <span class="absolute left-4 top-3 text-slate-400">🔍</span>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="{{ route('ac.export') }}" class="flex items-center gap-2 bg-emerald-600 text-white px-4 py-3 rounded-2xl text-[10px] font-black uppercase hover:bg-emerald-700 transition shadow-lg">
                            Download Excel
                        </a>
                        <label class="cursor-pointer flex items-center gap-2 bg-indigo-600 text-white px-4 py-3 rounded-2xl text-[10px] font-black uppercase hover:bg-indigo-700 transition shadow-lg">
                            <span x-show="!loadingImport">Import Excel</span>
                            <span x-show="loadingImport">Processing...</span>
                            <input type="file" id="file_excel" class="hidden" @change="handleUpload">
                        </label>
                    </div>

                    <button @click="openAcModal = true" class="bg-slate-800 text-white px-8 py-3 rounded-2xl font-bold text-sm hover:bg-slate-700 transition shadow-lg flex items-center gap-2 justify-center">
                        <span>+</span> Tambah Inventaris
                    </button>
                </div>

                {{-- Filter Dropdowns --}}
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-4 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                    <select x-model="filterBuilding" class="border-none shadow-sm rounded-xl py-2.5 text-xs focus:ring-indigo-500">
                        <option value="all">Semua Gedung</option>
                        @foreach($buildings as $b) <option value="{{ $b->id }}">{{ $b->name }}</option> @endforeach
                    </select>
                    <select x-model="filterRoom" class="border-none shadow-sm rounded-xl py-2.5 text-xs focus:ring-indigo-500">
                        <option value="all">Semua Ruangan</option>
                        @foreach($buildings as $b)
                            @foreach($b->floors as $f)
                                @foreach($f->rooms as $r) <option value="{{ $r->id }}">{{ $b->name }} - {{ $r->name }}</option> @endforeach
                            @endforeach
                        @endforeach
                    </select>
                    <select x-model="filterStatus" class="border-none shadow-sm rounded-xl py-2.5 text-xs focus:ring-indigo-500">
                        <option value="all">Semua Status</option>
                        <option value="baik">✅ Baik</option>
                        <option value="rusak">❌ Rusak</option>
                    </select>
                    <select x-model="filterBrand" class="border-none shadow-sm rounded-xl py-2.5 text-xs focus:ring-indigo-500">
                        <option value="all">Semua Brand</option>
                        @foreach($all_acs->pluck('brand')->unique() as $brand) <option value="{{ $brand }}">{{ $brand }}</option> @endforeach
                    </select>
                    <button @click="searchQuery = ''; filterStatus = 'all'; filterBuilding = 'all'; filterRoom = 'all'; filterBrand = 'all'" class="text-slate-500 text-xs font-bold hover:text-indigo-600 transition">Reset Filter</button>
                </div>
            </div>

            {{-- Grid Cards --}}
            <div class="bg-white/20 backdrop-blur-sm p-8 max-h-[600px] overflow-y-auto relative z-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 relative z-10" >
                    @foreach($all_acs as $ac)
                        <div x-show="shouldShow({
                            brand: '{{ $ac->brand }}',
                            status: '{{ $ac->status }}',
                            building_id: '{{ $ac->room->floor->building_id }}',
                            floor_id: '{{ $ac->room->floor_id }}',
                            room_id: '{{ $ac->room_id }}',
                            indoor_sn: '{{ $ac->indoor_sn }}'
                        })" class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition cursor-pointer group"
                            @click="selectedAc = {
                                id: {{ $ac->id }},
                                needs_service: {{ $ac->needs_service ? 'true' : 'false' }},
                                brand: '{{ $ac->brand }}',
                                model: '{{ $ac->model }}',
                                status: '{{ $ac->status }}',
                                ac_type: '{{ $ac->ac_type }}',
                                model_type: '{{ $ac->model_type }}',
                                indoor_sn: '{{ $ac->indoor_sn }}',
                                outdoor_sn: '{{ $ac->outdoor_sn }}',
                                specifications: '{{ $ac->specifications }}',
                                building_name: '{{ $ac->room->floor->building->name }}',
                                floor_name: '{{ $ac->room->floor->name }}',
                                room_name: '{{ $ac->room->name }}',
                                room_id: {{ $ac->room_id }},
                                image_indoor_url: '{{ $ac->image_indoor ? asset('storage/' . $ac->image_indoor) : '' }}',
                                image_outdoor_url: '{{ $ac->image_outdoor ? asset('storage/' . $ac->image_outdoor) : '' }}'
                            }; openDetailModal = true"
                        >
                            <div class="relative h-44 overflow-hidden bg-gray-200">
                                <img src="{{ $ac->image_indoor ? asset('storage/' . $ac->image_indoor) : 'https://via.placeholder.com/400x300?text=No+Indoor+Photo' }}" 
                                     class="h-full w-full object-cover group-hover:scale-110 transition duration-500">
                                
                                <div class="absolute top-2 right-2">
                                    @if($ac->needs_service)
                                        <div class="flex items-center gap-1.5 bg-red-600 text-white px-2 py-1 rounded-lg shadow-lg border border-red-400/50 animate-pulse">
                                            <span class="text-[10px]">⚠️</span>
                                            <span class="text-[8px] font-black uppercase tracking-tighter">Waktunya Servis</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="absolute top-2 left-2 flex flex-col gap-1 z-10">
                                    <span class="px-2.5 py-1 text-[9px] font-bold rounded-full shadow-sm {{ $ac->status == 'baik' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                                        {{ strtoupper($ac->status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="mb-2">
                                    <h4 class="font-bold text-slate-800 uppercase truncate leading-tight">{{ $ac->brand }} - {{ $ac->model }}</h4>
                                    <p class="text-[10px] text-indigo-600 font-bold">{{ $ac->ac_type }} ({{ $ac->model_type }})</p>
                                </div>
                                <div class="space-y-1.5 border-t pt-3">
                                    <div class="flex justify-between text-[11px]">
                                        <span class="text-gray-400">Gedung:</span>
                                        <span class="font-semibold text-slate-700">{{ $ac->room->floor->building->name }}</span>
                                    </div>
                                    <div class="flex justify-between text-[11px]">
                                        <span class="text-gray-400">Lokasi:</span>
                                        <span class="font-semibold text-slate-700">{{ $ac->room->name }}</span>
                                    </div>
                                    <div class="flex justify-between text-[11px]">
                                        <span class="text-gray-400">SN Indoor:</span>
                                        <span class="font-medium text-slate-700">{{ $ac->indoor_sn }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- MODAL REVIEW IMPORT --}}
        <div x-show="showReviewModal" 
             class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-cloak>
            
            <div class="bg-white rounded-[2rem] max-w-6xl w-full p-8 shadow-2xl flex flex-col h-[90vh] max-h-[90vh] ring-1 ring-black/5">
                <div class="flex justify-between items-center mb-6 shrink-0">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Review Data Import</h3>
                        <p class="text-slate-500 text-sm">Lengkapi foto unit secara massal (ZIP) atau satu per satu.</p>
                    </div>
                    <button @click="showReviewModal = false" class="p-2 hover:bg-slate-100 rounded-full transition text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mb-6 p-5 bg-indigo-50 border border-indigo-100 rounded-[1.5rem] flex flex-col md:flex-row items-center justify-between gap-4 shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-indigo-900 uppercase">Sinkronisasi Foto Massal</h4>
                            <p class="text-[10px] text-indigo-500 leading-tight">Gunakan ZIP atau pilih banyak foto sekaligus. Nama file wajib mengandung <b>Serial Number</b>.</p>
                        </div>
                    </div>
                    
                    <label class="relative inline-flex items-center justify-center px-6 py-3 bg-white border-2 border-indigo-200 text-indigo-600 rounded-xl cursor-pointer hover:bg-indigo-600 hover:text-white transition-all shadow-sm group">
                        <input type="file" class="hidden" multiple accept="image/*,.zip" @change="handleBulkPhotoUpload" :disabled="loadingZip">
                        <div class="flex items-center gap-2 font-black text-[10px] uppercase tracking-wider">
                            <template x-if="!loadingZip">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Pilih ZIP / Banyak Foto
                                </span>
                            </template>
                            <template x-if="loadingZip">
                                <span class="flex items-center gap-2 italic">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Memproses...
                                </span>
                            </template>
                        </div>
                    </label>
                </div>
                
                <div class="overflow-y-auto flex-1 border border-slate-200 rounded-3xl shadow-sm bg-slate-50/30 custom-scroll">
                    <table class="w-full text-xs text-left border-separate border-spacing-0">
                        <thead class="sticky top-0 z-10">
                            <tr class="text-slate-400 font-bold uppercase tracking-wider">
                                <th class="p-5 bg-white border-b border-slate-100 rounded-tl-3xl">Lokasi & Unit</th>
                                <th class="p-5 bg-white border-b border-slate-100">Detail Teknik</th>
                                <th class="p-5 bg-slate-50 border-b border-slate-100 text-center">Foto Indoor</th>
                                <th class="p-5 bg-slate-50 border-b border-slate-100 text-center rounded-tr-3xl">Foto Outdoor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, index) in importData" :key="index">
                                <tr class="hover:bg-indigo-50/30 transition-colors group">
                                    <td class="p-5">
                                        <div class="font-bold text-slate-800 text-sm" x-text="item.building + ' - ' + item.floor"></div>
                                        <div class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 font-bold mt-1" x-text="item.room_name"></div>
                                    </td>
                                    <td class="p-5">
                                        <div class="font-bold text-slate-700 uppercase" x-text="item.brand"></div>
                                        <div class="text-slate-500" x-text="item.ac_type"></div>
                                        <div class="font-mono text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded mt-2 inline-block font-black" x-text="'SN: ' + item.indoor_sn"></div>
                                    </td>
                                    
                                    <td class="p-5 bg-slate-50/30 group-hover:bg-transparent transition-colors">
                                        <div class="flex flex-col items-center gap-2">
                                            <label class="relative flex flex-col items-center justify-center w-16 h-16 border-2 border-dashed border-slate-300 rounded-2xl hover:border-indigo-400 hover:bg-white transition cursor-pointer overflow-hidden shadow-sm">
                                                <template x-if="!item.preview_indoor">
                                                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/></svg>
                                                </template>
                                                <template x-if="item.preview_indoor">
                                                    <img :src="item.preview_indoor" class="w-full h-full object-cover">
                                                </template>
                                                <input type="file" @change="handleFileSelect($event, index, 'indoor')" class="hidden" accept="image/*">
                                            </label>
                                            <span class="text-[9px] font-black uppercase tracking-tighter" :class="item.preview_indoor ? 'text-green-500' : 'text-slate-400'" x-text="item.preview_indoor ? 'Selesai' : 'Pilih Foto'"></span>
                                        </div>
                                    </td>

                                    <td class="p-5 bg-slate-50/30 group-hover:bg-transparent transition-colors text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <label class="relative flex flex-col items-center justify-center w-16 h-16 border-2 border-dashed border-slate-300 rounded-2xl hover:border-indigo-400 hover:bg-white transition cursor-pointer overflow-hidden shadow-sm">
                                                <template x-if="!item.preview_outdoor">
                                                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/></svg>
                                                </template>
                                                <template x-if="item.preview_outdoor">
                                                    <img :src="item.preview_outdoor" class="w-full h-full object-cover">
                                                </template>
                                                <input type="file" @change="handleFileSelect($event, index, 'outdoor')" class="hidden" accept="image/*">
                                            </label>
                                            <span class="text-[9px] font-black uppercase tracking-tighter" :class="item.preview_outdoor ? 'text-green-500' : 'text-slate-400'" x-text="item.preview_outdoor ? 'Selesai' : 'Pilih Foto'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-4 mt-8 shrink-0">
                    <button @click="showReviewModal = false" 
                            class="px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold uppercase text-xs hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button @click="confirmSave" 
                            :disabled="loadingImport"
                            class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black uppercase text-xs shadow-lg shadow-indigo-200 transition-all transform hover:-translate-y-0.5 active:scale-[0.98] disabled:opacity-50">
                        <span x-show="!loadingImport">Konfirmasi & Simpan <span class="ml-2 px-2 py-0.5 bg-indigo-500 rounded-md" x-text="importData.length"></span></span>
                        <span x-show="loadingImport" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Menyimpan Data...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL DETAIL AC --}}
        <div x-show="openDetailModal" class="fixed inset-0 z-[150] overflow-y-auto" x-cloak>
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openDetailModal = false; isEditingAc = false" class="bg-white rounded-[3rem] max-w-5xl w-full overflow-hidden shadow-2xl animate-zoomIn">
                    
                    <form :action="`/ac/${selectedAc.id}`" method="POST" enctype="multipart/form-data" @submit="confirmUpdate">
                        @csrf 
                        @method('PATCH')
                        
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
                                <template x-if="selectedAc.needs_service">
                                    <div class="mb-4 p-4 bg-orange-50 border-l-4 border-orange-500 rounded-r-xl flex items-center gap-3 animate-pulse">
                                        <span class="text-xl">⚠️</span>
                                        <div>
                                            <p class="text-orange-800 font-black text-[10px] uppercase tracking-widest">Peringatan Servis</p>
                                            <p class="text-orange-600 text-[11px] font-bold">Unit ini sudah melewati batas 6 bulan sejak servis terakhir.</p>
                                        </div>
                                    </div>
                                </template>

                                <div class="flex justify-between items-start mb-8">
                                    <div>
                                        <div class="mb-3">
                                            <template x-if="!isEditingAc">
                                                <span class="px-3 py-1 rounded-md text-[10px] font-black uppercase tracking-tighter inline-block"
                                                    :class="selectedAc.status === 'baik' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                                                    x-text="selectedAc.status">
                                                </span>
                                            </template>
                                            <template x-if="isEditingAc">
                                                <select name="status" x-model="selectedAc.status" class="text-[10px] font-black uppercase border-gray-200 rounded-lg px-2 py-1">
                                                    <option value="baik">BAIK</option>
                                                    <option value="rusak">RUSAK</option>
                                                </select>
                                            </template>
                                        </div>

                                        <template x-if="!isEditingAc">
                                            <h2 class="text-4xl font-black text-slate-800 uppercase tracking-tighter" x-text="`${selectedAc.brand} ${selectedAc.model}`"></h2>
                                        </template>
                                        <div x-show="isEditingAc" class="flex gap-2">
                                            <input type="text" name="brand" x-model="selectedAc.brand" class="text-2xl font-black text-slate-800 uppercase border-gray-200 rounded-xl w-1/2" placeholder="Brand">
                                            <input type="text" name="model" x-model="selectedAc.model" class="text-2xl font-black text-slate-800 uppercase border-gray-200 rounded-xl w-1/2" placeholder="Model">
                                        </div>

                                        <p class="text-indigo-600 font-bold text-sm mt-1" x-text="`${selectedAc.building_name} • ${selectedAc.floor_name} • ${selectedAc.room_name}`"></p>
                                    </div>
                                    <button type="button" @click="openDetailModal = false; isEditingAc = false" class="text-3xl text-slate-300 hover:text-slate-800 transition">×</button>
                                </div>

                                <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="col-span-2 p-4 bg-slate-50 rounded-2xl border border-slate-100" x-show="isEditingAc">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Relokasi Unit (Pindah Ruangan)</label>
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

                                <div class="mt-auto pt-10 flex justify-between items-center">
                                    <template x-if="isEditingAc">
                                        <button type="button" @click="confirmDelete()" class="text-red-500 text-xs font-black uppercase hover:underline">Hapus Unit</button>
                                    </template>
                                    
                                    <div class="flex gap-4 ml-auto">
                                        <button type="button" @click="fetchHistory(selectedAc.id)" class="bg-indigo-50 text-indigo-600 px-6 py-3 rounded-2xl font-bold text-xs hover:bg-indigo-100 transition flex items-center gap-2 uppercase tracking-widest">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Lihat Servis
                                        </button>
                                        <button x-show="!isEditingAc" type="button" @click="isEditingAc = true" class="bg-indigo-600 text-white px-10 py-3 rounded-2xl font-black text-xs uppercase shadow-lg hover:bg-indigo-700 transition">Edit Data</button>
                                        <button x-show="isEditingAc" type="button" @click="isEditingAc = false" class="border px-6 py-3 rounded-2xl font-black text-xs uppercase">Batal</button>
                                        <button x-show="isEditingAc" type="submit" class="bg-slate-800 text-white px-10 py-3 rounded-2xl font-black text-xs uppercase shadow-lg hover:bg-slate-900 transition">Simpan Perubahan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <form x-ref="masterDeleteForm" :action="`/ac/${selectedAc.id}`" method="POST" class="hidden">
                        @csrf 
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL TAMBAH AC --}}
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

                    <form action="{{ route('ac.store') }}" method="POST" enctype="multipart/form-data" @submit="confirmCreate">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

 {{-- MODAL HISTORY --}}
<div x-show="openHistoryModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openHistoryModal = false"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl font-black text-slate-800 uppercase">Riwayat Servis</h3>
                    <p class="text-xs text-[#2796A3] font-bold" x-text="acHistoryData.brand + ' - ' + acHistoryData.sn"></p>
                </div>
                <button @click="openHistoryModal = false" class="text-2xl text-gray-300 hover:text-gray-500">✕</button>
            </div>
            
            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                <template x-if="historyLoading">
                    <div class="text-center py-10 text-gray-400 font-bold animate-pulse">Memuat ...</div>
                </template>
                
                <template x-for="(item, index) in acHistoryData.history" :key="index">
                    {{-- Container diubah menjadi flex-col agar gambar bisa di bawah teks --}}
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase" x-text="item.date"></p>
                                <p class="text-sm font-bold text-slate-700 uppercase" x-text="item.name"></p>
                                <p class="text-[10px] font-black text-slate-500 uppercase">Dikerjakan oleh: <span x-text="item.work" class="text-slate-800"></span></p>
                            </div>
                            <span :class="{'bg-green-100 text-green-600': item.status === 'selesai','bg-yellow-100 text-yellow-600': item.status === 'proses','bg-red-100 text-red-600': item.status === 'belum'}" 
                                  class="px-3 py-1 rounded-lg text-[9px] font-black uppercase" x-text="item.status"></span>
                        </div>

                        {{-- Tampilan Bukti Foto --}}
                        <template x-if="item.proof && item.proof.length > 0">
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-200/60">
                                <template x-for="img in item.proof">
                                    <a :href="'/storage/' + img" target="_blank" class="block">
                                        <img :src="'/storage/' + img" 
                                             class="w-12 h-12 object-cover rounded-lg border border-white shadow-sm hover:scale-110 transition-transform">
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!historyLoading && (!acHistoryData.history || acHistoryData.history.length === 0)">
                    <div class="text-center py-10 text-gray-400 italic text-sm">Belum ada catatan servis.</div>
                </template>
            </div>
        </div>
    </div>
</div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </div>
</x-guest-layout>