@extends('portal.layouts.app')

@section('title', 'Ruang Diskusi - ' . $module->judul)

@section('content')
    {{-- Back Link --}}
    <div class="mb-4">
        <a href="{{ route('portal.modules.show', $module) }}"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
            <x-heroicon-s-arrow-left class="h-4 w-4" />
            <span>Kembali ke Detail Modul</span>
        </a>
    </div>

    {{-- Header Banner --}}
    <div class="mb-6 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
        <div class="space-y-1">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-0.5 text-xs font-extrabold text-primary">
                <x-heroicon-s-chat-bubble-left-right class="h-3.5 w-3.5" />
                <span>Forum Diskusi Modul</span>
            </span>
            <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">
                Ruang Tanya Jawab & Diskusi
            </h1>
            <p class="text-xs sm:text-sm text-on-surface-variant font-medium">
                {{ $module->judul }}
            </p>
        </div>
    </div>

    {{-- Form Ajukan Pertanyaan Baru --}}
    <form method="POST" action="{{ route('portal.modules.discuss.store', $module) }}"
        class="mb-6 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label for="isi" class="mb-1.5 block text-sm font-extrabold text-on-surface">Ajukan Pertanyaan Baru</label>
            <p class="text-xs text-on-surface-variant mb-3">Tanyakan hal yang belum kamu pahami seputar modul ini kepada sesama warga dan pengelola.</p>
            <textarea name="isi" id="isi" rows="3" required
                class="w-full rounded-xl border-outline-variant bg-surface-container-low p-4 text-sm text-on-surface focus:border-primary focus:ring-primary focus:bg-surface-container-lowest transition-all @error('isi') border-rose-500 @enderror"
                placeholder="Tuliskan pertanyaan atau topik diskusi kamu di sini...">{{ old('isi') }}</textarea>
            @error('isi')
                <p class="mt-1.5 text-xs font-bold text-rose-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-xs sm:text-sm font-extrabold text-on-primary shadow-xs hover:bg-surface-tint transition-all cursor-pointer">
                <x-heroicon-s-paper-airplane class="h-4 w-4" />
                <span>Kirim Pertanyaan</span>
            </button>
        </div>
    </form>

    {{-- Daftar Thread Pertanyaan --}}
    <div class="space-y-4">
        <h2 class="text-base font-extrabold text-on-surface flex items-center gap-2">
            <x-heroicon-s-chat-bubble-oval-left-ellipsis class="h-5 w-5 text-primary" />
            <span>Daftar Pertanyaan Warga ({{ $threads->total() }})</span>
        </h2>

        @if($threads->isEmpty())
            <div class="rounded-2xl border border-dashed border-outline-variant bg-surface-container-lowest p-12 text-center space-y-3">
                <x-heroicon-o-chat-bubble-left-right class="mx-auto h-12 w-12 text-outline" />
                <h3 class="text-base font-extrabold text-on-surface">Belum Ada Diskusi</h3>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">Jadilah warga pertama yang mengajukan pertanyaan seputar modul ini!</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($threads as $thread)
                    <a href="{{ route('portal.modules.discuss.show', [$module, $thread]) }}"
                        class="group block rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-2xs transition-all hover:border-primary/40 hover:bg-surface-container-low">
                        <div class="flex items-start gap-3.5">
                            <x-portal.avatar :name="$thread->user?->name" :src="$thread->user?->avatarUrl()" size="md" />
                            
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors">
                                        {{ $thread->user?->name ?? 'Warga' }}
                                    </span>

                                    @if($thread->is_pinned)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-600 dark:text-amber-400">
                                            <x-heroicon-s-bookmark class="h-3 w-3" />
                                            <span>Disematkan</span>
                                        </span>
                                    @endif

                                    <span class="text-[11px] text-on-surface-variant">
                                        • {{ $thread->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="line-clamp-2 text-sm text-on-surface leading-relaxed">
                                    {{ $thread->isi }}
                                </p>

                                <div class="pt-1 flex items-center gap-3 text-xs font-bold text-primary">
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-s-chat-bubble-oval-left class="h-3.5 w-3.5" />
                                        <span>{{ $thread->replies_count }} Balasan</span>
                                    </span>
                                </div>
                            </div>

                            <x-heroicon-s-chevron-right class="h-5 w-5 shrink-0 text-outline-variant group-hover:text-primary group-hover:translate-x-0.5 transition-all mt-1" />
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $threads->links() }}</div>
        @endif
    </div>
@endsection
