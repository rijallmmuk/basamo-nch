import confetti from 'canvas-confetti';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

/**
 * Tembakan confetti perayaan (modul selesai / lulus kuis).
 * Bisa dipanggil dari mana saja: window.fireConfetti()
 */
window.fireConfetti = function () {
    const duration = 1200;
    const end = Date.now() + duration;
    // Palet Nagari Creative Hub: deep blue, Minang gold, hijau SDG-3, biru SDG-14.
    const colors = ['#003857', '#fed33e', '#4c9f38', '#0a97d9'];

    (function frame() {
        confetti({ particleCount: 4, angle: 60, spread: 55, origin: { x: 0 }, colors });
        confetti({ particleCount: 4, angle: 120, spread: 55, origin: { x: 1 }, colors });
        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    })();

    confetti({ particleCount: 80, spread: 70, origin: { y: 0.6 }, colors });
};

// Dengarkan event dari Livewire (mis. lulus kuis).
document.addEventListener('livewire:init', () => {
    window.Livewire.on('confetti', () => window.fireConfetti());
});

/**
 * Loading universal untuk form POST biasa (bukan Livewire — yang sudah punya
 * wire:loading sendiri). Tombol submit ber-atribut `data-loading` akan dinonaktifkan
 * dan menampilkan spinner saat form dikirim. Markup dua-state (label ↔ spinner) dirender
 * oleh komponen `x-portal.button` (type=submit) atau tombol auth kustom.
 * Form akan navigasi penuh → status ter-reset otomatis saat halaman dimuat ulang.
 */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (! (form instanceof HTMLFormElement)) {
        return;
    }
    if (form.hasAttribute('wire:submit')) {
        return; // Livewire menangani loading-nya sendiri
    }

    const btn = form.querySelector('button[type="submit"][data-loading]');
    if (! btn || btn.disabled) {
        return;
    }

    btn.disabled = true;
    btn.querySelector('[data-loading-label]')?.classList.add('hidden');
    const spinner = btn.querySelector('[data-loading-spinner]');
    if (spinner) {
        spinner.classList.remove('hidden');
        spinner.classList.add('inline-flex');
    }
});

/**
 * Toggle lihat/sembunyikan kata sandi (komponen x-portal.password-input). Vanilla &
 * terdelegasi → berfungsi di SEMUA halaman termasuk login yang tak memuat Alpine.
 */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-password-toggle]');
    if (! btn) {
        return;
    }

    const input = btn.parentElement.querySelector('input');
    if (! input) {
        return;
    }

    const reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    btn.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
    btn.querySelectorAll('[data-eye]').forEach((el) => el.classList.toggle('hidden'));
});

/**
 * Editor foto profil: pilih → atur (crop 1:1, zoom, putar) → kompres <200KB → simpan.
 * Kompresi di sisi klien menghemat kuota unggah (target low-bandwidth) & menjamin
 * ukuran akhir kecil apa pun ukuran aslinya. Output selalu JPEG 512×512.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('avatarCropper', () => {
        // Instance Cropper disimpan di closure (bukan state reaktif Alpine) agar tidak
        // di-proxy — proxy bisa merusak internal Cropper.
        let cropper = null;
        let objectUrl = null;

        const toBlob = (canvas, quality) =>
            new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));

        const toast = (title, type = 'error') =>
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, title } }));

        return {
            open: false,
            busy: false,

            pick(event) {
                const file = event.target.files?.[0];
                if (! file) {
                    return;
                }
                if (! file.type.startsWith('image/')) {
                    toast('File harus berupa gambar.');
                    event.target.value = '';
                    return;
                }

                this.teardown();
                objectUrl = URL.createObjectURL(file);
                this.open = true;

                this.$nextTick(() => {
                    const img = this.$refs.image;
                    img.src = objectUrl;
                    cropper = new Cropper(img, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        background: false,
                        guides: false,
                        center: true,
                        responsive: true,
                        restore: false,
                        checkOrientation: true,
                    });
                });
            },

            zoom(delta) {
                cropper?.zoom(delta);
            },

            rotate(deg) {
                cropper?.rotate(deg);
            },

            teardown() {
                cropper?.destroy();
                cropper = null;
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
            },

            cancel() {
                this.open = false;
                this.busy = false;
                this.teardown();
                this.$refs.picker.value = '';
            },

            async save() {
                if (! cropper || this.busy) {
                    return;
                }
                this.busy = true;

                // Avatar tampil paling besar 80px (profil/podium) → 160px = 2× untuk retina,
                // tajam & minimal (file ~10–15KB, jauh <50KB). Turunkan kualitas dulu; bila
                // masih besar, perkecil dimensi lalu ulangi — jaminan ukuran akhir apa pun sumbernya.
                const maxBytes = 46 * 1024;
                let dimension = 160;
                let blob = null;

                while (dimension >= 96) {
                    const canvas = cropper.getCroppedCanvas({
                        width: dimension,
                        height: dimension,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high',
                        fillColor: '#fff',
                    });

                    let quality = 0.85;
                    blob = await toBlob(canvas, quality);
                    while (blob.size > maxBytes && quality > 0.35) {
                        quality -= 0.1;
                        blob = await toBlob(canvas, quality);
                    }

                    if (blob.size <= maxBytes) {
                        break;
                    }
                    dimension -= 32; // masih terlalu besar → perkecil dimensi & ulangi
                }

                const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                const data = new DataTransfer();
                data.items.add(file);
                this.$refs.upload.files = data.files;
                this.$refs.form.submit();
            },
        };
    });
});
