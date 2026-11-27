@extends('admin::layouts.app')

@section('title', 'Problem & Issue Tracker - Reports')

@section('content')
<div class="container-fluid py-3">
    @include('admin::reports.partials.header', [
        'reportTitle' => 'Problem & Issue Tracker',
        'reportSubtitle' => 'Identify cancelled bookings, rejected store handovers, QR tag verification mismatches, and lost/damaged tags.',
    ])

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">Cancelled Bookings</div><div class="fs-4 fw-bold text-danger">{{ $cancelledBookings->count() }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">Rejected Store Handovers</div><div class="fs-4 fw-bold text-warning">{{ $rejectedHandovers->count() }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">QR Tag Issues (Period)</div><div class="fs-4 fw-bold text-danger">{{ $tagIssues->count() }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">Lost / Damaged / Voided Tags</div><div class="fs-4 fw-bold text-dark">{{ $lostDamagedCount }}</div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold small">Rejected Store Handovers</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle small mb-0">
                            <thead class="table-light"><tr><th>Booking</th><th>Store</th><th>Reason</th><th>When</th></tr></thead>
                            <tbody>
                                @forelse($rejectedHandovers as $rh)
                                    <tr>
                                        <td><code>{{ $rh->booking?->reference ?? '—' }}</code></td>
                                        <td>{{ $rh->store?->name ?? '—' }}</td>
                                        <td class="text-danger">{{ $rh->rejection_reason }}</td>
                                        <td class="text-nowrap">{{ $rh->rejected_at?->format('d M, H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No rejected handovers in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold small">QR Tag Mismatches, Lost &amp; Damaged Events</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle small mb-0">
                            <thead class="table-light"><tr><th>Tag</th><th>Event</th><th>Notes</th><th>When</th></tr></thead>
                            <tbody>
                                @forelse($tagIssues as $ti)
                                    <tr>
                                        <td><code>{{ $ti->tag?->tag_code ?? '—' }}</code></td>
                                        <td><span class="badge bg-danger-subtle text-danger">{{ $ti->event_type }}</span></td>
                                        <td>{{ $ti->reason }}</td>
                                        <td class="text-nowrap">{{ $ti->occurred_at?->format('d M, H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No QR tag issues in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
