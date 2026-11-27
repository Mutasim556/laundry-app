<div class="sp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="sp-icon-box" style="background:#FEF3C7; color:#B45309;" aria-hidden="true">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0 text-dark" id="{{ $prefix }}MonthHeading">{{ $monthLabel }}</h5>
                    <span id="{{ $prefix }}Spinner" class="spinner-border spinner-border-sm text-warning d-none" role="status" aria-hidden="true"></span>
                </div>
                @if(!empty($subtitle))
                    <div class="small text-muted" style="font-size:0.76rem;">{{ $subtitle }}</div>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="{{ $prefix }}PrevBtn" data-month="{{ $prevMonth }}" data-year="{{ $prevYear }}" aria-label="Previous Month">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                <span class="d-none d-sm-inline">Prev</span>
            </button>
            <select id="{{ $prefix }}MonthSelect" class="form-select form-select-sm" style="width:auto;" aria-label="Select Month">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ \Carbon\Carbon::create(2026, $m, 1)->format('F') }}</option>
                @endfor
            </select>
            <select id="{{ $prefix }}YearSelect" class="form-select form-select-sm" style="width:auto;" aria-label="Select Year">
                @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                    <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
                @endfor
            </select>
            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="{{ $prefix }}NextBtn" data-month="{{ $nextMonth }}" data-year="{{ $nextYear }}" aria-label="Next Month">
                <span class="d-none d-sm-inline">Next</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <button type="button" class="btn btn-sm sp-btn-yellow px-3" id="{{ $prefix }}CurrentBtn">Current Month</button>
        </div>
    </div>
</div>
