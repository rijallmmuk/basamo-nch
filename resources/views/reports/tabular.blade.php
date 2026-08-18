<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        @page { margin: 20mm 12mm 17mm; }
        * { box-sizing: border-box; }
        body { color: #1e293b; font-family: DejaVu Sans, sans-serif; font-size: 8px; margin: 0; }
        .brand { border-bottom: 3px solid #d4ac0d; margin-bottom: 13px; padding-bottom: 9px; }
        .brand-name { color: #003857; font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
        h1 { color: #003857; font-size: 17px; margin: 4px 0 2px; }
        .meta { color: #64748b; font-size: 8px; }
        .summary { background: #f1f5f9; border-left: 3px solid #0f766e; margin: 10px 0; padding: 7px 9px; }
        .summary span { display: inline-block; margin-right: 20px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th { background: #0f766e; color: white; font-size: 7px; padding: 6px 4px; text-align: left; }
        td { border-bottom: 1px solid #cbd5e1; padding: 5px 4px; vertical-align: top; word-wrap: break-word; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tr { page-break-inside: avoid; }
        .empty { color: #64748b; font-style: italic; padding: 18px; text-align: center; }
        .footer { bottom: -11mm; color: #64748b; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">{{ config('app.name') }} · Dokumen dibuat sistem · Halaman <span class="page-number"></span></div>
    <header class="brand">
        <div class="brand-name">{{ config('app.name') }}</div>
        <h1>{{ $report->title }}</h1>
        <div class="meta">Dibuat {{ $generatedAt->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i') }} WIB oleh {{ $actor->name }}</div>
    </header>

    <div class="summary">
        <span><strong>Jumlah data:</strong> {{ number_format($rows->count(), 0, ',', '.') }}</span>
        @foreach($report->metadata as $label => $value)
            <span><strong>{{ $label }}:</strong> {{ $value }}</span>
        @endforeach
    </div>

    <table>
        <thead><tr>
            @foreach($report->columns as $column)
                <th>{{ $column->label }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value instanceof \BackedEnum ? $value->value : (is_array($value) ? implode(', ', $value) : $value) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($report->columns) }}">Tidak ada data yang sesuai dengan filter dan cakupan akses.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
