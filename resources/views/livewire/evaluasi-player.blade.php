<div>

    {{-- ═════════════════════════════════════════════════════════════════════ --}}
    {{-- ── 1. HASIL KUIS (SUBMITTED RESULT VIEW) ─────────────────────────── --}}
    {{-- ═════════════════════════════════════════════════════════════════════ --}}
    @if($submitted)
        @php
            $isPretest = $evaluasi->isPretest();
            $isPassed = $isPretest || $resultStatus === 'passed';
            $attemptsLeft = $evaluasi->sisaPercobaan(auth()->user());
        @endphp

        <div class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
            
            {{-- Result Banner Header --}}
            <div class="p-8 sm:p-12 text-center space-y-6 {{ $isPassed ? 'bg-emerald-500/10 border-b border-emerald-500/20' : 'bg-rose-500/10 border-b border-rose-500/20' }}">
                
                {{-- Icon Badge --}}
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full shadow-xs {{ $isPassed ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">
                    @if($isPassed)
                        <x-heroicon-s-trophy class="h-10 w-10" />
                    @else
                        <x-heroicon-s-x-circle class="h-10 w-10" />
                    @endif
                </div>

                {{-- Status Text --}}
                <div class="space-y-1">
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight {{ $isPassed ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        @if($evaluasi->isPretest())
                            Pre-test Selesai
                        @else
                            {{ $isPassed ? 'Selamat, Anda Lulus Evaluasi!' : 'Belum Lulus Evaluasi' }}
                        @endif
                    </h1>
                    <p class="text-xs sm:text-sm text-on-surface-variant font-medium">
                        @if($isPretest)
                            Jawaban awal Anda sudah tercatat. Hasil ini tidak menentukan kelulusan.
                        @else
                            {{ $isPassed ? 'Pemahaman Anda tentang modul ini sangat baik!' : 'Jangan berkecil hati, Anda dapat mempelajari kembali materi modul ini.' }}
                        @endif
                    </p>
                </div>

                {{-- Score Showcase Box --}}
                <div class="mx-auto inline-flex flex-col items-center justify-center rounded-2xl bg-surface-container-lowest p-6 border border-outline-variant shadow-xs min-w-[200px]">
                    <span class="text-xs font-extrabold uppercase tracking-wide text-on-surface-variant">{{ $isPretest ? 'Nilai Awal Anda' : 'Nilai Akhir Anda' }}</span>
                    <span class="text-5xl font-black my-1 {{ $isPassed ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $resultScore }}
                    </span>
                    @unless($isPretest)
                        <span class="text-xs font-bold text-on-surface-variant">
                            Batas Kelulusan: <strong class="text-on-surface">{{ $evaluasi->nilai_lulus }}</strong>/100
                        </span>
                    @endunless
                </div>
            </div>

            {{-- Actions Footer --}}
            <div class="p-6 sm:p-8 bg-surface-container-low/40 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('portal.modules.show', $module) }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-extrabold text-on-primary shadow-xs hover:bg-surface-tint transition-all">
                    <x-heroicon-s-arrow-left class="h-5 w-5" />
                    <span>Kembali ke Detail Modul</span>
                </a>

                <a href="{{ route('portal.modules.discuss', $module) }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest px-5 py-3.5 text-sm font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                    <x-heroicon-s-chat-bubble-left-right class="h-5 w-5 text-primary" />
                    <span>Diskusi Modul</span>
                </a>
            </div>

        </div>

    {{-- ═════════════════════════════════════════════════════════════════════ --}}
    {{-- ── 2. LEMBAR PENGERJAAN KUIS (QUIZ PLAYER FORM) ──────────────────── --}}
    {{-- ═════════════════════════════════════════════════════════════════════ --}}
    @else
        @php
            $totalQ = $this->pertanyaans->count();
            $answeredQ = $this->pertanyaans->filter(fn ($q) => ! empty($answers[$q->id]))->count();
            $answeredPct = $totalQ > 0 ? (int) round(($answeredQ / $totalQ) * 100) : 0;
        @endphp

        <div class="space-y-6">
            
            {{-- ── QUIZ HEADER & PROGRESS CARD ────────────────────────────────── --}}
            <header class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-outline-variant pb-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-0.5 text-xs font-extrabold text-primary mb-1">
                            <x-heroicon-s-academic-cap class="h-3.5 w-3.5" />
                            <span>{{ $module->judul }}</span>
                        </span>
                        <h1 class="text-xl font-black text-on-surface sm:text-2xl tracking-tight">
                            {{ $evaluasi->title }}
                        </h1>
                    </div>

                    {{-- Metadata Badges --}}
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1 text-xs font-bold text-on-surface-variant">
                            <x-heroicon-o-clipboard-document-list class="h-4 w-4 text-primary" />
                            <span>{{ $totalQ }} Soal</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1 text-xs font-bold text-on-surface-variant">
                            <x-heroicon-o-check-badge class="h-4 w-4 text-emerald-600" />
                            <span>Nilai Lulus: {{ $evaluasi->nilai_lulus }}</span>
                        </span>
                        @if($evaluasi->maks_percobaan > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1 text-xs font-bold text-on-surface-variant">
                                <x-heroicon-o-arrow-path class="h-4 w-4 text-primary" />
                                <span>Maks. {{ $evaluasi->maks_percobaan }}x Percobaan</span>
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Quick Question Grid Jump Buttons --}}
                <div>
                    <p class="text-[11px] font-bold text-on-surface-variant mb-2">Navigasi Soal Quick-Jump:</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($this->pertanyaans as $qIndex => $qItem)
                            @php
                                $isAnswered = ! empty($answers[$qItem->id]);
                            @endphp
                            <a href="#soal-{{ $qItem->id }}" 
                               class="flex h-8 w-8 items-center justify-center rounded-xl text-xs font-extrabold transition-all border
                                   {{ $isAnswered 
                                       ? 'bg-primary text-on-primary border-primary shadow-2xs' 
                                       : 'bg-surface-container-low text-on-surface-variant border-outline-variant hover:bg-surface-container-high' }}">
                                {{ $loop->iteration }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </header>

            {{-- ── ERROR ALERT BANNER ────────────────────────────────────────── --}}
            @if(! empty($evaluasiErrors))
                <div x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                    class="overflow-hidden rounded-2xl border border-rose-500/30 bg-rose-500/10 p-5 shadow-xs space-y-2">
                    <div class="flex items-center gap-2 text-sm font-extrabold text-rose-600 dark:text-rose-400">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 shrink-0" />
                        <span>Mohon Lengkapi Semua Jawaban Soal</span>
                    </div>
                    <ul class="text-xs font-semibold text-rose-700 dark:text-rose-300 list-disc list-inside space-y-1 pl-1">
                        @foreach($evaluasiErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── QUESTIONS LIST ────────────────────────────────────────────── --}}
            <div class="space-y-6">
                @foreach($this->pertanyaans as $question)
                    @php
                        $isUnanswered = ! empty($evaluasiErrors) && empty($answers[$question->id]);
                        $isMulti = $question->opsis->where('is_correct', true)->count() > 1;
                        $sel = $answers[$question->id] ?? null;
                    @endphp

                    <article id="soal-{{ $question->id }}" 
                        class="overflow-hidden rounded-2xl border bg-surface-container-lowest shadow-sm transition-all
                            {{ $isUnanswered ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-outline-variant' }}">

                        {{-- Question Title Header --}}
                        <div class="flex items-start gap-4 border-b border-outline-variant p-5 sm:p-6 bg-surface-container-low/30">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-sm font-black text-on-primary shadow-2xs">
                                {{ $loop->iteration }}
                            </span>
                            <div class="space-y-1 min-w-0 flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-on-surface leading-snug">
                                    {{ $question->pertanyaan }}
                                </h3>
                                @if($isMulti)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-sky-500/10 px-2.5 py-0.5 text-xs font-extrabold text-sky-600 dark:text-sky-400">
                                        <x-heroicon-s-check-circle class="h-3.5 w-3.5" />
                                        <span>Pilih semua jawaban yang benar (Ganda)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-surface-container-high px-2.5 py-0.5 text-[11px] font-semibold text-on-surface-variant">
                                        Pilih satu jawaban terbaik
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Options List --}}
                        <div class="p-5 sm:p-6 space-y-3">
                            @foreach($question->opsis as $optIdx => $option)
                                @php
                                    $optionLetter = chr(65 + $optIdx);
                                    $isSelected = $isMulti
                                        ? in_array($option->id, (array) $sel)
                                        : ($sel !== null && $sel == $option->id);
                                @endphp

                                <label class="group flex cursor-pointer items-start gap-3.5 rounded-xl border p-4 transition-all select-none
                                    {{ $isSelected 
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/20 shadow-2xs' 
                                        : 'border-outline-variant bg-surface-container-lowest hover:border-primary/50 hover:bg-surface-container-low' }}">
                                    
                                    {{-- Radio/Checkbox Input --}}
                                    <input type="{{ $isMulti ? 'checkbox' : 'radio' }}" 
                                        wire:model.live="answers.{{ $question->id }}" 
                                        value="{{ $option->id }}"
                                        class="mt-0.5 h-5 w-5 shrink-0 accent-primary cursor-pointer {{ $isMulti ? 'rounded-md' : '' }}">

                                    {{-- Option Letter Badge --}}
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-extrabold transition-colors mt-0.5
                                        {{ $isSelected ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant group-hover:bg-primary/10 group-hover:text-primary' }}">
                                        {{ $optionLetter }}
                                    </span>

                                    {{-- Option Text --}}
                                    <span class="text-sm font-semibold leading-relaxed text-on-surface flex-1 pt-0.5">
                                        {{ $option->teks_opsi }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                    </article>
                @endforeach
            </div>

            {{-- ── SUBMIT FOOTER CARD ────────────────────────────────────────── --}}
            <footer class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                    <x-heroicon-s-information-circle class="h-5 w-5 text-primary shrink-0" />
                    <span>
                        {{ $isPreview
                            ? 'Mode pratinjau: pilihan dapat dicoba, tetapi jawaban dan nilai tidak disimpan.'
                            : "Pastikan seluruh {$totalQ} soal telah Anda jawab. Jawaban tidak dapat diubah setelah dikumpulkan." }}
                    </span>
                </div>

                @if($isPreview)
                    <button type="button" disabled
                        class="flex w-full cursor-not-allowed items-center justify-center gap-2.5 rounded-xl bg-surface-container-high px-6 py-4 text-sm font-extrabold text-on-surface-variant opacity-80">
                        <x-heroicon-s-eye class="h-5 w-5" />
                        <span>Pratinjau Saja, Jawaban Tidak Disimpan</span>
                    </button>
                @else
                    <x-portal.confirm-dialog
                        title="Kumpulkan Jawaban Evaluasi?"
                        message="Apakah Anda yakin ingin mengumpulkan seluruh jawaban sekarang?"
                        confirm-label="Ya, Kumpulkan Sekarang"
                        cancel-label="Periksa Kembali"
                        icon="heroicon-o-paper-airplane"
                        on-confirm="$wire.submit()"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        trigger-class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-primary px-6 py-4 text-sm font-extrabold text-on-primary shadow-sm transition-all hover:bg-surface-tint disabled:opacity-60 cursor-pointer">

                        <span wire:loading.remove wire:target="submit" class="flex items-center gap-2">
                            <x-heroicon-s-paper-airplane class="h-5 w-5" />
                            <span>Kumpulkan Seluruh Jawaban</span>
                        </span>
                        <span wire:loading wire:target="submit" class="flex items-center gap-2">
                            <x-portal.spinner class="h-5 w-5" />
                            <span>Memproses Penilaian...</span>
                        </span>
                    </x-portal.confirm-dialog>
                @endif
            </footer>

        </div>
    @endif
</div>
