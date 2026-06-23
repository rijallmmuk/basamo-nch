@php
    $u = auth()->user();
    $super = (bool) $u?->isSuperAdmin();
    // Palet NCH tunggal untuk semua peran (tanpa pembedaan warna tema).
    // Identitas peran cukup dari teks label + nama desa.
    $label = $super ? 'Super Admin' : 'Admin · '.($u?->desa?->nama_lengkap ?? 'Desa');
@endphp

@if ($u)
    <span title="{{ $label }}"
        style="display:inline-flex;align-items:center;gap:.4rem;margin-right:.75rem;padding:.25rem .7rem;
               border-radius:9999px;font-size:.72rem;font-weight:600;line-height:1;white-space:nowrap;
               max-width:16rem;overflow:hidden;text-overflow:ellipsis;background:#cce5ff;color:#003857;">
        <span style="flex:none;width:.45rem;height:.45rem;border-radius:9999px;background:#735c00;"></span>
        <span style="overflow:hidden;text-overflow:ellipsis;">{{ $label }}</span>
    </span>
@endif
