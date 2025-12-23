<x-guest-layout>
    {{-- Library PDF.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>

    <div x-data="{ 
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
            }).then(res => res.json()).then(data => {
                console.log('Posisi tersimpan');
            });
        }
    }" class="max-w-7xl mx-auto p-6 bg-gray-50 min-h-screen">
        
        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <nav class="text-sm text-gray-400 mb-1 font-medium">
                    <a href="{{ route('location.index') }}" class="hover:text-slate-800 transition">Gedung</a> / 
                    <a href="{{ route('location.show', $building->id) }}" class="hover:text-slate-800 transition">{{ $building->name }}</a> / 
                    <span class="text-slate-800 font-bold">Interactive Plan</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">
                        <span x-text="editMode ? '⚙️ Mode Layouting' : '📍 Monitoring Denah'"></span>
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
                    <span x-text="editMode ? '🔓 Selesai Edit' : '🔒 Atur Posisi'"></span>
                </button>

                <div class="flex bg-white p-1 rounded-2xl shadow-sm border border-gray-200">
                    <button @click="zoom = Math.max(zoom - 10, 50)" class="px-3 py-1 hover:bg-gray-100 rounded-xl transition font-bold text-slate-600">-</button>
                    <span class="px-3 py-1 text-xs font-mono font-bold flex items-center min-w-[50px] justify-center" x-text="zoom + '%'"></span>
                    <button @click="zoom = Math.min(zoom + 10, 200)" class="px-3 py-1 hover:bg-gray-100 rounded-xl transition font-bold text-slate-600">+</button>
                </div>

                <a href="{{ route('location.show', $building->id) }}" 
                   class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 text-gray-400 hover:text-red-500 rounded-2xl shadow-sm transition-all font-bold">
                    ✕
                </a>
            </div>
        </div>

        <div class="space-y-12">
            @foreach($building->floors as $floor)
                <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-gray-100 transition-all">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="inline-block bg-slate-800 text-white px-6 py-2 rounded-full text-xs font-black uppercase tracking-widest">
                            {{ $floor->name }}
                        </h2>
                        
                        <template x-if="editMode">
                            <button @click="openEditPlanModal = true; selectedFloorId = '{{ $floor->id }}'; selectedFloorName = '{{ $floor->name }}'" 
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-4 py-2 rounded-xl transition">
                                🖼️ Ganti File Denah
                            </button>
                        </template>
                    </div>

                    <div class="relative bg-slate-50 rounded-[2.5rem] min-h-[600px] overflow-hidden flex justify-center border-4 transition-colors shadow-inner"
                         :class="editMode ? 'border-dashed border-indigo-200 bg-indigo-50/30' : 'border-transparent'">
                        
                        <div class="relative transition-transform duration-300 origin-top" 
                             :style="'transform: scale(' + zoom/100 + '); width: 100%; max-width: 1000px; aspect-ratio: 16/9;'">
                            
                            {{-- Denah Render --}}
                            <div class="absolute inset-0 z-0 flex items-center justify-center pointer-events-none">
                                @if($floor->floor_plan)
                                    @php $ext = pathinfo($floor->floor_plan, PATHINFO_EXTENSION); @endphp
                                    @if(strtolower($ext) === 'pdf')
                                        <canvas id="pdf-canvas-{{ $floor->id }}" 
                                                data-pdf-url="{{ asset('storage/' . $floor->floor_plan) }}" 
                                                class="w-full h-full object-contain opacity-80"></canvas>
                                    @else
                                        <img src="{{ asset('storage/' . $floor->floor_plan) }}" 
                                             class="w-full h-full object-contain opacity-80">
                                    @endif
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
                                                this.dragging = true;
                                                const container = $el.parentElement.getBoundingClientRect();
                                                const move = (moveEvent) => {
                                                    if (this.dragging) {
                                                        this.x = Math.max(0, Math.min(100, ((moveEvent.clientX - container.left) / container.width) * 100));
                                                        this.y = Math.max(0, Math.min(100, ((moveEvent.clientY - container.top) / container.height) * 100));
                                                    }
                                                };
                                                const stop = () => {
                                                    if (this.dragging) {
                                                        this.dragging = false;
                                                        savePosition(this.id, this.x, this.y);
                                                        window.removeEventListener('mousemove', move);
                                                        window.removeEventListener('mouseup', stop);
                                                    }
                                                };
                                                window.addEventListener('mousemove', move);
                                                window.addEventListener('mouseup', stop);
                                            }
                                         }"
                                         :style="'left: ' + x + '%; top: ' + y + '%; position: absolute;'"
                                         @mousedown="startDrag($event)"
                                         @click="if(!editMode) { selectedAc = acData; openDetailModal = true; }"
                                         class="z-10 group select-none translate-x-[-50%] translate-y-[-50%]"
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
            @endforeach
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
                    <form :action="'/floors/' + selectedFloorId" method="POST" enctype="multipart/form-data">
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
    </div>

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
                        const viewport = page.getViewport({ scale: 2.0 });
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        page.render({ canvasContext: context, viewport: viewport });
                    });
                });
            });
        });
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .select-none { user-select: none; -webkit-user-drag: none; }
    </style>
</x-guest-layout>