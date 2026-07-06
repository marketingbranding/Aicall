<x-guest-layout>
    <div class="mb-4 space-y-2 text-sm text-gray-600">
        <p class="text-base font-medium text-gray-900">Lupa password?</p>
        <p>Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru.</p>
        <p>Jika email terdaftar, tautan reset akan dikirim dan hanya berlaku sementara.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Kirim Tautan Reset
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
