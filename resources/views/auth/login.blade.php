<x-guest-layout>
    <style>
        /* --- Animasi Tetap Sama --- */
        @keyframes scrollUp { 0% { transform: translateY(0); } 100% { transform: translateY(-33.333%); } }
        @keyframes scrollDown { 0% { transform: translateY(-33.333%); } 100% { transform: translateY(0); } }
        .animate-up { animation: scrollUp 40s linear infinite; }
        .animate-down { animation: scrollDown 45s linear infinite; }
        .animate-up-slow { animation: scrollUp 55s linear infinite; }
        .animate-down-slow { animation: scrollDown 50s linear infinite; }
        .grid-animation-wrapper { display: flex; gap: 0.5vw; width: 250%; position: relative; left: 72.5%; transform: translateX(-50%); }
        .image-track-container { display: flex; flex-direction: column; will-change: transform; flex: 1; min-width: 300px; }
        .img-item { width: 100%; aspect-ratio: 1 / 1; flex-shrink: 0; border-radius: 24px; overflow: hidden; margin-bottom: 10px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .img-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .image-track-container:hover { animation-play-state: paused; }
        .img-item:hover img { transform: scale(1.05); }
        .custom-input { border: 1px solid #d1d5db !important; box-shadow: none !important; font-size: 0.9rem; }
        .btn-teal { background-color: #2da2ad; transition: all 0.3s ease; }
        .btn-teal:hover { background-color: #24818a; transform: translateY(-1px); }
        .btn-outline { border: 1px solid #2da2ad; color: #2da2ad; transition: all 0.3s ease; }
        .btn-outline:hover { background-color: #f0f9fa; }
    </style>

    <div class="min-h-screen w-full flex items-center justify-center bg-white">
        <div class="absolute inset-y-0 left-0 w-[80%] bg-gradient-to-r from-[#2796A3] via-[#2796A3]/60 to-transparent backdrop-blur-xl z-10 pointer-events-none" 
             style="mask-image: linear-gradient(to right, black 40%, transparent 90%); -webkit-mask-image: linear-gradient(to right, black 40%, transparent 90%);">
        </div>

        <div class="relative flex flex-col justify-center w-full md:w-[40%] min-h-[600px] bg-white rounded-[40px] overflow-hidden shadow-2xl z-20 p-8 lg:p-12">
            
            <div class="text-center mb-6">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-24 mx-auto">
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                
                <div class="space-y-4">
                    <div>
                        <input id="email" class="custom-input block w-full rounded-xl py-3 px-5 border-gray-300 focus:border-cyan-500 focus:ring-cyan-500" 
                               type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus />
                    </div>

                    <div>
                        <input id="password" class="custom-input block w-full rounded-xl py-3 px-5 border-gray-300 focus:border-cyan-500 focus:ring-cyan-500" 
                               type="password" name="password" placeholder="Password" required autocomplete="current-password" />
                    </div>
                </div>

                <div class="flex justify-between items-center text-[11px] font-medium px-1">
                    <label class="flex items-center text-gray-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-cyan-600 focus:ring-0 mr-2">
                        <span>Remember Me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-gray-700 hover:text-cyan-700 font-semibold">Forgot Your Password?</a>
                </div>

                <div class="flex gap-4 pt-2">
                    <a href="{{ route('register') }}" class="btn-outline flex-1 text-center py-3 rounded-xl font-bold uppercase text-xs tracking-wider">
                        Register
                    </a>
                    <button type="submit" class="btn-teal flex-1 py-3 text-white rounded-xl font-bold uppercase text-xs tracking-wider shadow-lg shadow-cyan-100">
                        Login
                    </button>
                </div>
            </form>
        </div>

        <div class="hidden md:flex w-[55%] h-[740px] self-center bg-[#080808] ml-9 rounded-[30px] overflow-hidden relative p-0 items-center justify-center shadow-inner">
            <div class="grid-animation-wrapper">
                @php
                    $sets = [
                        ['anim' => 'animate-up', 'files' => ['Group 274.png', 'Group 275.png', 'Group 276.png', 'Group 277.png']],
                        ['anim' => 'animate-down', 'files' => ['Group 278.png', 'Group 279.png', 'Group 280.png', 'Group 281.png']],
                        ['anim' => 'animate-up-slow', 'files' => ['Group 282.png', 'Group 283.png', 'Group 284.png', 'Group 285.png']],
                        ['anim' => 'animate-down-slow', 'files' => ['Group 285.png', 'Group 281.png', 'Group 282.png', 'Rectangle 86.png']],
                    ];
                @endphp

                @foreach($sets as $set)
                    <div class="image-track-container {{ $set['anim'] }}">
                        @foreach(array_merge($set['files'], $set['files'], $set['files']) as $file)
                            <div class="img-item">
                                <img src="{{ asset('images/'.$file) }}" alt="Photo">
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="absolute inset-0 bg-gradient-to-b from-[#080808] via-transparent to-[#080808] pointer-events-none opacity-60"></div>
        </div>
    </div>
</x-guest-layout>