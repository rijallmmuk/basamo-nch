@extends('portal.layouts.app')

@section('title', 'Detail Pertanyaan - ' . $module->judul)

@php
    // Helper function untuk format text mention @Nama menjadi badge tag
    $formatContent = function(string $text): string {
        $escaped = e($text);
        // Match @NamaUser atau @Nama User di awal atau di dalam teks
        return preg_replace(
            '/@([A-Za-z0-9_\s\-]{2,30})(?=\s|[.,!?]|$)/u',
            '<span class="inline-flex items-center gap-1 rounded-md bg-primary/10 px-2 py-0.5 text-xs font-extrabold text-primary">@$1</span>',
            $escaped
        );
    };
@endphp

@section('content')
    {{-- Back Link --}}
    <div class="mb-4">
        <a href="{{ route('portal.modules.discuss', $module) }}"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
            <x-heroicon-s-arrow-left class="h-4 w-4" />
            <span>Kembali ke Ruang Diskusi</span>
        </a>
    </div>

    {{-- Interactive Reply Wrapper (Alpine.js) --}}
    <div x-data="{ 
        replyToName: '',
        setReply(name) {
            this.replyToName = name;
            const input = this.$refs.replyInput;
            const tag = '@' + name + ' ';
            if (!input.value.startsWith(tag)) {
                input.value = tag + input.value.replace(/^@[A-Za-z0-9_\s\-]{2,30}\s?/, '');
            }
            this.$refs.replyForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            input.focus();
        },
        cancelReply() {
            this.replyToName = '';
            const input = this.$refs.replyInput;
            input.value = input.value.replace(/^@[A-Za-z0-9_\s\-]{2,30}\s?/, '');
        }
    }" class="space-y-6 max-w-4xl mx-auto">
        
        {{-- Pertanyaan Utama (Main Question Card) --}}
        <article class="overflow-hidden rounded-2xl border border-primary/30 bg-primary/5 p-6 shadow-sm space-y-4">
            <div class="flex items-start gap-3.5">
                <x-portal.avatar :name="$discussion->user?->name" :src="$discussion->user?->avatarUrl()" size="md" />
                
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-extrabold text-on-surface">
                                {{ $discussion->user?->name ?? 'Warga' }}
                            </span>
                            <span class="text-xs text-on-surface-variant">
                                • {{ $discussion->created_at->diffForHumans() }}
                            </span>
                        </div>

                        {{-- Button Balas di Pertanyaan Utama --}}
                        <button type="button" 
                            @click="setReply(@js($discussion->user?->name ?? 'Warga'))"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-1.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors cursor-pointer">
                            <x-heroicon-s-arrow-uturn-left class="h-3.5 w-3.5 text-primary" />
                            <span>Balas</span>
                        </button>
                    </div>

                    <div class="whitespace-pre-line text-sm sm:text-base text-on-surface leading-relaxed pt-1">
                        {!! $formatContent($discussion->isi) !!}
                    </div>
                </div>
            </div>
        </article>

        {{-- Seksi Balasan --}}
        <section class="space-y-4">
            <h2 class="text-base font-extrabold text-on-surface flex items-center gap-2">
                <x-heroicon-s-chat-bubble-left-right class="h-5 w-5 text-primary" />
                <span>Balasan ({{ $replies->total() }})</span>
            </h2>

            @if($replies->isEmpty())
                <div class="rounded-2xl border border-dashed border-outline-variant bg-surface-container-lowest p-8 text-center text-xs text-on-surface-variant">
                    Belum ada balasan. Bantu jawab pertanyaan warga ini di bawah!
                </div>
            @else
                <div class="space-y-3">
                    @foreach($replies as $reply)
                        <article class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-2xs space-y-2">
                            <div class="flex items-start gap-3">
                                <x-portal.avatar :name="$reply->user?->name" :src="$reply->user?->avatarUrl()" size="sm" />
                                
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-bold text-on-surface">
                                                {{ $reply->user?->name ?? 'Warga' }}
                                            </span>
                                            <span class="text-[11px] text-on-surface-variant">
                                                • {{ $reply->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        {{-- Button Balas pada setiap Balasan (Social Media Tagging) --}}
                                        <button type="button" 
                                            @click="setReply(@js($reply->user?->name ?? 'Warga'))"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline cursor-pointer">
                                            <x-heroicon-s-arrow-uturn-left class="h-3.5 w-3.5" />
                                            <span>Balas</span>
                                        </button>
                                    </div>

                                    <div class="whitespace-pre-line text-xs sm:text-sm text-on-surface leading-relaxed">
                                        {!! $formatContent($reply->isi) !!}
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4">{{ $replies->links() }}</div>
            @endif
        </section>

        {{-- Form Balasan dengan Auto-Tag --}}
        <form x-ref="replyForm" 
            id="form-balasan" 
            method="POST" 
            action="{{ route('portal.modules.discuss.reply', [$module, $discussion]) }}"
            class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm space-y-4">
            @csrf
            
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="isi" class="text-sm font-extrabold text-on-surface">Tulis Balasan</label>
                    
                    {{-- Active Reply Tag Badge --}}
                    <template x-if="replyToName">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-extrabold text-primary">
                            <span>Membalas: <strong x-text="'@' + replyToName"></strong></span>
                            <button type="button" @click="cancelReply()" class="ml-1 text-rose-500 hover:text-rose-700 font-bold" title="Batalkan Tag">✕</button>
                        </div>
                    </template>
                </div>

                <textarea x-ref="replyInput" name="isi" id="isi" rows="3" required
                    class="w-full rounded-xl border-outline-variant bg-surface-container-low p-4 text-sm text-on-surface focus:border-primary focus:ring-primary focus:bg-surface-container-lowest transition-all @error('isi') border-rose-500 @enderror"
                    placeholder="Tuliskan balasan kamu di sini...">{{ old('isi') }}</textarea>
                @error('isi')
                    <p class="mt-1.5 text-xs font-bold text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-xs sm:text-sm font-extrabold text-on-primary shadow-xs hover:bg-surface-tint transition-all cursor-pointer">
                    <x-heroicon-s-paper-airplane class="h-4 w-4" />
                    <span>Kirim Balasan</span>
                </button>
            </div>
        </form>

    </div>
@endsection
