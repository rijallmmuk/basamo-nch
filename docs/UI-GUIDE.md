# UI-GUIDE.md — Nagari Creative Hub (Basamo NCH)

> **Sumber kebenaran tampilan front-end.** Diturunkan dari design Google Stitch
> (`stitch_nagari_creative_hub_redesign/`). Target kemiripan dengan mockup **>98%**.
> Berlaku untuk Portal Warga & Frontend Publik (Blade + Tailwind v4).
> Panel Admin (Filament) mengikuti semangat palet ini sebisanya, bukan pixel-perfect.

---

## Prinsip konsistensi (wajib)

1. **Token, bukan hex.** Selalu pakai class token (`bg-primary`, `text-on-surface`,
   `text-display-lg`). JANGAN tulis hex/warna mentah di Blade. Semua token ada di
   `resources/css/app.css` `@theme` — itu satu-satunya tempat ubah palet.
2. **Komponen, bukan markup ad-hoc.** Rakit halaman dari `resources/views/components/`
   (`<x-portal.button>`, `<x-portal.card>`, dst). Cek komponen yang ada sebelum bikin baru.
3. **Motif terpusat.** Pakai utility motif (`gonjong-bg`, `glass-card`, `minang-divider`,
   `card-shadow`, `card-hover`) — jangan copy CSS per halaman.
4. **Workflow per-layar.** Untuk tiap screen: buka `code.html` + `screen.png` →
   port ke Blade pakai token+komponen → bandingkan dengan `screen.png` → revisi sampai cocok.
5. **Ikon = Heroicons** (lihat tabel pemetaan di bawah). Design pakai Material Symbols;
   kita map ke Heroicon terdekat sesuai aturan CLAUDE.md.

---

## Identitas merek

**Visionary · Rooted · Prestigious** — perpaduan *Corporate Modernism* + *Tactile Heritage*.
Aksen motif Minangkabau (*Pucuk Rabuang* = pertumbuhan, *Bada Mudiak* = harmoni) sebagai
watermark/divider opacity rendah. Font tunggal: **Plus Jakarta Sans**.

---

## Warna (token semantik)

| Peran | Token | Hex | Catatan |
|---|---|---|---|
| Aksi utama | `primary` | `#003857` | NCH Deep Blue — stabil, otoritatif |
| Di atas primary | `on-primary` | `#ffffff` | |
| Kontainer primary | `primary-container` | `#1b4f72` | |
| Aksen / CTA tinggi | `secondary-container` | `#fed33e` | **Minang Gold** — hemat, untuk highlight |
| Label di atas gold | `on-secondary-container` | `#725b00` | |
| Sukses / tumbuh | `tertiary` | `#003d1c` | Sustainable Green |
| Kontainer hijau | `tertiary-container` | `#00572a` | |
| Latar halaman | `background` / `surface` | `#f7f9fb` | off-white, bukan putih murni |
| Kartu/permukaan | `surface-container-lowest` | `#ffffff` | |
| Teks utama | `on-surface` | `#191c1e` | |
| Teks sekunder | `on-surface-variant` | `#41474e` | |
| Garis/border | `outline-variant` | `#c1c7cf` | |
| Error | `error` | `#ba1a1a` | |

SDG (18 poin) tetap tersedia: `bg-sdg-1` … `bg-sdg-18` (warna standar UN, lihat `app.css`).

---

## Tipografi (Plus Jakarta Sans)

| Token | Ukuran | Weight | Pakai untuk |
|---|---|---|---|
| `text-display-lg` | 48px | 800 | Hero judul utama (desktop) |
| `text-display-md` | 36px | 700 | Judul section besar |
| `text-headline-lg` | 30px | 700 | Judul halaman |
| `text-headline-md` | 24px | 600 | Subjudul / judul kartu besar |
| `text-headline-sm` | 18px | 600 | Judul kartu kecil *(kompat)* |
| `text-body-lg` | 18px | 400 | Paragraf intro (line-height 1.6) |
| `text-body-md` | 16px | 400 | Body utama |
| `text-label-md` | 14px | 600 | Label/metadata, tracking 0.05em (sering uppercase) |
| `text-label-sm` | 12px | 500 | Caption/chip |
| `text-metric-lg` | 32px | 800 | Angka statistik *(kompat)* |

Heading: weight Bold/ExtraBold + tracking sedikit rapat. Body: line-height 1.6.

---

## Bentuk, spacing, elevasi

- **Radius (high circularity):** card besar `rounded-2xl`/`rounded-3xl`, elemen dalam `rounded-xl`,
  input `rounded-xl`, chip & tombol `rounded-full`.
  > Skala `rounded-*` dibiarkan default Tailwind (tidak di-override global) agar card lama tidak
  > membengkak. Terapkan radius eksplisit per komponen sesuai mockup.
- **Spacing:** base 8px. Padding internal kartu/section besar (≥`p-8`). Gap antar section besar
  (`section-gap` = 80px). Token halaman: `gutter` 24px, `margin-mobile` 20px, `margin-desktop` 48px.

> ⚠️ **Gotcha `max-w` (penting):** token spacing kustom `--spacing-xs/sm/md/lg/xl` (dipakai
> `gap-md`, `p-lg`, dll) **menimpa** skala lebar Tailwind, sehingga `max-w-sm/md/lg/xl` jadi nilai
> mungil (8–24px) dan meng-kolaps konten. **JANGAN pakai `max-w-{sm,md,lg,xl}`.** Pakai
> `max-w-2xl … max-w-7xl` (tak bentrok) atau arbitrary `max-w-[24rem]`/`max-w-[32rem]`.
> Untuk grid 2 kolom teks+media gunakan pola `grid-cols-12` + `col-span-*` (bukan `grid-cols-2`).
- **Elevasi (shadow lembut bertinta biru, hindari hitam pekat):**
  - Level 1 (kartu/input): `.card-shadow` → `0 4px 12px rgba(27,79,114,.06)` + border 1px `outline-variant`.
  - Level 2 (hover): `.card-hover` → naik 4px + `0 12px 24px rgba(27,79,114,.1)`.
  - Overlay/modal: `.glass-card` / `.glass-panel` (backdrop blur).

### Utility motif (di `app.css`)
`gonjong-bg` · `songket-pattern` · `pattern-bada-mudiak` · `minang-divider` ·
`card-shadow` · `card-hover` · `glass-card` · `glass-panel` · `tech-glow` ·
`gonjong-peak` · `pucuk-rabuang`.

---

## Komponen

- **Tombol** — Primary: `bg-primary text-on-primary` pill, hover gelap/gradien.
  Secondary: outline 1.5px `border-primary`. Accent: `bg-secondary-container` (gold) untuk CTA prioritas.
- **Kartu** — `bg-surface-container-lowest rounded-2xl` + `card-shadow` + border tipis `outline-variant`;
  hover `card-hover`. Opsional watermark `gonjong-bg` opacity rendah di pojok.
- **Input** — default isi `bg-surface-container-low` tanpa border, `rounded-xl`; focus → putih + border 2px gold.
- **Chip/Tag** — latar soft (opacity ~10% primary/tertiary), label SemiBold.
- **Divider** — pakai `minang-divider` / `pattern-bada-mudiak`, bukan garis polos.

---

## Pemetaan ikon: Material Symbols (design) → Heroicon (implementasi)

Aturan: pakai komponen `<x-heroicon-o-*>` (outline) untuk nav/aksi, `<x-heroicon-s-*>` (solid)
untuk state aktif. Untuk yang **tak ada padanan**, pakai Heroicon terdekat *atau* SVG inline custom.

| Material | Heroicon | Material | Heroicon |
|---|---|---|---|
| `arrow_forward` | `arrow-right` | `notifications` | `bell` |
| `arrow_back` | `arrow-left` | `add` | `plus` |
| `chevron_right` | `chevron-right` | `close` | `x-mark` |
| `chevron_left` | `chevron-left` | `check` | `check` |
| `expand_more` | `chevron-down` | `check_circle` | `check-circle` |
| `more_vert` | `ellipsis-vertical` | `verified` / `workspace_premium` | `check-badge` |
| `dashboard` | `squares-2x2` | `search` | `magnifying-glass` |
| `analytics` | `chart-bar` | `trending_up` | `arrow-trending-up` |
| `school` | `academic-cap` | `settings` | `cog-6-tooth` |
| `storefront` / `store` | `building-storefront` | `public` | `globe-alt` |
| `location_on` / `map` | `map-pin` / `map` | `logout` | `arrow-right-start-on-rectangle` |
| `person` | `user` | `groups` | `user-group` |
| `favorite` | `heart` | `star` / `stars` | `star` |
| `forum` / `chat` | `chat-bubble-left-right` | `info` | `information-circle` |
| `help` | `question-mark-circle` | `warning` | `exclamation-triangle` |
| `download` | `arrow-down-tray` | `share` | `share` |
| `menu` | `bars-3` | `lightbulb` | `light-bulb` |
| `palette` | `swatch` | `laptop_mac` | `computer-desktop` |
| `table_chart` | `table-cells` | `inventory_2` | `archive-box` |
| `rocket_launch` | `rocket-launch` | `save` | `bookmark` |

**Tanpa padanan Heroicon (pakai SVG inline custom / terdekat):**
`handshake` (kemitraan) · `eco` (SDG hijau → `sparkles`/leaf SVG) · `hub` · `account_tree` ·
`sensors` (IoT → `signal`/`cpu-chip`) · `format_quote` (dekoratif → glyph `"`).

---

## Peta scope front-end

| Layar | Lapisan | Status |
|---|---|---|
| Beranda, Tentang NCH, Kemitraan, 4 Pilar | Publik | **Kita kerjakan** |
| Katalog UMKM (publik) + detail produk | Publik | **Kita kerjakan** |
| Portal warga (Beranda, Belajar, Peringkat, Notifikasi, Profil) | Portal | **Kita kerjakan** |
| Kelola/Katalog UMKM | Admin (Filament) | **Kita kerjakan** (best-effort) |
| Dashboard/Capaian SDGs, Kelola Proyek SDGs | — | Referensi (programmer lain) |
| Manajemen/Kelola Sensor IoT | — | Referensi (programmer lain) |

---

## Filament Admin Panel

Palet **tunggal NCH Deep Blue** untuk semua peran — **tanpa pembedaan warna per-peran**
(super_admin & admin desa identik; identitas peran cukup lewat chip teks di topbar).

```php
// AdminPanelProvider.php — ramp eksplisit agar shade 600 (tombol solid) = #003857.
// Color::hex('#003857') TIDAK dipakai: warna gelap itu ditempatkan Filament di shade ~900
// sehingga tombol (600) jadi biru muda. Beri ramp 50–950 manual, 500=#1b4f72, 600=#003857.
->colors(['primary' => [ /* 50..950, 600 => '#003857' */ ]])
->brandLogo(fn (): Htmlable => view('filament.brand'))   // mark gonjong + wordmark (HTMLable, bukan URL)
->brandLogoHeight('2.25rem')
->font('Plus Jakarta Sans')
```
- Login Filament direstyle via `theme.css` (`.fi-simple-layout` latar gonjong, `.fi-simple-main`
  sudut bulat + bayangan) agar senada login portal.
- View Filament pakai **inline-style** untuk warna/motif (utility custom app.css mis. `gonjong-peak`
  TIDAK ada di build theme.css). Ikon via `@svg('heroicon-…')` (komponen `<x-icon>` dimatikan panel).
- Filament tetap Heroicons. Pixel-perfect tidak dikejar; cukup palet & nuansa selaras.
