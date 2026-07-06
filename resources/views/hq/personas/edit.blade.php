<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Persona: {{ $persona->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
                Mengedit persona akan membuat versi baru. Versi sebelumnya tetap tersimpan.
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('hq.personas.update', $persona) }}" class="p-6">
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

                    <div class="mb-6">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                        <input type="text" id="code" name="code" value="{{ old('code', $persona->code) }}" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Kode unik untuk identifikasi.</p>
                    </div>

                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $persona->name) }}" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Nama simulasi konsumen.</p>
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea id="description" name="description" rows="4"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('description', $persona->currentVersion?->public_profile_text) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Profil singkat yang terlihat oleh pengguna Sales.</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Perbarui Persona
                        </button>
                        <a href="{{ route('hq.personas.index') }}"
                            class="text-sm text-gray-600 hover:text-gray-900">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
