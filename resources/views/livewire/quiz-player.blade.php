<div>

    {{-- ══════════ HASIL ══════════ --}}
    @if($submitted)
        @php
            $resultMap = [
                'passed' => ['ring' => 'border-emerald-200', 'bg' => 'bg-emerald-100', 'fg' => 'text-emerald-600', 'icon' => 'heroicon-s-check-circle', 'title' => 'Selamat, Kamu Lulus!', 'titleColor' => 'text-emerald-700'],
                'failed' => ['ring' => 'border-red-200', 'bg' => 'bg-red-100', 'fg' => 'text-red-500', 'icon' => 'heroicon-s-x-circle', 'title' => 'Belum Lulus', 'titleColor' => 'text-red-600'],
            ];
            $r = $resultMap[$resultStatus] ?? $resultMap['failed'];
        @endphp

        <div class="flex flex-col items-center justify-center rounded-2xl border bg-white px-6 py-14 text-center shadow-sm {{ $r['ring'] }}">
            <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-full {{ $r['bg'] }}">
                <x-dynamic-component :component="$r['icon']" class="h-12 w-12 {{ $r['fg'] }}" />
            </div>
            <h2 class="text-2xl font-bold {{ $r['titleColor'] }}">{{ $r['title'] }}</h2>

            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-gray-400">Nilai kamu</p>
            <p class="text-4xl font-bold {{ $r['fg'] }}">{{ $resultScore }}</p>
            <p class="mt-1 text-sm text-gray-400">Nilai minimum lulus: {{ $quiz->passing_score }}</p>
            @if($resultStatus === 'failed')
                <p class="mt-2 text-sm text-gray-500">Pelajari kembali materi lalu coba lagi.</p>
            @endif

            <a href="{{ route('portal.modules.show', $module) }}"
                class="mt-7 inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-50">
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
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h1 class="text-lg font-bold text-gray-900 sm:text-xl">{{ $quiz->title }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-gray-500">
                <span class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-clipboard-document-list class="h-4 w-4" /> {{ $totalQ }} soal
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-check-badge class="h-4 w-4" /> Nilai lulus {{ $quiz->passing_score }}
                </span>
                @if($quiz->max_attempts > 0)
                    <span class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-arrow-path class="h-4 w-4" /> Maks. {{ $quiz->max_attempts }}x
                    </span>
                @endif
            </div>

            <div class="mt-4">
                <div class="mb-1.5 flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-500">{{ $answeredQ }} dari {{ $totalQ }} soal terjawab</span>
                    <span class="font-bold text-indigo-600">{{ $answeredPct }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-indigo-500 transition-all duration-300" style="width: {{ $answeredPct }}%"></div>
                </div>
            </div>
        </div>

        {{-- Error banner --}}
        @if(! empty($quizErrors))
            <div x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                class="mt-5 overflow-hidden rounded-xl border border-red-200 bg-red-50">
                <div class="flex items-center gap-2 border-b border-red-100 px-4 py-3 text-sm font-semibold text-red-700">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 shrink-0" />
                    Ada soal yang belum dijawab
                </div>
                <ul class="px-4 py-2 text-sm text-red-600">
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
                @endphp
                <div class="overflow-hidden rounded-2xl border bg-white shadow-sm {{ $isUnanswered ? 'border-red-300 ring-1 ring-red-100' : 'border-gray-200' }}">

                    {{-- Question header --}}
                    <div class="flex items-start gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">
                            {{ $loop->iteration }}
                        </span>
                        <div class="flex-1">
                            <p class="text-[15px] font-semibold leading-relaxed text-gray-900">{{ $question->question }}</p>
                        </div>
                    </div>

                    {{-- Answer area --}}
                    <div class="px-5 py-4 sm:px-6 sm:py-5">
                        <div class="space-y-2.5">
                            @foreach($question->options as $option)
                                @php $isSelected = isset($answers[$question->id]) && $answers[$question->id] == $option->id; @endphp
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-all
                                    {{ $isSelected ? 'border-indigo-400 bg-indigo-50 ring-1 ring-indigo-200' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}">
                                    <input type="radio" wire:model.live="answers.{{ $question->id }}" value="{{ $option->id }}"
                                        class="h-5 w-5 shrink-0 accent-indigo-600">
                                    <span class="text-sm leading-relaxed text-gray-800">{{ $option->option_text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Submit --}}
        <div class="mt-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="mb-4 text-sm leading-relaxed text-gray-500">
                Pastikan semua soal sudah dijawab. <strong class="text-gray-700">Jawaban tidak dapat diubah setelah dikumpulkan.</strong>
            </p>
            <button wire:click="submit"
                wire:confirm="Yakin ingin mengumpulkan jawaban? Tindakan ini tidak dapat dibatalkan."
                wire:loading.attr="disabled"
                class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:opacity-60">
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
            </button>
        </div>
    @endif
</div>
