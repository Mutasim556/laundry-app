{{-- Reusable JS controller for #fgTagScannerModal (supports camera fallback + manual entry + auto teardown) --}}
<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script>
window.FreshGoTagScanner = (function () {
    let html5Qr = null;
    let scanning = false;
    let activeConfig = null;

    const els = () => ({
        modal: document.getElementById('fgTagScannerModal'),
        form: document.getElementById('fgTagScannerForm'),
        title: document.getElementById('fgTagModalTitle'),
        alert: document.getElementById('fgTagModalAlert'),
        modeManual: document.getElementById('fgModeManualBtn'),
        modeScan: document.getElementById('fgModeScanBtn'),
        scanWrap: document.getElementById('fgScanWrap'),
        scanStatus: document.getElementById('fgScanStatus'),
        codeInput: document.getElementById('fgTagCodeInput'),
        methodInput: document.getElementById('fgTagMethodInput'),
        idempInput: document.getElementById('fgTagIdempKey'),
        pkgGroup: document.getElementById('fgPackageLabelGroup'),
        pkgInput: document.getElementById('fgPackageLabelInput'),
        notesGroup: document.getElementById('fgVerifyNotesGroup'),
        notesInput: document.getElementById('fgVerifyNotesInput'),
        submitBtn: document.getElementById('fgTagSubmitBtn'),
    });

    async function stopCamera() {
        if (html5Qr && scanning) {
            try { await html5Qr.stop(); } catch (e) {}
            try { html5Qr.clear(); } catch (e) {}
        }
        scanning = false;
    }

    async function startCamera() {
        const ui = els();
        if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            ui.scanStatus.innerHTML = '<span class="text-warning-emphasis">Camera requires HTTPS on mobile browsers. Use <strong>Enter Tag Code</strong> above.</span>';
            return;
        }
        if (typeof Html5Qrcode === 'undefined') {
            ui.scanStatus.innerHTML = '<span class="text-danger">QR scanner library unavailable. Please type the tag code manually.</span>';
            return;
        }
        try {
            await stopCamera();
            html5Qr = new Html5Qrcode('fgQrReader');
            ui.scanStatus.textContent = 'Starting camera...';
            await html5Qr.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                async (decodedText) => {
                    ui.codeInput.value = decodedText.trim();
                    ui.methodInput.value = 'qr';
                    ui.scanStatus.innerHTML = '<span class="text-success fw-semibold">QR detected! Submitting...</span>';
                    await stopCamera();
                    ui.form.requestSubmit();
                },
                () => {}
            );
            scanning = true;
            ui.scanStatus.textContent = 'Camera ready — align the QR code inside the frame.';
        } catch (err) {
            ui.scanStatus.innerHTML = '<span class="text-danger">Unable to access camera (' + (err?.message || 'permission denied') + '). Enter the tag code manually instead.</span>';
        }
    }

    function setMode(mode) {
        const ui = els();
        if (mode === 'scan') {
            ui.modeScan.classList.add('btn-dark', 'active');
            ui.modeScan.classList.remove('btn-outline-dark');
            ui.modeManual.classList.remove('btn-dark', 'active');
            ui.modeManual.classList.add('btn-outline-dark');
            ui.scanWrap.classList.remove('d-none');
            ui.methodInput.value = 'qr';
            startCamera();
        } else {
            ui.modeManual.classList.add('btn-dark', 'active');
            ui.modeManual.classList.remove('btn-outline-dark');
            ui.modeScan.classList.remove('btn-dark', 'active');
            ui.modeScan.classList.add('btn-outline-dark');
            ui.scanWrap.classList.add('d-none');
            ui.methodInput.value = 'manual';
            stopCamera();
        }
    }

    function init() {
        const ui = els();
        if (!ui.modal || ui.modal.dataset.bound === '1') return;
        ui.modal.dataset.bound = '1';

        ui.modeManual.addEventListener('click', () => setMode('manual'));
        ui.modeScan.addEventListener('click', () => setMode('scan'));
        ui.modal.addEventListener('hidden.bs.modal', () => stopCamera());

        ui.form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!activeConfig?.actionUrl) return;
            ui.submitBtn.disabled = true;
            const origText = ui.submitBtn.textContent;
            ui.submitBtn.textContent = 'Verifying...';
            ui.alert.classList.add('d-none');

            try {
                const res = await fetch(activeConfig.actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: new FormData(ui.form),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    ui.alert.className = 'alert alert-success small py-2';
                    ui.alert.textContent = data.message || 'Success';
                    ui.idempInput.value = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));
                    ui.codeInput.value = '';
                    if (typeof activeConfig.onSuccess === 'function') {
                        activeConfig.onSuccess(data);
                    }
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(ui.modal)?.hide();
                    }, 550);
                } else {
                    ui.alert.className = 'alert alert-danger small py-2';
                    ui.alert.textContent = data.message || 'Verification failed.';
                }
            } catch (err) {
                ui.alert.className = 'alert alert-danger small py-2';
                ui.alert.textContent = 'Network error communicating with server.';
            } finally {
                ui.submitBtn.disabled = false;
                ui.submitBtn.textContent = origText;
            }
        });
    }

    return {
        open(config) {
            init();
            activeConfig = config;
            const ui = els();
            ui.form.reset();
            ui.alert.classList.add('d-none');
            ui.title.textContent = config.title || 'Laundry Tag';
            ui.submitBtn.textContent = config.submitLabel || 'Verify & Submit';
            ui.idempInput.value = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));

            const isStore = config.context === 'store';
            ui.pkgGroup.classList.toggle('d-none', isStore);
            ui.notesGroup.classList.toggle('d-none', !isStore);

            setMode('manual');
            bootstrap.Modal.getOrCreateInstance(ui.modal).show();
            setTimeout(() => ui.codeInput?.focus(), 250);
        }
    };
})();
</script>
