@php
    $u = auth()->user();
    $super = (bool) $u?->isSuperAdmin();
    // Inline-style agar pasti tampil tanpa bergantung pada kelas Tailwind hasil build Filament.
    $bg = $super ? '#e0e7ff' : '#ccfbf1';   // indigo-100 / teal-100
    $fg = $super ? '#3730a3' : '#115e59';   // indigo-800 / teal-800
    $dot = $super ? '#6366f1' : '#14b8a6';  // indigo-500 / teal-500
    $label = $super ? 'Super Admin' : 'Admin · '.($u?->desa?->nama_lengkap ?? 'Desa');
@endphp

@if ($u)
    <span title="{{ $label }}"
        style="display:inline-flex;align-items:center;gap:.4rem;margin-right:.75rem;padding:.25rem .7rem;
               border-radius:9999px;font-size:.72rem;font-weight:600;line-height:1;white-space:nowrap;
               max-width:16rem;overflow:hidden;text-overflow:ellipsis;background:{{ $bg }};color:{{ $fg }};">
        <span style="flex:none;width:.45rem;height:.45rem;border-radius:9999px;background:{{ $dot }};"></span>
        <span style="overflow:hidden;text-overflow:ellipsis;">{{ $label }}</span>
    </span>
@endif
