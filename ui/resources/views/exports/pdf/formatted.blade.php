<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: '{{ \Mrj\Foundation\Services\PDFService::defaultFont() }}', sans-serif; font-size: 11px; }
        .letterhead { text-align: center; margin-bottom: 6px; }
        .letterhead img { max-height: 46px; margin-bottom: 4px; }
        .company { font-size: 15px; font-weight: bold; }
        .title { text-align: center; font-size: 14px; font-weight: bold; margin: 6px 0 2px; }
        .subtitle { text-align: center; font-size: 11px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .doc-header td { border: none; padding: 2px 6px; }
        .doc-header .lbl { font-weight: bold; width: 160px; }
        .section-title { background:#f2f2f2; font-weight:bold; padding:5px; border:1px solid #999; margin-top:12px; }
        .fields td { border: 1px solid #ccc; }
        .fields .lbl { font-weight: bold; width: 220px; background:#fafafa; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 9px; }
    </style>
</head>
<body>
    @if ($letterhead)
        <div class="letterhead">
            {{-- mPDF ignores max-height from the stylesheet, so the height must be set on the image itself. --}}
            @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ __('foundation::foundation.exports.logo_alt') }}" style="height: 46px; width: auto;"><br>@endif
            <span class="company">{{ $companyName }}</span>
        </div>
    @endif

    <div class="title">{{ $title }}</div>
    @foreach ($subtitles as $subtitle)
        <p class="subtitle">{{ $subtitle }}</p>
    @endforeach

    @if ($kind === 'sections')
        @foreach ($sections as $section)
            <div class="section-title">{{ $section['title'] ?? '' }}</div>
            @if (($section['type'] ?? 'fields') === 'table')
                <table>
                    <thead>
                        <tr>@foreach ($section['columns'] ?? [] as $h)<th>{{ $h }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach ($section['rows'] ?? [] as $r)
                            <tr>@foreach ($r as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <table class="fields">
                    @foreach ($section['fields'] ?? [] as $label => $value)
                        <tr><td class="lbl">{{ $label }}</td><td>{{ $value }}</td></tr>
                    @endforeach
                </table>
            @endif
        @endforeach
    @else
        @if ($kind === 'document' && ! empty($documentHeader))
            <table class="doc-header">
                @foreach ($documentHeader as $label => $value)
                    <tr><td class="lbl">{{ $label }}:</td><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        @endif

        <table>
            <thead>
                @if ($headerGroups)
                    <tr>
                        @foreach ($headerGroups['leading'] as $label)<th rowspan="2">{{ $label }}</th>@endforeach
                        @foreach ($headerGroups['groups'] as $group)<th colspan="{{ count($group['children']) }}">{{ $group['label'] }}</th>@endforeach
                    </tr>
                    <tr>
                        @foreach ($headerGroups['groups'] as $group)
                            @foreach ($group['children'] as $child)<th>{{ $child['label'] }}</th>@endforeach
                        @endforeach
                    </tr>
                @else
                    <tr>@foreach ($columns as $header)<th>{{ $header }}</th>@endforeach</tr>
                @endif
            </thead>
            <tbody>
                @foreach ($data as $row)
                    <tr @if (! empty($row['__row_type'])) style="font-weight:bold;background:#f7f7f7;" @endif>
                        @foreach ($columns as $key)<td>{{ $row[$key] ?? '' }}</td>@endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">{!! __('foundation::foundation.exports.footer', ['date' => now()->format('d-M-Y h:i:s A')]) !!}</div>
</body>
</html>
