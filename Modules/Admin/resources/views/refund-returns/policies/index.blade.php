@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 100%; overflow-x: clip;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-0">Refund, Rewash &amp; Return Policy Settings</h4>
            <div class="small text-muted">Configure versioned rules, deadlines, and public text. Drafts require explicit review before publishing.</div>
        </div>
        <a href="{{ route('admin.refund-returns.policies.history') }}" class="btn btn-sm btn-light border">Policy History</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-3">Create / Update Policy Draft Version</h6>
                    @php
                        $firstPolicy = $policies->first();
                        $latestVer = $firstPolicy?->versions->first();
                        $r = array_replace_recursive($defaultRules, $latestVer?->rules_json ?? []);
                    @endphp
                    <form method="POST" action="{{ route('admin.refund-returns.policies.draft') }}">
                        @csrf
                        <input type="hidden" name="policy_id" value="{{ $firstPolicy?->id }}">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1">Policy Name</label>
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $firstPolicy?->name ?? 'Default Global Policy' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold mb-1">Country (Optional)</label>
                                <input type="text" name="country_code" class="form-control form-control-sm" value="{{ $firstPolicy?->country_code }}" placeholder="e.g. GB">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold mb-1">Store Scope</label>
                                <select name="store_id" class="form-select form-select-sm">
                                    <option value="">All Stores</option>
                                    @foreach($stores as $st)
                                        <option value="{{ $st->id }}" @selected((int) $firstPolicy?->store_id === (int) $st->id)>{{ $st->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="policy_enabled" value="1" class="form-check-input" id="polEn" @checked($r['policy_enabled'])><label class="form-check-label" for="polEn">Policy Enabled</label></div></div>
                            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="customer_cancel_enabled" value="1" class="form-check-input" id="canEn" @checked($r['customer_cancel_enabled'])><label class="form-check-label" for="canEn">Customer Cancellation</label></div></div>
                            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="rewash_enabled" value="1" class="form-check-input" id="rewEn" @checked($r['rewash_enabled'])><label class="form-check-label" for="rewEn">Rewash Enabled</label></div></div>
                            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="claims_enabled" value="1" class="form-check-input" id="clmEn" @checked($r['claims_enabled'])><label class="form-check-label" for="clmEn">Damage/Missing Claims</label></div></div>
                            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="evidence_required" value="1" class="form-check-input" id="evReq" @checked($r['evidence_required'])><label class="form-check-label" for="evReq">Evidence Required</label></div></div>
                            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="manual_review_always_required" value="1" class="form-check-input" id="manRev" @checked($r['manual_review_always_required'])><label class="form-check-label" for="manRev">Manual Admin Review</label></div></div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold mb-1">Cancel Cutoff (hrs)</label>
                                <input type="number" name="cancel_cutoff_hours_before_collection" class="form-control form-control-sm" value="{{ $r['cancel_cutoff_hours_before_collection'] }}" min="0" max="168" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold mb-1">Rewash Deadline (hrs)</label>
                                <input type="number" name="rewash_deadline_hours_after_delivery" class="form-control form-control-sm" value="{{ $r['rewash_deadline_hours_after_delivery'] }}" min="1" max="720" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold mb-1">Damage/Missing Deadline (hrs)</label>
                                <input type="number" name="damage_claim_deadline_hours" class="form-control form-control-sm" value="{{ $r['damage_claim_deadline_hours'] }}" min="1" max="720" required>
                                <input type="hidden" name="missing_claim_deadline_hours" value="{{ $r['missing_claim_deadline_hours'] }}">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold mb-1">Customer-Facing Public Policy Summary</label>
                            <textarea name="public_text" rows="3" class="form-control form-control-sm" required>{{ $latestVer?->public_text }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-1">Legal / Review Note</label>
                            <input type="text" name="legal_disclaimer_note" class="form-control form-control-sm" value="{{ $latestVer?->legal_disclaimer_note }}">
                        </div>

                        <button type="submit" class="btn btn-sm fw-bold" style="background-color: #FFC629; color: #171717;">Save Draft Version</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-3">Policy Versions</h6>
                    @foreach($policies as $pol)
                        <div class="fw-bold mb-2">{{ $pol->name }} ({{ $pol->code }})</div>
                        @foreach($pol->versions as $ver)
                            <div class="p-2 px-3 rounded-3 border mb-2 d-flex justify-content-between align-items-center gap-2 {{ $ver->status === 'published' ? 'bg-success-subtle' : 'bg-light' }}">
                                <div>
                                    <strong>v{{ $ver->version_number }}</strong>
                                    <span class="badge bg-white text-dark border ms-1">{{ ucfirst($ver->status) }}</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">Effective: {{ $ver->effective_at?->format('d M Y, H:i') }}</div>
                                </div>
                                @if($ver->status === 'draft')
                                    <form method="POST" action="{{ route('admin.refund-returns.policies.publish', $ver) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-success fw-bold">Publish</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
