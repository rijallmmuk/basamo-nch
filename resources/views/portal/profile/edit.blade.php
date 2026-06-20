@extends('portal.layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Profil Saya'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Profil Saya</h1>
        <p class="mt-1 text-sm text-gray-500">Atur foto profil yang tampil di portal.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <x-portal.card class="max-w-lg">
        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Foto saat ini --}}
            <div class="flex items-center gap-4">
                <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" size="lg" variant="solid" class="!h-20 !w-20 !text-2xl" />
                <div class="min-w-0">
                    <p class="truncate font-semibold text-gray-900">{{ $user->name }}</p>
                    <p class="truncate text-sm text-gray-500">{{ $user->nagari?->nama ?? 'Warga' }}</p>
                </div>
            </div>

            {{-- Upload --}}
            <div>
                <label for="avatar" class="mb-1.5 block text-sm font-medium text-gray-700">Ganti foto profil</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-1.5 text-xs text-gray-400">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                @error('avatar')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Hapus foto --}}
            @if($user->avatarUrl())
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remove_avatar" value="1" class="rounded border-gray-300 text-indigo-600">
                    Hapus foto profil (kembali ke inisial)
                </label>
            @endif

            <div class="pt-1">
                <x-portal.button type="submit">Simpan</x-portal.button>
            </div>
        </form>
    </x-portal.card>
@endsection
