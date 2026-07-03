@extends('public.layouts.app')

@section('title', 'Katalog UMKM')

@section('content')
    <h1 class="text-2xl font-bold">Katalog UMKM</h1>
    <p class="mt-1 text-sm text-gray-500">Produk dari pelaku UMKM desa. Hubungi penjual langsung via WhatsApp.</p>

    {{-- Filter --}}
    <form method="GET" class="mt-5 grid gap-3 sm:grid-cols-4">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari produk…"
            class="rounded-lg border-gray-300 text-sm sm:col-span-2">
        <select name="desa" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua desa</option>
            @foreach($desaList as $id => $nama)
                <option value="{{ $id }}" @selected(($filters['desa'] ?? null) == $id)>{{ $nama }}</option>
            @endforeach
        </select>
        <select name="kategori" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua kategori</option>
            @foreach($kategoriList as $id => $nama)
                <option value="{{ $id }}" @selected(($filters['kategori'] ?? null) == $id)>{{ $nama }}</option>
            @endforeach
        </select>
        <div class="sm:col-span-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Terapkan</button>
            <a href="{{ route('public.umkm.index') }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
        </div>
    </form>

    {{-- Grid produk --}}
    @if($products->isEmpty())
        <p class="mt-10 text-center text-gray-500">Belum ada produk yang cocok.</p>
    @else
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($products as $product)
                <a href="{{ route('public.umkm.show', $product) }}"
                    class="group overflow-hidden rounded-xl border border-gray-200 bg-white transition-shadow hover:shadow-md">
                    <img src="{{ $product->coverUrl() }}" alt="{{ $product->nama_produk }}"
                        class="aspect-square w-full object-cover">
                    <div class="p-3">
                        <p class="truncate text-sm font-semibold">{{ $product->nama_produk }}</p>
                        <p class="mt-0.5 text-sm font-bold text-indigo-600">
                            {{ $product->harga ? 'Rp '.number_format($product->harga, 0, ',', '.') : 'Hubungi penjual' }}
                        </p>
                        <p class="mt-1 truncate text-xs text-gray-400">
                            {{ $product->category?->nama }} · {{ $product->umkmProfile->desa?->nama_lengkap }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $products->links() }}</div>
    @endif
@endsection
