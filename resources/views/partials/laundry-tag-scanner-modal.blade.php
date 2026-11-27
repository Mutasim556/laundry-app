{{-- Reusable Bootstrap 5 Laundry Tag Manual Entry & Camera QR Scanner Modal --}}
<div class="modal fade" id="fgTagScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="fgTagScannerForm" class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="fgTagModalTitle">Assign Laundry Tag</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="fgTagModalAlert" class="alert d-none small py-2"></div>

                <div class="btn-group w-100 mb-3" role="group">
                    <button type="button" class="btn btn-sm btn-dark active" id="fgModeManualBtn">
                        <i class="mdi mdi-keyboard-outline me-1"></i>Enter Tag Code
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark" id="fgModeScanBtn">
                        <i class="mdi mdi-qrcode-scan me-1"></i>Scan QR Camera
                    </button>
                </div>

                <div id="fgScanWrap" class="d-none mb-3">
                    <div id="fgQrReader" class="rounded-3 overflow-hidden border bg-dark text-white" style="min-height: 220px;"></div>
                    <div id="fgScanStatus" class="small text-muted mt-2 text-center">Point your camera at the Fresh Go hanging tag QR code.</div>
                </div>

                <input type="hidden" name="method" id="fgTagMethodInput" value="manual">
                <input type="hidden" name="idempotency_key" id="fgTagIdempKey" value="">

                <div class="mb-3">
                    <label for="fgTagCodeInput" class="form-label small fw-semibold">Laundry Tag Code or QR Payload</label>
                    <input type="text" name="tag_code" id="fgTagCodeInput" class="form-control font-monospace text-uppercase"
                           placeholder="e.g. FG-T-000001" required autocomplete="off">
                    <div class="form-text">Enter printed code (like <code>FG-T-000001</code>) or scan via camera above.</div>
                </div>

                <div class="mb-0" id="fgPackageLabelGroup">
                    <label for="fgPackageLabelInput" class="form-label small fw-semibold">Bag / Package Label (Optional)</label>
                    <input type="text" name="package_label" id="fgPackageLabelInput" class="form-control form-control-sm"
                           maxlength="60" placeholder="e.g. Bag 1, Suit Bag, Delicate Bag">
                </div>

                <div class="mb-0 d-none" id="fgVerifyNotesGroup">
                    <label for="fgVerifyNotesInput" class="form-label small fw-semibold">Verification Notes (Optional)</label>
                    <input type="text" name="notes" id="fgVerifyNotesInput" class="form-control form-control-sm"
                           maxlength="500" placeholder="Optional note on tag/parcel condition">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="submit" id="fgTagSubmitBtn" class="btn btn-warning btn-sm fw-semibold">Verify &amp; Attach Tag</button>
            </div>
        </form>
    </div>
</div>
