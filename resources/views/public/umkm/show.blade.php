@extends('public.layouts.app')

@section('title', $product->nama_produk)

@section('content')
    @php
        $profile = $product->umkmProfile;
        $photos = $product->getMedia('photos');
        $waText = 'Halo, saya tertarik dengan produk "'.$product->nama_produk.'" di katalog UMKM '.($profile->desa?->nama_lengkap ?? 'desa').'.';
    @endphp

    <a href="{{ route('public.umkm.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke katalog</a>

    <div class="mt-4 grid gap-8 lg:grid-cols-2">
        {{-- Foto --}}
        <div>
            <img src="{{ $product->coverUrl() }}" alt="{{ $product->nama_produk }}"
                class="aspect-square w-full rounded-xl object-cover">
            @if($photos->count() > 1)
                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach($photos as $media)
                        <img src="{{ $media->getUrl('card') }}" alt="" class="aspect-square w-full rounded-lg object-cover ring-1 ring-gray-200">
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div>
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">{{ $profile->category?->nama }}</span>
            <h1 class="mt-2 text-2xl font-bold">{{ $product->nama_produk }}</h1>
            <p class="mt-1 text-xl font-bold text-indigo-600">
                {{ $product->harga ? 'Rp '.number_format($product->harga, 0, ',', '.') : 'Hubungi penjual untuk harga' }}
            </p>

            @if($product->deskripsi)
                <p class="mt-4 whitespace-pre-line text-sm text-gray-600">{{ $product->deskripsi }}</p>
            @endif

            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-sm font-semibold">{{ $profile->nama_usaha }}</p>
                <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-500">
                    @if($profile->desa?->kabupatenLogoUrl())
                        <img src="{{ $profile->desa->kabupatenLogoUrl() }}" alt="Logo kabupaten" class="h-4 w-4 shrink-0 object-contain">
                    @endif
                    <span>{{ $profile->desa?->nama_lengkap }}@if($profile->alamat) · {{ $profile->alamat }}@endif</span>
                </div>

                <a href="{{ $profile->whatsappUrl($waText) }}" target="_blank" rel="noopener"
                    class="mt-3 inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                    Hubungi via WhatsApp
                </a>
            </div>

            <p class="mt-3 text-xs text-gray-400">{{ number_format($product->jumlah_dilihat) }}× dilihat</p>
        </div>
    </div>
@endsection
