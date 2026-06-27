@extends('portal.layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Profil Saya'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Profil Saya</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Atur foto profil yang tampil di portal.</p>
    </div>

    {{-- Flash sukses ditangani oleh layout (portal.layouts.app) — tidak diulang di sini. --}}

    <x-portal.card class="max-w-[32rem]">
        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Foto saat ini --}}
            <div class="flex items-center gap-4">
                <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" size="lg" variant="solid" class="!h-20 !w-20 !text-2xl" />
                <div class="min-w-0">
                    <p class="truncate font-semibold text-on-surface">{{ $user->name }}</p>
                    <p class="truncate text-sm text-on-surface-variant">{{ $user->desa?->nama_lengkap ?? 'Warga' }}</p>
                </div>
            </div>

            {{-- Upload --}}
            <div>
                <label for="avatar" class="mb-1.5 block text-sm font-medium text-on-surface">Ganti foto profil</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-sm text-on-surface-variant file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20">
                <p class="mt-1.5 text-xs text-on-surface-variant">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                @error('avatar')
                    <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Hapus foto --}}
            @if($user->avatarUrl())
                <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                    <input type="checkbox" name="remove_avatar" value="1" class="rounded border-outline-variant accent-primary">
                    Hapus foto profil (kembali ke inisial)
                </label>
            @endif

            <div class="pt-1">
                <x-portal.button type="submit">Simpan</x-portal.button>
            </div>
        </form>
    </x-portal.card>
@endsection
