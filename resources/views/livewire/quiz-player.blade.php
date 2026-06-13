<div>
    {{-- ============= HASIL SUBMIT ============= --}}
    @if($submitted)
        <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
            @if($resultStatus === 'passed')
                <div class="text-5xl mb-3">🎉</div>
                <h2 class="text-xl font-bold text-green-700">Selamat, Anda Lulus!</h2>
                <p class="text-gray-500 mt-2">Nilai Anda: <span class="font-bold text-green-600">{{ $resultScore }}%</span></p>
            @elseif($resultStatus === 'failed')
                <div class="text-5xl mb-3">😔</div>
                <h2 class="text-xl font-bold text-red-600">Belum Lulus</h2>
                <p class="text-gray-500 mt-2">Nilai Anda: <span class="font-bold text-red-500">{{ $resultScore }}%</span></p>
                <p class="text-sm text-gray-400 mt-1">Nilai minimum: {{ $quiz->passing_score }}%</p>
            @elseif($resultStatus === 'pending_review')
                <div class="text-5xl mb-3">⏳</div>
                <h2 class="text-xl font-bold text-amber-600">Kuis Dikirim!</h2>
                <p class="text-gray-500 mt-2 text-sm max-w-sm mx-auto">
                    Jawaban essay Anda sedang menunggu penilaian dari Admin Nagari.
                    Hasil akan diumumkan setelah review selesai.
                </p>
            @endif

            <div class="mt-6">
                <a href="{{ url()->previous() }}"
                    class="inline-block px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Kembali ke Modul
                </a>
            </div>
        </div>

    {{-- ============= FORM KUIS ============= --}}
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-100">
                <h1 class="font-bold text-gray-900">{{ $quiz->title }}</h1>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $this->questions->count() }} soal ·
                    Nilai lulus {{ $quiz->passing_score }}%
                    @if($quiz->max_attempts > 0) · Maks. {{ $quiz->max_attempts }}x percobaan @endif
                </p>
            </div>
        </div>

        {{-- Error messages --}}
        @if(!empty($errors))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                @foreach($errors as $error)
                    <p class="text-sm text-red-600">⚠️ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Questions --}}
        <div class="space-y-4">
            @foreach($this->questions as $question)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="shrink-0 w-7 h-7 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">
                            {{ $loop->iteration }}
                        </span>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800 leading-relaxed">{{ $question->question }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                @if($question->type === 'essay')
                                    <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">✍️ Essay</span>
                                @else
                                    <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">🔘 Pilihan Ganda</span>
                                @endif
                                <span class="text-xs text-gray-400">{{ $question->points }} poin</span>
                            </div>
                        </div>
                    </div>

                    {{-- Multiple choice --}}
                    @if($question->type === 'multiple_choice')
                        <div class="space-y-2 pl-10">
                            @foreach($question->options as $option)
                                <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                                    {{ isset($answers[$question->id]) && $answers[$question->id] == $option->id
                                        ? 'border-indigo-400 bg-indigo-50'
                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}">
                                    <input
                                        type="radio"
                                        wire:model="answers.{{ $question->id }}"
                                        value="{{ $option->id }}"
                                        class="mt-0.5 accent-indigo-600">
                                    <span class="text-sm text-gray-700">{{ $option->option_text }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    {{-- Essay --}}
                    @if($question->type === 'essay')
                        <div class="pl-10">
                            <textarea
                                wire:model.live="answers.{{ $question->id }}"
                                rows="4"
                                placeholder="Tulis jawaban Anda di sini..."
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 outline-none focus:ring-2 focus:ring-indigo-500 resize-none">
                            </textarea>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Submit --}}
        <div class="mt-6">
            <button
                wire:click="submit"
                wire:confirm="Yakin ingin mengumpulkan jawaban? Jawaban tidak bisa diubah setelah dikumpulkan."
                wire:loading.attr="disabled"
                class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium rounded-xl transition-colors">
                <span wire:loading.remove>📤 Kumpulkan Jawaban</span>
                <span wire:loading>Memproses...</span>
            </button>
        </div>
    @endif
</div>
