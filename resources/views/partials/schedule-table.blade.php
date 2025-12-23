<div class="overflow-hidden rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/50 bg-white">
    <table class="w-full text-left border-collapse">
<thead class="bg-[#2D365E] text-white">
    <tr>
        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-[0.2em]">Nama Jadwal</th>
        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-[0.2em] text-center">Mulai</th>
        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-[0.2em] text-center">Selesai</th> 
        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-[0.2em]">Area Gedung</th>
        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-[0.2em] text-center">Status</th>
        <th class="px-8 py-7 text-[10px] font-black uppercase tracking-[0.2em] text-center">Aksi</th>
    </tr>
</thead>
<tbody class="divide-y divide-slate-50">
    <template x-for="item in filteredSchedules" :key="item.id">
        <tr class="hover:bg-slate-50/80 transition-colors group">
            <td class="px-8 py-6">
                <span class="text-sm font-black text-[#2D365E] uppercase" x-text="item.name"></span>
            </td>
            <td class="px-8 py-6 text-center text-xs font-bold text-slate-500 uppercase" x-text="item.start_date"></td>
            
            <td class="px-8 py-6 text-center">
                <span class="text-xs font-black text-emerald-600 uppercase" 
                      x-text="item.end_date ? item.end_date : '-'"></span>
            </td>

            <td class="px-8 py-6">
    <div class="flex flex-col gap-2"> <template x-for="ac in item.acs" :key="ac.id">
            <div class="flex flex-col items-start gap-1">
                <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-lg text-[9px] font-black uppercase" 
                      x-text="ac.room?.floor?.building?.name"></span>
                
                <template x-if="ac.next_service_date">
                    <span class="text-[8px] bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded border border-emerald-100 font-bold">
                        NEXT SERVICE: <span x-text="new Date(ac.next_service_date).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})"></span>
                    </span>
                </template>
            </div>
        </template>
    </div>
</td>
            <td class="px-8 py-6 text-center">
                <span :class="{
                    'bg-green-100 text-green-600': item.status === 'selesai',
                    'bg-yellow-100 text-yellow-600': item.status === 'proses',
                    'bg-red-100 text-red-600': item.status === 'belum'
                }" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase" x-text="item.status"></span>
            </td>
            <td class="px-8 py-6 text-center">
    <div class="flex items-center justify-center gap-2">
        <button @click="openDetail(item)" 
                class="bg-[#2D365E] hover:bg-[#3d4a82] text-white px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all active:scale-95 shadow-md shadow-indigo-900/10">
            Detail
        </button>

        <button @click="openEdit(item)" 
                class="bg-white border-2 border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-600 p-2.5 rounded-xl transition-all hover:scale-105 active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
        </button>
    </div>
</td>
        </tr>
    </template>
</tbody>
    </table>
</div>