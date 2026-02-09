<x-guest-layout>
    <style>
        [x-cloak] { display: none !important; }
        pre { font-family: 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', monospace; }
        
        /* Animasi Blob dari file Location */
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }

        /* Kustomisasi scrollbar untuk JSON detail */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #2D365E; border-radius: 10px; }
    </style>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-cyan-100/50 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        
        {{-- Animated Blobs Background --}}
        <div class="fixed top-[-15%] right-[-15%] w-[50vw] h-[50vw] bg-[#2da2ad]/70 rounded-full blur-[60px] animate-blob"></div>
        <div class="fixed bottom-[-15%] left-[-15%] w-[55vw] h-[55vw] bg-[#D1FADF]/90 rounded-full blur-[70px] animate-blob animation-delay-2000"></div>

        <div class="max-w-7xl mx-auto relative">
            
            {{-- Wrapper Putih dengan Glassmorphism --}}
            <div class="bg-white/70 backdrop-blur-sm rounded-[3rem] p-8 md:p-12 shadow-2xl border border-white">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <div>
                        <h2 class="text-3xl font-black text-[#2D365E] uppercase tracking-widest">System Audit Log</h2>
                        <p class="text-gray-500 text-sm mt-1">Pantau semua aktivitas dan perubahan data sistem</p>
                    </div>
                    
    <a href="{{ url(path: '/dashboard') }}" class="absolute top-8 right-8 text-gray-400 hover:text-red-500 transition-colors duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </a>
                </div>

                <div class="mb-8 bg-white/50 backdrop-blur-md p-6 rounded-[2rem] border border-white shadow-inner">
                    <form action="{{ route('history.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-black uppercase text-[#2D365E] mb-2 ml-1 tracking-wider">Cari User / Model</label>
                            <input type="text" name="search" value="{{ request('search') }}" 
                                placeholder="Contoh: Admin atau Ac..."
                                class="w-full px-5 py-3 rounded-2xl border-none ring-1 ring-gray-200 focus:ring-2 focus:ring-[#2da2ad] text-xs shadow-sm bg-white/80">
                        </div>

                        <div class="w-full md:w-auto">
                            <label class="block text-[10px] font-black uppercase text-[#2D365E] mb-2 ml-1 tracking-wider">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" 
                                class="w-full px-5 py-3 rounded-2xl border-none ring-1 ring-gray-200 focus:ring-2 focus:ring-[#2da2ad] text-xs shadow-sm text-gray-500 bg-white/80">
                        </div>

                        <div class="w-full md:w-auto">
                            <label class="block text-[10px] font-black uppercase text-[#2D365E] mb-2 ml-1 tracking-wider">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" 
                                class="w-full px-5 py-3 rounded-2xl border-none ring-1 ring-gray-200 focus:ring-2 focus:ring-[#2da2ad] text-xs shadow-sm text-gray-500 bg-white/80">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="bg-[#2D365E] text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase hover:bg-[#2da2ad] transition shadow-lg active:scale-95 tracking-widest">
                                Filter
                            </button>
                            <a href="{{ route('history.index') }}" class="bg-white text-gray-500 border border-gray-100 px-8 py-3 rounded-2xl text-[10px] font-black uppercase hover:bg-gray-50 transition text-center shadow-sm tracking-widest">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="bg-white/80 backdrop-blur-md rounded-[2.5rem] shadow-xl overflow-hidden border border-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-[#2D365E] text-white">
                                <tr>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-wider">Waktu</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-wider">User</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-wider">Aksi</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-wider">Model</th>
                                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-wider text-center">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($logs as $log)
                                <tr class="hover:bg-white/60 transition group" x-data="{ openDetail: false }">
                                    <td class="px-8 py-5 text-xs font-medium text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-8 py-5 text-xs font-bold text-indigo-600">{{ $log->causer->name ?? 'System' }}</td>
                                    <td class="px-8 py-5">
                                        <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase shadow-sm
                                            {{ $log->description == 'created' ? 'bg-green-100 text-green-600' : ($log->description == 'updated' ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600') }}">
                                            {{ $log->description }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-xs text-[#2D365E] font-bold">{{ class_basename($log->subject_type) }}</td>
                                    <td class="px-8 py-5 text-center">
                                        @if($log->changes)
                                            <button @click="openDetail = true" class="inline-flex items-center gap-2 bg-white border border-gray-100 text-[#2D365E] px-5 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-[#2D365E] hover:text-white transition shadow-sm group-hover:shadow-md">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Lihat Perubahan
                                            </button>

<template x-teleport="body">
    <div x-show="openDetail" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-[#0a0f2b]/60 backdrop-blur-md p-4" 
         x-cloak>
        
        <div @click="openDetail = false" class="fixed inset-0 bg-transparent"></div>
        
        <div class="relative bg-white rounded-[3rem] shadow-[0_35px_60px_-15px_rgba(0,0,0,0.3)] w-full max-w-4xl overflow-hidden border border-white transform transition-all">
            
            <div class="bg-[#2D365E] p-8 text-white flex justify-between items-center relative z-10">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-[0.2em]">Detail Perubahan Data</h3>
                    <p class="text-[10px] opacity-60 mt-1 italic uppercase tracking-widest">
                        ID: #{{ $log->id }} • {{ $log->created_at->format('d M Y - H:i:s') }}
                    </p>
                </div>
                <button @click="openDetail = false" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition hover:rotate-90 duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-8 bg-gray-50/50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase text-green-600 mb-3 ml-2 flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            Sesudah (Attributes)
                        </span>
                        <div class="bg-white p-5 rounded-[2rem] border border-green-100 shadow-sm h-full">
                            <div class="max-h-80 overflow-y-auto custom-scroll text-[11px] text-gray-600 font-mono">
                                @if(isset($log->changes['attributes']))
                                    <pre class="whitespace-pre-wrap">{{ json_encode($log->changes['attributes'], JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    <p class="italic text-gray-400">Atribut tidak tersedia (Data Baru).</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase text-red-600 mb-3 ml-2 flex items-center gap-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                            Sebelum (Old)
                        </span>
                        <div class="bg-white p-5 rounded-[2rem] border border-red-100 shadow-sm h-full">
                            <div class="max-h-80 overflow-y-auto custom-scroll text-[11px] text-gray-600 font-mono">
                                @if(isset($log->changes['old']))
                                    <pre class="whitespace-pre-wrap">{{ json_encode($log->changes['old'], JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    <p class="italic text-gray-400">Tidak ada data lama (Create Action).</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-8 bg-white border-t flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-[10px] text-gray-400 uppercase font-black tracking-widest">
                    Sistem Audit Log • Digital Asset Management
                </div>
                <button @click="openDetail = false" class="bg-[#2D365E] text-white px-12 py-4 rounded-2xl text-[10px] font-black uppercase hover:bg-indigo-700 transition shadow-xl active:scale-95 tracking-widest">
                    Tutup Detail
                </button>
            </div>
        </div>
    </div>
</template>
                                        @else
                                            <span class="text-[10px] text-gray-300 italic">Tidak ada perubahan</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="p-6 bg-gray-50 rounded-full mb-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <p class="text-gray-400 font-bold uppercase text-[10px] tracking-widest">Data log tidak ditemukan</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 px-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>