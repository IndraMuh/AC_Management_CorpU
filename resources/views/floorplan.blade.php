<x-guest-layout>
    {{-- Library PDF.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>

    <div x-data="{ 
        searchAc: '',
        openReplaceModal: false,
        zoom: 100, 
        editMode: false,
        openEditPlanModal: false, 
        selectedFloorId: null, 
        selectedFloorName: '',
        
        // State untuk Detail AC
        openDetailModal: false,
        selectedAc: {},

savePosition(acId, x, y) {
    fetch(`/ac/${acId}/update-position`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ 
            x: parseFloat(x).toFixed(2), 
            y: parseFloat(y).toFixed(2) 
        })
    }).then(res => {
        // Toast Notifikasi (Lebih halus daripada modal untuk drag & drop)
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
        });
        Toast.fire({
            icon: 'success',
            title: 'Posisi AC diperbarui'
        });
    });
},

        // FUNGSI BARU: Simpan Rotasi ke Server
saveRotation(floorId, rotation) {
    fetch(`/floors/${floorId}/update-rotation`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ rotation: rotation })
    }).then(res => {
        Swal.fire({
            icon: 'success',
            title: 'Rotasi Tersimpan',
            text: 'Tampilan denah telah diperbarui.',
            timer: 1500,
            showConfirmButton: false
        });
    });
}
    }" class="min-h-screen w-full bg-[#F3F4F6] relative overflow-y-auto p-8">
        
        {{-- Animated Blob Background --}}
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
            [x-cloak] { display: none !important; }
            .select-none { user-select: none; -webkit-user-drag: none; }
            /* Transition smooth untuk rotasi */
            .rotate-transition { transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        </style>

        {{-- Main Container --}}
        <div class="max-w-7xl mx-auto relative z-10">
            
            {{-- Header Section --}}
            <div class="bg-white/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/30 mb-8 overflow-hidden">
                <div class="bg-white/25 backdrop-blur-sm px-8 py-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <nav class="text-sm text-gray-400 mb-2 font-medium">
                            <a href="{{ route('location.index') }}" class="hover:text-slate-800 transition">Gedung</a> / 
                            <a href="{{ route('location.show', $building->id) }}" class="hover:text-slate-800 transition">{{ $building->name }}</a> / 
                            <span class="text-slate-800 font-bold">Interactive Plan</span>
                        </nav>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('location.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-white/50 hover:bg-white/80 transition text-gray-600 hover:text-gray-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </a>
                            <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">
                                <span x-text="editMode ? '⚙️ Mode Layouting' : '🗺 Monitoring Denah AC TelkomCorpU'"></span>
                            </h1>
                            <template x-if="editMode">
                                <span class="animate-pulse bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] font-bold border border-amber-200">EDITING</span>
                            </template>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button @click="editMode = !editMode" 
                            :class="editMode ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 border'"
                            class="px-5 py-2.5 rounded-2xl font-bold text-xs transition-all shadow-sm flex items-center gap-2">
                            <span x-text="editMode ? '🔒 Selesai Edit' : '🔧 Atur Posisi'"></span>
                        </button>

                        <div class="flex bg-white p-1 rounded-2xl shadow-sm border border-gray-200">
                            <button @click="zoom = Math.max(zoom - 10, 50)" class="px-3 py-1 hover:bg-gray-100 rounded-xl transition font-bold text-slate-600">-</button>
                            <span class="px-3 py-1 text-xs font-mono font-bold flex items-center min-w-[50px] justify-center" x-text="zoom + '%'"></span>
                            <button @click="zoom = Math.min(zoom + 10, 200)" class="px-3 py-1 hover:bg-gray-100 rounded-xl transition font-bold text-slate-600">+</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Floor Plans Section --}}
            <div class="space-y-8">
                @foreach($building->floors as $floor)
                    {{-- Tambahkan x-data untuk mengelola rotasi per lantai --}}
                    <div x-data="{ 
                            floorRotation: {{ $floor->rotation ?? 0 }},
                            rotate() {
                                this.floorRotation = (this.floorRotation + 90) % 360;
                                saveRotation('{{ $floor->id }}', this.floorRotation);
                            }
                         }" 
                         class="bg-white/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/30 overflow-hidden">
                        <div class="bg-white/25 backdrop-blur-sm p-8">
                            <div class="flex justify-between items-center mb-6">
                                <div class="flex items-center gap-4">
                                    <h2 class="inline-block bg-slate-800 text-white px-6 py-2 rounded-full text-xs font-black uppercase tracking-widest">
                                        {{ $floor->name }}
                                    </h2>
                                    {{-- Tombol Rotasi (Hanya muncul saat Edit Mode) --}}
                                    <template x-if="editMode">
                                        <button @click="rotate()" class="p-2 bg-white rounded-xl shadow-sm border border-gray-200 hover:bg-gray-50 transition text-slate-600" title="Putar Denah">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                                
                                <template x-if="editMode">
                                    <button @click="openEditPlanModal = true; selectedFloorId = '{{ $floor->id }}'; selectedFloorName = '{{ $floor->name }}'" 
                                            class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-4 py-2 rounded-xl transition">
                                        🖼️ Ganti File Denah
                                    </button>
                                </template>
                            </div>

                            <div class="relative bg-white/20 backdrop-blur-sm rounded-[2.5rem] overflow-hidden flex justify-center items-center border-4 transition-colors shadow-inner p-4"
                                 :class="editMode ? 'border-dashed border-indigo-200 bg-indigo-50/30' : 'border-transparent'"
                                 style="min-height: 400px;">
                                
                                {{-- Wrapper untuk Zoom dan Rotasi --}}
                                <div class="relative transition-transform duration-300 rotate-transition" 
                                     :style="'transform: scale(' + zoom/100 + ') rotate(' + floorRotation + 'deg); width: 100%; height: auto;'">
                                    
                                    {{-- Denah Render --}}
                                    <div class="w-full z-0 flex items-center justify-center pointer-events-none">
                                        @if($floor->floor_plan)
                                            @php $ext = pathinfo($floor->floor_plan, PATHINFO_EXTENSION); @endphp
                                            @if(strtolower($ext) === 'pdf')
                                                <canvas id="pdf-canvas-{{ $floor->id }}" 
                                                        data-pdf-url="{{ asset('storage/' . $floor->floor_plan) }}" 
                                                        class="max-w-full h-auto opacity-80"></canvas>
                                            @else
                                                <img src="{{ asset('storage/' . $floor->floor_plan) }}" 
                                                     class="max-w-full h-auto opacity-80 rounded-2xl">
                                            @endif
                                        @else
                                            <div class="text-slate-300 text-center p-8">
                                                <span class="text-6xl">📐</span>
                                                <p class="text-sm font-bold mt-4">Belum ada denah</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Grid Helper --}}
                                    <template x-if="editMode">
                                        <div class="absolute inset-0 pointer-events-none" 
                                             style="background-image: radial-gradient(#6366f1 0.5px, transparent 0.5px); background-size: 20px 20px; opacity: 0.2;">
                                        </div>
                                    </template>

                                    {{-- Ikon AC --}}
                                    @foreach($floor->rooms as $room)
                                        @foreach($room->acs as $ac)
                                            <div x-data="{ 
                                                    x: {{ $ac->x_position ?? 50 }}, 
                                                    y: {{ $ac->y_position ?? 50 }},
                                                    dragging: false,
                                                    id: {{ $ac->id }},
                                                    acData: {
                                                        id: {{ $ac->id }},
                                                        room_id: {{ $ac->room_id }},
                                                        brand: '{{ $ac->brand }}',
                                                        model: '{{ $ac->model }}',
                                                        ac_type: '{{ $ac->ac_type }}',
                                                        model_type: '{{ $ac->model_type }}',
                                                        indoor_sn: '{{ $ac->indoor_sn }}',
                                                        outdoor_sn: '{{ $ac->outdoor_sn }}',
                                                        status: '{{ $ac->status }}',
                                                        specifications: '{{ $ac->specifications }}',
                                                        room_name: '{{ $ac->room->name }}',
                                                        floor_name: '{{ $ac->room->floor->name }}',
                                                        building_name: '{{ $building->name }}',
                                                        image_indoor_url: '{{ $ac->image_indoor ? asset('storage/'.$ac->image_indoor) : '' }}',
                                                        image_outdoor_url: '{{ $ac->image_outdoor ? asset('storage/'.$ac->image_outdoor) : '' }}'
                                                    },
startDrag(e) {
    if(!editMode) return;

    // Ambil data rotasi lantai saat ini (default 0 jika tidak ada)
    const rotation = this.floorRotation || 0;
    
    // Ambil ukuran dan posisi denah saat ini
    // Kita gunakan container gambar karena ini adalah area referensi koordinat %
    const rect = $el.parentElement.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;

    const move = (mE) => {
        // 1. Hitung posisi mouse relatif terhadap titik tengah denah
        let dx = mE.clientX - centerX;
        let dy = mE.clientY - centerY;

        // 2. Transformasi koordinat mouse berdasarkan rotasi denah (Trigonometri)
        // Ini memastikan geseran mouse selaras dengan visual denah yang miring
        const rad = (rotation * Math.PI) / 180;
        const rotatedX = dx * Math.cos(rad) + dy * Math.sin(rad);
        const rotatedY = dy * Math.cos(rad) - dx * Math.sin(rad);

        // 3. Konversi kembali ke persentase (0-100) terhadap ukuran denah
        let newX = (rotatedX / rect.width * 100) + 50;
        let newY = (rotatedY / rect.height * 100) + 50;

        // 4. Batasi (Clamp) agar tidak keluar dari area denah
        this.x = Math.max(0, Math.min(100, newX));
        this.y = Math.max(0, Math.min(100, newY));
    };

    const stop = () => {
        this.savePosition(this.id, this.x, this.y);
        window.removeEventListener('mousemove', move);
        window.removeEventListener('mouseup', stop);
    };

    window.addEventListener('mousemove', move);
    window.addEventListener('mouseup', stop);
}
                                                 }"
                                                 :style="'left: ' + x + '%; top: ' + y + '%; position: absolute; transform: translate(-50%, -50%) rotate(-' + floorRotation + 'deg);'"
                                                 @mousedown="startDrag($event)"
                                                 @click="if(!editMode) { selectedAc = acData; openDetailModal = true; }"
                                                 class="z-10 group select-none"
                                                 :class="editMode ? 'cursor-move' : 'cursor-pointer'">
                                                
                                                <div class="flex flex-col items-center">
                                                    <template x-if="!editMode">
                                                        <div class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white shadow-sm {{ $ac->status == 'baik' ? 'bg-green-500' : 'bg-red-500 animate-ping' }}"></div>
                                                    </template>

                                                    <div :class="[
                                                        dragging ? 'scale-125 shadow-2xl ring-4 ring-indigo-400' : 'scale-100',
                                                        editMode ? 'border-2 border-white' : ''
                                                    ]" 
                                                         class="w-9 h-9 rounded-xl flex items-center justify-center text-[8px] font-black text-white shadow-lg transition-all duration-200
                                                         {{ $ac->status == 'baik' ? 'bg-slate-800' : 'bg-red-600' }}">
                                                        AC
                                                    </div>

                                                    <div :class="editMode ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                                                         class="mt-1.5 bg-white/90 px-2 py-0.5 rounded-md border text-[7px] font-black text-slate-700 whitespace-nowrap transition-opacity shadow-sm">
                                                        {{ $room->name }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        {{-- MODAL DETAIL AC LENGKAP --}}
        <div x-show="openDetailModal" class="fixed inset-0 z-[150] overflow-y-auto" x-cloak x-transition>
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openDetailModal = false" class="bg-white rounded-[3rem] max-w-2xl w-full overflow-hidden shadow-2xl transform transition-all">
                    
                    {{-- Header Modal --}}
                    <div class="relative h-32 bg-slate-800 flex items-center px-10">
                        <div class="z-10">
                            <h3 class="text-3xl font-black text-white uppercase tracking-tighter" x-text="selectedAc.brand"></h3>
                            <p class="text-indigo-300 font-bold text-sm tracking-widest uppercase" x-text="selectedAc.building_name + ' • ' + selectedAc.floor_name"></p>
                        </div>
                        <div class="absolute right-10 top-1/2 -translate-y-1/2">
                            <span :class="selectedAc.status === 'baik' ? 'bg-green-500' : 'bg-red-500'" 
                                  class="px-4 py-2 rounded-full text-white text-[10px] font-black uppercase tracking-widest shadow-lg" 
                                  x-text="selectedAc.status"></span>
                        </div>
                    </div>

                    <div class="p-10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            {{-- Kolom Kiri: Informasi Teknis --}}
                            <div class="space-y-6">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Informasi Unit</label>
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500 font-medium">Model</span>
                                            <span class="text-xs text-slate-800 font-black" x-text="selectedAc.model || '-'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500 font-medium">Tipe AC</span>
                                            <span class="text-xs text-slate-800 font-black" x-text="selectedAc.ac_type || '-'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500 font-medium">Model Type</span>
                                            <span class="text-xs text-slate-800 font-black" x-text="selectedAc.model_type || '-'"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs text-slate-500 font-medium">Ruangan</span>
                                            <span class="text-xs text-indigo-600 font-black" x-text="selectedAc.room_name"></span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Serial Numbers</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                                            <p class="text-[8px] font-bold text-slate-400 uppercase">Indoor SN</p>
                                            <p class="text-[10px] font-black text-slate-700 truncate" x-text="selectedAc.indoor_sn || '-'"></p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                                            <p class="text-[8px] font-bold text-slate-400 uppercase">Outdoor SN</p>
                                            <p class="text-[10px] font-black text-slate-700 truncate" x-text="selectedAc.outdoor_sn || '-'"></p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Spesifikasi Tambahan</label>
                                    <p class="text-xs text-slate-600 bg-slate-50 p-4 rounded-2xl border border-slate-100 leading-relaxed italic" 
                                       x-text="selectedAc.specifications || 'Tidak ada spesifikasi tambahan.'"></p>
                                </div>
                            </div>

                            {{-- Kolom Kanan: Foto Unit --}}
                            <div class="space-y-6">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Foto Dokumentasi</label>
                                    <div class="space-y-4">
                                        {{-- Indoor Image --}}
                                        <div class="relative group aspect-video bg-slate-100 rounded-2xl overflow-hidden border border-slate-200">
                                            <template x-if="selectedAc.image_indoor_url">
                                                <img :src="selectedAc.image_indoor_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!selectedAc.image_indoor_url">
                                                <div class="flex flex-col items-center justify-center h-full text-slate-300">
                                                    <span class="text-2xl">🖼️</span>
                                                    <span class="text-[8px] font-black uppercase mt-1">Indoor Belum Ada</span>
                                                </div>
                                            </template>
                                            <div class="absolute bottom-2 left-2 bg-black/50 backdrop-blur px-2 py-1 rounded text-[8px] text-white font-bold uppercase">Indoor</div>
                                        </div>
                                        
                                        {{-- Outdoor Image --}}
                                        <div class="relative group aspect-video bg-slate-100 rounded-2xl overflow-hidden border border-slate-200">
                                            <template x-if="selectedAc.image_outdoor_url">
                                                <img :src="selectedAc.image_outdoor_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!selectedAc.image_outdoor_url">
                                                <div class="flex flex-col items-center justify-center h-full text-slate-300">
                                                    <span class="text-2xl">🖼️</span>
                                                    <span class="text-[8px] font-black uppercase mt-1">Outdoor Belum Ada</span>
                                                </div>
                                            </template>
                                            <div class="absolute bottom-2 left-2 bg-black/50 backdrop-blur px-2 py-1 rounded text-[8px] text-white font-bold uppercase">Outdoor</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

<div class="mt-10 flex gap-4">
    <button @click="openDetailModal = false; setTimeout(() => { openReplaceModal = true }, 100)" 
            class="flex-1 bg-amber-500 text-white py-4 rounded-2xl font-black shadow-xl hover:bg-amber-600 transition-all uppercase tracking-widest text-xs">
        🔄 Ganti dari Unit Lain
    </button>
    
    <button @click="openDetailModal = false" 
            class="flex-1 bg-slate-800 text-white py-4 rounded-2xl font-black shadow-xl hover:bg-slate-900 transition-all uppercase tracking-widest text-xs">
        Tutup Detail
    </button>
</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Update Denah --}}
        <div x-show="openEditPlanModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak x-transition>
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div @click.away="openEditPlanModal = false" class="bg-white rounded-[2.5rem] max-w-md w-full p-10 shadow-2xl">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Update Denah</h2>
                        <button @click="openEditPlanModal = false" class="text-gray-300 hover:text-red-500 text-3xl font-light">&times;</button>
                    </div>
                    <form :action="'/floors/' + selectedFloorId" method="POST" enctype="multipart/form-data"
      @submit.prevent="
        Swal.fire({
            title: 'Ganti File Denah?',
            text: 'File denah lama akan diganti dengan file baru.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Upload Sekarang',
            confirmButtonColor: '#4F46E5'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    didOpen: () => { Swal.showLoading() }
                });
                $el.submit();
            }
        })
      ">
                        @csrf
                        @method('PATCH')
                        <div class="space-y-6">
                            <div class="relative border-4 border-dashed border-gray-100 rounded-[2rem] p-10 text-center hover:border-indigo-200 transition group">
                                <input type="file" name="floor_plan" required class="absolute inset-0 opacity-0 cursor-pointer">
                                <div class="text-slate-400">
                                    <span class="text-4xl">📄</span>
                                    <p class="text-[10px] font-black mt-3 uppercase tracking-widest">Pilih File Baru</p>
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black shadow-xl hover:bg-indigo-700 transition-all uppercase tracking-widest">Simpan Denah</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

{{-- MODAL RELOKASI (REPLACE DARI UNIT LAIN) --}}
<div x-show="openReplaceModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak x-transition>
    <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-md"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-[3rem] max-w-2xl w-full p-10 shadow-2xl">
            <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">Relokasi Unit AC</h2>
            <p class="text-sm text-slate-500 mb-6 font-medium">Cari dan pilih unit AC dari gedung mana pun untuk dipindahkan ke posisi ini.</p>
            
            <form :action="'/ac/' + selectedAc.id + '/relocate'" method="POST" 
      @submit.prevent="
        Swal.fire({
            title: 'Konfirmasi Relokasi?',
            text: 'Unit AC akan dipindahkan ke posisi ini secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4F46E5',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Pindahkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $el.submit();
            }
        })
      ">
                @csrf
                
                {{-- Input Pencarian --}}
                <div class="relative mb-4">
                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input x-model="searchAc" type="text" placeholder="Cari Brand, Serial Number, atau Gedung..." 
                           class="w-full pl-12 pr-4 py-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>

                {{-- List AC dengan Scroll --}}
                <div class="max-h-[300px] overflow-y-auto mb-8 rounded-2xl border border-slate-100 divide-y divide-slate-50 bg-slate-50/30">
                    @php
                        // Mengambil semua AC kecuali yang sedang dipilih
                        $allAcs = \App\Models\Ac::with('room.floor.building')->get();
                    @endphp

@foreach($allAcs as $otherAc)
    {{-- Gunakan x-show milik Alpine untuk menyembunyikan AC yang sedang dipilih --}}
    <label x-show="selectedAc.id != {{ $otherAc->id }} && '{{ strtolower($otherAc->brand . ' ' . $otherAc->indoor_sn . ' ' . ($otherAc->room->floor->building->name ?? '')) }}'.includes(searchAc.toLowerCase())" 
           class="flex items-center p-4 hover:bg-white cursor-pointer transition-colors group">
        
        <input type="radio" name="target_ac_id" value="{{ $otherAc->id }}" required 
               class="w-5 h-5 text-indigo-600 border-slate-300 focus:ring-indigo-500">
        
        <div class="ml-4">
            <p class="text-sm font-black text-slate-800 uppercase tracking-tight">
                {{ $otherAc->brand }} <span class="text-[10px] text-slate-400 font-bold ml-2">SN: {{ $otherAc->indoor_sn }}</span>
            </p>
            <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-widest">
                {{ $otherAc->room->floor->building->name ?? 'N/A' }} — {{ $otherAc->room->name ?? 'N/A' }}
            </p>
        </div>
    </label>
@endforeach
                </div>

                <div class="flex gap-4">
                    <button type="button" @click="openReplaceModal = false" 
                            class="flex-1 bg-slate-100 text-slate-600 py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-slate-200 transition-all">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl font-black shadow-lg uppercase text-xs tracking-widest hover:bg-indigo-700 transition-all">
                        Konfirmasi Relokasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

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
        text: "{{ session('error') }}",
    });
</script>
@endif

    {{-- Script PDF --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pdfjsLib = window['pdfjs-dist/build/pdf'];
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
            const canvases = document.querySelectorAll('canvas[id^="pdf-canvas-"]');
            canvases.forEach(canvas => {
                const url = canvas.getAttribute('data-pdf-url');
                pdfjsLib.getDocument(url).promise.then(pdf => {
                    pdf.getPage(1).then(page => {
                        const viewport = page.getViewport({ scale: 1.5 });
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        canvas.style.maxWidth = '100%';
                        canvas.style.height = 'auto';
                        page.render({ canvasContext: context, viewport: viewport });
                    });
                });
            });
        });
    </script>
    {{-- Library SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</x-guest-layout>