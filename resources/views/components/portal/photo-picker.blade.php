{{--
    Pemilih foto ber-thumbnail (pengganti input file native yang menyesatkan:
    memilih file kedua MENGGANTI pilihan pertama). Foto bisa ditambah berkali-kali
    sampai `max`, tiap pilihan tampil sebagai thumbnail + tombol hapus. Berkas
    disinkronkan ke input file tersembunyi (DataTransfer) → terkirim sebagai
    `{{ '{name}' }}[]` biasa, validasi server tak berubah.

    Mengirim event `photos-updated` { firstUrl, count } saat pilihan berubah —
    dipakai form pengajuan untuk memutakhirkan kartu pratinjau produk.
--}}
@props([
    'name' => 'photos',
    'max' => 5,
])

<div x-data="{
        max: {{ (int) $max }},
        items: [],
        async add(e) {
            const picked = Array.from(e.target.files);
            for (const file of picked) {
                if (this.items.length >= this.max) break;
                const optimized = await this.compress(file);
                this.items.push({ file: optimized, url: URL.createObjectURL(optimized) });
            }
            this.sync();
        },
        // Kompres di klien (pola foto profil warga): sisi terpanjang maks 1920px,
        // JPEG q0.8 — warga bebas memilih foto sebesar apa pun, transfer tetap ringan.
        // Gagal (format tak terbaca) → kirim asli; server tetap mengompres ulang.
        async compress(file) {
            try {
                const bmp = await createImageBitmap(file);
                const scale = Math.min(1, 1920 / Math.max(bmp.width, bmp.height));
                const w = Math.max(1, Math.round(bmp.width * scale));
                const h = Math.max(1, Math.round(bmp.height * scale));
                const canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h); // PNG transparan → latar putih
                ctx.drawImage(bmp, 0, 0, w, h);
                bmp.close();
                const blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.8));
                if (! blob) return file;
                return new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
            } catch { return file; }
        },
        remove(i) {
            URL.revokeObjectURL(this.items[i].url);
            this.items.splice(i, 1);
            this.sync();
        },
        sync() {
            const dt = new DataTransfer();
            this.items.forEach(({ file }) => dt.items.add(file));
            this.$refs.input.files = dt.files;
            this.$dispatch('photos-updated', { firstUrl: this.items[0]?.url ?? null, count: this.items.length });
        },
    }">
    <input type="file" x-ref="input" name="{{ $name }}[]" accept="image/jpeg,image/png,image/webp"
        multiple class="hidden" @change="add($event)">

    {{-- Foto terpilih: thumbnail + hapus per foto --}}
    <div class="mb-3 flex flex-wrap gap-3" x-show="items.length > 0">
        <template x-for="(item, i) in items" :key="item.url">
            <div class="relative">
                <img :src="item.url" alt="" class="h-24 w-24 rounded-xl object-cover ring-1 ring-outline-variant">
                <button type="button" @click="remove(i)" title="Hapus foto ini"
                    class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-error text-white shadow ring-2 ring-surface transition-transform hover:scale-110">
                    <x-heroicon-m-x-mark class="h-3.5 w-3.5" />
                </button>
            </div>
        </template>
    </div>

    <div class="flex items-center gap-3">
        <button type="button" @click="$refs.input.click()" :disabled="items.length >= max"
            class="inline-flex items-center gap-2 rounded-xl bg-primary/10 px-4 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-primary/20 disabled:cursor-not-allowed disabled:opacity-50">
            <x-heroicon-o-photo class="h-4 w-4" />
            <span x-text="items.length ? 'Tambah foto lagi' : 'Pilih foto'"></span>
        </button>
        <span class="text-xs font-medium text-on-surface-variant" x-text="items.length + '/' + max + ' foto'"></span>
    </div>
</div>
