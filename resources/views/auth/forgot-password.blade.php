<x-guest-layout>
   

{{-- Background Glow Effects --}}
    <div class="fixed top-[-15%] right-[-15%] w-[50vw] h-[50vw] bg-[#2da2ad]/70 rounded-full blur-[60px] animate-blob -z-10"></div>
    <div class="fixed bottom-[-15%] left-[-15%] w-[55vw] h-[55vw] bg-[#D1FADF]/90 rounded-full blur-[70px] animate-blob animation-delay-2000 -z-10"></div>

    <div class="min-h-screen flex flex-col items-center justify-center p-6">
        
        {{-- Wrapper Putih Transparan (Glassmorphism) --}}
        <div class="w-full max-w-md bg-white/70 backdrop-blur-md rounded-[3rem] p-8 md:p-12 shadow-2xl border border-white/20 relative">
            
            {{-- Tombol Kembali ke Login --}}
            <a href="{{ route('login') }}" class="absolute top-8 right-8 text-gray-400 hover:text-[#2da2ad] transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>

            {{-- Header --}}
            <div class="mb-8">
                <h1 class="text-2xl font-black text-[#2D365E] uppercase tracking-tighter">Reset Password</h1>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mt-2 leading-relaxed">
                    {{ __('Lupa password? Masukkan alamat email Anda dan kami akan mengirimkan link reset password.') }}
                </p>
            </div>

            <x-auth-session-status class="mb-4 bg-green-100 text-green-700 p-4 rounded-2xl font-bold text-xs" :status="session('status')" />

<form method="POST" action="{{ route('password.direct_update') }}">
    @csrf
    <div class="mb-4">
        <x-text-input id="email" class="block mt-1 w-full bg-white border-none rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#2da2ad] transition-all text-gray-900 placeholder:text-slate-400" 
            type="email" name="email" :value="old('email')" required autofocus placeholder="Masukkan Email Terdaftar" />
        <x-input-error :messages="$errors->get('email')" class="mt-2 text-[10px] font-bold text-red-500 uppercase ml-1" />
    </div>

    <div class="mb-4">
        <x-text-input id="password" class="block mt-1 w-full bg-white border-none rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#2da2ad] transition-all text-gray-900 placeholder:text-slate-400" 
            type="password" name="password" required placeholder="Password Baru" />
        <x-input-error :messages="$errors->get('password')" class="mt-2 text-[10px] font-bold text-red-500 uppercase ml-1" />
    </div>

    <div class="mb-4">
        <x-text-input id="password_confirmation" class="block mt-1 w-full bg-white border-none rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#2da2ad] transition-all text-gray-900 placeholder:text-slate-400" 
            type="password" name="password_confirmation" required placeholder="Ulangi Password Baru" />
    </div>

    <div class="pt-2">
        <button type="submit" class="w-full bg-[#2D365E] text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl hover:bg-[#1a213d] transition-all active:scale-95">
            {{ __('Update Password Sekarang') }}
        </button>
    </div>
</form>

            {{-- Footer link --}}
            <div class="mt-8 text-center">
                <a href="{{ route('login') }}" class="text-[10px] font-black text-slate-400 hover:text-[#2da2ad] uppercase tracking-widest transition-colors">
                    Kembali ke halaman Login
                </a>
            </div>
        </div>
    </div>

    <style>
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: blob 12s infinite alternate ease-in-out;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
    </style>
</x-guest-layout>