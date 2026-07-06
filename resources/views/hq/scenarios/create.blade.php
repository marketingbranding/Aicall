<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Buat Skenario Baru
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('hq.scenarios.store') }}">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                            <input type="text" id="code" name="code" value="{{ old('code') }}" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <p class="mt-1 text-xs text-gray-500">Kode unik, contoh: TLPN_PERTAMA</p>
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <p class="mt-1 text-xs text-gray-500">Nama skenario, contoh: Telepon Pertama Masuk</p>
                        </div>
                    </div>
                </div>

                @php
                    $scenario = null;
                @endphp

                @include('hq.scenarios.partials._builder_sections')

                <div class="flex items-center gap-4 mt-6">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Simpan Skenario
                    </button>
                    <a href="{{ route('hq.scenarios.index') }}"
                        class="text-sm text-gray-600 hover:text-gray-900">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
