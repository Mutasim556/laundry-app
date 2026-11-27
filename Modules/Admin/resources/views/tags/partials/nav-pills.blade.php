<ul class="nav nav-pills tm-nav-pills gap-1 mb-3 flex-wrap">
    @if(auth('admin')->user()->can('manage-laundry-tags', 'admin'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.tags.generate*') || request()->routeIs('admin.tags.print-preview') ? 'active' : '' }}"
               href="{{ route('admin.tags.generate') }}">
                <i data-feather="plus-square" style="width:14px;height:14px;"></i>Generate &amp; Print
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tags.index') || request()->routeIs('admin.tags.show') ? 'active' : '' }}"
           href="{{ route('admin.tags.index') }}">
            <i data-feather="layers" style="width:14px;height:14px;"></i>Tag Inventory
        </a>
    </li>
    @if(auth('admin')->user()->can('manage-laundry-tags', 'admin'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.tags.allocations*') ? 'active' : '' }}"
               href="{{ route('admin.tags.allocations.index') }}">
                <i data-feather="user-plus" style="width:14px;height:14px;"></i>Assign to Delivery Person
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tags.history*') ? 'active' : '' }}"
           href="{{ route('admin.tags.history') }}">
            <i data-feather="clock" style="width:14px;height:14px;"></i>Tag History
        </a>
    </li>
    @if(auth('admin')->user()->can('manage-laundry-tags', 'admin'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.tags.printer-settings*') ? 'active' : '' }}"
               href="{{ route('admin.tags.printer-settings.index') }}">
                <i data-feather="printer" style="width:14px;height:14px;"></i>Printer Settings
            </a>
        </li>
    @endif
</ul>

<div id="tmToastContainer" class="tm-toast-container" aria-live="polite"></div>

@push('scripts')
<script>
window.tmToast = function (message, type = 'success') {
    const container = document.getElementById('tmToastContainer');
    if (!container) return;
    const el = document.createElement('div');
    const bg = type === 'error' ? 'bg-danger text-white' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-success text-white');
    el.className = `tm-toast ${bg}`;
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.2s ease';
        setTimeout(() => el.remove(), 220);
    }, 3500);
};
</script>
@endpush
