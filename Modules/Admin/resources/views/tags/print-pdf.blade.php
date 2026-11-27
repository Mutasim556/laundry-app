<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fresh Go Laundry Tags — {{ $printJob->print_reference }}</title>
    <style>
        @page {
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0F172A;
        }
        .pdf-page {
            padding: {{ $profile->margin_top_mm }}mm {{ $profile->margin_right_mm }}mm {{ $profile->margin_bottom_mm }}mm {{ $profile->margin_left_mm }}mm;
            page-break-after: always;
        }
        .pdf-page:last-child {
            page-break-after: auto;
        }
        .pdf-grid-table {
            border-collapse: separate;
            border-spacing: {{ $profile->gap_horizontal_mm }}mm {{ $profile->gap_vertical_mm }}mm;
            margin: -{{ $profile->gap_vertical_mm }}mm -{{ $profile->gap_horizontal_mm }}mm;
        }
        .pdf-grid-cell {
            padding: 0;
            vertical-align: top;
        }
    </style>
</head>
<body>
@foreach($pages as $pageSlots)
    @php
        $rows = array_chunk($pageSlots, max(1, (int) $profile->tags_per_row));
    @endphp
    <div class="pdf-page">
        <table class="pdf-grid-table">
            @foreach($rows as $rowSlots)
                <tr>
                    @foreach($rowSlots as $slot)
                        <td class="pdf-grid-cell">
                            @include('admin::tags.partials.hanging-tag-card', ['slot' => $slot, 'profile' => $profile, 'forPdf' => true])
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
