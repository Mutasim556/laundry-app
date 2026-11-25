@php
    $elig = $claimEligibility ?? app(\App\Services\PolicyResolutionService::class)->getCustomerEligibility($booking);
    $rules = $elig['rules'] ?? [];
    $existingClaims = $booking->claims ?? collect();
    $opItems = $booking->operationalItems->where('is_removed', false);
@endphp

<div class="mt-3" id="orderNeedHelpSection">
    <div class="card border-0 shadow-sm" style="border-radius: 16px; border: 1px solid #FDE68A !important; background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <h6 class="fw-bold mb-0" style="color: #171717;">Need Help With This Order?</h6>
                    <div class="small text-muted">{{ $elig['public_text'] ?? '' }}</div>
                </div>
                @if($elig['policy_version'])
                    <span class="badge bg-light text-dark border">Policy v{{ $elig['policy_version']->version_number }}</span>
                @endif
            </div>

            <div class="d-flex flex-wrap gap-2 mt-2">
                @if(!empty($elig['can_request_cancellation']))
                    <button type="button" class="btn btn-sm btn-outline-danger fw-semibold js-open-claim-modal" data-claim-type="cancellation_refund" data-title="Request Cancellation & Refund">
                        Request Cancellation
                    </button>
                @endif
                @if(!empty($elig['can_request_refund']))
                    <button type="button" class="btn btn-sm fw-semibold text-dark js-open-claim-modal" style="background-color: #FFC629;" data-claim-type="cancellation_refund" data-title="Request Refund">
                        Request Refund
                    </button>
                @endif
                @if(!empty($elig['can_request_rewash']))
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold js-open-claim-modal" data-claim-type="rewash" data-title="Request Free Rewash / Correction">
                        Request Rewash
                    </button>
                @endif
                @if(!empty($elig['can_report_claim']))
                    <button type="button" class="btn btn-sm btn-outline-dark fw-semibold js-open-claim-modal" data-claim-type="damage_missing" data-title="Report Damaged / Missing / Wrong Item">
                        Report Damaged / Missing Item
                    </button>
                @endif
                @if(empty($elig['has_any_self_service']))
                    <span class="small text-muted">Self-service claims are not available at this stage. Please contact customer support with booking reference <strong>{{ $booking->reference }}</strong> for assistance.</span>
                @endif
            </div>

            @if($existingClaims->isNotEmpty())
                <div class="mt-3 pt-3 border-top">
                    <div class="small fw-bold mb-2 text-dark">Your Submitted Requests &amp; Claims</div>
                    <div class="d-flex flex-column gap-2" id="custClaimsList">
                        @foreach($existingClaims as $clm)
                            <div class="p-2 px-3 rounded-3 bg-white border small d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <strong class="text-dark">{{ $clm->case_reference }}</strong>
                                    <span class="text-muted">&bull; {{ $clm->type_label }}</span>
                                    <div class="text-muted" style="font-size: 0.78rem;">{{ \Illuminate\Support\Str::limit($clm->description, 90) }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary-subtle text-primary">{{ $clm->status_label }}</span>
                                    @if($clm->approved_amount_minor > 0)
                                        <div class="fw-bold text-success mt-1" style="font-size: 0.78rem;">
                                            Approved: {{ $currencyService->formatMinor((int) $clm->approved_amount_minor, $clm->currency) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div id="custClaimsList" class="mt-2"></div>
            @endif
        </div>
    </div>
</div>

<!-- Bootstrap 5 AJAX Claim / Refund / Rewash Modal -->
<div class="modal fade" id="customerClaimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form id="customerClaimForm" class="modal-content border-0 shadow" style="border-radius: 16px;" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="claim_type" id="custClaimTypeInput" value="cancellation_refund">
            <div class="modal-header border-0 pb-1" style="background-color: #FFF8E5; border-radius: 16px 16px 0 0;">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="custClaimModalTitle">Submit Request</h6>
                    <div class="small text-muted">Booking: {{ $booking->reference }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body small">
                <div id="custClaimErrors" class="alert alert-danger py-2 px-3 d-none mb-2"></div>
                <div id="custClaimSuccess" class="alert alert-success py-2 px-3 d-none mb-2"></div>

                <div class="mb-2">
                    <label class="form-label fw-semibold mb-1">Reason <span class="text-danger">*</span></label>
                    <select name="reason_code" id="custClaimReasonSelect" class="form-select form-select-sm" required></select>
                </div>

                @if($opItems->isNotEmpty())
                    <div class="mb-2" id="custClaimItemsWrap">
                        <label class="form-label fw-semibold mb-1">Affected Service / Item(s)</label>
                        <select name="operational_item_ids[]" class="form-select form-select-sm" multiple size="3">
                            @foreach($opItems as $op)
                                <option value="{{ $op->id }}">
                                    {{ $op->service_name }}{{ $op->item_name ? ' (' . $op->item_name . ')' : '' }} &times; {{ (int) $op->quantity }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-muted" style="font-size: 0.72rem;">Hold Ctrl/Cmd to select multiple items if applicable.</div>
                    </div>
                @endif

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold mb-1">Affected Qty</label>
                        <input type="number" name="affected_quantity" min="1" max="100" value="1" class="form-control form-control-sm">
                    </div>
                    <div class="col-6" id="custClaimAmountWrap">
                        <label class="form-label fw-semibold mb-1">Requested Amount ({{ $booking->currency }})</label>
                        <input type="number" step="0.01" min="0" name="requested_amount" class="form-control form-control-sm" placeholder="Optional">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold mb-1">Preferred Resolution</label>
                    <select name="preferred_resolution" class="form-select form-select-sm">
                        <option value="full_refund">Full Refund</option>
                        <option value="partial_refund">Partial Refund</option>
                        <option value="free_rewash">Free Rewash / Re-service</option>
                        <option value="item_replacement_credit">Compensation / Replacement Review</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold mb-1">Details / Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="3" class="form-control form-control-sm" required minlength="5" maxlength="2000" placeholder="Describe the issue clearly..."></textarea>
                </div>

                <div id="custClaimEvidenceWrap">
                    <label class="form-label fw-semibold mb-1">Supporting Photos / Evidence (JPG, PNG, WEBP, PDF up to 5MB)</label>
                    <input type="file" name="evidence[]" class="form-control form-control-sm" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="custClaimSubmitBtn" class="btn btn-sm fw-bold text-dark" style="background-color: #FFC629;">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const reasonsByType = {
        cancellation_refund: @json($rules['cancellation_reasons'] ?? []),
        rewash: @json($rules['rewash_allowed_reasons'] ?? []),
        damage_missing: @json($rules['claim_reasons'] ?? [])
    };

    const modalEl = document.getElementById('customerClaimModal');
    const formEl = document.getElementById('customerClaimForm');
    const reasonSel = document.getElementById('custClaimReasonSelect');
    const typeInput = document.getElementById('custClaimTypeInput');
    const titleEl = document.getElementById('custClaimModalTitle');
    const errEl = document.getElementById('custClaimErrors');
    const okEl = document.getElementById('custClaimSuccess');
    const submitBtn = document.getElementById('custClaimSubmitBtn');

    document.querySelectorAll('.js-open-claim-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const type = btn.getAttribute('data-claim-type');
            const title = btn.getAttribute('data-title');
            typeInput.value = type;
            titleEl.textContent = title || 'Submit Request';
            errEl.classList.add('d-none');
            okEl.classList.add('d-none');

            reasonSel.innerHTML = '';
            const map = reasonsByType[type] || {};
            Object.entries(map).forEach(([k, label]) => {
                const opt = document.createElement('option');
                opt.value = k;
                opt.textContent = label;
                reasonSel.appendChild(opt);
            });

            if (window.bootstrap && modalEl) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });

    if (formEl) {
        formEl.addEventListener('submit', async function (e) {
            e.preventDefault();
            errEl.classList.add('d-none');
            okEl.classList.add('d-none');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const fd = new FormData(formEl);
                const res = await fetch('{{ route('orders.claims.store', $booking->reference) }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Unable to submit request.');
                }

                okEl.textContent = data.message || 'Request submitted.';
                okEl.classList.remove('d-none');
                setTimeout(() => window.location.reload(), 900);
            } catch (err) {
                errEl.textContent = err.message || 'Submission failed.';
                errEl.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Request';
            }
        });
    }
});
</script>
