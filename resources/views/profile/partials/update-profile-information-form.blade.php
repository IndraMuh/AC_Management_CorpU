<section>
    <header class="text-center mb-8">
        <h2 class="text-4xl font-semibold text-[#1A1A1A]">
            {{ __('Profile Information') }}
        </h2>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div class="px-4">
            <label class="block font-bold text-gray-600 text-sm mb-1 ml-1">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-[#F0F0F0] focus:ring-0 focus:border-gray-400 transition" value="{{ old('name', $user->name) }}" required />
        </div>

        <div class="px-4">
            <label class="block font-bold text-gray-600 text-sm mb-1 ml-1">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-[#F0F0F0] focus:ring-0 focus:border-gray-400 transition" value="{{ old('email', $user->email) }}" required />
        </div>

        <div class="px-4 pt-2">
            <button type="submit" class="bg-[#86D64B] hover:bg-[#75C03F] text-white font-extrabold py-2 px-8 rounded-xl text-xs uppercase tracking-tighter">
                {{ __('SAVE') }}
            </button>
        </div>
    </form>
</section>