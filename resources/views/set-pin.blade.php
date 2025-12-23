<x-guest-layout>
    <div class="max-w-5xl mx-auto p-6">
        <h2 class="text-xl font-bold mb-4">Tentukan Posisi AC: {{ $ac->brand }} ({{ $ac->model }})</h2>
        <p class="text-sm text-gray-500 mb-4">Klik pada gambar denah di bawah untuk meletakkan posisi AC.</p>

        <div id="floor-plan-container" class="relative inline-block border-2 border-dashed border-gray-300 rounded-xl overflow-hidden cursor-crosshair">
            <img id="floor-plan-image" src="{{ asset('storage/' . $ac->room->floor->floor_plan) }}" class="block w-full h-auto">
            
            <div id="temp-pin" class="absolute hidden bg-red-500 w-6 h-6 rounded-full border-2 border-white shadow-lg flex items-center justify-center -ml-3 -mt-3">
                <span class="text-[10px] text-white">❄️</span>
            </div>
        </div>

        <form action="{{ route('ac.update-position', $ac->id) }}" method="POST" class="mt-6">
            @csrf
            @method('PATCH')
            <input type="hidden" name="x_position" id="input-x">
            <input type="hidden" name="y_position" id="input-y">
            
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg">Simpan Posisi</button>
                <a href="{{ url()->previous() }}" class="bg-gray-200 px-6 py-2 rounded-lg">Batal</a>
            </div>
        </form>
    </div>

    <script>
        const container = document.getElementById('floor-plan-container');
        const img = document.getElementById('floor-plan-image');
        const pin = document.getElementById('temp-pin');
        const inputX = document.getElementById('input-x');
        const inputY = document.getElementById('input-y');

        container.addEventListener('click', function(e) {
            // Mendapatkan dimensi gambar saat ini
            const rect = img.getBoundingClientRect();
            
            // Hitung koordinat klik relatif terhadap gambar
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Konversi ke Persentase
            const xPercent = (x / rect.width) * 100;
            const yPercent = (y / rect.height) * 100;

            // Update Input Hidden
            inputX.value = xPercent.toFixed(2);
            inputY.value = yPercent.toFixed(2);

            // Tampilkan Preview Pin di layar
            pin.style.left = xPercent + '%';
            pin.style.top = yPercent + '%';
            pin.classList.remove('hidden');
        });
    </script>
</x-guest-layout>