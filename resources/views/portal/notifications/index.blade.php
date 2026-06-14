@extends('portal.layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Notifikasi</h1>
        <p class="mt-1 text-sm text-gray-500">Info modul baru dan hasil kuis kamu.</p>
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
                    class="flex items-start gap-3 rounded-2xl border bg-white p-4 shadow-sm transition-colors hover:bg-slate-50
                        {{ $isUnread ? 'border-indigo-200 bg-indigo-50/40' : 'border-gray-200' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                        {{ $isUnread ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400' }}">
                        <x-dynamic-component :component="$icon" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[15px] font-semibold text-gray-900">{{ $data['title'] ?? 'Notifikasi' }}</p>
                            @if($isUnread)
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-500"></span>
                            @endif
                        </div>
                        @if(! empty($data['body']))
                            <p class="mt-0.5 text-sm text-gray-600">{{ $data['body'] }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
