import confetti from 'canvas-confetti';
import Cropper from 'cropperjs';
import focus from '@alpinejs/focus';

// Plugin resmi Alpine (HEADLESS — perilaku saja, nol desain; tampilan tetap milik
// komponen kita). Dipasang sebelum Alpine.start() milik Livewire.
//   focus — x-trap (focus-trap modal) di portal warga.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus);

    window.Alpine.data('appShell', () => ({
        mobileNavOpen: false,
        desktopSidebarOpen: true,

        init() {
            try {
                const storedPreference = window.localStorage.getItem('basamo.shell.sidebar-open');

                if (storedPreference !== null) {
                    this.desktopSidebarOpen = storedPreference === 'true';
                }
            } catch {
                this.desktopSidebarOpen = true;
            }
        },

        toggleDesktopSidebar() {
            this.desktopSidebarOpen = ! this.desktopSidebarOpen;

            try {
                window.localStorage.setItem('basamo.shell.sidebar-open', String(this.desktopSidebarOpen));
            } catch {
                // Preferensi visual tetap bekerja untuk sesi aktif saat storage diblokir.
            }

            window.dispatchEvent(new CustomEvent('app-sidebar-toggled'));
        },
    }));
});

/**
 * Tembakan confetti perayaan (modul selesai / lulus kuis).
 * Bisa dipanggil dari mana saja: window.fireConfetti()
 */
window.fireConfetti = function () {
    const duration = 1200;
    const end = Date.now() + duration;
    const styles = getComputedStyle(document.documentElement);
    const themeColor = (token) => styles.getPropertyValue(`--color-${token}`).trim();
    const colors = [themeColor('primary'), themeColor('secondary-container'), themeColor('success'), themeColor('info')];

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
    btn.querySelector('[data-loading-label]')?.classList.add('invisible');
    btn.querySelector('[data-loading-spinner]')?.classList.remove('invisible');
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
// Cropper 2 dibangun dari Web Component, bukan opsi objek seperti versi 1. Susunan
// elemennya kita tentukan sendiri di sini:
//   initial-center-size="cover" — foto memenuhi kanvas, tak ada area kosong
//   handle "move" di kanvas     — seret di luar bingkai menggeser foto
//   aspect-ratio="1"            — bingkai avatar dikunci persegi
// Ukuran kanvas ditulis inline karena <cropper-canvas> hanya setinggi 100px bila tak
// diberi ukuran, dan style inline sudah berlaku sebelum elemen masuk DOM (tanpa balapan
// layout, dan tak bergantung pada pemindaian kelas Tailwind di berkas JS).
const CROPPER_TEMPLATE = `
<cropper-canvas background style="display:block;width:100%;aspect-ratio:1;max-height:55vh">
    <cropper-image rotatable scalable translatable initial-center-size="cover"></cropper-image>
    <cropper-shade hidden></cropper-shade>
    <cropper-handle action="move" plain></cropper-handle>
    <cropper-selection initial-coverage="0.9" aspect-ratio="1" movable resizable outlined>
        <cropper-grid role="grid" bordered covered></cropper-grid>
        <cropper-handle action="move" theme-color="rgba(255, 255, 255, 0.35)"></cropper-handle>
        <cropper-handle action="n-resize"></cropper-handle>
        <cropper-handle action="e-resize"></cropper-handle>
        <cropper-handle action="s-resize"></cropper-handle>
        <cropper-handle action="w-resize"></cropper-handle>
        <cropper-handle action="ne-resize"></cropper-handle>
        <cropper-handle action="nw-resize"></cropper-handle>
        <cropper-handle action="se-resize"></cropper-handle>
        <cropper-handle action="sw-resize"></cropper-handle>
    </cropper-selection>
</cropper-canvas>`;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('avatarCropper', () => {
        // Instance Cropper disimpan di closure (bukan state reaktif Alpine) agar tidak
        // di-proxy — proxy bisa merusak internal Cropper.
        let cropper = null;
        let cropperImage = null;
        let selection = null;
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
                    // Cropper 2 membaca `src` sekali saat dikonstruksi, jadi urutannya wajib
                    // set src dulu, baru bikin instance.
                    img.src = objectUrl;
                    cropper = new Cropper(img, { template: CROPPER_TEMPLATE });
                    cropperImage = cropper.getCropperImage();
                    selection = cropper.getCropperSelection();
                });
            },

            zoom(delta) {
                cropperImage?.$zoom(delta);
            },

            rotate(deg) {
                cropperImage?.$rotate(`${deg}deg`);
            },

            teardown() {
                cropper?.destroy();
                cropper = null;
                cropperImage = null;
                selection = null;
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
                if (! selection || this.busy) {
                    return;
                }
                this.busy = true;

                // Avatar dikompresi & dipotong 1:1 dengan batas aman hingga 10 MB (9.5 MB).
                const maxBytes = 9.5 * 1024 * 1024;
                let dimension = 400;
                let blob = null;

                try {
                    while (dimension >= 160) {
                        // Latar putih & penghalusan gambar tidak lagi jadi opsi di Cropper 2;
                        // keduanya disetel lewat beforeDraw, yang jalan sebelum foto digambar.
                        const canvas = await selection.$toCanvas({
                            width: dimension,
                            height: dimension,
                            beforeDraw: (context, target) => {
                                context.imageSmoothingEnabled = true;
                                context.imageSmoothingQuality = 'high';
                                context.fillStyle = '#fff';
                                context.fillRect(0, 0, target.width, target.height);
                            },
                        });

                        let quality = 0.85;
                        blob = await toBlob(canvas, quality);
                        while (blob && blob.size > maxBytes && quality > 0.35) {
                            quality -= 0.1;
                            blob = await toBlob(canvas, quality);
                        }

                        if (blob && blob.size <= maxBytes) {
                            break;
                        }
                        dimension -= 64; // masih terlalu besar → perkecil dimensi & ulangi
                    }
                } catch {
                    blob = null;
                }

                if (! blob || blob.size > maxBytes) {
                    this.busy = false;
                    toast('Foto gagal diproses. Coba pilih foto lain.');

                    return;
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

/**
 * Penyegaran nilai sensor EWS pada halaman Medan Nan Bapaneh.
 *
 * Menembak endpoint JSON aplikasi sendiri, BUKAN Blynk. Dashboard acuan memanggil
 * Blynk langsung dari peramban sehingga tokennya terbaca di view-source; token itu
 * juga berlaku untuk endpoint `update`, jadi siapa pun bisa menulis nilai palsu ke
 * alat peringatan dini banjir.
 *
 * Halaman sudah tergambar penuh dari server sebelum skrip ini jalan, jadi bila
 * JavaScript mati atau permintaan gagal, angka terakhir tetap terbaca.
 */
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('[data-ews-endpoint]');

    if (! panel) {
        return;
    }

    const endpoint = panel.dataset.ewsEndpoint;
    const JEDA = 30000; // 30 detik; server tetap menyaring lewat cache-nya sendiri.

    const angka = (nilai, desimal = 0) => nilai === null || nilai === undefined
        ? '--'
        : Number(nilai).toLocaleString('id-ID', {
            minimumFractionDigits: desimal,
            maximumFractionDigits: desimal,
        });

    const isi = (selector, teks) => {
        panel.querySelectorAll(selector).forEach((el) => { el.textContent = teks; });
    };

    const segarkan = async () => {
        try {
            const respons = await fetch(endpoint, { headers: { Accept: 'application/json' } });

            if (! respons.ok) {
                return;
            }

            const data = await respons.json();

            isi('[data-ews-tinggi]', angka(data.tinggi_air));
            isi('[data-ews-hujan]', angka(data.curah_hujan));
            isi('[data-ews-ph]', angka(data.ph_air, 1));
            isi('[data-ews-getaran]', angka(data.getaran));
            isi('[data-ews-status]', data.status_label);
            isi('[data-ews-sambungan]', data.terhubung ? 'Alat terhubung' : 'Alat tidak terhubung');
            isi('[data-ews-waktu]', data.diambil_pada_manusia ?? 'belum ada');
            isi('[data-ews-ph-catatan]', data.ph_mencurigakan ? 'sensor perlu kalibrasi' : 'pH');

            const status = panel.querySelector('[data-ews-status]');
            if (status) {
                const statusClass = data.terhubung && ! data.basi
                    ? data.status_kelas
                    : 'text-on-surface-variant';
                status.className = `mt-1 text-4xl font-black tracking-tight ${statusClass}`;
            }

            const lampu = panel.querySelector('[data-ews-lampu]');
            if (lampu) {
                lampu.className = `h-2 w-2 rounded-full ${data.terhubung ? 'bg-emerald-500' : 'bg-slate-400'}`;
            }
        } catch {
            // Diamkan: halaman sudah menampilkan pembacaan terakhir dari server.
        }
    };

    let timer = setInterval(segarkan, JEDA);

    // Tab yang tersembunyi tidak perlu terus menembak server.
    document.addEventListener('visibilitychange', () => {
        clearInterval(timer);

        if (! document.hidden) {
            segarkan();
            timer = setInterval(segarkan, JEDA);
        }
    });
});

/**
 * Teks panjang yang dilipat (komponen x-public.teks-lipat).
 *
 * Deskripsi di sistem ini tidak dibatasi panjangnya, jadi tampilan yang menahan.
 * Tombolnya hanya ditampilkan bila teksnya BENAR-BENAR meluap, diukur dari selisih
 * scrollHeight dan clientHeight, bukan dari menebak jumlah karakter: satu paragraf
 * yang sama bisa meluap di ponsel dan tidak meluap di layar lebar.
 *
 * Vanilla dan terdelegasi, sebab halaman publik tidak memuat Alpine.
 */
const nyalakanTeksLipat = () => {
    document.querySelectorAll('[data-teks-lipat]').forEach((wadah) => {
        const isi = wadah.querySelector('[data-teks-lipat-isi]');
        const tombol = wadah.querySelector('[data-teks-lipat-tombol]');

        if (! isi || ! tombol) {
            return;
        }

        tombol.classList.toggle('hidden', isi.scrollHeight <= isi.clientHeight + 1);
    });
};

document.addEventListener('DOMContentLoaded', nyalakanTeksLipat);
// Lebar berubah → yang tadinya meluap bisa jadi tidak, dan sebaliknya.
window.addEventListener('resize', nyalakanTeksLipat);

document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-teks-lipat-tombol]');
    if (! tombol) {
        return;
    }

    const wadah = tombol.closest('[data-teks-lipat]');
    const isi = wadah?.querySelector('[data-teks-lipat-isi]');
    if (! isi) {
        return;
    }

    const clamp = wadah.dataset.teksLipatClamp || 'line-clamp-4';
    const terbuka = ! isi.classList.contains(clamp);

    isi.classList.toggle(clamp, terbuka);
    tombol.querySelector('[data-teks-lipat-label]').textContent = terbuka
        ? tombol.dataset.labelBuka
        : tombol.dataset.labelTutup;
    tombol.querySelector('[data-teks-lipat-ikon]')?.classList.toggle('rotate-180', ! terbuka);
});

/**
 * Filter katalog publik tanpa muat ulang halaman.
 *
 * Server tetap menjadi sumber kebenaran: browser meminta HTML hasil filter yang
 * sama dengan kunjungan biasa, lalu hanya mengganti section hasilnya. URL ikut
 * diperbarui supaya dapat disalin dan tombol kembali/maju tetap bermakna. Tanpa
 * JavaScript, tombol di dalam <noscript> mempertahankan alur GET biasa.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-live-filter]').forEach((form) => {
        const targetSelector = form.dataset.liveTarget;
        const status = form.querySelector('[data-live-filter-status]');
        const reset = form.querySelector('[data-live-filter-reset]');
        let timer = null;
        let request = null;
        let statusTimer = null;

        const setStatus = (message) => {
            if (! status) {
                return;
            }

            clearTimeout(statusTimer);
            status.textContent = message;

            if (message === 'Hasil diperbarui.') {
                statusTimer = setTimeout(() => { status.textContent = ''; }, 1200);
            }
        };

        const hasFilters = () => [...form.elements].some((control) =>
            control.name && ! ['submit', 'button'].includes(control.type) && String(control.value).trim() !== '');

        const syncReset = () => {
            if (! reset) {
                return;
            }

            const active = hasFilters();
            reset.classList.toggle('hidden', ! active);
            reset.classList.toggle('inline-flex', active);
        };

        const urlFromForm = () => {
            const url = new URL(form.action, window.location.href);
            const params = new URLSearchParams();

            new FormData(form).forEach((value, key) => {
                const normalized = String(value).trim();
                if (normalized !== '') {
                    params.append(key, normalized);
                }
            });

            url.search = params.toString();
            return url;
        };

        const syncFormFromUrl = (url) => {
            const params = new URL(url, window.location.href).searchParams;

            form.querySelectorAll('[name]').forEach((control) => {
                if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement) {
                    control.value = params.get(control.name) ?? '';
                }
            });

            syncReset();
        };

        const load = async (url, pushHistory = true) => {
            const currentTarget = document.querySelector(targetSelector);
            if (! currentTarget) {
                window.location.assign(url);
                return;
            }

            request?.abort();
            request = new AbortController();
            const thisRequest = request;

            currentTarget.setAttribute('aria-busy', 'true');
            currentTarget.classList.add('opacity-60');
            setStatus('Memuat hasil…');

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: thisRequest.signal,
                });

                if (! response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextTarget = page.querySelector(targetSelector);
                if (! nextTarget) {
                    throw new Error(`Target ${targetSelector} tidak ditemukan`);
                }

                currentTarget.replaceWith(nextTarget);
                page.querySelectorAll('[data-live-sync]').forEach((nextElement) => {
                    const key = nextElement.dataset.liveSync;
                    const currentElement = document.querySelector(`[data-live-sync="${CSS.escape(key)}"]`);
                    currentElement?.replaceWith(nextElement);
                });
                document.title = page.title;

                if (pushHistory && url.toString() !== window.location.href) {
                    window.history.pushState({ liveFilter: true }, '', url);
                }

                document.dispatchEvent(new CustomEvent('public:content-updated', {
                    detail: { root: nextTarget },
                }));
                nyalakanTeksLipat();
                setStatus('Hasil diperbarui.');
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                // Kegagalan enhancement tidak boleh membuat filter mati. Navigasi
                // GET biasa masih memberi hasil dan pesan kesalahan browser standar.
                window.location.assign(url);
            } finally {
                if (request === thisRequest) {
                    request = null;
                    document.querySelector(targetSelector)?.removeAttribute('aria-busy');
                    document.querySelector(targetSelector)?.classList.remove('opacity-60');
                }
            }
        };

        const schedule = (immediate = false) => {
            clearTimeout(timer);
            syncReset();
            timer = setTimeout(() => load(urlFromForm()), immediate ? 0 : 350);
        };

        form.addEventListener('input', (event) => {
            if (event.target.matches('input[type="search"], input[type="text"]')) {
                schedule(false);
            }
        });

        form.addEventListener('change', (event) => {
            if (! event.target.matches('input[type="search"], input[type="text"]')) {
                schedule(true);
            }
        });
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            schedule(true);
        });

        reset?.addEventListener('click', (event) => {
            event.preventDefault();
            form.querySelectorAll('[name]').forEach((control) => {
                if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement) {
                    control.value = '';
                }
            });
            schedule(true);
        });

        window.addEventListener('popstate', () => {
            syncFormFromUrl(window.location.href);
            load(new URL(window.location.href), false);
        });

        syncReset();
    });
});
