@extends('admin::layouts.app')

@section('title', 'Generate & Print Laundry Tags')

@include('admin::tags.partials.shared-styles')

@section('content')
<div class="container-fluid py-3 tm-anim">
    <!-- Page Header -->
    <div class="tm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">
                        Tag Management
                    </span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">
                        QR Minting &amp; Print
                    </span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #171717;">Generate &amp; Print Laundry Tags</h4>
                <div class="small text-muted">
                    Permanently mint unique sequential Fresh Go QR tags and prepare print-ready sheets or PDFs.
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.tags.index') }}" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="layers" style="width: 14px; height: 14px;" class="me-1"></i>Tag Inventory ({{ $summary['available'] }})
                </a>
                <a href="{{ route('admin.tags.printer-settings.index') }}" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="printer" style="width: 14px; height: 14px;" class="me-1"></i>Printer Settings
                </a>
            </div>
        </div>
    </div>

    @include('admin::tags.partials.nav-pills')

    <!-- Compact KPI Cards -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-4">
            <div class="tm-kpi-card">
                <div class="tm-kpi-bar" style="background: #171717;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Total Minted</div>
                        <div class="fs-4 fw-bold text-dark" id="sumTotal">{{ $summary['total'] }}</div>
                    </div>
                    <span class="tm-kpi-icon" style="background: #F1F5F9; color: #171717;">
                        <i data-feather="hash" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="tm-kpi-card">
                <div class="tm-kpi-bar" style="background: #16A34A;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Available Stock</div>
                        <div class="fs-4 fw-bold text-success" id="sumAvailable">{{ $summary['available'] }}</div>
                    </div>
                    <span class="tm-kpi-icon" style="background: #ECFDF5; color: #16A34A;">
                        <i data-feather="check-circle" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="tm-kpi-card">
                <div class="tm-kpi-bar" style="background: #3B82F6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Allocated to DP</div>
                        <div class="fs-4 fw-bold text-primary" id="sumAllocated">{{ $summary['allocated'] }}</div>
                    </div>
                    <span class="tm-kpi-icon" style="background: #EFF6FF; color: #2563EB;">
                        <i data-feather="user-check" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- LEFT: Tag Generation Form -->
        <div class="col-12 col-lg-5">
            <div class="tm-section-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span class="fw-bold small text-dark"><i data-feather="plus-square" style="width: 15px; height: 15px;" class="me-1 text-warning"></i>Generate New Tag Batch</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.7rem;">Max {{ \App\Services\LaundryTagService::MAX_GENERATION_QUANTITY }} / batch</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div id="genAlertBox"></div>
                    <form id="tagGenerateForm" method="POST" action="{{ route('admin.tags.generate.store') }}">
                        @csrf
                        <input type="hidden" name="idempotency_key" id="genIdempotencyKey" value="{{ \Illuminate\Support\Str::uuid() }}">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Quick Quantity Presets</label>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach([1, 8, 10, 30, 40, 50, 100] as $presetQty)
                                    <button type="button" class="btn btn-outline-secondary btn-sm fg-qty-preset" data-qty="{{ $presetQty }}">{{ $presetQty }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="genQuantity" class="form-label fw-semibold small">Custom Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="genQuantity" name="quantity" min="1" max="{{ \App\Services\LaundryTagService::MAX_GENERATION_QUANTITY }}" value="10" required>
                        </div>

                        <div class="mb-3">
                            <label for="genProfileId" class="form-label fw-semibold small">Printer / Paper Layout Profile</label>
                            <select name="printer_profile_id" id="genProfileId" class="form-select form-select-sm">
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile->id }}" @selected($profile->is_default)>
                                        {{ $profile->name }} ({{ $profile->tags_per_row }}×{{ $profile->tags_per_column }} / page)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="genLabel" class="form-label fw-semibold small">Batch Label / Notes (Optional)</label>
                            <input type="text" class="form-control form-control-sm" id="genLabel" name="label" maxlength="120" placeholder="e.g. Peak Weekend Stock">
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_reusable" value="1" id="genReusable">
                            <label class="form-check-label small" for="genReusable">
                                Mark tags as reusable (allow Admin recovery after completion)
                            </label>
                        </div>

                        <button type="submit" id="btnSubmitGenerate" class="btn btn-warning btn-sm w-100 fw-semibold py-2 shadow-sm">
                            <i data-feather="zap" style="width: 14px; height: 14px;" class="me-1"></i>Generate Permanent Tags
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: Tag Preview & Print Readiness -->
        <div class="col-12 col-lg-7">
            <div class="tm-section-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span class="fw-bold small text-dark"><i data-feather="eye" style="width: 15px; height: 15px;" class="me-1 text-primary"></i>Fresh Go Tag Preview &amp; Print Readiness</span>
                    @if($defaultProfile)
                        <span class="small text-muted">{{ $defaultProfile->tag_width_mm }}×{{ $defaultProfile->tag_height_mm }}mm</span>
                    @endif
                </div>
                <div class="card-body p-3 p-md-4">
                    <div id="latestBatchBanner" class="p-3 rounded-3 bg-success-subtle border border-success-subtle mb-3 d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-bold text-success small" id="latestBatchTitle">Batch Ready</div>
                                <div class="small text-muted" id="latestBatchRange"></div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" id="latestPrintPreviewLink" class="btn btn-warning btn-sm fw-semibold">Open Print Preview</a>
                                <a href="#" id="latestPrintPdfLink" class="btn btn-outline-primary btn-sm bg-white">Download PDF</a>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-sm-5 text-center">
                            <div class="p-3 rounded-3 bg-light border d-inline-block">
                                @if($defaultProfile)
                                    <div style="transform: scale(0.85); transform-origin: top center; margin-bottom: -15mm;">
                                        @include('admin::tags.partials.hanging-tag-card', [
                                            'slot' => ['tag_code' => 'FG-T-000001', 'qr_data_uri' => $sampleQrDataUri],
                                            'profile' => $defaultProfile,
                                        ])
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <h6 class="fw-bold small text-uppercase text-muted mb-2">Recent Print Jobs</h6>
                            <div class="tm-table-scroll border rounded-3">
                                <table class="table table-sm align-middle small mb-0" style="min-width: 380px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Print Ref</th>
                                            <th>Tags</th>
                                            <th>Status</th>
                                            <th>When</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentPrintJobs as $pj)
                                            <tr>
                                                <td><code>{{ $pj->print_reference }}</code> @if($pj->is_reprint)<span class="badge bg-info-subtle text-info">Reprint</span>@endif</td>
                                                <td>{{ $pj->tag_count }} ({{ $pj->copies }}×)</td>
                                                <td><span class="badge bg-{{ $pj->status === 'confirmed' ? 'success' : ($pj->status === 'requested' ? 'primary' : 'secondary') }}">{{ ucfirst($pj->status) }}</span></td>
                                                <td class="text-nowrap">{{ $pj->prepared_at?->format('d M, H:i') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted text-center py-3">No print jobs recorded yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tag Batches -->
    <div class="tm-section-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold small text-dark"><i data-feather="package" style="width: 15px; height: 15px;" class="me-1 text-primary"></i>Recent Tag Batches (Reprint Any Existing Batch Safely)</span>
        </div>
        <div class="card-body p-0" id="recentBatchesContainer">
            @include('admin::tags.partials.recent-batches', compact('recentBatches'))
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.fg-qty-preset').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('genQuantity').value = btn.dataset.qty;
    });
});

document.getElementById('tagGenerateForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('btnSubmitGenerate');
    const alertBox = document.getElementById('genAlertBox');
    if (btn.disabled) return;
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating Tags...';
    alertBox.innerHTML = '';

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: new FormData(form),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            alertBox.innerHTML = `<div class="alert alert-success small py-2">${data.message}</div>`;
            document.getElementById('latestBatchBanner').classList.remove('d-none');
            document.getElementById('latestBatchTitle').textContent = `Generated ${data.batch.quantity} Tags (${data.batch.batch_reference})`;
            document.getElementById('latestBatchRange').textContent = `Tag Range: ${data.batch.code_from} to ${data.batch.code_to}`;
            document.getElementById('latestPrintPreviewLink').href = data.batch.print_preview_url;
            document.getElementById('latestPrintPdfLink').href = data.batch.print_pdf_url;
            if (data.recent_batches_html) {
                document.getElementById('recentBatchesContainer').innerHTML = data.recent_batches_html;
                if (window.feather) feather.replace();
            }
            const qty = parseInt(data.batch.quantity || 0, 10);
            document.getElementById('sumTotal').textContent = parseInt(document.getElementById('sumTotal').textContent, 10) + qty;
            document.getElementById('sumAvailable').textContent = parseInt(document.getElementById('sumAvailable').textContent, 10) + qty;
            document.getElementById('genIdempotencyKey').value = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));
            if (window.tmToast) window.tmToast(data.message, 'success');
        } else {
            alertBox.innerHTML = `<div class="alert alert-danger small py-2">${data.message || 'Generation failed.'}</div>`;
            if (window.tmToast) window.tmToast(data.message || 'Generation failed.', 'error');
        }
    } catch (err) {
        alertBox.innerHTML = `<div class="alert alert-danger small py-2">Network error while generating tags.</div>`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (window.feather) feather.replace();
    }
});
</script>
@endpush
