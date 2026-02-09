{{-- resources/views/partials/schedule-calendar.blade.php --}}
<div class="mt-6">
    {{-- Header Navigasi Bulan --}}
    <div class="flex justify-end items-center mb-6 gap-4">
        <div class="flex items-center bg-white border-2 border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <button @click="if(month === 0) { month = 11; year--; } else { month--; }" class="px-4 py-2 hover:bg-gray-50 text-[10px] font-bold border-r-2 border-slate-200 text-slate-400">&lt; Previous Month</button>
            
            <span class="px-8 py-2 text-[10px] font-black uppercase tracking-widest text-[#2D365E]" 
                  x-text="new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date(year, month))"></span>
            
            <button @click="if(month === 11) { month = 0; year++; } else { month++; }" class="px-4 py-2 hover:bg-gray-50 text-[10px] font-bold border-l-2 border-slate-200 text-slate-400">Next Month &gt;</button>
        </div>
    </div>

    {{-- Kotak Nama Hari (Border ditebalkan menjadi border-2) --}}
    <div class="bg-white border-2 border-slate-200 rounded-[0.8rem] shadow-sm mb-4">
        <div class="grid grid-cols-7">
            <template x-for="day in ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']">
                <div class="py-5 text-center text-xs font-bold text-[1F2855]" x-text="day"></div>
            </template>
        </div>
    </div>

    {{-- Container Utama Kalender (Border ditebalkan menjadi border-2) --}}
    <div class="bg-white border-2 border-slate-200 rounded-[2rem] overflow-hidden shadow-sm">
        <div class="grid grid-cols-7 auto-rows-[120px]">
            {{-- Padding awal bulan --}}
            <template x-for="i in new Date(year, month, 1).getDay()">
                <div class="border-r-2 border-b-2 border-slate-100 bg-slate-50/20"></div>
            </template>

{{-- Render Hari --}}
<template x-for="date in new Date(year, month + 1, 0).getDate()">
    <div @click="showDayDetails(date, month, year)"
         class="border-r-2 border-b-2 border-slate-100 p-2 relative hover:bg-slate-50 transition group last:border-r-0 cursor-pointer overflow-hidden">
        
        {{-- Angka Tanggal --}}
        <span class="z-10 text-[11px] font-bold text-slate-400 absolute bottom-2 right-3 group-hover:text-[#2D365E]" x-text="date"></span>
        
        <div class="flex flex-col items-center justify-start h-full gap-1">
            {{-- 1. Ambil 3 Jadwal Pertama --}}
            <template x-for="event in schedules_calendar.filter(e => e.day === date && e.month === month && e.year === year).slice(0, 3)">
                <div @click.stop="openDetail(event)"
                     :class="{
                         'bg-[#DCFCE7] text-[#4CAF50] rounded-full': event.status === 'selesai',
                         'bg-[#FEF3C7] text-[#FFB800] rounded-[1.5rem]': event.status === 'proses',
                         'bg-[#FEE2E2] text-[#F64E60] rounded-[2rem]': event.status === 'belum'
                     }" 
                     class="px-2 py-1 w-full text-center text-[9px] font-black shadow-sm transition-transform hover:scale-95 truncate"
                     x-text="event.name">
                </div>
            </template>

            {{-- 2. Tampilkan hitungan sisanya jika lebih dari 3 --}}
            <template x-if="schedules_calendar.filter(e => e.day === date && e.month === month && e.year === year).length > 3">
                <div class="text-[9px] font-extrabold text-slate-400 text-center mt-1 bg-slate-100 rounded-full px-2 py-0.5">
                    +<span x-text="schedules_calendar.filter(e => e.day === date && e.month === month && e.year === year).length - 3"></span> lainnya
                </div>
            </template>
        </div>
    </div>
</template>

            {{-- Fill sisa grid agar border tetap konsisten --}}
            <template x-for="i in (42 - (new Date(year, month, 1).getDay() + new Date(year, month + 1, 0).getDate())) % 7">
                <div class="border-r-2 border-b-2 border-slate-100 bg-slate-50/20"></div>
            </template>
        </div>
    </div>
</div>