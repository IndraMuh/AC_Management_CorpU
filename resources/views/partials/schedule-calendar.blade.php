{{-- resources/views/partials/schedule-calendar.blade.php --}}
<div class="mt-6">
    {{-- Header Navigasi Bulan --}}
    {{-- Kita gunakan variabel 'month' dan 'year' yang ada di x-data file utama --}}
    <div class="flex justify-end items-center mb-4 gap-4">
        <div class="flex items-center bg-white border rounded-xl overflow-hidden shadow-sm">
            <button @click="if(month === 0) { month = 11; year--; } else { month--; }" class="px-4 py-2 hover:bg-gray-50 text-xs font-bold border-r">&lt;</button>
            
            <span class="px-8 py-2 text-xs font-black uppercase tracking-widest text-slate-700" 
                  x-text="new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(year, month))"></span>
            
            <button @click="if(month === 11) { month = 0; year++; } else { month++; }" class="px-4 py-2 hover:bg-gray-50 text-xs font-bold border-l">&gt;</button>
        </div>
    </div>

    <div class="bg-white border rounded-[2rem] overflow-hidden shadow-sm">
        {{-- Nama-nama Hari --}}
        <div class="grid grid-cols-7 border-b">
            <template x-for="day in ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']">
                <div class="py-4 text-center text-[10px] font-black uppercase text-slate-400 border-r last:border-r-0" x-text="day"></div>
            </template>
        </div>

        <div class="grid grid-cols-7 h-[600px]">
            {{-- Padding awal bulan --}}
            <template x-for="i in new Date(year, month, 1).getDay()">
                <div class="border-r border-b p-2 bg-gray-50/30"></div>
            </template>

            {{-- Render Hari --}}
            <template x-for="date in new Date(year, month + 1, 0).getDate()">
                <div @click="showDayDetails(date, month, year)"
                     class="border-r border-b p-2 relative hover:bg-slate-50 transition group last:border-r-0 cursor-pointer overflow-y-auto">
                    
                    <span class="text-[10px] font-bold text-slate-400 absolute top-3 left-4 group-hover:text-slate-800" x-text="date"></span>
                    
                    <div class="mt-8 space-y-1">
                        {{-- Filter jadwal dari 'schedules_calendar' yang ada di x-data utama --}}
                        <template x-for="event in schedules_calendar.filter(e => e.day === date && e.month === month && e.year === year)">
                            <div @click.stop="openDetail(event)"
                                 :class="{
                                     'bg-[#86D052] text-white': event.status === 'selesai',
                                     'bg-[#FFB800] text-white': event.status === 'proses',
                                     'bg-[#FF6B6B] text-white': event.status === 'belum'
                                 }" 
                                 class="px-2 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-tighter shadow-sm transition-transform hover:scale-95"
                                 x-text="event.name">
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>