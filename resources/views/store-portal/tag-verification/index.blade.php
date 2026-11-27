@extends('store-portal.layouts.app')

@section('title', 'QR Tag Verification')

@section('content')
<div class="sp-card p-3 p-md-4 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A; border-left: 5px solid #FFC629;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="badge rounded-pill px-2 py-1 fw-bold mb-1" style="background: #FFC629; color: #171717; font-size: 0.72rem;">Store Operations</span>
            <h4 class="fw-bold mb-1" style="color: #171717;">QR Tag Verification</h4>
            <div class="small text-muted">Scan or enter a laundry tag to view its booking and update processing status.</div>
        </div>
        <div class="small text-muted">
            Store: <strong class="text-dark">{{ $store->name }}</strong>
        </div>
    </div>
</div>

<!-- Scanner & Manual Lookup Card -->
<div class="sp-card p-3 p-md-4 mb-3">
    <div id="spTvAlertBox" class="alert d-none small py-2 mb-3"></div>

    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-7">
            <form id="spTvVerifyForm" class="row g-2 align-items-end">
                <input type="hidden" name="method" id="spTvMethod" value="manual">
                <div class="col-12 col-sm-7">
                    <label for="spTvTagCode" class="form-label small fw-semibold mb-1">Tag Code</label>
                    <input type="text" name="tag_code" id="spTvTagCode" value="{{ $prefillCode }}"
                           class="form-control font-monospace text-uppercase" placeholder="e.g. FG-T-000125" required autocomplete="off">
                </div>
                <div class="col-6 col-sm-3 d-grid">
                    <button type="submit" id="spTvVerifyBtn" class="btn btn-warning fw-semibold">Verify Tag</button>
                </div>
                <div class="col-6 col-sm-2 d-grid">
                    <button type="button" id="spTvResetBtn" class="btn btn-outline-secondary">Clear</button>
                </div>
            </form>
        </div>
        <div class="col-12 col-md-5 text-md-end">
            <button type="button" id="spTvToggleScanBtn" class="btn btn-dark fw-semibold w-100 w-md-auto">
                <i class="mdi mdi-qrcode-scan me-1"></i>Scan QR Code
            </button>
        </div>
    </div>

    <div id="spTvScannerPanel" class="d-none mt-3 p-3 rounded-3 bg-light border">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-dark">Camera QR Scanner</span>
            <button type="button" id="spTvCloseScanBtn" class="btn btn-sm btn-outline-danger">Close Scanner</button>
        </div>
        <div id="spTvQrReader" class="rounded-3 overflow-hidden border bg-dark text-white mx-auto" style="max-width: 380px; min-height: 220px;"></div>
        <div id="spTvScanStatus" class="small text-muted text-center mt-2">Point rear camera at the Fresh Go hanging tag QR code.</div>
    </div>
</div>

<!-- Verified Booking & Tag List Result -->
<div id="spTvResultContainer">
    <div class="sp-card p-4 text-center text-muted small">
        <i class="mdi mdi-qrcode-scan fs-2 d-block mb-2 text-warning"></i>
        Scan a Fresh Go QR laundry tag or enter its code (e.g. <code>FG-T-000001</code>) above to load booking details and mark bags completed.
    </div>
</div>

<!-- Mark Tag Completed Confirmation Modal -->
<div class="modal fade" id="spTvCompleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="spTvCompleteForm" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-light border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0" id="spTvCompModalTitle">Confirm Bag Processing Completed</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 small">
                <div id="spTvCompleteError" class="alert alert-danger d-none py-2 mb-2"></div>
                <div id="spTvFinalWarningBox" class="alert alert-warning py-2 mb-3 d-none">
                    <div class="fw-bold mb-1"><i class="mdi mdi-alert-outline me-1"></i>Last Pending Laundry Tag</div>
                    <div>Are you sure you want to verify this tag? This is the last pending laundry tag for this booking. Verifying this tag will automatically mark the booking as <strong>Store Complete</strong>.</div>
                </div>
                <p class="mb-2" id="spTvNormalPrompt">Verify tag <strong id="mdlCompTagCodeInline"></strong>?</p>
                <div class="p-2 rounded-3 bg-light border mb-3">
                    <div><strong>Booking Reference:</strong> <span id="mdlCompBookingRef"></span></div>
                    <div><strong>Tag Code:</strong> <code id="mdlCompTagCode" class="fw-bold text-primary"></code> (<span id="mdlCompBag"></span>)</div>
                    <div><strong>Progress:</strong> <span id="mdlCompProgress"></span></div>
                    <div><strong>Current Status:</strong> <span id="mdlCompStatus"></span></div>
                </div>
                <label class="form-label fw-semibold mb-1">Processing Notes (Optional)</label>
                <input type="text" name="notes" id="mdlCompNotes" class="form-control form-control-sm" maxlength="500" placeholder="Optional note...">
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="spTvCompleteSubmitBtn" class="btn btn-sm btn-success fw-semibold">Confirm Tag Completed</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const verifyForm = document.getElementById('spTvVerifyForm');
    const codeInput = document.getElementById('spTvTagCode');
    const methodInput = document.getElementById('spTvMethod');
    const alertBox = document.getElementById('spTvAlertBox');
    const resultBox = document.getElementById('spTvResultContainer');
    const scannerPanel = document.getElementById('spTvScannerPanel');
    const scanStatus = document.getElementById('spTvScanStatus');
    let qrScanner = null;
    let isScanning = false;
    let isSubmitting = false;

    function showAlert(msg, type = 'success') {
        alertBox.className = `alert alert-${type === 'error' ? 'danger' : (type === 'warning' ? 'warning' : 'success')} small py-2 mb-3`;
        alertBox.textContent = msg;
        alertBox.classList.remove('d-none');
    }

    async function stopScanner() {
        if (qrScanner && isScanning) {
            try { await qrScanner.stop(); } catch (e) {}
            try { qrScanner.clear(); } catch (e) {}
        }
        isScanning = false;
        scannerPanel.classList.add('d-none');
    }

    async function startScanner() {
        scannerPanel.classList.remove('d-none');
        if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            scanStatus.innerHTML = '<span class="text-warning-emphasis fw-semibold">Camera scanning requires a secure connection. Please enter the tag code manually.</span>';
            return;
        }
        if (typeof Html5Qrcode === 'undefined') {
            scanStatus.innerHTML = '<span class="text-danger">QR scanner library unavailable. Please enter the tag code manually.</span>';
            return;
        }
        try {
            if (isScanning) await stopScanner();
            scannerPanel.classList.remove('d-none');
            qrScanner = new Html5Qrcode('spTvQrReader');
            scanStatus.textContent = 'Starting rear camera...';
            await qrScanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                async (decodedText) => {
                    if (isSubmitting) return;
                    codeInput.value = decodedText.trim();
                    methodInput.value = 'qr';
                    await stopScanner();
                    verifyForm.requestSubmit();
                },
                () => {}
            );
            isScanning = true;
            scanStatus.textContent = 'Camera active — align the Fresh Go QR tag inside the frame.';
        } catch (err) {
            scanStatus.innerHTML = '<span class="text-danger">Camera unavailable (' + (err?.message || 'permission denied') + '). Please enter the tag code manually.</span>';
        }
    }

    document.getElementById('spTvToggleScanBtn')?.addEventListener('click', () => {
        if (isScanning) stopScanner(); else startScanner();
    });
    document.getElementById('spTvCloseScanBtn')?.addEventListener('click', () => stopScanner());

    document.getElementById('spTvResetBtn')?.addEventListener('click', () => {
        verifyForm.reset();
        methodInput.value = 'manual';
        alertBox.classList.add('d-none');
        stopScanner();
        codeInput.focus();
    });

    verifyForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (isSubmitting) return;
        isSubmitting = true;
        const btn = document.getElementById('spTvVerifyBtn');
        btn.disabled = true;
        btn.textContent = 'Verifying...';
        alertBox.classList.add('d-none');

        try {
            const res = await fetch('{{ route('store.tag-verification.verify') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: new FormData(verifyForm),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                showAlert(data.message, 'success');
                resultBox.innerHTML = data.booking_panel_html;
                methodInput.value = 'manual';
            } else {
                showAlert(data.message || 'Tag verification failed.', 'error');
            }
        } catch (err) {
            showAlert('Network error verifying tag.', 'error');
        } finally {
            isSubmitting = false;
            btn.disabled = false;
            btn.textContent = 'Verify Tag';
        }
    });

    document.addEventListener('click', (e) => {
        const compBtn = e.target.closest('.js-open-complete-tag');
        if (!compBtn) return;
        const form = document.getElementById('spTvCompleteForm');
        form.dataset.url = compBtn.dataset.url;
        const remaining = parseInt(compBtn.dataset.remaining || '0', 10);
        const total = parseInt(compBtn.dataset.total || '0', 10);
        const completed = parseInt(compBtn.dataset.completed || '0', 10);
        const isFinal = remaining === 1;

        document.getElementById('spTvCompModalTitle').textContent = isFinal ? 'Complete Final Laundry Tag' : 'Confirm Bag Processing Completed';
        document.getElementById('spTvFinalWarningBox').classList.toggle('d-none', !isFinal);
        document.getElementById('spTvNormalPrompt').classList.toggle('d-none', isFinal);
        document.getElementById('mdlCompTagCodeInline').textContent = compBtn.dataset.code || '';
        document.getElementById('mdlCompBookingRef').textContent = compBtn.dataset.ref || '';
        document.getElementById('mdlCompTagCode').textContent = compBtn.dataset.code || '';
        document.getElementById('mdlCompBag').textContent = compBtn.dataset.bag || '';
        document.getElementById('mdlCompProgress').textContent = `${completed} / ${total} completed (${remaining} remaining)`;
        document.getElementById('mdlCompStatus').textContent = compBtn.dataset.status || '';
        document.getElementById('spTvCompleteSubmitBtn').textContent = isFinal ? 'Verify & Complete Store' : 'Confirm Tag Completed';
        document.getElementById('mdlCompNotes').value = '';
        document.getElementById('spTvCompleteError').classList.add('d-none');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('spTvCompleteModal')).show();
    });

    document.getElementById('spTvCompleteForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('spTvCompleteSubmitBtn');
        const errBox = document.getElementById('spTvCompleteError');
        if (btn.disabled) return;
        btn.disabled = true;
        btn.textContent = 'Completing...';
        errBox.classList.add('d-none');

        try {
            const res = await fetch(this.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: new FormData(this),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                bootstrap.Modal.getInstance(document.getElementById('spTvCompleteModal'))?.hide();
                const alertType = data.auto_store_complete_status === 'prerequisites_pending' ? 'warning' : 'success';
                showAlert(data.message, alertType);
                if (data.booking_panel_html) {
                    resultBox.innerHTML = data.booking_panel_html;
                }
            } else {
                errBox.textContent = data.message || 'Unable to complete tag.';
                errBox.classList.remove('d-none');
            }
        } catch (err) {
            errBox.textContent = 'Network error updating tag.';
            errBox.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirm Tag Completed';
        }
    });

    if (codeInput.value.trim() !== '') {
        verifyForm.requestSubmit();
    }
})();
</script>
@endpush
