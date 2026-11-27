<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Track Delivery Person Application - Fresh Go</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}?v={{ filemtime(public_path('css/laundry-home.css')) }}">
</head>
<body class="fg-home-body" style="background-color: #F7F6F2;">
@include('shared.header')

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h1 class="h4 fw-bold mb-1">Track Your Application</h1>
                <p class="small text-secondary mb-4">Enter your application reference, email, and password to view your current status.</p>

                <div id="statusAlert" class="alert d-none mb-3"></div>

                <form id="lookupForm" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Reference</label>
                        <input type="text" name="reference" id="lookupRef" class="form-control" value="{{ $prefillReference }}" placeholder="DPA-..." required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" id="lookupEmail" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Password</label>
                        <input type="password" name="password" id="lookupPassword" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold" id="lookupBtn">Check Status</button>
                    </div>
                </form>

                <div id="statusResult" class="d-none mt-4 pt-4 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold mb-0" id="resRef"></h2>
                        <span class="badge rounded-pill text-dark px-3 py-2" style="background: #FFF8E5; border: 1px solid #FFC629;" id="resStatus"></span>
                    </div>
                    <p class="small text-secondary mb-2">Submitted: <span id="resSubmitted"></span></p>
                    <p class="small mb-3" id="resMessage"></p>

                    <div id="resLoginBox" class="d-none">
                        <a href="{{ route('delivery-person.login') }}" class="btn rounded-pill px-4 fw-bold text-dark" style="background: #FFC629;">
                            Sign In to Delivery Person Portal
                        </a>
                    </div>

                    <form id="resubmitForm" class="d-none mt-3 pt-3 border-top" enctype="multipart/form-data">
                        @csrf
                        <h3 class="h6 fw-bold mb-2">Submit Additional Information</h3>
                        <p class="small text-warning-emphasis mb-2" id="resAdditionalPrompt"></p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Your Response / Notes <span class="text-danger">*</span></label>
                            <textarea name="additional_notes" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Updated Identity Document (Optional)</label>
                            <input type="file" name="identity_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold" id="resubmitBtn">Submit Additional Information</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('shared.footer')

<div class="d-lg-none">
    @include('shared.bottom-nav')
</div>

@include('shared.offcanvas-nav')

<script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/laundry-home.js') }}?v={{ filemtime(public_path('js/laundry-home.js')) }}"></script>
<script>
(function () {
    const lookupForm = document.getElementById('lookupForm');
    const resubmitForm = document.getElementById('resubmitForm');
    const alertBox = document.getElementById('statusAlert');
    const resultBox = document.getElementById('statusResult');

    function renderApp(app) {
        resultBox.classList.remove('d-none');
        document.getElementById('resRef').textContent = `${app.full_name} (${app.reference})`;
        document.getElementById('resStatus').textContent = app.status_label;
        document.getElementById('resSubmitted').textContent = app.submitted_at || '—';
        document.getElementById('resMessage').textContent = app.applicant_message || '';
        document.getElementById('resLoginBox').classList.toggle('d-none', !app.can_login);
        resubmitForm.classList.toggle('d-none', !app.can_resubmit);
        document.getElementById('resAdditionalPrompt').textContent = app.additional_info_requested || '';
    }

    lookupForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        alertBox.className = 'alert d-none mb-3';
        const res = await fetch('{{ route('delivery-person.application-status.lookup') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: new FormData(lookupForm),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            resultBox.classList.add('d-none');
            alertBox.className = 'alert alert-danger mb-3';
            alertBox.textContent = data.message || 'Unable to find application.';
            return;
        }
        renderApp(data.application);
    });

    resubmitForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const fd = new FormData(resubmitForm);
        fd.append('reference', document.getElementById('lookupRef').value);
        fd.append('email', document.getElementById('lookupEmail').value);
        fd.append('password', document.getElementById('lookupPassword').value);

        const res = await fetch('{{ route('delivery-person.application-status.resubmit') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: fd,
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            alertBox.className = 'alert alert-danger mb-3';
            alertBox.textContent = data.message || 'Unable to submit additional information.';
            return;
        }
        alertBox.className = 'alert alert-success mb-3';
        alertBox.textContent = data.message;
        renderApp(data.application);
    });
})();
</script>
</body>
</html>
