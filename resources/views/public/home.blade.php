@extends('public.layouts.app')

@section('title', 'Nagari Creative Hub & Smart Learning Center')
@section('main-class', 'w-full')

@section('content')
<x-public.ecosystem-hero />

{{-- Beranda hanya memberi pintasan ringkas. Seluruh data publik dihimpun di Teras
     Nagari agar tidak tersebar sebagai halaman statistik tambahan. --}}
<section class="relative overflow-hidden border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading eyebrow="Ringkasan Ekosistem" title="BASAMO NCH dalam angka." description="Gambaran singkat seluruh nagari mitra dan layanan yang tersedia." />
            <a href="{{ route('public.teras') }}" class="group inline-flex shrink-0 items-center gap-2 rounded-full bg-primary px-6 py-3.5 text-sm font-extrabold text-on-primary shadow-lg transition-all hover:-translate-y-1 hover:shadow-xl">
                Buka Teras Nagari <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
            </a>
        </div>
        <x-public.stat-grid class="mt-10" :cols="count($metrics)">
            @foreach($metrics as $metric)
                <x-public.stat-card :label="$metric['label']" :value="$metric['value']" :icon="$metric['icon']" :description="$metric['description']" />
            @endforeach
        </x-public.stat-grid>
    </div>
</section>

{{-- ══ PEMISAH MINANG ══ --}}
<div class="minang-divider" aria-hidden="true"></div>

{{-- ══ NAGARI MITRA — daftar situs tenant aktif ══ --}}
@if($mitraNagari->isNotEmpty())
<section id="mitra" class="relative overflow-hidden bg-background py-section-gap">
    <div class="gonjong-peak absolute right-0 top-0 h-64 w-full -translate-y-32 transform bg-primary/5" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="mb-12 flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading
                eyebrow="Jaringan Mitra"
                title="Nagari yang sudah punya wajah digital."
                description="Setiap nagari mitra memiliki situs resminya sendiri. Kunjungi untuk melihat data Teras, katalog belajar, budaya, dan Lapau UMKM-nya."
            />
            <a href="{{ route('public.teras') }}" class="inline-flex shrink-0 items-center gap-2 font-bold uppercase tracking-wider text-primary transition-all hover:gap-4">
                Lihat di peta <x-heroicon-o-arrow-right class="h-5 w-5" />
            </a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($mitraNagari as $mitra)
                <x-public.partner-nagari-card :nagari="$mitra" />
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Peta utama beserta seluruh data nagari berada di Pilar Teras Nagari. --}}
@include('public.partials.collaboration')

{{-- ══ FAQ — daftar accordion (native <details>, tanpa JS). Isi dikelola superadmin
     lewat panel /panel/faqs — sinkron langsung, tanpa cache. ══ --}}
@if($faqs->isNotEmpty())
<section id="faq" class="relative overflow-hidden border-t border-outline-variant bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            class="mx-auto mb-12"
            align="center"
            eyebrow="FAQ"
            title="Pertanyaan Umum"
            description="Jawaban singkat seputar BASAMO NCH, akses warga, dan kemitraan nagari."
        />
        <div class="mx-auto max-w-5xl divide-y divide-outline-variant overflow-hidden rounded-3xl border border-outline-variant bg-surface-container-lowest shadow-sm">
            @foreach($faqs as $faq)
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 text-left font-bold text-primary transition-colors hover:bg-surface-container-low group-open:bg-surface-container-low sm:px-8 [&::-webkit-details-marker]:hidden">
                        {{ $faq->pertanyaan }}
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/5 text-primary transition-transform duration-300 group-open:rotate-45">
                            <x-heroicon-m-plus class="h-4 w-4" />
                        </span>
                    </summary>
                    <p class="text-pretty px-6 pb-6 pt-1 leading-relaxed text-on-surface-variant sm:px-8">{{ $faq->jawaban }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══ HUBUNGI KAMI — Jadi Mitra / Keluhan & Saran (tab, vanilla JS — beranda
     publik sengaja TANPA Livewire/Alpine; kirim ke DB, BUKAN email — lihat
     KontakController & panel admin "Kontak Masuk").
     Menggantikan CTA "Jadi Mitra" lama (dihapus 2026-07-13) — sudah fungsional
     lewat form ini, tak perlu kartu terpisah lagi. ══ --}}
<section id="kontak" class="relative overflow-hidden border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            class="mx-auto mb-10"
            align="center"
            eyebrow="Hubungi Kami"
            title="Ingin nagari Anda jadi mitra, atau punya keluhan & saran?"
            description="Isi salah satu formulir di bawah — pesan Anda tersimpan langsung dan ditindaklanjuti tim SLC Basamo NCH."
        />

        @if(session('kontak_sukses'))
            <div class="mx-auto mb-8 flex max-w-2xl items-center gap-3 rounded-2xl border border-tertiary/30 bg-tertiary/10 px-6 py-4 text-sm font-semibold text-tertiary">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
                {{ session('kontak_sukses') }}
            </div>
        @endif

        @php
            $tabAwal = old('kategori', 'mitra') === 'mitra' ? 'mitra' : 'lainnya';
            $btnAktif = 'bg-primary text-on-primary shadow-sm';
            $btnPasif = 'text-on-surface-variant hover:text-primary';
        @endphp
        <div class="mx-auto max-w-2xl">
            {{-- Tab (vanilla JS, skripnya didorong ke tumpukan "scripts" di bawah) --}}
            <div class="mb-8 flex gap-2 rounded-full border border-outline-variant bg-background p-1.5">
                <button type="button" data-kontak-tab="mitra"
                    class="flex-1 rounded-full px-5 py-2.5 text-sm font-bold transition-colors {{ $tabAwal === 'mitra' ? $btnAktif : $btnPasif }}">Jadi Mitra</button>
                <button type="button" data-kontak-tab="lainnya"
                    class="flex-1 rounded-full px-5 py-2.5 text-sm font-bold transition-colors {{ $tabAwal === 'lainnya' ? $btnAktif : $btnPasif }}">Keluhan &amp; Saran</button>
            </div>

            @php
                $inputClass = 'w-full rounded-xl border border-control-border bg-background px-4 py-3 text-sm text-on-surface placeholder:text-on-surface-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
                $labelClass = 'mb-1.5 block text-sm font-bold text-on-surface';
                $errorClass = 'mt-1.5 text-xs text-error';
            @endphp

            {{-- Form: Jadi Mitra --}}
            <form data-kontak-form="mitra" method="POST" action="{{ route('public.kontak.store') }}"
                class="{{ $tabAwal === 'mitra' ? '' : 'hidden' }} space-y-4 rounded-3xl border border-outline-variant bg-background p-6 shadow-sm sm:p-8">
                @csrf
                <input type="hidden" name="kategori" value="mitra">

                <div>
                    <label for="mitra_nama" class="{{ $labelClass }}">Nama<span class="text-error">*</span></label>
                    <input type="text" id="mitra_nama" name="nama" required maxlength="150"
                        value="{{ old('kategori') === 'mitra' ? old('nama') : '' }}" class="{{ $inputClass }}">
                    @error('nama') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="mitra_email" class="{{ $labelClass }}">Email<span class="text-error">*</span></label>
                        <input type="email" id="mitra_email" name="email" required maxlength="150"
                            value="{{ old('kategori') === 'mitra' ? old('email') : '' }}" class="{{ $inputClass }}">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="mitra_no_hp" class="{{ $labelClass }}">No. HP/WhatsApp<span class="text-error">*</span></label>
                        <input type="text" id="mitra_no_hp" name="no_hp" required placeholder="0812…"
                            value="{{ old('kategori') === 'mitra' ? old('no_hp') : '' }}" class="{{ $inputClass }}">
                        @error('no_hp') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="mitra_nagari" class="{{ $labelClass }}">Nama nagari<span class="text-error">*</span></label>
                    <input type="text" id="mitra_nagari" name="nama_nagari" required maxlength="150"
                        value="{{ old('kategori') === 'mitra' ? old('nama_nagari') : '' }}" class="{{ $inputClass }}">
                    @error('nama_nagari') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="mitra_isi" class="{{ $labelClass }}">Ceritakan rencana kemitraannya<span class="text-error">*</span></label>
                    <textarea id="mitra_isi" name="isi" rows="4" required maxlength="5000"
                        class="{{ $inputClass }}">{{ old('kategori') === 'mitra' ? old('isi') : '' }}</textarea>
                    @error('isi') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint">
                    Kirim Pengajuan Mitra <x-heroicon-o-paper-airplane class="h-4 w-4" />
                </button>
            </form>

            {{-- Form: Keluhan & Saran — SATU kategori gabungan (keputusan user
                 2026-07-13), tanpa pilihan jenis lagi. --}}
            <form data-kontak-form="lainnya" method="POST" action="{{ route('public.kontak.store') }}"
                class="{{ $tabAwal === 'lainnya' ? '' : 'hidden' }} space-y-4 rounded-3xl border border-outline-variant bg-background p-6 shadow-sm sm:p-8">
                @csrf
                <input type="hidden" name="kategori" value="keluhan_saran">

                <div>
                    <label for="ls_nama" class="{{ $labelClass }}">Nama<span class="text-error">*</span></label>
                    <input type="text" id="ls_nama" name="nama" required maxlength="150"
                        value="{{ old('kategori') !== 'mitra' ? old('nama') : '' }}" class="{{ $inputClass }}">
                    @error('nama') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ls_email" class="{{ $labelClass }}">Email<span class="text-error">*</span></label>
                        <input type="email" id="ls_email" name="email" required maxlength="150"
                            value="{{ old('kategori') !== 'mitra' ? old('email') : '' }}" class="{{ $inputClass }}">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ls_no_hp" class="{{ $labelClass }}">No. HP/WhatsApp<span class="text-error">*</span></label>
                        <input type="text" id="ls_no_hp" name="no_hp" required placeholder="0812…"
                            value="{{ old('kategori') !== 'mitra' ? old('no_hp') : '' }}" class="{{ $inputClass }}">
                        @error('no_hp') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="ls_isi" class="{{ $labelClass }}">Isi<span class="text-error">*</span></label>
                    <textarea id="ls_isi" name="isi" rows="4" required maxlength="5000"
                        class="{{ $inputClass }}">{{ old('kategori') !== 'mitra' ? old('isi') : '' }}</textarea>
                    @error('isi') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint">
                    Kirim <x-heroicon-o-paper-airplane class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>

    {{-- Dialog konfirmasi sebelum kirim — vanilla (public site tanpa Alpine),
         gaya disamakan dgn x-portal.confirm-dialog: ikon+judul tengah, Batal kiri/Kirim kanan. --}}
    <div id="kontak-confirm-modal" class="fixed inset-0 z-[70] hidden items-end justify-center p-4 sm:items-center">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-kontak-confirm-cancel></div>
        <div class="relative w-full max-w-[26rem] rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 shadow-xl" role="dialog" aria-modal="true">
            <div class="flex flex-col items-center text-center">
                <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <x-heroicon-o-paper-airplane class="h-7 w-7" />
                </span>
                <h3 class="text-lg font-bold text-on-surface">Kirim pesan ini?</h3>
                <p id="kontak-confirm-message" class="mt-1.5 text-sm leading-relaxed text-on-surface-variant"></p>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-center">
                <button type="button" data-kontak-confirm-cancel
                    class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm font-bold text-on-surface-variant transition-colors hover:bg-surface-container-low sm:w-auto">
                    Batal
                </button>
                <button type="button" id="kontak-confirm-submit"
                    class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint sm:w-auto">
                    Ya, Kirim
                </button>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Reveal-on-scroll (panutan halaman publik): section memudar masuk saat terlihat.
    // Aman-degradasi: tanpa JS/tanpa IntersectionObserver/prefers-reduced-motion →
    // konten langsung tampil (kelas penyembunyi hanya ditambahkan bila fitur ada).
    document.addEventListener('DOMContentLoaded', () => {
        if (!('IntersectionObserver' in window)
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100');
                    entry.target.classList.remove('opacity-0');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.05, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('main section').forEach((el) => {
            el.classList.add('transition-opacity', 'duration-1000', 'opacity-0');
            observer.observe(el);
        });
    });
</script>
<script>
    // Tab "Hubungi Kami" (Jadi Mitra / Keluhan & Saran) — vanilla, beranda publik
    // TANPA Alpine (cuma dimuat lewat Livewire di portal, lihat catatan komentar
    // section #kontak). Delegasi di document → tetap kerja meski elemen berubah.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-kontak-tab]');
        if (!btn) return;

        const tab = btn.dataset.kontakTab;

        document.querySelectorAll('[data-kontak-tab]').forEach((b) => {
            const aktif = b.dataset.kontakTab === tab;
            b.classList.toggle('bg-primary', aktif);
            b.classList.toggle('text-on-primary', aktif);
            b.classList.toggle('shadow-sm', aktif);
            b.classList.toggle('text-on-surface-variant', !aktif);
            b.classList.toggle('hover:text-primary', !aktif);
        });

        document.querySelectorAll('[data-kontak-form]').forEach((form) => {
            form.classList.toggle('hidden', form.dataset.kontakForm !== tab);
        });
    });
</script>
<script>
    // Konfirmasi sebelum kirim form kontak (Jadi Mitra / Keluhan & Saran).
    (function () {
        const modal = document.getElementById('kontak-confirm-modal');
        const pesan = document.getElementById('kontak-confirm-message');
        const btnKirim = document.getElementById('kontak-confirm-submit');
        let pendingForm = null;

        function buka(form) {
            pendingForm = form;
            pesan.textContent = form.dataset.kontakForm === 'mitra'
                ? 'Pastikan data pengajuan kemitraan sudah benar sebelum dikirim.'
                : 'Pastikan pesan keluhan/saran sudah benar sebelum dikirim.';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function tutup() {
            pendingForm = null;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.querySelectorAll('[data-kontak-form]').forEach((form) => {
            form.addEventListener('submit', (e) => {
                if (form.dataset.kontakConfirmed === '1') return; // sudah dikonfirmasi → lanjut kirim asli
                e.preventDefault();
                if (form.reportValidity()) buka(form);
            });
        });

        btnKirim.addEventListener('click', () => {
            if (!pendingForm) return;
            const form = pendingForm;
            tutup();
            form.dataset.kontakConfirmed = '1';
            form.requestSubmit();
        });

        modal.querySelectorAll('[data-kontak-confirm-cancel]').forEach((el) => el.addEventListener('click', tutup));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) tutup();
        });
    })();
</script>
@endpush
