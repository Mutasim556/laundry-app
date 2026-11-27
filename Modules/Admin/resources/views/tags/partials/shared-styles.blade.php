@push('styles')
<style>
    @keyframes tmFadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .tm-anim {
        animation: tmFadeUp 0.25s ease-out both;
    }
    @media (prefers-reduced-motion: reduce) {
        .tm-anim, .tm-kpi-card, .tm-section-card, .tm-nav-pills .nav-link {
            animation: none !important;
            transition: none !important;
        }
    }
    .tm-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%);
        border: 1px solid #FDE68A;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14);
    }
    .tm-kpi-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 0.9rem 1rem;
        position: relative;
        overflow: hidden;
        height: 100%;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .tm-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .tm-kpi-bar {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
    }
    .tm-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .tm-nav-pills {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.45rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }
    .tm-nav-pills .nav-link {
        color: #475569;
        font-weight: 600;
        font-size: 0.82rem;
        border-radius: 10px;
        padding: 0.48rem 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .tm-nav-pills .nav-link:hover {
        background-color: #FFF8E5;
        color: #171717;
    }
    .tm-nav-pills .nav-link.active {
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
        color: #171717;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    }
    .tm-section-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        min-width: 0;
    }
    .tm-section-card > .card-header {
        background: linear-gradient(90deg, #F8FAFC 0%, #FFFFFF 100%);
        border-bottom: 1px solid #E2E8F0;
        padding: 0.85rem 1.15rem;
    }
    .tm-table-scroll {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .tm-table-scroll > table {
        min-width: 780px;
        margin-bottom: 0;
    }
    .tm-loading-overlay {
        position: relative;
        opacity: 0.6;
        pointer-events: none;
        transition: opacity 0.15s ease;
    }
    .tm-empty-state {
        padding: 2.5rem 1rem;
        text-align: center;
        color: #64748B;
    }
    .tm-empty-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #F1F5F9;
        color: #64748B;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
    }
    .tm-toast-container {
        position: fixed;
        bottom: 1.25rem;
        right: 1.25rem;
        z-index: 1085;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-width: 360px;
    }
    .tm-toast {
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-size: 0.84rem;
        font-weight: 500;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
        animation: tmFadeUp 0.2s ease-out both;
    }
</style>
@endpush
