<div>

    {{-- ══════════ HASIL ══════════ --}}
    @if($submitted)
        @php
            $resultMap = [
                'passed' => ['ring' => 'border-sdg-3/30', 'bg' => 'bg-sdg-3/10', 'fg' => 'text-sdg-3', 'icon' => 'heroicon-s-check-circle', 'title' => 'Selamat, Kamu Lulus!', 'titleColor' => 'text-sdg-3'],
                'failed' => ['ring' => 'border-error/30', 'bg' => 'bg-error-container', 'fg' => 'text-error', 'icon' => 'heroicon-s-x-circle', 'title' => 'Belum Lulus', 'titleColor' => 'text-error'],
            ];
            $r = $resultMap[$resultStatus] ?? $resultMap['failed'];
        @endphp

        <div class="flex flex-col items-center justify-center rounded-2xl border bg-surface-container-lowest px-6 py-14 text-center shadow-sm {{ $r['ring'] }}">
            <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-full {{ $r['bg'] }}">
                <x-dynamic-component :component="$r['icon']" class="h-12 w-12 {{ $r['fg'] }}" />
            </div>
            <h2 class="text-2xl font-bold {{ $r['titleColor'] }}">{{ $r['title'] }}</h2>

            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-on-surface-variant">Nilai kamu</p>
            <p class="text-4xl font-bold {{ $r['fg'] }}">{{ $resultScore }}</p>
            <p class="mt-1 text-sm text-on-surface-variant">Nilai minimum lulus: {{ $quiz->nilai_lulus }}</p>
            @if($resultStatus === 'failed')
                <p class="mt-2 text-sm text-on-surface-variant">Pelajari kembali materi lalu coba lagi.</p>
            @endif

            <a href="{{ route('portal.modules.show', $module) }}"
                class="mt-7 inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest px-6 py-3 text-sm font-bold text-on-surface-variant shadow-sm transition-colors hover:bg-surface-container-low">
                <x-heroicon-o-arrow-left class="h-5 w-5" />
                Kembali ke Modul
            </a>
        </div>

    {{-- ══════════ FORM KUIS ══════════ --}}
    @else
        @php
            $totalQ = $this->questions->count();
            $answeredQ = $this->questions->filter(fn ($q) => ! empty($answers[$q->id]))->count();
            $answeredPct = $totalQ > 0 ? (int) ($answeredQ / $totalQ * 100) : 0;
        @endphp

        {{-- Header + progress --}}
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm sm:p-6">
            <h1 class="text-lg font-bold text-on-surface sm:text-xl">{{ $quiz->title }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-on-surface-variant">
                <span class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-clipboard-document-list class="h-4 w-4" /> {{ $totalQ }} soal
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-check-badge class="h-4 w-4" /> Nilai lulus {{ $quiz->nilai_lulus }}
                </span>
                @if($quiz->maks_percobaan > 0)
                    <span class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-arrow-path class="h-4 w-4" /> Maks. {{ $quiz->maks_percobaan }}x
                    </span>
                @endif
            </div>

            <div class="mt-4">
                <div class="mb-1.5 flex items-center justify-between text-xs">
                    <span class="font-medium text-on-surface-variant">{{ $answeredQ }} dari {{ $totalQ }} soal terjawab</span>
                    <span class="font-bold text-primary">{{ $answeredPct }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-surface-container-high">
                    <div class="h-full rounded-full bg-primary transition-all duration-300" style="width: {{ $answeredPct }}%"></div>
                </div>
            </div>
        </div>

        {{-- Error banner --}}
        @if(! empty($quizErrors))
            <div x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                class="mt-5 overflow-hidden rounded-xl border border-error/30 bg-error-container">
                <div class="flex items-center gap-2 border-b border-error/20 px-4 py-3 text-sm font-semibold text-on-error-container">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 shrink-0" />
                    Ada soal yang belum dijawab
                </div>
                <ul class="px-4 py-2 text-sm text-on-error-container">
                    @foreach($quizErrors as $error)
                        <li class="py-0.5">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Questions --}}
        <div class="mt-5 space-y-4">
            @foreach($this->questions as $question)
                @php
                    $isUnanswered = ! empty($quizErrors) && empty($answers[$question->id]);
                    $isMulti = $question->options->where('is_correct', true)->count() > 1;
                @endphp
                <div class="overflow-hidden rounded-2xl border bg-surface-container-lowest shadow-sm {{ $isUnanswered ? 'border-error ring-1 ring-error/20' : 'border-outline-variant' }}">

                    {{-- Question header --}}
                    <div class="flex items-start gap-3 border-b border-outline-variant px-5 py-4 sm:px-6">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-on-primary">
                            {{ $loop->iteration }}
                        </span>
                        <div class="flex-1">
                            <p class="text-[15px] font-semibold leading-relaxed text-on-surface">{{ $question->pertanyaan }}</p>
                            @if($isMulti)
                                <p class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-primary">
                                    <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                                    Pilih semua jawaban yang benar
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Answer area --}}
                    <div class="px-5 py-4 sm:px-6 sm:py-5">
                        <div class="space-y-2.5">
                            @foreach($question->options as $option)
                                @php
                                    $sel = $answers[$question->id] ?? null;
                                    $isSelected = $isMulti
                                        ? in_array($option->id, (array) $sel)
                                        : ($sel !== null && $sel == $option->id);
                                @endphp
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-all
                                    {{ $isSelected ? 'border-primary bg-primary/5 ring-1 ring-primary/20' : 'border-outline-variant hover:border-outline hover:bg-surface-container-low' }}">
                                    <input type="{{ $isMulti ? 'checkbox' : 'radio' }}" wire:model.live="answers.{{ $question->id }}" value="{{ $option->id }}"
                                        class="h-5 w-5 shrink-0 accent-primary {{ $isMulti ? 'rounded' : '' }}">
                                    <span class="text-sm leading-relaxed text-on-surface">{{ $option->teks_opsi }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Submit --}}
        <div class="mt-5 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm sm:p-6">
            <p class="mb-4 text-sm leading-relaxed text-on-surface-variant">
                Pastikan semua soal sudah dijawab. <strong class="text-on-surface">Jawaban tidak dapat diubah setelah dikumpulkan.</strong>
            </p>
            <x-portal.confirm-dialog
                title="Kumpulkan jawaban?"
                message="Pastikan semua soal sudah dijawab. Jawaban tidak dapat diubah setelah dikumpulkan."
                confirm-label="Ya, Kumpulkan"
                cancel-label="Periksa Lagi"
                icon="heroicon-o-paper-airplane"
                on-confirm="$wire.submit()"
                wire:loading.attr="disabled"
                wire:target="submit"
                trigger-class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint disabled:opacity-60">
                <span wire:loading.remove wire:target="submit" class="flex items-center gap-2.5">
                    <x-heroicon-o-paper-airplane class="h-5 w-5" />
                    Kumpulkan Jawaban
                </span>
                <span wire:loading wire:target="submit" class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Memproses...
                </span>
            </x-portal.confirm-dialog>
        </div>
    @endif
</div>
