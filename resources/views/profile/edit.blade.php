<x-app-layout>
    <div class="min-h-screen w-full bg-[#F3F4F6] relative overflow-y-auto flex items-start justify-center p-4 md:p-8 pt-10">
        
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
        </style>

        <div class="max-w-4xl w-full mx-auto relative z-10">
            <div class="bg-white/80 backdrop-blur-md shadow-2xl rounded-[3rem] p-8 lg:p-12 relative border border-white/50">
                
                <div class="absolute top-8 right-10 text-xl font-bold text-gray-800 cursor-pointer hover:text-black">
                    <a href="{{ route('dashboard') }}">X</a>
                </div>

                <div class="mb-10">
                    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">User Profile</h1>
                    <p class="text-gray-600">Update your account settings and password.</p>
                </div>

                <div class="space-y-10">
                    <div class="p-8 bg-white/50 rounded-[25px] border border-gray-100 shadow-sm">
                        @include('profile.partials.update-profile-information-form')
                    </div>

                    <div class="p-8 bg-white/50 rounded-[25px] border border-gray-100 shadow-sm">
                        @include('profile.partials.update-password-form')
                    </div>

                    <div class="p-8 bg-red-50/30 rounded-[25px] border border-red-100 shadow-sm">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>