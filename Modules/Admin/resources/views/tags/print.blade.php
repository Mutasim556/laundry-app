@extends('admin::layouts.app')

@section('content')
@push('styles')
<style>
    @media print {
        @page {
            size: {{ $profile->paper_width_mm }}mm {{ $profile->paper_height_mm }}mm {{ $profile->orientation }};
            margin: 0;
        }
        body {
            background: #FFFFFF !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: {{ $profile->background_printing ? 'exact' : 'economy' }} !important;
            print-color-adjust: {{ $profile->background_printing ? 'exact' : 'economy' }} !important;
        }
        .topbar, .left-sidebar, .no-print, .preloader {
            display: none !important;
        }
        #main-wrapper, .page-wrapper, .container-fluid {
            margin: 0 !important;
            padding: 0 !important;
            max-width: none !important;
            width: auto !important;
            background: #FFFFFF !important;
        }
        .fg-print-sheet {
            box-shadow: none !important;
            margin: 0 !important;
            border: none !important;
            page-break-after: always;
        }
        .fg-print-sheet:last-child {
            page-break-after: auto;
        }
    }
    .fg-print-sheet {
        background: #FFFFFF;
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
        margin: 0 auto 24px auto;
        border: 1px solid #E2E8F0;
        box-sizing: border-box;
    }
</style>
@endpush

<div class="page-breadcrumb no-print">
    <div class="row align-items-center">
        <div class="col-md-7">
            <h4 class="page-title mb-1">Print Preview — Fresh Go Hanging Tags</h4>
            <div class="text-muted small">
                Job <code>{{ $printJob->print_reference }}</code> &bull; {{ $tags->count() }} tag(s) &bull; {{ $copies }} copy/copies &bull; Profile: <strong>{{ $profile->name }}</strong>
            </div>
        </div>
        <div class="col-md-5 text-md-end mt-2 mt-md-0 d-flex flex-wrap gap-2 justify-content-md-end">
            <a href="{{ route('admin.tags.generate') }}" class="btn btn-outline-secondary btn-sm">Back to Generate</a>
            <a href="{{ route('admin.tags.print-pdf', request()->query()) }}" class="btn btn-outline-primary btn-sm">Download PDF</a>
            <button type="button" class="btn btn-warning fw-semibold btn-sm" onclick="window.print()">Print Now (100% Scale)</button>
            <button type="button" class="btn btn-success btn-sm" id="btnConfirmPrinted"
                data-url="{{ route('admin.tags.print-jobs.confirm', $printJob) }}">
                Confirm Physically Printed
            </button>
        </div>
    </div>
</div>

<div class="container-fluid">
    @if(!empty($warnings))
        <div class="alert alert-warning no-print small">
            <strong>Reprint Warning:</strong>
            <ul class="mb-0 mt-1">
                @foreach($warnings as $w)
                    <li>{{ $w }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3 no-print">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.tags.print-preview') }}" class="row g-2 align-items-end">
                @if($batch)
                    <input type="hidden" name="batch_id" value="{{ $batch->id }}">
                @endif
                @if(request()->filled('tag_ids'))
                    @foreach((array) request()->input('tag_ids') as $tid)
                        <input type="hidden" name="tag_ids[]" value="{{ $tid }}">
                    @endforeach
                @endif
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Printer Profile</label>
                    <select name="profile_id" class="form-select form-select-sm">
                        @foreach($profiles as $p)
                            <option value="{{ $p->id }}" @selected($p->id === $profile->id)>
                                {{ $p->name }} ({{ $p->tags_per_row }}×{{ $p->tags_per_column }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Copies / Tag</label>
                    <input type="number" name="copies" min="1" max="5" value="{{ $copies }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Start Offset (Skip Slots)</label>
                    <input type="number" name="start_offset" min="0" max="40" value="{{ $startOffset }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Layout</button>
                    <span class="small text-muted align-self-center">Print scale: <strong>100% (Actual Size)</strong>, disable headers/footers.</span>
                </div>
            </form>
        </div>
    </div>

    <div class="overflow-auto py-2">
        @php
            $sheetW = $profile->orientation === 'landscape' ? $profile->paper_height_mm : $profile->paper_width_mm;
            $sheetH = $profile->orientation === 'landscape' ? $profile->paper_width_mm : $profile->paper_height_mm;
        @endphp
        @foreach($pages as $pageSlots)
            <div class="fg-print-sheet" style="
                width: {{ $sheetW }}mm;
                min-height: {{ $sheetH }}mm;
                padding: {{ $profile->margin_top_mm }}mm {{ $profile->margin_right_mm }}mm {{ $profile->margin_bottom_mm }}mm {{ $profile->margin_left_mm }}mm;
            ">
                <div style="display: grid; grid-template-columns: repeat({{ $profile->tags_per_row }}, {{ $profile->tag_width_mm }}mm); column-gap: {{ $profile->gap_horizontal_mm }}mm; row-gap: {{ $profile->gap_vertical_mm }}mm;">
                    @foreach($pageSlots as $slot)
                        @include('admin::tags.partials.hanging-tag-card', ['slot' => $slot, 'profile' => $profile])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('btnConfirmPrinted')?.addEventListener('click', async function () {
    const btn = this;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Confirming...';
    try {
        const res = await fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (res.ok && data.success) {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
            btn.textContent = 'Print Confirmed ✓';
        } else {
            alert(data.message || 'Unable to confirm print job.');
            btn.disabled = false;
            btn.textContent = orig;
        }
    } catch (e) {
        alert('Network error confirming print job.');
        btn.disabled = false;
        btn.textContent = orig;
    }
});
</script>
@endpush
