<div class="row g-2 g-md-3">
    <div class="col-12 col-md-4">
        <div class="dp-card p-3">
            <div class="small text-muted">Completed Jobs ({{ $period['label'] }})</div>
            <div class="fs-4 fw-bold mt-1 text-success">{{ $completedCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="dp-card p-3">
            <div class="small text-muted">Avg Collection Duration</div>
            <div class="fs-4 fw-bold mt-1">{{ $avgColMins !== null ? $avgColMins . ' mins' : 'N/A' }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="dp-card p-3">
            <div class="small text-muted">Avg Final Delivery Duration</div>
            <div class="fs-4 fw-bold mt-1">{{ $avgFdMins !== null ? $avgFdMins . ' mins' : 'N/A' }}</div>
        </div>
    </div>
</div>
