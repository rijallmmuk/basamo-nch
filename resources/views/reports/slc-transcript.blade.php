<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Transkrip Belajar {{ $rekap['warga']->name }}</title>
    <style>
        @page { margin: 18mm 12mm 16mm; }
        body { color:#1e293b; font-family:DejaVu Sans,sans-serif; font-size:8px; margin:0; }
        header { border-bottom:3px solid #d4ac0d; margin-bottom:12px; padding-bottom:9px; }
        .brand { color:#003857; font-size:10px; font-weight:bold; text-transform:uppercase; }
        h1 { color:#003857; font-size:18px; margin:4px 0; }
        .meta { color:#64748b; }
        .identity { background:#f1f5f9; border-left:4px solid #0f766e; margin:10px 0; padding:8px; }
        .kpis { margin:10px 0; width:100%; }
        .kpis td { background:#f8fafc; border:1px solid #cbd5e1; padding:7px; text-align:center; }
        .kpis strong { color:#003857; display:block; font-size:14px; }
        table.data { border-collapse:collapse; table-layout:fixed; width:100%; }
        .data th { background:#0f766e; color:#fff; padding:6px 4px; text-align:left; }
        .data td { border-bottom:1px solid #cbd5e1; padding:5px 4px; vertical-align:top; }
        .data tr { page-break-inside:avoid; }
        .footer { bottom:-10mm; color:#64748b; font-size:7px; left:0; position:fixed; right:0; text-align:center; }
        .page:after { content:counter(page); }
    </style>
</head>
<body>
    <div class="footer">{{ config('app.name') }} · Transkrip dihasilkan sistem · Halaman <span class="page"></span></div>
    <header>
        <div class="brand">{{ config('app.name') }}</div>
        <h1>Transkrip Belajar Warga</h1>
        <div class="meta">Dibuat {{ $generatedAt->translatedFormat('d F Y, H:i') }} WIB</div>
    </header>
    <div class="identity"><strong>{{ $rekap['warga']->name }}</strong> · {{ $rekap['warga']->nagari?->nama ?? 'Nagari tidak diketahui' }}</div>
    @php($s = $rekap['summary'])
    <table class="kpis"><tr>
        <td><strong>{{ $s['modul_selesai'] }}/{{ $s['modul_total'] }}</strong>Modul selesai</td>
        <td><strong>{{ $s['materi_selesai'] }}/{{ $s['materi_total'] }}</strong>Materi selesai</td>
        <td><strong>{{ $s['pretest_dikerjakan'] }}/{{ $s['pretest_total'] }}</strong>Pre-test</td>
        <td><strong>{{ $s['evaluasi_lulus'] }}/{{ $s['evaluasi_total'] }}</strong>Evaluasi lulus</td>
        <td><strong>{{ $s['diskusi_topik'] + $s['diskusi_balasan'] }}</strong>Partisipasi diskusi</td>
    </tr></table>
    <table class="data">
        <thead><tr><th>Pelatihan</th><th>Modul</th><th>Status</th><th>Materi</th><th>Pre-test</th><th>Evaluasi Kegiatan</th><th>Diskusi</th><th>Selesai Pada</th></tr></thead>
        <tbody>
        @forelse($rekap['modules'] as $row)
            <tr>
                <td>{{ $row['module']->pelatihan?->tema?->nama ?? '—' }}</td>
                <td><strong>{{ $row['module']->judul }}</strong></td>
                <td>{{ $row['status']->getLabel() }}</td>
                <td>{{ $row['materi_selesai'] }}/{{ $row['materi_total'] }}</td>
                <td>{{ $row['pretest_attempt']?->nilai ?? 'Belum dikerjakan' }}</td>
                <td>@if($latest = $row['evaluasi_attempts']->first()){{ $latest->nilai }} · {{ $latest->status->getLabel() }}@else Belum dikerjakan @endif</td>
                <td>{{ $row['diskusi_topik'] }} topik · {{ $row['diskusi_balasan'] }} balasan</td>
                <td>{{ $row['completed_at']?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Belum ada modul dalam cakupan pembelajaran.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
