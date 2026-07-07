<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Rubrik: {{ $rubric->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('hq.global-rubrics.update', $rubric) }}" id="rubricForm">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Rubrik</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $rubric->name) }}" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Kriteria Penilaian</h3>
                        <button type="button" onclick="addItem()"
                            class="inline-flex items-center px-3 py-1.5 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Tambah Item
                        </button>
                    </div>
                    <div class="p-6" id="itemsContainer">
                        @forelse ($rubric->items as $item)
                            <div class="border rounded-lg p-4 mb-4 bg-gray-50" data-index="{{ $loop->index }}">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-medium text-sm text-gray-700">Item #{{ $loop->iteration }}</span>
                                    <button type="button" onclick="this.closest('[data-index]').remove(); checkEmpty()"
                                        class="text-sm text-red-600 hover:text-red-800">Hapus</button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Key</label>
                                        <input type="text" name="items[{{ $loop->index }}][key]"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm"
                                            value="{{ $item->key }}" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Judul</label>
                                        <input type="text" name="items[{{ $loop->index }}][title]"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm"
                                            value="{{ $item->title }}" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Bobot</label>
                                        <input type="number" name="items[{{ $loop->index }}][weight]"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm"
                                            value="{{ $item->weight }}" min="1" max="1000">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
                                    <textarea name="items[{{ $loop->index }}][description]"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm"
                                        rows="2">{{ $item->description }}</textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Panduan Evaluasi</label>
                                    <textarea name="items[{{ $loop->index }}][evaluation_guidance]"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm"
                                        rows="2">{{ $item->evaluation_guidance }}</textarea>
                                </div>
                                <div class="mt-2">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="items[{{ $loop->index }}][is_disabled]"
                                            class="rounded border-gray-300 text-sage-500 shadow-sm focus:ring-sage-500"
                                            {{ !$item->is_enabled ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-600">Nonaktif</span>
                                    </label>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm text-center" id="noItemsMessage">Belum ada item. Klik "Tambah Item" untuk menambahkan.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Simpan
                    </button>
                    <a href="{{ route('hq.global-rubrics.index') }}"
                        class="text-sm text-gray-600 hover:text-gray-900">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let itemIndex = {{ $rubric->items->count() }};

            function addItem(data = {}) {
                const index = itemIndex++;
                const container = document.getElementById('itemsContainer');
                const noMessage = document.getElementById('noItemsMessage');
                if (noMessage) noMessage.remove();

                const html = `
                    <div class="border rounded-lg p-4 mb-4 bg-gray-50" data-index="${index}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-medium text-sm text-gray-700">Item Baru</span>
                            <button type="button" onclick="this.closest('[data-index]').remove(); checkEmpty()"
                                class="text-sm text-red-600 hover:text-red-800">Hapus</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Key</label>
                                <input type="text" name="items[${index}][key]"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Judul</label>
                                <input type="text" name="items[${index}][title]"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Bobot</label>
                                <input type="number" name="items[${index}][weight]"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm"
                                    value="100" min="1" max="1000">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea name="items[${index}][description]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm" rows="2"></textarea>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Panduan Evaluasi</label>
                            <textarea name="items[${index}][evaluation_guidance]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm" rows="2"></textarea>
                        </div>
                        <div class="mt-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="items[${index}][is_disabled]"
                                    class="rounded border-gray-300 text-sage-500 shadow-sm focus:ring-sage-500">
                                <span class="ml-2 text-sm text-gray-600">Nonaktif</span>
                            </label>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', html);
            }

            function checkEmpty() {
                const container = document.getElementById('itemsContainer');
                if (!container.querySelector('[data-index]')) {
                    container.innerHTML = '<p class="text-gray-500 text-sm text-center" id="noItemsMessage">Belum ada item. Klik "Tambah Item" untuk menambahkan.</p>';
                }
            }
        </script>
    @endpush
</x-app-layout>
