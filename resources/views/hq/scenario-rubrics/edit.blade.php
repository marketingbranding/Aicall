<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rubrik Skenario: {{ $scenario->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('hq.scenario-rubrics.update', $scenario) }}" id="rubricForm">
                @csrf

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
                        <input type="text" id="name" name="name" value="{{ old('name', $rubric->name ?? '') }}" required
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
                        @if ($rubric && $rubric->items->isNotEmpty())
                            @foreach ($rubric->items as $item)
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
                            @endforeach
                        @else
                            <p class="text-gray-500 text-sm text-center" id="noItemsMessage">Belum ada item. Klik "Tambah Item" untuk menambahkan.</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Override Rubrik Global</h3>
                    </div>
                    <div class="p-6">
                        @if ($globalRubrics->isEmpty())
                            <p class="text-gray-500 text-sm">Tidak ada rubrik global yang aktif.</p>
                        @else
                            <p class="text-sm text-gray-500 mb-4">Sesuaikan bobot atau nonaktifkan item dari rubrik global untuk skenario ini.</p>
                            @php
                                $overrideIndex = 0;
                                $existingOverrides = $scenario->currentVersion?->rubricOverrides ?? collect();
                            @endphp
                            @foreach ($globalRubrics as $globalRubric)
                                <h4 class="font-medium text-gray-800 mb-2 mt-4 first:mt-0">{{ $globalRubric->name }}</h4>
                                @foreach ($globalRubric->items as $globalItem)
                                    @php
                                        $override = $existingOverrides->firstWhere('global_rubric_item_key', $globalItem->key);
                                    @endphp
                                    <div class="border rounded p-3 mb-2 bg-gray-50">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">{{ $globalItem->title }}</span>
                                                <br><span class="text-xs text-gray-500">({{ $globalItem->key }}, bobot default: {{ $globalItem->weight }})</span>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Override Bobot</label>
                                                <input type="number" name="overrides[{{ $overrideIndex }}][weight_override]"
                                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm"
                                                    value="{{ $override->weight_override ?? '' }}" placeholder="Kosongkan = default" min="1" max="1000">
                                            </div>
                                            <div>
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="overrides[{{ $overrideIndex }}][is_enabled_override]"
                                                        class="rounded border-gray-300 text-sage-500 shadow-sm focus:ring-sage-500" value="1"
                                                        {{ $override?->is_enabled_override === false ? '' : 'checked' }}>
                                                    <span class="ml-2 text-sm text-gray-600">Aktif</span>
                                                </label>
                                            </div>
                                        </div>
                                        <input type="hidden" name="overrides[{{ $overrideIndex }}][global_rubric_item_key]" value="{{ $globalItem->key }}">
                                    </div>
                                    @php $overrideIndex++; @endphp
                                @endforeach
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Simpan Rubrik
                    </button>
                    <a href="{{ route('hq.scenarios.index') }}"
                        class="text-sm text-gray-600 hover:text-gray-900">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let itemIndex = {{ ($rubric && $rubric->items->isNotEmpty()) ? $rubric->items->count() : 0 }};

            function addItem() {
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
