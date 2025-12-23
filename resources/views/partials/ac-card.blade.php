<div @click="
    selectedAc = {
        id: '{{ $ac->id }}',
        brand: '{{ $ac->brand }}',
        model: '{{ $ac->model }}',
        ac_type: '{{ $ac->ac_type }}',
        model_type: '{{ $ac->model_type }}',
        indoor_sn: '{{ $ac->indoor_sn }}',
        outdoor_sn: '{{ $ac->outdoor_sn }}',
        status: '{{ $ac->status }}',
        specifications: '{{ $ac->specifications }}',
        room_id: '{{ $ac->room_id }}',
        floor_id: '{{ $ac->room->floor_id }}',
        room_name: '{{ $ac->room->name }}',
        floor_name: '{{ $ac->room->floor->name }}',
        building_name: '{{ $ac->room->floor->building->name }}', // <-- Perubahan di sini
        image_indoor_url: '{{ $ac->image_indoor ? asset('storage/'.$ac->image_indoor) : '' }}',
        image_outdoor_url: '{{ $ac->image_outdoor ? asset('storage/'.$ac->image_outdoor) : '' }}'
    };
    openDetailModal = true;
    isEditingAc = false;
" class="bg-white rounded-3xl shadow-sm overflow-hidden border border-gray-100 group hover:shadow-md transition cursor-pointer">
    
    <div class="relative h-44 overflow-hidden bg-gray-200">
        <img src="{{ $ac->image_indoor ? asset('storage/' . $ac->image_indoor) : 'https://via.placeholder.com/400x300?text=No+Indoor+Photo' }}" 
             class="h-full w-full object-cover group-hover:scale-110 transition duration-500">
        
        <div class="absolute top-3 right-3">
            <span class="px-3 py-1 text-[10px] font-bold rounded-full shadow-sm {{ $ac->status == 'baik' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
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

<style>
    [x-cloak] { display: none !important; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .animate-fadeIn { animation: fadeIn 0.4s ease-out; }
    .animate-zoomIn { animation: zoomIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
