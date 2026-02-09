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

{{-- AREA GEDUNG DENGAN TOOLTIP TELEPORT --}}
<td class="px-8 py-6">
    <div x-data="{ 
        open: false,
        triggerEl: null,
        tooltipStyles: { top: '0px', left: '0px' },
        calculatePosition() {
            if (!this.$refs.button) return;
            const rect = this.$refs.button.getBoundingClientRect();
            // Menghitung posisi agar melayang di atas tombol (bottom-up)
            this.tooltipStyles.left = rect.left + 'px';
            this.tooltipStyles.top = (rect.top + window.scrollY - 10) + 'px';
        }
    }" 
    x-init="$watch('open', value => { if(value) { $nextTick(() => calculatePosition()) } })"
    @click.away="open = false"
    class="relative">
        
        <button x-ref="button" @click="open = !open; calculatePosition()" 
                class="flex items-center gap-3 focus:outline-none group/btn transition-transform active:scale-95 text-left">
            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-lg border border-indigo-100 shadow-sm group-hover/btn:bg-indigo-100 transition-colors">
                🏢
            </div>
            <div class="flex flex-col">
                <span class="text-[10px] font-black text-[#2D365E] uppercase leading-tight" 
                      x-text="item.acs.length + ' Lokasi AC'"></span>
                <span class="text-[9px] text-slate-400 font-bold italic mt-0.5 uppercase" 
                      x-text="item.acs.length > 0 ? (item.acs[0].room?.floor?.building?.name?.substring(0, 10) + '...') : 'Kosong'"></span>
            </div>
        </button>

        <template x-teleport="body">
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak
                 :style="{ 
                    position: 'absolute', 
                    left: tooltipStyles.left, 
                    top: tooltipStyles.top,
                    transform: 'translateY(-100%)',
                    zIndex: 9999 
                 }"
                 class="min-w-[240px] pointer-events-none">
                
                <div class="relative bg-[#2D365E] text-white p-4 rounded-[1.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.4)] border border-white/10 pointer-events-auto">
                    <p class="text-[9px] font-black text-indigo-300 uppercase tracking-[0.2em] mb-3 border-b border-white/10 pb-2 text-center">Daftar Detail Area</p>
                    
                    <div class="space-y-3 max-h-[200px] overflow-y-auto pr-2 custom-scrollbar">
                        <template x-for="ac in item.acs" :key="ac.id">
                            <div class="flex flex-col items-start gap-1 p-2 rounded-lg bg-white/5 border border-white/5">
                                <span class="bg-indigo-500/20 text-indigo-200 px-2 py-1 rounded text-[9px] font-black uppercase" 
                                      x-text="ac.room?.floor?.building?.name"></span>
                                
                                <template x-if="ac.next_service_date">
                                    <span class="text-[8px] text-emerald-400 font-bold tracking-tighter">
                                        NEXT: <span x-text="new Date(ac.next_service_date).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="absolute top-[99%] left-6 w-0 h-0 border-l-[8px] border-l-transparent border-r-[8px] border-r-transparent border-t-[8px] border-t-[#2D365E]"></div>
                </div>
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