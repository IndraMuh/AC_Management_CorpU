<section>
    <header class="mb-6 px-4">
        <h2 class="text-2xl font-bold text-gray-600">
            {{ __('Delete Account') }}
        </h2>
    </header>

    <div class="px-4">
        <button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="bg-[#DC2626] hover:bg-red-700 text-white font-extrabold py-2 px-8 rounded-xl text-xs uppercase"
        >
            {{ __('Delete') }}
        </button>
    </div>
</section>