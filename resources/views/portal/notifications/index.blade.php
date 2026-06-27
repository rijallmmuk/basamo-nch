@extends('portal.layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Notifikasi</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Info modul baru dan hasil kuis kamu.</p>
    </div>

    @if($notifications->isEmpty())
        <x-portal.empty
            icon="heroicon-o-bell"
            title="Belum ada notifikasi"
            subtitle="Notifikasi akan muncul saat ada modul baru atau hasil kuis." />
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                    $icon = $data['icon'] ?? 'heroicon-s-bell';
                @endphp
                <a href="{{ $data['url'] ?? route('portal.home') }}"
                    class="flex items-start gap-3 rounded-2xl border p-4 shadow-sm transition-colors
                        {{ $isUnread
                            ? 'border-primary/20 bg-primary/5 hover:bg-primary/10'
                            : 'border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                        {{ $isUnread ? 'bg-primary/10 text-primary' : 'bg-surface-container-high text-on-surface-variant' }}">
                        <x-dynamic-component :component="$icon" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[15px] font-semibold text-on-surface">{{ $data['title'] ?? 'Notifikasi' }}</p>
                            @if($isUnread)
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary"></span>
                            @endif
                        </div>
                        @if(! empty($data['body']))
                            <p class="mt-0.5 text-sm text-on-surface-variant">{{ $data['body'] }}</p>
                        @endif
                        <p class="mt-1 text-xs text-on-surface-variant">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
