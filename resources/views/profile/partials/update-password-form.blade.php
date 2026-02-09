<section>
    <header class="text-center mb-8">
        <h2 class="text-4xl font-semibold text-[#1A1A1A]">
            {{ __('Update Password') }}
        </h2>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div class="px-4">
            <label class="block font-bold text-gray-600 text-sm mb-1 ml-1">{{ __('Current Password') }}</label>
            <input name="current_password" type="password" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-[#F0F0F0] focus:ring-0 focus:border-gray-400" />
        </div>

        <div class="px-4">
            <label class="block font-bold text-gray-600 text-sm mb-1 ml-1">{{ __('New Password') }}</label>
            <input name="password" type="password" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-[#F0F0F0] focus:ring-0 focus:border-gray-400" />
        </div>

        <div class="px-4">
            <label class="block font-bold text-gray-600 text-sm mb-1 ml-1">{{ __('Confirm Password') }}</label>
            <input name="password_confirmation" type="password" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-[#F0F0F0] focus:ring-0 focus:border-gray-400" />
        </div>

        <div class="px-4 pt-2">
            <button type="submit" class="bg-[#86D64B] hover:bg-[#75C03F] text-white font-extrabold py-2 px-8 rounded-xl text-xs uppercase tracking-tighter">
                {{ __('SAVE') }}
            </button>
        </div>
    </form>
</section>