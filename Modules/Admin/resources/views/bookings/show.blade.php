@extends('admin::layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .bk-detail-page {
        --bk-text-primary: #171717;
        --bk-text-secondary: #475569;
        --bk-text-muted: #64748B;
        --bk-yellow-bg: #FFF7E2;
        --bk-yellow-border: #F5BE48;
        --bk-yellow-heading: #946200;
        --bk-blue-bg: #EFF6FF;
        --bk-blue-border: #93C5FD;
        --bk-blue-heading: #1D4ED8;
        --bk-green-bg: #ECFDF5;
        --bk-green-border: #86EFAC;
        --bk-green-heading: #15803D;
        --bk-purple-bg: #F5F3FF;
        --bk-purple-border: #C4B5FD;
        --bk-purple-heading: #6D28D9;
        --bk-rose-bg: #FFF1F2;
        --bk-rose-border: #FDA4AF;
        --bk-rose-heading: #BE123C;
        --bk-cyan-bg: #ECFEFF;
        --bk-cyan-border: #67E8F9;
        --bk-cyan-heading: #0E7490;
        background-color: #F6F8FC;
        border-radius: 16px;
        color: var(--bk-text-primary);
        font-size: 0.875rem;
    }
    .bk-detail-page .text-muted {
        color: var(--bk-text-secondary) !important;
    }
    .bk-detail-page .bk-label {
        color: var(--bk-text-secondary);
        font-weight: 500;
        font-size: 0.8125rem;
    }
    .bk-detail-page .bk-value {
        color: var(--bk-text-primary);
        font-weight: 600;
        font-size: 0.875rem;
    }
    .bk-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        transition: transform 0.16s ease, box-shadow 0.16s ease;
    }
    .bk-card:hover {
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
    }
    .bk-card-heading {
        font-size: 1rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }
    .bk-card-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .bk-card-theme--yellow {
        background-color: var(--bk-yellow-bg);
        border: 1px solid var(--bk-yellow-border);
    }
    .bk-card-theme--yellow .bk-card-heading { color: var(--bk-yellow-heading); }
    .bk-card-theme--yellow .bk-card-icon { background-color: #FEF3C7; color: var(--bk-yellow-heading); border: 1px solid #F5BE48; }
    .bk-card-theme--blue {
        background-color: var(--bk-blue-bg);
        border: 1px solid var(--bk-blue-border);
    }
    .bk-card-theme--blue .bk-card-heading { color: var(--bk-blue-heading); }
    .bk-card-theme--blue .bk-card-icon { background-color: #DBEAFE; color: var(--bk-blue-heading); border: 1px solid #93C5FD; }
    .bk-card-theme--green {
        background-color: var(--bk-green-bg);
        border: 1px solid var(--bk-green-border);
    }
    .bk-card-theme--green .bk-card-heading { color: var(--bk-green-heading); }
    .bk-card-theme--green .bk-card-icon { background-color: #DCFCE7; color: var(--bk-green-heading); border: 1px solid #86EFAC; }
    .bk-card-theme--purple {
        background-color: var(--bk-purple-bg);
        border: 1px solid var(--bk-purple-border);
    }
    .bk-card-theme--purple .bk-card-heading { color: var(--bk-purple-heading); }
    .bk-card-theme--purple .bk-card-icon { background-color: #EDE9FE; color: var(--bk-purple-heading); border: 1px solid #C4B5FD; }
    .bk-card-theme--rose {
        background-color: var(--bk-rose-bg);
        border: 1px solid var(--bk-rose-border);
        border-radius: 14px;
    }
    .bk-card-theme--rose .bk-card-heading { color: var(--bk-rose-heading); }
    .bk-card-theme--rose .bk-card-icon { background-color: #FFE4E6; color: var(--bk-rose-heading); border: 1px solid #FDA4AF; }
    .bk-card-theme--cyan {
        background-color: var(--bk-cyan-bg);
        border: 1px solid var(--bk-cyan-border);
        border-radius: 14px;
    }
    .bk-card-theme--cyan .bk-card-heading { color: var(--bk-cyan-heading); }
    .bk-card-theme--cyan .bk-card-icon { background-color: #CFFAFE; color: var(--bk-cyan-heading); border: 1px solid #67E8F9; }
    .bk-inner-surface {
        background-color: #FFFFFF;
        border-radius: 12px;
        border: 1px solid rgba(15, 23, 42, 0.08);
    }
    .bk-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        border: 1px solid #FDE68A;
        border-left: 5px solid #FFC629;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(255, 198, 41, 0.12);
    }
    .bk-btn-yellow {
        background-color: #FFC629;
        border-color: #FFC629;
        color: #171717;
        font-weight: 600;
        border-radius: 10px;
        transition: background-color 0.15s ease, transform 0.15s ease;
    }
    .bk-btn-yellow:hover, .bk-btn-yellow:focus {
        background-color: #f2b718;
        border-color: #f2b718;
        color: #171717;
        transform: translateY(-1px);
    }
    .bk-btn-outline-danger {
        border-radius: 10px;
        font-weight: 600;
    }
    .bk-assigned-store-box {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        border: 1px solid #FDE68A;
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
    }
    .bk-assigned-store-address {
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: normal;
        line-height: 1.45;
    }
    /* Shared Assignment Modals & Select2 Design System */
    .assignment-modal .modal-dialog {
        max-width: 600px;
        width: calc(100% - 1.5rem);
        margin: 0.75rem auto;
    }
    .assignment-modal .modal-content {
        border-radius: 14px;
        overflow: hidden;
        max-height: calc(100vh - 1.5rem);
        display: flex;
        flex-direction: column;
    }
    .assignment-modal .modal-body {
        background-color: #F6F8FC;
        overflow-y: auto;
        overflow-x: hidden;
        color: #0F172A;
    }
    .assignment-modal .modal-title,
    .assignment-modal .form-label {
        color: #0F172A !important;
    }
    .assignment-modal .text-muted {
        color: #334155 !important;
    }
    .assignment-modal .form-control {
        color: #0F172A;
        font-size: 0.875rem;
        font-weight: 500;
    }
    .assignment-modal .form-control::placeholder {
        color: #64748B;
        font-weight: 400;
    }
    .assignment-modal-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background-color: #FFC629;
        color: #171717;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .assignment-modal .select2-container {
        width: 100% !important;
    }
    .assignment-modal .select2-container--default .select2-selection--single {
        height: 44px;
        min-height: 44px;
        display: flex;
        align-items: center;
        padding: 0 2.75rem 0 0.85rem;
        border: 1px solid #CBD5E1;
        border-radius: 10px;
        background-color: #FFFFFF;
        position: relative;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .assignment-modal .select2-container--default.select2-container--open .select2-selection--single,
    .assignment-modal .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #FFC629;
        box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.25);
        outline: 0;
    }
    .assignment-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
        display: block;
        color: #0F172A;
        font-size: 0.875rem;
        font-weight: 600;
        height: 42px;
        line-height: 42px;
        padding: 0;
        margin: 0;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .assignment-modal .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #475569;
        font-weight: 500;
        line-height: 42px;
    }
    .assignment-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px;
        width: 24px;
        top: 1px;
        right: 0.55rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .assignment-modal .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute;
        right: 2.05rem;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748B;
        margin: 0;
        font-size: 1.1rem;
        line-height: 1;
        z-index: 2;
        border-radius: 50%;
    }
    .assignment-modal .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #171717;
        background-color: #F1F5F9;
    }
    .assignment-select2-dropdown {
        border: 1px solid #CBD5E1 !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14) !important;
        background-color: #FFFFFF !important;
        overflow: hidden;
        z-index: 1075 !important;
    }
    .assignment-select2-dropdown.select2-dropdown--below {
        margin-top: 4px;
    }
    .assignment-select2-dropdown.select2-dropdown--above {
        margin-bottom: 4px;
    }
    .assignment-select2-dropdown .select2-search--dropdown {
        padding: 0.6rem 0.7rem;
        background-color: #F8FAFC;
        border-bottom: 1px solid #E2E8F0;
    }
    .assignment-select2-dropdown .select2-search--dropdown .select2-search__field {
        height: 38px;
        min-height: 38px;
        width: 100%;
        padding: 0.35rem 0.75rem 0.35rem 2.15rem;
        font-size: 0.85rem;
        color: #171717;
        background-color: #FFFFFF;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 0.7rem center;
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        outline: none;
        box-sizing: border-box;
        line-height: 1.4;
    }
    .assignment-select2-dropdown .select2-search--dropdown .select2-search__field:focus {
        border-color: #FFC629;
        box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.22);
    }
    .assignment-select2-dropdown .select2-results__options {
        max-height: min(240px, 34vh);
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 0.45rem 0.55rem;
    }
    .assignment-select2-dropdown .select2-results__options::-webkit-scrollbar {
        width: 6px;
    }
    .assignment-select2-dropdown .select2-results__options::-webkit-scrollbar-track {
        background: #F1F5F9;
        border-radius: 999px;
    }
    .assignment-select2-dropdown .select2-results__options::-webkit-scrollbar-thumb {
        background: #CBD5E1;
        border-radius: 999px;
    }
    .assignment-select2-dropdown .select2-results__option {
        padding: 0.65rem 0.8rem;
        margin-bottom: 0.25rem;
        border-radius: 10px;
        border-bottom: 1px solid #F1F5F9;
        background-color: #FFFFFF;
        color: #171717;
        transition: background-color 0.12s ease;
    }
    .assignment-select2-dropdown .select2-results__option:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    .assignment-select2-dropdown .select2-results__option--highlighted[aria-selected],
    .assignment-select2-dropdown .select2-results__option[aria-selected="true"] {
        background-color: #FFF8E5 !important;
        color: #171717 !important;
    }
    .assignment-row-check {
        display: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: #FFC629;
        color: #171717;
        font-size: 0.72rem;
        font-weight: 700;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .assignment-select2-dropdown .select2-results__option[aria-selected="true"] .assignment-row-check {
        display: inline-flex;
    }
    .assignment-result-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
    }
    .assignment-result-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background-color: #FFF8E5;
        border: 1px solid #FDE68A;
        color: #B45309;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .assignment-result-icon--circle {
        border-radius: 50%;
    }
    .assignment-result-main {
        min-width: 0;
        flex: 1 1 auto;
    }
    .assignment-result-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #0F172A;
        line-height: 1.35;
        word-break: break-word;
    }
    .assignment-result-meta {
        font-size: 0.78rem;
        font-weight: 500;
        color: #334155;
        line-height: 1.35;
        margin-top: 2px;
        word-break: break-word;
    }
    .assignment-badge {
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        white-space: nowrap;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
    }
    .assignment-badge--match {
        background-color: #DCFCE7;
        color: #166534;
    }
    .assignment-badge--manual {
        background-color: #FEF3C7;
        color: #92400E;
    }
    .assignment-badge--current {
        background-color: #DBEAFE;
        color: #1D4ED8;
    }
    .bk-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
        color: #171717;
        font-weight: 700;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .bk-services-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748B;
        border-bottom: 1px solid #E2E8F0;
        background: #F8FAFC;
    }
    .bk-services-table tbody tr.bk-service-row {
        transition: background-color 0.14s ease;
    }
    .bk-services-table tbody tr.bk-service-row:hover {
        background-color: #F8FAFC;
    }
    .bk-subtotal-row td {
        background-color: #FFF8E5;
        border-top: 2px solid #FFC629;
        color: #171717;
    }
    .bk-schedule-box-collect {
        background-color: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 12px;
    }
    .bk-schedule-box-deliver {
        background-color: #ECFDF5;
        border: 1px solid #A7F3D0;
        border-radius: 12px;
    }
    .bk-final-total-box {
        background: linear-gradient(135deg, #FFF8E5 0%, #FEF3C7 100%);
        border: 1px solid #FCD34D;
        border-radius: 12px;
    }
    .bk-status-select-wrap .select2-container {
        width: 100% !important;
    }
    .bk-status-select-wrap .select2-container--default .select2-selection--single {
        min-height: 36px;
        border: 1px solid #CBD5E1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        background-color: #FFFFFF;
    }
    .bk-status-select-wrap .select2-container--default.select2-container--open .select2-selection--single,
    .bk-status-select-wrap .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #FFC629;
        box-shadow: 0 0 0 0.2rem rgba(255, 198, 41, 0.25);
    }
    .bk-status-select-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #171717;
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 34px;
        padding-left: 10px;
        padding-right: 28px;
    }
    .bk-status-select-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px;
        right: 6px;
    }
    .select2-dropdown {
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.85rem;
    }
    .select2-container--default .select2-results > .select2-results__options {
        max-height: 280px;
    }
    .select2-container--default .select2-results__option {
        padding: 0;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #FFF8E5;
        color: #171717;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #FFF8E5;
        color: #171717;
    }
    .s2-st-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 12px;
        font-size: 0.85rem;
        border-radius: 6px;
    }
    .s2-st-completed {
        background-color: #DCFCE7;
        color: #171717;
    }
    .s2-st-current {
        background-color: #FFF8E5;
        color: #171717;
        font-weight: 700;
    }
    .s2-st-upcoming {
        color: #777777;
    }
    .s2-st-check {
        color: #16A34A;
        font-weight: 700;
        margin-right: 6px;
    }
    .s2-st-current-pill {
        background-color: #FFC629;
        color: #171717;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        flex-shrink: 0;
    }
    @media (prefers-reduced-motion: reduce) {
        .bk-card, .bk-btn-yellow, .bk-services-table tbody tr.bk-service-row {
            transition: none !important;
        }
        .bk-btn-yellow:hover {
            transform: none !important;
        }
    }
    @include('admin::bookings.invoices._print_styles')
    .bk-btn-print-invoice {
        height: 34px;
        padding: 0 0.75rem;
        border-radius: 999px;
        background-color: #FFFFFF;
        border: 1px solid #C4B5FD;
        color: #5B21B6;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        white-space: nowrap;
    }
    .bk-btn-print-invoice:hover, .bk-btn-print-invoice:focus {
        background-color: #EDE9FE;
        border-color: #6D28D9;
        color: #4C1D95;
        transform: translateY(-1px);
    }
    .invoice-preview-modal .modal-dialog {
        max-width: 820px;
        width: calc(100% - 1.5rem);
        margin: 0.75rem auto;
    }
    .invoice-preview-modal .modal-content {
        border-radius: 14px;
        overflow: hidden;
        max-height: calc(100vh - 1.5rem);
        display: flex;
        flex-direction: column;
    }
    .invoice-preview-canvas {
        background-color: #E2E8F0;
        padding: 1rem;
        overflow-y: auto;
        overflow-x: auto;
        min-height: 260px;
    }
    .invoice-preview-paper {
        background-color: #FFFFFF;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        border-radius: 8px;
        margin: 0 auto;
        overflow: hidden;
    }
    .invoice-preview-paper--thermal {
        width: fit-content;
        max-width: 100%;
        padding: 0.5rem;
    }
    .invoice-preview-paper--a4 {
        width: 100%;
        max-width: 190mm;
    }
    .invoice-seg-btn {
        border: 1px solid #CBD5E1;
        background: #FFFFFF;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.38rem 0.85rem;
        border-radius: 8px;
        transition: all 0.15s ease;
    }
    .invoice-seg-btn.active {
        background-color: #FFF8E5;
        border-color: #FFC629;
        color: #171717;
        box-shadow: 0 0 0 2px rgba(255, 198, 41, 0.25);
    }
</style>
@endpush

@section('content')
@php
    $address = is_array($booking->address_snapshot) ? $booking->address_snapshot : [];
    $schedule = is_array($booking->schedule_snapshot) ? $booking->schedule_snapshot : [];
    $isTerminal = in_array($booking->booking_status, [\App\Models\Booking::STATUS_CANCELLED, \App\Models\Booking::STATUS_COMPLETED], true);

    $statusStyle = \App\Models\Booking::statusBadgeStyle($booking->booking_status);

    $payBadgeStyle = match($pricingSummary['payment_status_label']) {
        'Paid' => 'background-color: #D1FAE5; color: #047857;',
        'Authorized' => 'background-color: #DBEAFE; color: #1D4ED8;',
        'Failed' => 'background-color: #FEE2E2; color: #B91C1C;',
        'Refunded', 'Partially Refunded' => 'background-color: #EDE9FE; color: #6D28D9;',
        default => 'background-color: #FEF3C7; color: #B45309;',
    };

    $nameParts = preg_split('/\s+/', trim((string) $booking->customer_name)) ?: [];
    $initials = strtoupper(
        substr($nameParts[0] ?? 'C', 0, 1) .
        (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '')
    );

    $categoryPalette = [
        ['bg' => '#FFF8E5', 'border' => '#FFC629', 'text' => '#92400E'],
        ['bg' => '#EFF6FF', 'border' => '#3B82F6', 'text' => '#1D4ED8'],
        ['bg' => '#F5F3FF', 'border' => '#8B5CF6', 'text' => '#5B21B6'],
        ['bg' => '#ECFDF5', 'border' => '#10B981', 'text' => '#065F46'],
    ];
@endphp

<div class="container-fluid py-3 bk-detail-page">
    <!-- Top Compact Booking Header -->
    <div class="bk-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <a href="{{ route('admin.bookings.index') }}" class="text-decoration-none small fw-semibold" style="color: #475569;">
                    &larr; Back to Booking Management
                </a>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                    <h2 class="fw-bold mb-0" style="color: #171717; font-size: clamp(1.375rem, 2vw, 1.625rem);">{{ $booking->reference }}</h2>
                    <span class="badge rounded-pill px-3 py-1 fw-semibold" style="{{ $statusStyle }}">
                        {{ $statusLabels[$booking->booking_status] ?? ucfirst($booking->booking_status) }}
                    </span>
                </div>
                <div class="small mt-1" style="color: #475569; font-weight: 500;">
                    <span class="fw-bold" style="color: #171717;">{{ $booking->customer_name }}</span>
                    &middot; {{ $booking->customer_email }}
                    &middot; Created {{ $booking->created_at?->format('d M Y, H:i') }}
                    &middot; Version {{ $booking->version }}
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('manage-bookings', 'admin')
                    @if(!$isTerminal)
                        <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm bk-btn-yellow px-3 py-2">
                            <i class="mdi mdi-pencil me-1"></i> Manage / Edit Booking
                        </a>
                    @endif
                @endcan
                @can('cancel-bookings', 'admin')
                    @if(!$isTerminal)
                        <button type="button" class="btn btn-sm btn-outline-danger bk-btn-outline-danger px-3 py-2" data-bs-toggle="modal" data-bs-target="#cancelShowBookingModal">
                            <i class="mdi mdi-close-circle-outline me-1"></i> Cancel Booking
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 py-2 px-3 small mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($booking->booking_status === \App\Models\Booking::STATUS_CANCELLED)
        <div class="alert alert-danger rounded-4 py-2 px-3 mb-3">
            <div class="fw-bold small">Booking Cancelled</div>
            <div class="small">Cancelled at: {{ $booking->cancelled_at?->format('d M Y H:i') }} &middot; Reason: {{ $booking->cancellation_reason }}</div>
        </div>
    @endif

    <!-- First Row: Services & Items (Full Width) -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="bk-card bk-card-theme--yellow p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="bk-card-heading">
                        <span class="bk-card-icon"><i class="mdi mdi-basket"></i></span>
                        <span>Services &amp; Items</span>
                    </h5>
                    <span class="badge rounded-pill px-3 py-1 fw-semibold"
                          style="{{ $pricingSummary['is_pricing_complete'] ? 'background-color: #D1FAE5; color: #065F46;' : 'background-color: #FEF3C7; color: #92400E;' }}">
                        {{ $pricingSummary['pricing_status_label'] }}
                    </span>
                </div>
                <div class="table-responsive bk-inner-surface">
                    <table class="table table-sm align-middle mb-0 bk-services-table">
                        <thead>
                            <tr>
                                <th>Service / Item</th>
                                <th class="text-end" style="width: 140px;">Qty / Weight</th>
                                <th class="text-end" style="width: 170px;">Unit Price</th>
                                <th class="text-end" style="width: 160px;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryGroups as $group)
                                @php
                                    $catTheme = $categoryPalette[$loop->index % count($categoryPalette)];
                                @endphp
                                <tr>
                                    <td colspan="4" class="py-2 px-3 fw-bold text-uppercase small"
                                        style="background-color: {{ $catTheme['bg'] }}; border-left: 4px solid {{ $catTheme['border'] }}; color: {{ $catTheme['text'] }};">
                                        {{ $group['category_name'] }}
                                    </td>
                                </tr>
                                @forelse($group['items'] as $line)
                                    <tr class="bk-service-row">
                                        <td class="py-2">
                                            <div class="fw-semibold" style="color: #171717;">
                                                {{ $line->service_name }}
                                                @if($line->is_price_overridden)
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;">Overridden</span>
                                                @endif
                                            </div>
                                            @if($line->item_name)
                                                <div class="small" style="color: #64748B;">Item: {{ $line->item_name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap py-2">
                                            @if($line->pricing_mode === 'weight')
                                                @if($line->weight_kg !== null && (float) $line->weight_kg > 0)
                                                    <span class="fw-semibold">{{ number_format((float) $line->weight_kg, 2) }} kg</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-dark">Pending</span>
                                                @endif
                                            @else
                                                <span class="fw-semibold">{{ (int) $line->quantity }} {{ strtolower($line->unit_name ?: 'item') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap py-2">
                                            {{ $currencyService->formatMinor((int) $line->actual_unit_price_minor, $booking->currency) }}
                                            <span class="small" style="color: #64748B;">/ {{ $line->pricing_mode === 'weight' ? 'kg' : strtolower($line->unit_name ?: 'unit') }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-nowrap py-2" style="color: #171717;">
                                            {{ $currencyService->formatMinor((int) $line->line_total_minor, $booking->currency) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="small py-2 px-3" style="color: #64748B;">No items added yet (pricing pending).</td>
                                    </tr>
                                @endforelse
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 small" style="color: #64748B;">No active operational services recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bk-subtotal-row">
                                <td colspan="3" class="text-end fw-bold py-2 px-3">Items Subtotal</td>
                                <td class="text-end fw-bold fs-6 py-2 px-3 text-nowrap">
                                    {{ $currencyService->formatMinor((int) $pricingSummary['items_subtotal_minor'], $booking->currency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($booking->operational_notes)
                    <div class="mt-3 p-2 px-3 rounded-3 small" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; color: #334155;">
                        <strong>Operational Notes:</strong> {{ $booking->operational_notes }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Second Row: Three Compact Information Cards -->
    <div class="row g-3 mb-3">
        <!-- Left: Customer & Status -->
        <div class="col-md-6 col-lg-4">
            <div class="bk-card bk-card-theme--blue p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <h6 class="bk-card-heading mb-3">
                        <span class="bk-card-icon"><i class="mdi mdi-account-circle"></i></span>
                        <span>Customer &amp; Status</span>
                    </h6>
                    <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded-3 bk-inner-surface">
                        <div class="bk-avatar">{{ $initials }}</div>
                        <div class="min-w-0">
                            <div class="fw-bold text-truncate" style="color: #171717; font-size: 0.95rem;">{{ $booking->customer_name }}</div>
                            <div class="small text-truncate" style="color: #475569;">{{ $booking->customer_email }}</div>
                        </div>
                    </div>
                    <div class="small mb-2 d-flex justify-content-between">
                        <span class="bk-label"><i class="mdi mdi-phone me-1"></i>Phone</span>
                        <span class="bk-value">{{ $booking->customer_phone ?: '-' }}</span>
                    </div>
                    <div class="small mb-2 d-flex justify-content-between">
                        <span class="bk-label"><i class="mdi mdi-account-outline me-1"></i>Type</span>
                        <span class="bk-value">{{ ucfirst($booking->customer_type ?? 'individual') }}</span>
                    </div>
                    <div class="small mb-3 d-flex justify-content-between align-items-center">
                        <span class="bk-label"><i class="mdi mdi-tag me-1"></i>Status</span>
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $statusStyle }}">
                            {{ $statusLabels[$booking->booking_status] ?? ucfirst($booking->booking_status) }}
                        </span>
                    </div>
                </div>

                @php
                    $currentStoreAssignment = $booking->currentStoreAssignment;
                    $needsStoreToAdvance = $booking->booking_status === \App\Models\Booking::STATUS_STORE_ASSIGNED && ! $hasValidStoreAssignment;
                    $needsCollectionCourierToAdvance = $booking->booking_status === \App\Models\Booking::STATUS_DELIVERY_MAN_ASSIGNED && ! $hasValidCollectionCourier;
                    $needsFinalCourierToAdvance = $booking->booking_status === \App\Models\Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP && ! $hasValidFinalDeliveryCourier;
                    $stageBlockedByAssignment = $needsStoreToAdvance || $needsCollectionCourierToAdvance || $needsFinalCourierToAdvance;
                    $stageBlockMessage = $needsStoreToAdvance
                        ? 'Please assign a store before completing this stage.'
                        : ($needsCollectionCourierToAdvance
                            ? 'Please assign a collection delivery person before completing this stage.'
                            : ($needsFinalCourierToAdvance ? 'Please assign a final delivery person before completing this stage.' : ''));
                @endphp

                {{-- Inline Stage Prompt: Store Assignment (ONLY visible during Store Assigned stage) --}}
                @if($booking->booking_status === \App\Models\Booking::STATUS_STORE_ASSIGNED)
                    <div class="border-top pt-3 mb-3" id="inlineStoreAssignmentBox">
                        <div class="bk-assigned-store-box">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <span class="text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em; color: #64748B;">
                                    <i class="mdi mdi-store me-1 text-warning"></i>Assigned Store
                                </span>
                                @can('manage-bookings', 'admin')
                                    <button type="button"
                                            id="btnOpenAssignStoreModal"
                                            class="btn btn-sm bk-btn-yellow px-2 py-1 text-nowrap flex-shrink-0 js-open-assign-store"
                                            style="border-radius: 8px; font-size: 0.76rem;">
                                        {{ $currentStoreAssignment ? 'Change Store' : 'Assign Store' }}
                                    </button>
                                @endcan
                            </div>

                            <div id="inlineStoreAssignedDetails" class="{{ $currentStoreAssignment ? '' : 'd-none' }} small">
                                <div id="inlineStoreName" class="fw-bold text-dark mb-1">{{ $currentStoreAssignment?->store_name_snapshot ?? '' }}</div>
                                <div class="d-flex align-items-start gap-1 text-muted mb-1">
                                    <i class="mdi mdi-map-marker text-primary flex-shrink-0" style="margin-top: 2px;"></i>
                                    <div class="bk-assigned-store-address" id="inlineStoreAddress">{{ $currentStoreAssignment?->store_address_snapshot ?? '' }}</div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                                    <span class="badge bg-white text-dark border" id="inlineStorePostcodeWrap">
                                        Postcode: <strong id="inlineStorePostcode">{{ $currentStoreAssignment?->store_postcode_snapshot ?? '' }}</strong>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.78rem;">
                                        <i class="mdi mdi-phone text-success me-1"></i>
                                        <span id="inlineStorePhone">{{ ($currentStoreAssignment && filled($currentStoreAssignment->store_phone_snapshot)) ? $currentStoreAssignment->store_phone_snapshot : 'Contact number not provided' }}</span>
                                    </span>
                                </div>
                            </div>

                            <div id="inlineStoreUnassignedBox" class="{{ $currentStoreAssignment ? 'd-none' : '' }} small">
                                <div class="fw-semibold text-danger">Store: Not Assigned</div>
                                <div id="inlineStoreRequiredNote" class="text-muted" style="font-size: 0.76rem;">
                                    Please assign a store before completing this stage.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Inline Stage Prompt: Collection Delivery Person (ONLY visible during Delivery Man Assigned stage) --}}
                @if($booking->booking_status === \App\Models\Booking::STATUS_DELIVERY_MAN_ASSIGNED)
                    <div class="border-top pt-3 mb-3" id="inlineCollectionCourierBox">
                        <div class="bk-assigned-store-box">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <span class="text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em; color: #64748B;">
                                    <i class="mdi mdi-motorbike me-1 text-primary"></i>Collection Delivery Person
                                </span>
                                @can('manage-bookings', 'admin')
                                    <button type="button"
                                            class="btn btn-sm bk-btn-yellow px-2 py-1 text-nowrap flex-shrink-0 js-open-assign-courier"
                                            data-assignment-type="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION }}"
                                            style="border-radius: 8px; font-size: 0.76rem;">
                                        {{ $collectionAssignment ? 'Change Delivery Person' : 'Assign Delivery Person' }}
                                    </button>
                                @endcan
                            </div>

                            <div id="inlineCollectionCourierDetails" class="{{ $collectionAssignment ? '' : 'd-none' }} small">
                                <div id="inlineCollectionCourierName" class="fw-bold text-dark">{{ $collectionAssignment?->deliveryPerson?->full_name ?? '' }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    <span id="inlineCollectionCourierPhone">{{ $collectionAssignment?->deliveryPerson?->phone_display ?: 'Not provided' }}</span>
                                    &bull;
                                    <span id="inlineCollectionCourierStatus">{{ $collectionAssignment?->status_label ?? '' }}</span>
                                </div>
                            </div>

                            <div id="inlineCollectionCourierUnassigned" class="{{ $collectionAssignment ? 'd-none' : '' }} small">
                                <div class="fw-semibold text-danger">Delivery Person: Not Assigned</div>
                                <div class="text-muted" style="font-size: 0.76rem;">
                                    Please assign a collection delivery person before completing this stage.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Inline Stage Prompt: Final Delivery Person (ONLY visible during Delivery Man Assigned for Store Pickup stage) --}}
                @if($booking->booking_status === \App\Models\Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP)
                    <div class="border-top pt-3 mb-3" id="inlineFinalCourierBox">
                        <div class="bk-assigned-store-box">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <span class="text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em; color: #64748B;">
                                    <i class="mdi mdi-truck-delivery me-1 text-success"></i>Final Delivery Person
                                </span>
                                @can('manage-bookings', 'admin')
                                    <button type="button"
                                            class="btn btn-sm bk-btn-yellow px-2 py-1 text-nowrap flex-shrink-0 js-open-assign-courier"
                                            data-assignment-type="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY }}"
                                            style="border-radius: 8px; font-size: 0.76rem;">
                                        {{ $finalDeliveryAssignment ? 'Change Delivery Person' : 'Assign Delivery Person' }}
                                    </button>
                                @endcan
                            </div>

                            <div id="inlineFinalCourierDetails" class="{{ $finalDeliveryAssignment ? '' : 'd-none' }} small">
                                <div id="inlineFinalCourierName" class="fw-bold text-dark">{{ $finalDeliveryAssignment?->deliveryPerson?->full_name ?? '' }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    <span id="inlineFinalCourierPhone">{{ $finalDeliveryAssignment?->deliveryPerson?->phone_display ?: 'Not provided' }}</span>
                                    &bull;
                                    <span id="inlineFinalCourierStatus">{{ $finalDeliveryAssignment?->status_label ?? '' }}</span>
                                </div>
                            </div>

                            <div id="inlineFinalCourierUnassigned" class="{{ $finalDeliveryAssignment ? 'd-none' : '' }} small">
                                <div class="fw-semibold text-danger">Final Delivery Person: Not Assigned</div>
                                <div class="text-muted" style="font-size: 0.76rem;">
                                    Please assign a final delivery person before completing this stage.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @can('manage-bookings', 'admin')
                    @if($booking->booking_status !== \App\Models\Booking::STATUS_CANCELLED)
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="quickStatusSelect" class="form-label small fw-semibold mb-0" style="color: #64748B;">Quick Status Update</label>
                                <span class="badge rounded-pill px-2 py-1"
                                      style="{{ $stageProgress['is_finished'] ? 'background-color: #DCFCE7; color: #16A34A;' : 'background-color: #DBEAFE; color: #1D4ED8;' }}">
                                    @if($stageProgress['is_finished'])
                                        11/11 Completed
                                    @elseif($stageProgress['current_step_number'] > 0)
                                        Step {{ $stageProgress['current_step_number'] }} of {{ $stageProgress['total_steps'] }}
                                    @else
                                        {{ $stageProgress['completed_count'] }}/{{ $stageProgress['total_steps'] }} Done
                                    @endif
                                </span>
                            </div>

                            @if(!$stageProgress['is_finished'])
                                <form method="POST" action="{{ route('admin.bookings.status.advance', $booking) }}" class="mb-2">
                                    @csrf
                                    <input type="hidden" name="expected_stage" value="{{ $booking->booking_status }}">
                                    <input type="hidden" name="version" value="{{ $booking->version }}">
                                    <button type="submit"
                                            id="btnMarkStageDone"
                                            class="btn btn-sm btn-dark w-100 fw-semibold py-2"
                                            style="border-radius: 10px;"
                                            @if($stageBlockedByAssignment) disabled title="{{ $stageBlockMessage }}" @endif>
                                        <i class="mdi mdi-check-circle me-1"></i>
                                        @if(array_key_exists($booking->booking_status, $operationalStatuses))
                                            Mark {{ $booking->getStatusLabel() }} Done
                                        @else
                                            Start Processing (Step 1 of 11)
                                        @endif
                                    </button>
                                </form>
                            @else
                                <div class="small fw-semibold px-2 py-2 rounded-3 mb-2 text-center" style="background-color: #DCFCE7; color: #16A34A;">
                                    <i class="mdi mdi-check-all me-1"></i> All 11 operational stages completed
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.bookings.status.update', $booking) }}">
                                @csrf
                                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 bk-status-select-wrap">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select id="quickStatusSelect" name="booking_status" class="form-select form-select-sm">
                                            @if(!array_key_exists($booking->booking_status, $operationalStatuses))
                                                <option value="{{ $booking->booking_status }}"
                                                    data-completed="0"
                                                    data-current="1"
                                                    selected>
                                                    {{ $booking->getStatusLabel() }}
                                                </option>
                                            @endif
                                            @foreach($operationalStatuses as $stKey => $stLabel)
                                                @php
                                                    $isDone = in_array($stKey, $completedOperationalStatuses, true);
                                                    $isCurr = !$stageProgress['is_finished'] && $booking->booking_status === $stKey;
                                                @endphp
                                                <option value="{{ $stKey }}"
                                                    data-completed="{{ $isDone ? '1' : '0' }}"
                                                    data-current="{{ $isCurr ? '1' : '0' }}"
                                                    @selected($booking->booking_status === $stKey)>
                                                    {{ $stLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if(!$stageProgress['is_finished'])
                                        <button type="submit" class="btn btn-sm btn-outline-secondary px-3 text-nowrap" style="min-height: 36px; border-radius: 10px;">Update</button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>

        <!-- Middle: Collection & Delivery -->
        <div class="col-md-6 col-lg-4">
            <div class="bk-card bk-card-theme--green p-3 p-md-4 h-100">
                <h6 class="bk-card-heading mb-3">
                    <span class="bk-card-icon"><i class="mdi mdi-truck"></i></span>
                    <span>Collection &amp; Delivery</span>
                </h6>
                @php
                    $fullAddress = $address['full_address'] ?? $address['address_line'] ?? null;
                    $postcode = $address['postcode'] ?? null;
                    $details = $address['address_details'] ?? $address['instructions'] ?? null;
                    $isHotel = ! empty($address['is_hotel']) || ! empty($address['hotel_name']);
                @endphp

                @if($fullAddress || $postcode || $isHotel)
                    <div class="small mb-2" style="overflow-wrap: anywhere;">
                        <span class="bk-label"><i class="mdi mdi-map-marker me-1 text-success"></i>Address:</span>
                        <span class="bk-value">{{ $fullAddress ?: 'Not provided' }}</span>
                    </div>
                    @if($postcode)
                        <div class="small mb-2">
                            <span class="bk-label">Postcode:</span>
                            <span class="bk-value">{{ $postcode }}</span>
                        </div>
                    @endif
                    @if($isHotel)
                        <div class="small mb-2 p-2 rounded-3 bk-inner-surface" style="color: #171717;">
                            @if(!empty($address['hotel_name']))
                                <div><span class="bk-label">Hotel:</span> <strong class="bk-value">{{ $address['hotel_name'] }}</strong></div>
                            @endif
                            @if(!empty($address['room_number']))
                                <div><span class="bk-label">Room:</span> <strong class="bk-value">{{ $address['room_number'] }}</strong></div>
                            @endif
                            @if(!empty($address['hotel_preference']))
                                <div><span class="bk-label">Preference:</span> <strong class="bk-value">{{ $address['hotel_preference'] }}</strong></div>
                            @endif
                        </div>
                    @endif
                    @if($details)
                        <div class="small mb-3" style="color: #334155;"><strong style="color: #171717;">Details:</strong> {{ $details }}</div>
                    @else
                        <div class="mb-3"></div>
                    @endif
                @else
                    <div class="small p-2 rounded-3 bk-inner-surface mb-3" style="color: #475569;">Address details unavailable.</div>
                @endif

                <div class="bk-schedule-box-collect p-2 px-3 mb-2 small">
                    <div class="fw-semibold" style="color: #1D4ED8;">
                        <i class="mdi mdi-calendar me-1"></i>Collection
                    </div>
                    <div style="color: #1E293B;">
                        {{ $schedule['collection_date'] ?? $schedule['pickup_date'] ?? 'Not scheduled' }}
                        <span class="ms-1" style="color: #475569;"><i class="mdi mdi-clock me-1"></i>{{ $schedule['collection_slot'] ?? $schedule['pickup_slot'] ?? '-' }}</span>
                    </div>
                </div>

                <div class="bk-schedule-box-deliver p-2 px-3 small">
                    <div class="fw-semibold" style="color: #047857;">
                        <i class="mdi mdi-calendar-check me-1"></i>Delivery
                    </div>
                    <div style="color: #1E293B;">
                        {{ $schedule['delivery_date'] ?? 'Not scheduled' }}
                        <span class="ms-1" style="color: #475569;"><i class="mdi mdi-clock me-1"></i>{{ $schedule['delivery_slot'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Payment Summary -->
        <div class="col-12 col-lg-4">
            <div class="bk-card bk-card-theme--purple p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h6 class="bk-card-heading mb-0">
                            <span class="bk-card-icon"><i class="mdi mdi-credit-card"></i></span>
                            <span>Payment Summary</span>
                        </h6>
                        @if($isInvoiceEligible)
                            <button type="button" id="btnOpenPrintInvoiceModal" class="bk-btn-print-invoice">
                                <i class="mdi mdi-printer"></i>
                                <span>Print Invoice</span>
                            </button>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span class="bk-label">Gateway</span>
                        <span class="fw-bold text-uppercase" style="color: #171717;">{{ $booking->payment_gateway }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span class="bk-label">Payment Status</span>
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $payBadgeStyle }}">
                            {{ $pricingSummary['payment_status_label'] }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span class="bk-label">Pricing Status</span>
                        <span class="badge rounded-pill px-2 py-1 fw-semibold"
                              style="{{ $pricingSummary['is_pricing_complete'] ? 'background-color: #D1FAE5; color: #065F46;' : 'background-color: #FEF3C7; color: #92400E;' }}">
                            {{ $pricingSummary['pricing_status_label'] }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span class="bk-label">Original Booking Estimate</span>
                        <span class="fw-semibold" style="color: #334155;">{{ $currencyService->formatMinor((int) $booking->estimated_amount_minor, $booking->currency) }}</span>
                    </div>

                    <hr class="my-2" style="border-color: #DDD6FE;">

                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="bk-label">Items Subtotal</span>
                        <span class="fw-bold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['items_subtotal_minor'], $booking->currency) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="bk-label">Service Fee ({{ $booking->service_fee_override_mode }})</span>
                        <span class="fw-semibold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['service_fee_minor'], $booking->currency) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="bk-label">Collection &amp; Delivery Fee ({{ $booking->delivery_fee_override_mode }})</span>
                        <span class="fw-semibold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['delivery_fee_minor'], $booking->currency) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="bk-label">Tax</span>
                        <span class="fw-semibold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['tax_minor'], $booking->currency) }}</span>
                    </div>
                    @if((int) $pricingSummary['tip_minor'] > 0)
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="bk-label">Driver Tip</span>
                            <span class="fw-semibold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['tip_minor'], $booking->currency) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span class="bk-label">Discount</span>
                        <span class="fw-semibold" style="color: #DC2626;">-{{ $currencyService->formatMinor((int) $pricingSummary['discount_minor'], $booking->currency) }}</span>
                    </div>
                </div>

                <div class="bk-final-total-box p-2 px-3 mt-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="color: #171717;">Final Total</span>
                        @if($pricingSummary['is_pricing_complete'])
                            <span class="fs-5 fw-bold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['final_total_minor'], $booking->currency) }}</span>
                        @else
                            <span class="fw-bold small" style="color: #B45309;">Final pricing pending</span>
                        @endif
                    </div>
                    @if(!$pricingSummary['is_pricing_complete'] && !empty($pricingSummary['pending_reasons']))
                        <div class="small text-end mt-1" style="color: #B45309;">{{ $pricingSummary['pending_reasons'][0] }}</div>
                    @endif
                </div>

                @if(isset($financialAllocation))
                    @php
                        $faMargin = (int) $financialAllocation['admin_delivery_margin_minor'];
                        $faShortfall = $faMargin < 0;
                    @endphp
                    <div class="mt-3 p-2 px-3 rounded-3 small" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;" id="paymentSummaryAllocationBox">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold" style="color: #1E293B; font-size: 0.78rem;">
                                <i class="mdi mdi-cash-multiple text-primary me-1"></i>Delivery &amp; Admin Settlement
                            </span>
                            <span class="badge bg-{{ $financialAllocation['financial_status_badge'] }}-subtle text-{{ $financialAllocation['financial_status_badge'] }}" id="summaryFinancialStatusBadge" style="font-size: 0.68rem;">
                                {{ $financialAllocation['financial_status'] }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Collection Delivery Payment</span>
                            <span class="fw-semibold text-dark" id="summaryColPayment">{{ $currencyService->formatMinor((int) $financialAllocation['collection_payment_minor'], $booking->currency) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Final Delivery Payment</span>
                            <span class="fw-semibold text-dark" id="summaryFinPayment">{{ $currencyService->formatMinor((int) $financialAllocation['final_payment_minor'], $booking->currency) }}</span>
                        </div>
                        @if((int) $financialAllocation['customer_tip_minor'] > 0)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted">Tip Split (50% / 50%)</span>
                                <span class="fw-semibold text-dark">{{ $currencyService->formatMinor((int) $financialAllocation['collection_tip_share_minor'], $booking->currency) }} / {{ $currencyService->formatMinor((int) $financialAllocation['final_tip_share_minor'], $booking->currency) }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center mb-1 pt-1 border-top">
                            <span class="text-muted">Admin Delivery Margin</span>
                            <span class="fw-bold {{ $faShortfall ? 'text-danger' : 'text-success' }}" id="summaryAdminMargin">
                                {{ $faShortfall ? '-' : '' }}{{ $currencyService->formatMinor(abs($faMargin), $booking->currency) }}
                                @if($faShortfall)
                                    <span class="badge bg-danger-subtle text-danger ms-1" style="font-size: 0.65rem;">Shortfall</span>
                                @endif
                            </span>
                        </div>
                        @if((int) $financialAllocation['advance_received_minor'] > 0)
                            <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                                <span class="text-muted">Advance Settlement</span>
                                <span class="badge bg-{{ $financialAllocation['advance_badge'] }}-subtle text-{{ $financialAllocation['advance_badge'] }}" style="font-size: 0.68rem;">
                                    {{ $financialAllocation['advance_status'] }} ({{ $currencyService->formatMinor((int) $financialAllocation['advance_received_minor'], $booking->currency) }})
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(isset($profitSummary))
        @php
            $psTotalProfit = (int) $profitSummary['total_profit_minor'];
            $psIsPositive = $psTotalProfit >= 0;
            $psCanShowNumbers = $profitSummary['is_finalized'] || $profitSummary['has_provisional_estimate'];
        @endphp
        <!-- Booking Profit Summary (Compact Contribution Profit Card) -->
        <div class="card border-0 mb-3" id="bookingProfitSummaryCard"
             style="background-color: #F5F3FF; border: 1px solid #C4B5FD !important; border-radius: 16px; box-shadow: 0 4px 14px rgba(109, 40, 217, 0.06);">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                              style="width: 32px; height: 32px; background-color: #EDE9FE; color: #6D28D9;">
                            <i class="mdi mdi-chart-line"></i>
                        </span>
                        <div>
                            <h6 class="fw-bold mb-0" style="color: #6D28D9;">Booking Profit Summary</h6>
                            <div class="small" style="color: #5B21B6; font-size: 0.76rem;">Contribution profit before unrecorded business overhead ({{ $profitSummary['currency'] }})</div>
                        </div>
                    </div>
                    <span id="profitSummaryStatusBadge"
                          class="badge rounded-pill px-3 py-1 bg-{{ $profitSummary['status_badge'] }}-subtle text-{{ $profitSummary['status_badge'] }}"
                          style="font-size: 0.74rem;">
                        {{ $profitSummary['status_label'] }}
                    </span>
                </div>

                @if(!$psCanShowNumbers)
                    <div id="profitNotFinalizedNotice" class="bg-white rounded-3 p-3 border small text-center" style="border-color: #DDD6FE !important; color: #64748B;">
                        <i class="mdi mdi-clock-outline text-warning me-1"></i>
                        <strong>Profit Not Finalized</strong> &mdash; Complete store assignment and operational pricing to view provisional or finalized booking profit.
                    </div>
                @endif

                <div id="profitBreakdownContent" class="{{ $psCanShowNumbers ? '' : 'd-none' }}">
                    <div class="row g-2 g-md-3 mb-3">
                        <!-- Block 1: Store Contribution -->
                        <div class="col-12 col-lg-4">
                            <div class="bg-white rounded-3 p-3 h-100 border small" style="border-color: #DDD6FE !important;">
                                <div class="fw-bold text-uppercase mb-2" style="color: #6D28D9; font-size: 0.72rem; letter-spacing: 0.04em;">Store Contribution</div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Eligible Service Revenue</span>
                                    <span class="fw-semibold text-dark" id="profitEligibleService">{{ $profitSummary['formatted']['eligible_service_revenue'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">
                                        Store Net Earnings
                                        @if($profitSummary['store_commission_rate'] !== null)
                                            <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.65rem;">{{ number_format((float) $profitSummary['store_commission_rate'], 1) }}% comm.</span>
                                        @endif
                                    </span>
                                    <span class="fw-semibold text-danger" id="profitStoreNet">-{{ $profitSummary['formatted']['store_net_earnings'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="fw-semibold text-dark">Store Contribution</span>
                                    <strong class="{{ (int) $profitSummary['store_contribution_minor'] >= 0 ? 'text-success' : 'text-danger' }}" id="profitStoreContribution">{{ $profitSummary['formatted']['store_contribution'] }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Block 2: Delivery Contribution -->
                        <div class="col-12 col-lg-4">
                            <div class="bg-white rounded-3 p-3 h-100 border small" style="border-color: #DDD6FE !important;">
                                <div class="fw-bold text-uppercase mb-2" style="color: #6D28D9; font-size: 0.72rem; letter-spacing: 0.04em;">Delivery Contribution</div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Customer Delivery Charge</span>
                                    <span class="fw-semibold text-dark" id="profitDeliveryCharge">{{ $profitSummary['formatted']['delivery_fee'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Collection Person Payment</span>
                                    <span class="fw-semibold text-danger" id="profitCollectionPay">-{{ $profitSummary['formatted']['collection_payment'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Final Person Payment</span>
                                    <span class="fw-semibold text-danger" id="profitFinalPay">-{{ $profitSummary['formatted']['final_payment'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="fw-semibold text-dark" id="profitDeliveryLabel">{{ (int) $profitSummary['delivery_contribution_minor'] < 0 ? 'Delivery Loss' : 'Delivery Profit' }}</span>
                                    <strong class="{{ (int) $profitSummary['delivery_contribution_minor'] >= 0 ? 'text-success' : 'text-danger' }}" id="profitDeliveryContribution">{{ $profitSummary['formatted']['delivery_contribution'] }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Block 3: Other Platform & Pass-Throughs -->
                        <div class="col-12 col-lg-4">
                            <div class="bg-white rounded-3 p-3 h-100 border small" style="border-color: #DDD6FE !important;">
                                <div class="fw-bold text-uppercase mb-2" style="color: #6D28D9; font-size: 0.72rem; letter-spacing: 0.04em;">Platform Fees &amp; Pass-Throughs</div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Platform Service Fee</span>
                                    <span class="fw-semibold text-dark" id="profitServiceFee">{{ $profitSummary['formatted']['service_fee'] }}</span>
                                </div>
                                @if((int) $profitSummary['discount_minor'] > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">Booking Discount</span>
                                        <span class="fw-semibold text-danger" id="profitDiscount">-{{ $profitSummary['formatted']['discount'] }}</span>
                                    </div>
                                @endif
                                @if((int) $profitSummary['refunded_minor'] > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">Recorded Refunds</span>
                                        <span class="fw-semibold text-danger" id="profitRefunded">-{{ $profitSummary['formatted']['refunded'] }}</span>
                                    </div>
                                @endif
                                @if((int) $profitSummary['tax_minor'] > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">Authority Tax (Excluded)</span>
                                        <span class="text-muted" id="profitTaxExcluded">{{ $profitSummary['formatted']['tax'] }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-2">
                                    <span class="fw-semibold text-dark">Other Contribution</span>
                                    <strong class="{{ (int) $profitSummary['other_contribution_minor'] >= 0 ? 'text-success' : 'text-danger' }}" id="profitOtherContribution">{{ $profitSummary['formatted']['other_contribution'] }}</strong>
                                </div>
                                <div class="p-2 rounded-2" style="background-color: #F8FAFC; border: 1px dashed #CBD5E1; font-size: 0.72rem;">
                                    <div class="d-flex justify-content-between text-muted">
                                        <span>Customer Tips (Pass-Through):</span>
                                        <span class="fw-semibold text-dark" id="profitCustomerTip">{{ $profitSummary['formatted']['customer_tip'] }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted">
                                        <span>Allocated to Couriers (50/50):</span>
                                        <span class="fw-semibold text-dark" id="profitAllocatedTips">{{ $profitSummary['formatted']['allocated_tips'] }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between fw-semibold text-dark">
                                        <span>Tip Net Profit Contribution:</span>
                                        <span>{{ $currencyService->formatMinor(0, $profitSummary['currency']) }}</span>
                                    </div>
                                    @if($profitSummary['has_tip_reconciliation_issue'])
                                        <div class="text-danger fw-semibold mt-1">
                                            <i class="mdi mdi-alert-outline me-1"></i>Tip allocation mismatch flagged for reconciliation.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Highlight Bar: Total Booking Profit & Contribution Margin % -->
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-md-7">
                            <div id="profitTotalBox" class="rounded-3 p-3 d-flex justify-content-between align-items-center"
                                 style="background-color: {{ $psIsPositive ? '#ECFDF5' : '#FFF1F2' }}; border: 1px solid {{ $psIsPositive ? '#A7F3D0' : '#FECACA' }};">
                                <div>
                                    <div class="fw-bold text-uppercase" style="font-size: 0.74rem; color: {{ $psIsPositive ? '#16A34A' : '#EF4444' }}; letter-spacing: 0.04em;">
                                        Total Booking Profit {{ $profitSummary['is_provisional'] ? '(Provisional)' : '' }}
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.74rem;">Store + Delivery + Platform Fees &minus; Discounts/Refunds</div>
                                </div>
                                <div id="profitTotalValue" class="fs-4 fw-bold" style="color: {{ $psIsPositive ? '#16A34A' : '#EF4444' }};">
                                    {{ $profitSummary['formatted']['total_profit'] }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <div class="bg-white rounded-3 p-3 d-flex justify-content-between align-items-center border h-100" style="border-color: #DDD6FE !important;">
                                <div>
                                    <div class="fw-bold text-uppercase" style="font-size: 0.74rem; color: #6D28D9; letter-spacing: 0.04em;">Contribution Margin %</div>
                                    <div class="small text-muted" style="font-size: 0.74rem;">
                                        Recognized Net Revenue: <strong class="text-dark" id="profitRecognizedRevenue">{{ $profitSummary['formatted']['recognized_net_revenue'] }}</strong>
                                    </div>
                                </div>
                                <div id="profitMarginPctValue" class="fs-4 fw-bold text-dark">
                                    {{ $profitSummary['margin_percentage_formatted'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Collection & Payment Verification -->
    <div class="bk-card p-3 p-md-4 mb-3" id="collectionVerificationSection">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h6 class="fw-bold mb-0" style="color: #171717;">
                <i class="mdi mdi-shield-check-outline text-primary me-1"></i>Collection &amp; Payment Verification
            </h6>
            @can('view-delivery-settlements', 'admin')
                <a href="{{ route('admin.delivery-person-settlements.index') }}" class="btn btn-xs btn-outline-primary">Daily Cash Settlements</a>
            @endcan
        </div>
        <div class="row g-2 small">
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-3 bg-light border">
                    <div class="text-muted" style="font-size: 0.72rem;">Collection Delivery Person</div>
                    <div class="fw-semibold text-dark">{{ $collectionAssignment?->deliveryPerson?->full_name ?? 'Unassigned' }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-3 bg-light border">
                    <div class="text-muted" style="font-size: 0.72rem;">Collection OTP Status</div>
                    @if($booking->collection_otp_verified_at)
                        <div class="fw-semibold text-success">Verified ({{ $booking->collection_otp_verified_at->format('d M Y, H:i') }})</div>
                    @else
                        <div class="fw-semibold text-warning-emphasis">Pending Verification</div>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-3 bg-light border">
                    <div class="text-muted" style="font-size: 0.72rem;">Customer Payment Channel</div>
                    <div class="fw-semibold text-dark">{{ ucfirst(str_replace('_', ' ', (string) ($booking->customer_payment_channel ?: $booking->payment_gateway ?: 'Pending'))) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-3 bg-light border">
                    <div class="text-muted" style="font-size: 0.72rem;">Cash Collected by Delivery Person</div>
                    <div class="fw-semibold text-dark">{{ $currencyService->formatMinor((int) $booking->cash_collected_minor, $booking->currency) }}</div>
                </div>
            </div>
        </div>
        @if($booking->currentStoreHandover)
            @php $admHandover = $booking->currentStoreHandover; @endphp
            <div class="mt-2 p-2 px-3 rounded-3 bg-light border small d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong>Store Handover ({{ $admHandover->request_reference }}):</strong>
                    @if($admHandover->status === 'accepted')
                        <span class="text-success fw-semibold">Accepted by {{ $admHandover->acceptedByStoreUser?->name ?? 'Store Staff' }} at {{ $admHandover->accepted_at?->format('d M Y, H:i') }}</span>
                    @elseif($admHandover->status === 'pending')
                        <span class="text-warning-emphasis fw-semibold">Pending Store Acceptance (Requested {{ $admHandover->requested_at?->format('d M Y, H:i') }})</span>
                    @elseif($admHandover->status === 'rejected')
                        <span class="text-danger fw-semibold">Issue Reported: {{ $admHandover->rejection_reason }}</span>
                    @else
                        <span class="text-muted">{{ ucfirst($admHandover->status) }}</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Store & Delivery Assignments (Full-Width Compact Section) -->
    <div class="bk-card p-3 p-md-4 mb-3" id="storeDeliveryAssignmentsSection">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-0" style="color: #171717;">Store &amp; Delivery Assignments</h6>
                <div class="small" style="color: #64748B;">Authoritative store, collection delivery person, and final delivery person for this booking.</div>
            </div>
            @if($customerPostcode)
                <span class="badge rounded-pill px-3 py-1" style="background-color: #FEF3C7; color: #92400E;">
                    Customer Postcode: {{ $customerPostcode }}
                </span>
            @endif
        </div>

        <div class="row g-3">
            <!-- Card 1: Assigned Store (Soft Rose) -->
            <div class="col-12 col-lg-4">
                <div class="bk-card-theme--rose p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center gap-1 mb-2">
                            <span class="bk-card-heading text-nowrap" style="font-size: 0.78rem; gap: 0.35rem;">
                                <span class="bk-card-icon" style="width: 24px; height: 24px; font-size: 0.8rem;"><i class="mdi mdi-store"></i></span>
                                <span>Assigned Store</span>
                            </span>
                            <span id="cardStoreMatchBadge" class="badge rounded-pill flex-shrink-0 {{ $currentStoreAssignment ? ($currentStoreAssignment->postcode_matched ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis') : 'bg-secondary-subtle text-secondary' }}" style="font-size: 0.65rem; padding: 0.22rem 0.48rem;">
                                {{ $currentStoreAssignment ? ($currentStoreAssignment->postcode_matched ? 'Postcode Match' : 'Manual Selection') : 'Unassigned' }}
                            </span>
                        </div>

                        <div id="cardStoreDetails" class="{{ $currentStoreAssignment ? '' : 'd-none' }} small">
                            <div id="cardStoreName" class="fw-bold mb-1" style="font-size: 0.95rem; color: #171717;">{{ $currentStoreAssignment?->store_name_snapshot ?? '' }}</div>
                            <div id="cardStoreAddress" class="mb-2" style="white-space: normal; word-break: break-word; line-height: 1.45; color: #334155;">{{ $currentStoreAssignment?->store_address_snapshot ?? '' }}</div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-white text-dark border">Postcode: <strong id="cardStorePostcode">{{ $currentStoreAssignment?->store_postcode_snapshot ?? '' }}</strong></span>
                                <span class="badge bg-white text-dark border"><i class="mdi mdi-phone text-success me-1"></i><span id="cardStorePhone">{{ ($currentStoreAssignment && filled($currentStoreAssignment->store_phone_snapshot)) ? $currentStoreAssignment->store_phone_snapshot : 'Contact number not provided' }}</span></span>
                            </div>
                            <div style="font-size: 0.78rem; color: #475569;">
                                Assigned: <span id="cardStoreAssignedAt" class="fw-semibold" style="color: #171717;">{{ $currentStoreAssignment?->assigned_at?->format('d M Y, H:i') ?? '-' }}</span>
                            </div>
                        </div>

                        <div id="cardStoreEmpty" class="{{ $currentStoreAssignment ? 'd-none' : '' }} small py-2" style="color: #475569;">
                            Not assigned yet.
                        </div>
                    </div>

                    @can('manage-bookings', 'admin')
                        @if(!$isTerminal)
                            <div class="mt-3 pt-2 border-top d-flex justify-content-end" style="border-color: rgba(190, 18, 60, 0.15) !important;">
                                <button type="button"
                                        class="btn btn-sm bk-btn-yellow px-3 py-1 js-open-assign-store"
                                        id="btnCardAssignStore"
                                        style="border-radius: 8px; font-size: 0.78rem;">
                                    {{ $currentStoreAssignment ? 'Change Store' : 'Assign Store' }}
                                </button>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>

            <!-- Card 2: Collection Delivery Person (Soft Cyan) -->
            @php
                $collectionMatched = $collectionAssignment && $customerPostcode
                    ? $collectionAssignment->deliveryPerson?->postcodes?->contains('postcode_compact', \Modules\Admin\Models\UkAddress::compactPostcode($customerPostcode))
                    : false;
            @endphp
            <div class="col-12 col-lg-4">
                <div class="bk-card-theme--cyan p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center gap-1 mb-2">
                            <span class="bk-card-heading text-nowrap" style="font-size: 0.78rem; gap: 0.35rem;">
                                <span class="bk-card-icon" style="width: 24px; height: 24px; font-size: 0.8rem;"><i class="mdi mdi-motorbike"></i></span>
                                <span>Collection Delivery Person</span>
                            </span>
                            <span id="cardCollectionBadge" class="badge rounded-pill flex-shrink-0 {{ $collectionAssignment ? ($collectionMatched ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis') : 'bg-secondary-subtle text-secondary' }}" style="font-size: 0.65rem; padding: 0.22rem 0.48rem;">
                                {{ $collectionAssignment ? ($collectionMatched ? 'Covers ' . $customerPostcode : 'Manual Assignment') : 'Unassigned' }}
                            </span>
                        </div>

                        <div id="cardCollectionDetails" class="{{ $collectionAssignment ? '' : 'd-none' }} small">
                            @php
                                $collectionPerson = $collectionAssignment?->deliveryPerson;
                                $collectionAddress = $collectionPerson?->full_residential_address
                                    ?: (implode(', ', array_filter([$collectionPerson?->address_line_1, $collectionPerson?->city, $collectionPerson?->residential_postcode])) ?: 'Address not provided');
                            @endphp
                            <div id="cardCollectionName" class="fw-bold mb-1" style="font-size: 0.95rem; color: #171717;">
                                {{ $collectionPerson?->full_name ?? '' }}
                                <span id="cardCollectionCode" class="fw-normal" style="color: #475569;">({{ $collectionPerson?->code ?? '' }})</span>
                            </div>
                            <div id="cardCollectionAddress" class="mb-2" style="white-space: normal; word-break: break-word; line-height: 1.45; color: #334155;">{{ $collectionPerson ? $collectionAddress : '' }}</div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-white text-dark border"><i class="mdi mdi-phone text-success me-1"></i><span id="cardCollectionPhone">{{ $collectionPerson?->phone_display ?: 'Not provided' }}</span></span>
                                <span class="badge bg-primary-subtle text-primary" id="cardCollectionStatus">{{ $collectionAssignment?->status_label ?? '' }}</span>
                            </div>
                            <div style="font-size: 0.78rem; color: #475569;">
                                Assigned: <span id="cardCollectionAssignedAt" class="fw-semibold" style="color: #171717;">{{ $collectionAssignment?->assigned_at?->format('d M Y, H:i') ?? '-' }}</span>
                            </div>
                            @php
                                $colRateMinor = (int) ($collectionAssignment?->rate_minor ?? ($financialAllocation['default_collection_payment_minor'] ?? 0));
                                $colTipMinor = (int) ($collectionAssignment?->tip_share_minor ?? ($financialAllocation['collection_tip_share_minor'] ?? 0));
                                $colTotalMinor = $colRateMinor + $colTipMinor;
                            @endphp
                            <div class="mt-2 pt-2 border-top" style="border-color: rgba(14, 116, 144, 0.15) !important; font-size: 0.78rem;">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Delivery Payment:</span>
                                    <strong id="cardCollectionPay" class="text-dark">{{ $currencyService->formatMinor($colRateMinor, $booking->currency) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Tip Share (50%):</span>
                                    <strong id="cardCollectionTip" class="text-dark">{{ $currencyService->formatMinor($colTipMinor, $booking->currency) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="fw-semibold text-dark">Total Expected:</span>
                                    <strong id="cardCollectionTotal" class="text-primary">{{ $currencyService->formatMinor($colTotalMinor, $booking->currency) }}</strong>
                                </div>
                            </div>
                        </div>

                        <div id="cardCollectionEmpty" class="{{ $collectionAssignment ? 'd-none' : '' }} small py-2" style="color: #475569;">
                            Not assigned yet.
                        </div>
                    </div>

                    @can('manage-bookings', 'admin')
                        @if(!$isTerminal)
                            <div class="mt-3 pt-2 border-top d-flex justify-content-end" style="border-color: rgba(14, 116, 144, 0.15) !important;">
                                <button type="button"
                                        class="btn btn-sm bk-btn-yellow px-3 py-1 js-open-assign-courier"
                                        id="btnCardAssignCollection"
                                        data-assignment-type="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION }}"
                                        style="border-radius: 8px; font-size: 0.78rem;">
                                    {{ $collectionAssignment ? 'Change Delivery Person' : 'Assign Delivery Person' }}
                                </button>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>

            <!-- Card 3: Final Delivery Person (Soft Cyan) -->
            @php
                $finalMatched = $finalDeliveryAssignment && $customerPostcode
                    ? $finalDeliveryAssignment->deliveryPerson?->postcodes?->contains('postcode_compact', \Modules\Admin\Models\UkAddress::compactPostcode($customerPostcode))
                    : false;
                $finalDeliveryStageReached = in_array($booking->booking_status, [
                    \App\Models\Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
                    \App\Models\Booking::STATUS_ON_DELIVERY,
                    \App\Models\Booking::STATUS_COMPLETED,
                ], true) || $finalDeliveryAssignment !== null;
            @endphp
            <div class="col-12 col-lg-4">
                <div class="bk-card-theme--cyan p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center gap-1 mb-2">
                            <span class="bk-card-heading text-nowrap" style="font-size: 0.78rem; gap: 0.35rem;">
                                <span class="bk-card-icon" style="width: 24px; height: 24px; font-size: 0.8rem;"><i class="mdi mdi-truck-delivery"></i></span>
                                <span>Final Delivery Person</span>
                            </span>
                            <span id="cardFinalBadge" class="badge rounded-pill flex-shrink-0 {{ $finalDeliveryAssignment ? ($finalMatched ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis') : 'bg-secondary-subtle text-secondary' }}" style="font-size: 0.65rem; padding: 0.22rem 0.48rem;">
                                {{ $finalDeliveryAssignment ? ($finalMatched ? 'Covers ' . $customerPostcode : 'Manual Assignment') : 'Pending' }}
                            </span>
                        </div>

                        <div id="cardFinalDetails" class="{{ $finalDeliveryAssignment ? '' : 'd-none' }} small">
                            @php
                                $finalPerson = $finalDeliveryAssignment?->deliveryPerson;
                                $finalAddress = $finalPerson?->full_residential_address
                                    ?: (implode(', ', array_filter([$finalPerson?->address_line_1, $finalPerson?->city, $finalPerson?->residential_postcode])) ?: 'Address not provided');
                            @endphp
                            <div id="cardFinalName" class="fw-bold mb-1" style="font-size: 0.95rem; color: #171717;">
                                {{ $finalPerson?->full_name ?? '' }}
                                <span id="cardFinalCode" class="fw-normal" style="color: #475569;">({{ $finalPerson?->code ?? '' }})</span>
                            </div>
                            <div id="cardFinalAddress" class="mb-2" style="white-space: normal; word-break: break-word; line-height: 1.45; color: #334155;">{{ $finalPerson ? $finalAddress : '' }}</div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-white text-dark border"><i class="mdi mdi-phone text-success me-1"></i><span id="cardFinalPhone">{{ $finalPerson?->phone_display ?: 'Not provided' }}</span></span>
                                <span class="badge bg-success-subtle text-success" id="cardFinalStatus">{{ $finalDeliveryAssignment?->status_label ?? '' }}</span>
                            </div>
                            <div style="font-size: 0.78rem; color: #475569;">
                                Assigned: <span id="cardFinalAssignedAt" class="fw-semibold" style="color: #171717;">{{ $finalDeliveryAssignment?->assigned_at?->format('d M Y, H:i') ?? '-' }}</span>
                            </div>
                            @php
                                $finRateMinor = (int) ($finalDeliveryAssignment?->rate_minor ?? ($financialAllocation['default_final_payment_minor'] ?? 0));
                                $finTipMinor = (int) ($finalDeliveryAssignment?->tip_share_minor ?? ($financialAllocation['final_tip_share_minor'] ?? 0));
                                $finTotalMinor = $finRateMinor + $finTipMinor;
                            @endphp
                            <div class="mt-2 pt-2 border-top" style="border-color: rgba(14, 116, 144, 0.15) !important; font-size: 0.78rem;">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Delivery Payment:</span>
                                    <strong id="cardFinalPay" class="text-dark">{{ $currencyService->formatMinor($finRateMinor, $booking->currency) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Tip Share (50%):</span>
                                    <strong id="cardFinalTip" class="text-dark">{{ $currencyService->formatMinor($finTipMinor, $booking->currency) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="fw-semibold text-dark">Total Expected:</span>
                                    <strong id="cardFinalTotal" class="text-success">{{ $currencyService->formatMinor($finTotalMinor, $booking->currency) }}</strong>
                                </div>
                            </div>
                        </div>

                        <div id="cardFinalEmpty" class="{{ $finalDeliveryAssignment ? 'd-none' : '' }} small py-2" style="color: #475569;">
                            {{ $finalDeliveryStageReached ? 'Not assigned yet.' : 'Pending until Delivery Man Assigned for Store Pickup.' }}
                        </div>
                    </div>

                    @can('manage-bookings', 'admin')
                        @if(!$isTerminal)
                            <div class="mt-3 pt-2 border-top d-flex justify-content-end" style="border-color: rgba(14, 116, 144, 0.15) !important;">
                                <button type="button"
                                        class="btn btn-sm bk-btn-yellow px-3 py-1 js-open-assign-courier"
                                        id="btnCardAssignFinal"
                                        data-assignment-type="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY }}"
                                        style="border-radius: 8px; font-size: 0.78rem;">
                                    {{ $finalDeliveryAssignment ? 'Change Delivery Person' : 'Assign Delivery Person' }}
                                </button>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Read-Only Audit History -->
    <div class="bk-card p-3 p-md-4">
        <h6 class="fw-bold mb-3" style="color: #171717;">Read-Only Audit History</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr class="small">
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Summary</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($booking->auditLogs->sortByDesc('id') as $log)
                        <tr>
                            <td class="small text-nowrap" style="color: #64748B;">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="small">{{ $log->actor_name }} <span style="color: #64748B;">({{ $log->actor_type }})</span></td>
                            <td><span class="badge bg-light text-dark border">{{ $log->action }}</span></td>
                            <td class="small">{{ $log->summary }}</td>
                            <td class="small" style="color: #64748B;">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-3 small" style="color: #64748B;">No audit logs recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@can('cancel-bookings', 'admin')
    @if(!$isTerminal)
        <div class="modal fade" id="cancelShowBookingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}">
                    @csrf
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Cancel Booking {{ $booking->reference }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">The booking and payment records will be preserved. The customer will be notified by email.</p>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
                                <textarea name="cancellation_reason" class="form-control" rows="3" required minlength="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan

{{-- Assign Store Bootstrap 5 Modal --}}
@can('manage-bookings', 'admin')
@if(!$isTerminal)
<div class="modal fade assignment-modal" id="assignStoreModal" tabindex="-1" aria-labelledby="assignStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom bg-white py-2 px-3 flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="assignment-modal-icon">
                        <i class="mdi mdi-store"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="assignStoreModalLabel" style="color: #171717;">{{ $currentStoreAssignment ? 'Change Store' : 'Assign Store' }}</h6>
                        <div class="small text-muted" style="font-size: 0.78rem;">Select the laundry store responsible for this booking.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignStoreAjaxForm" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
                @csrf
                <div class="modal-body p-3">
                    <div id="assignStoreErrors" class="alert alert-danger py-2 px-3 small d-none mb-2"></div>

                    <div class="p-2 px-3 rounded-3 mb-3 d-flex justify-content-between align-items-center small" style="background-color: #EFF6FF; border: 1px solid #BFDBFE;">
                        <span class="fw-semibold" style="color: #1D4ED8;"><i class="mdi mdi-map-marker me-1"></i>Customer Collection Postcode</span>
                        <span class="badge rounded-pill px-2 py-1" style="background-color: #FFFFFF; color: #171717; border: 1px solid #93C5FD;">
                            {{ $customerPostcode ?? 'Not provided' }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <label for="assignStoreSelect" class="form-label small fw-semibold text-dark mb-1">Select Laundry Store <span class="text-danger">*</span></label>
                        <select id="assignStoreSelect" name="store_id" class="form-select assignment-select2" style="width: 100%;" required>
                            @if($currentStoreAssignment)
                                <option value="{{ $currentStoreAssignment->store_id }}" selected>
                                    {{ $currentStoreAssignment->store_name_snapshot }} ({{ $currentStoreAssignment->store_postcode_snapshot ?: 'No Postcode' }})
                                </option>
                            @else
                                <option value="">Search store by name, code, postcode, or city...</option>
                            @endif
                        </select>
                    </div>

                    <div id="assignStorePreviewCard" class="p-3 rounded-3 bg-white border mb-3 small {{ $currentStoreAssignment ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <strong id="previewStoreName" class="text-dark">{{ $currentStoreAssignment?->store_name_snapshot ?? '' }}</strong>
                            <span id="previewMatchBadge" class="assignment-badge {{ $currentStoreAssignment?->postcode_matched ? 'assignment-badge--match' : 'assignment-badge--manual' }}">
                                {{ $currentStoreAssignment?->postcode_matched ? 'Postcode Match' : 'Manual Selection' }}
                            </span>
                        </div>
                        <div id="previewStoreAddress" class="text-muted bk-assigned-store-address">{{ $currentStoreAssignment?->store_address_snapshot ?? '' }}</div>
                        <div class="d-flex flex-wrap justify-content-between gap-2 text-muted mt-2 pt-2 border-top">
                            <span>Postcode: <strong id="previewStorePostcode" class="text-dark">{{ $currentStoreAssignment?->store_postcode_snapshot ?? '' }}</strong></span>
                            <span><i class="mdi mdi-phone text-success me-1"></i><span id="previewStorePhone">{{ ($currentStoreAssignment && filled($currentStoreAssignment->store_phone_snapshot)) ? $currentStoreAssignment->store_phone_snapshot : 'Contact number not provided' }}</span></span>
                        </div>
                    </div>

                    <div id="assignStoreOverrideWrap" class="mb-1 {{ ($currentStoreAssignment && !$currentStoreAssignment->postcode_matched) ? '' : 'd-none' }}">
                        <label for="assignStoreOverrideReason" class="form-label small fw-semibold text-warning-emphasis mb-1">
                            Override Reason <span class="text-danger">*</span>
                        </label>
                        <textarea id="assignStoreOverrideReason"
                                  name="override_reason"
                                  rows="2"
                                  class="form-control"
                                  style="border-radius: 10px;"
                                  placeholder="Please explain why a store outside the customer's postcode was selected."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-light rounded-3 px-3" style="min-height: 38px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveStoreAssignment" class="btn btn-sm bk-btn-yellow px-3" style="border-radius: 8px; min-height: 38px;">
                        <i class="mdi mdi-check me-1"></i>Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Assign Delivery Person Modal (Collection & Final Delivery) --}}
<div class="modal fade assignment-modal" id="assignCourierModal" tabindex="-1" aria-labelledby="assignCourierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-2 px-3 flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="assignment-modal-icon">
                        <i class="mdi mdi-account-check"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="assignCourierModalLabel" style="color: #171717;">Assign Collection Delivery Person</h6>
                        <div class="small text-muted" id="assignCourierSubtitle" style="font-size: 0.78rem;">Select an eligible active delivery person for this booking leg.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignCourierAjaxForm" class="d-flex flex-column flex-grow-1" style="min-height: 0;">
                @csrf
                <input type="hidden" id="assignCourierTypeInput" name="assignment_type" value="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION }}">
                <div class="modal-body p-3">
                    <div id="assignCourierErrors" class="alert alert-danger py-2 px-3 small d-none mb-2"></div>

                    <div class="p-2 px-3 rounded-3 mb-3 small" style="background-color: #EFF6FF; border: 1px solid #BFDBFE;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold" style="color: #1D4ED8;"><i class="mdi mdi-map-marker me-1"></i>Customer Postcode</span>
                            <span class="badge rounded-pill px-2 py-1" style="background-color: #FFFFFF; color: #171717; border: 1px solid #93C5FD;">
                                {{ $customerPostcode ?? 'Not provided' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.78rem;">
                            <span>Assigned Store:</span>
                            <strong id="courierModalStoreName" class="text-dark">{{ $currentStoreAssignment?->store_name_snapshot ?? 'Not assigned' }}</strong>
                        </div>
                        <div class="text-muted mt-1" id="courierModalRouteText" style="font-size: 0.76rem;"></div>
                    </div>

                    <div id="assignCourierNoMatchBanner" class="p-2 px-3 rounded-3 small d-none mb-3 d-flex align-items-start gap-2" style="background-color: #FFF8E5; border: 1px solid #FDE68A; color: #92400E;">
                        <i class="mdi mdi-alert-outline flex-shrink-0" style="font-size: 1rem; margin-top: 1px;"></i>
                        <span>No delivery person covers this postcode. Another eligible active delivery person can be selected manually.</span>
                    </div>

                    <div class="mb-3">
                        <label for="assignCourierSelect" class="form-label small fw-semibold text-dark mb-1">Select Delivery Person <span class="text-danger">*</span></label>
                        <select id="assignCourierSelect" name="delivery_person_id" class="form-select assignment-select2" style="width: 100%;" required>
                            <option value="">Search delivery person by name, code, phone, or postcode...</option>
                        </select>
                    </div>

                    <div id="assignCourierPreviewCard" class="p-3 rounded-3 bg-white border mb-3 small d-none">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background-color: #DCFCE7; color: #16A34A;">
                                    <i class="mdi mdi-check"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-muted" style="font-size: 0.72rem;">Selected Delivery Person</div>
                                    <div class="text-truncate">
                                        <strong id="previewCourierName" class="text-dark"></strong>
                                        <span id="previewCourierCode" class="text-muted ms-1"></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <i class="mdi mdi-phone text-success me-1"></i><span id="previewCourierPhone"></span>
                                        &bull; Active jobs: <strong id="previewCourierLoad" class="text-dark">0</strong>
                                    </div>
                                </div>
                            </div>
                            <span id="previewCourierBadge" class="assignment-badge"></span>
                        </div>
                    </div>

                    <div id="assignCourierOverrideWrap" class="mb-1 d-none">
                        <label for="assignCourierOverrideReason" class="form-label small fw-semibold text-warning-emphasis mb-1">
                            Override / Reassignment Reason <span class="text-danger">*</span>
                        </label>
                        <textarea id="assignCourierOverrideReason"
                                  name="override_reason"
                                  rows="2"
                                  class="form-control"
                                  style="border-radius: 10px;"
                                  placeholder="Explain why this delivery person is being assigned outside covered postcodes or reassigned."></textarea>
                    </div>

                    <div class="mt-3 p-3 rounded-3 small" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                        <div class="fw-bold text-dark mb-2">
                            <i class="mdi mdi-cash-multiple text-primary me-1"></i>Delivery Payment &amp; Tip Allocation
                        </div>
                        <div class="d-flex justify-content-between text-muted mb-1">
                            <span>Booking Collection &amp; Delivery Fee</span>
                            <strong class="text-dark" id="modalBookingDeliveryFee">{{ $currencyService->formatMinor((int) ($financialAllocation['delivery_fee_minor'] ?? 0), $booking->currency) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between text-muted mb-1">
                            <span>Customer Tip (Total)</span>
                            <strong class="text-dark" id="modalBookingCustomerTip">{{ $currencyService->formatMinor((int) ($financialAllocation['customer_tip_minor'] ?? 0), $booking->currency) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between text-muted mb-2">
                            <span>Default Delivery Payment (50%)</span>
                            <strong class="text-dark" id="modalDefaultLegPayment">-</strong>
                        </div>

                        <div class="mb-2">
                            <label for="assignCourierDeliveryPayment" class="form-label small fw-semibold text-dark mb-1">
                                Delivery Payment ({{ $booking->currency ?: $currencyService->code() }})
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   id="assignCourierDeliveryPayment"
                                   name="delivery_payment"
                                   class="form-control form-control-sm"
                                   style="border-radius: 8px;"
                                   placeholder="0.00">
                            <div class="form-text" style="font-size: 0.72rem;">Defaults to 50% of the booking delivery fee. You may adjust manually.</div>
                        </div>

                        <div class="d-flex justify-content-between text-muted mb-1">
                            <span>Tip Share (50%)</span>
                            <strong class="text-dark" id="modalCourierTipShare">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="fw-semibold text-dark">Total Expected Earnings</span>
                            <strong class="text-primary" id="modalCourierTotalEarnings">-</strong>
                        </div>

                        <div id="modalAllocationPreviewBox" class="rounded-2 p-2" style="background-color: #ECFDF5; border: 1px solid #A7F3D0;">
                            <div class="d-flex justify-content-between text-muted mb-1" style="font-size: 0.76rem;">
                                <span>Combined Delivery Payout (Collection + Final)</span>
                                <strong class="text-dark" id="modalCombinedPayout">-</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.78rem;">
                                <span class="fw-semibold" id="modalAdminMarginLabel">Admin Delivery Margin</span>
                                <strong id="modalAdminMarginValue" class="text-success">-</strong>
                            </div>
                            <div id="modalShortfallWarning" class="text-danger fw-semibold mt-1 d-none" style="font-size: 0.74rem;">
                                <i class="mdi mdi-alert-circle-outline me-1"></i>Combined delivery payments exceed customer delivery fee; Admin Account will absorb the shortfall on completion.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-light rounded-3 px-3" style="min-height: 38px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveCourierAssignment" class="btn btn-sm bk-btn-yellow px-3" style="border-radius: 8px; min-height: 38px;">
                        <i class="mdi mdi-check me-1"></i>Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan

@if($isInvoiceEligible)
{{-- Print Invoice Preview Modal --}}
<div class="modal fade invoice-preview-modal" id="printInvoiceModal" tabindex="-1" aria-labelledby="printInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-2 px-3 flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="assignment-modal-icon">
                        <i class="mdi mdi-printer"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="printInvoiceModalLabel" style="color: #171717;">Print Invoice</h6>
                        <div class="small text-muted" style="font-size: 0.78rem;">Choose a printer format and preview the finalized booking invoice.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="bg-white border-bottom px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2 flex-shrink-0">
                <div class="d-flex align-items-center gap-2" role="group" aria-label="Invoice Format">
                    <button type="button" class="invoice-seg-btn active" data-invoice-format="thermal" id="btnFormatThermal">
                        <i class="mdi mdi-receipt me-1"></i>Thermal Receipt
                    </button>
                    <button type="button" class="invoice-seg-btn" data-invoice-format="a4" id="btnFormatA4">
                        <i class="mdi mdi-file-document me-1"></i>A4 Invoice
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2" id="thermalPaperSizeGroup">
                    <span class="small fw-semibold" style="color: #475569;">Paper:</span>
                    <button type="button" class="invoice-seg-btn" data-paper-size="58mm" id="btnPaper58">58mm</button>
                    <button type="button" class="invoice-seg-btn active" data-paper-size="80mm" id="btnPaper80">80mm</button>
                </div>
            </div>

            <div class="modal-body invoice-preview-canvas flex-grow-1" style="min-height: 0;">
                <div id="invoicePreviewError" class="alert alert-danger small d-none mb-3"></div>

                <div id="invoicePreviewLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-warning" role="status" aria-hidden="true"></div>
                    <div class="small fw-semibold mt-2" style="color: #334155;">Preparing invoice preview...</div>
                </div>

                <div id="invoicePreviewPaper" class="invoice-preview-paper invoice-preview-paper--thermal"></div>
            </div>

            <div class="modal-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center flex-shrink-0">
                <div class="small" style="color: #475569;">
                    Invoice: <strong id="invoiceModalNumber" style="color: #171717;">-</strong>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light rounded-3 px-3" style="min-height: 38px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btnConfirmPrintInvoice" class="btn btn-sm bk-btn-yellow px-3" style="border-radius: 8px; min-height: 38px;">
                        <i class="mdi mdi-printer me-1"></i>Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<iframe id="invoicePrintFrame" title="Invoice Print Frame" style="position: fixed; width: 0; height: 0; border: 0; visibility: hidden;"></iframe>
@endif

{{-- Lightweight Toast Container --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="assignStoreToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body small fw-semibold" id="assignStoreToastBody">Store assigned successfully.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
    const $ = jQuery;
    const $select = $('#quickStatusSelect');
    if (!$select.length || $select.data('select2')) return;

    $select.select2({
        width: '100%',
        allowClear: false,
        templateResult: function (state) {
            if (!state.id || !state.element) return state.text;
            const isCompleted = state.element.getAttribute('data-completed') === '1';
            const isCurrent = state.element.getAttribute('data-current') === '1';

            let cls = 's2-st-item ';
            if (isCurrent) {
                cls += 's2-st-current';
            } else if (isCompleted) {
                cls += 's2-st-completed';
            } else {
                cls += 's2-st-upcoming';
            }

            const $wrap = $('<div></div>').addClass(cls);
            const $left = $('<span></span>');
            if (isCompleted) {
                $left.append($('<span class="s2-st-check" aria-hidden="true">&#10003;</span>'));
            }
            $left.append(document.createTextNode(state.text));
            $wrap.append($left);

            if (isCurrent) {
                $wrap.append($('<span class="s2-st-current-pill">Current</span>'));
            }

            return $wrap;
        },
        templateSelection: function (state) {
            if (!state.id || !state.element) return state.text;
            const isCompleted = state.element.getAttribute('data-completed') === '1';
            const $sel = $('<span></span>');
            if (isCompleted) {
                $sel.append($('<span class="s2-st-check" aria-hidden="true">&#10003;</span>'));
            }
            $sel.append(document.createTextNode(state.text));
            return $sel;
        }
    });
    let hasAssignedStore = @json($currentStoreAssignment !== null);
    let currentAssignedStoreId = @json($booking->assigned_store_id);
    const $assignSelect = $('#assignStoreSelect');
    const assignModalEl = document.getElementById('assignStoreModal');

    function getAssignModal() {
        if (!assignModalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return null;
        return bootstrap.Modal.getOrCreateInstance(assignModalEl);
    }

    if ($assignSelect.length && !$assignSelect.data('select2')) {
        $assignSelect.select2({
            dropdownParent: $('#assignStoreModal'),
            dropdownCssClass: 'assignment-select2-dropdown',
            width: '100%',
            allowClear: true,
            placeholder: 'Search store by name, code, postcode, or city...',
            minimumInputLength: 0,
            ajax: {
                url: @json(route('admin.bookings.stores.search', $booking)),
                dataType: 'json',
                delay: 200,
                data: params => ({ q: params.term || '', page: params.page || 1 }),
                processResults: (data, params) => {
                    params.page = params.page || 1;
                    return {
                        results: data.results || [],
                        pagination: { more: Boolean(data.pagination && data.pagination.more) }
                    };
                }
            },
            templateResult: function (state) {
                if (!state.id) return state.text;
                const matched = Boolean(state.postcode_match);
                const isCurrent = hasAssignedStore && Number(state.id) === Number(currentAssignedStoreId);

                const $row = $('<div class="assignment-result-row"></div>');
                $row.append($('<span class="assignment-result-icon"><i class="mdi mdi-store"></i></span>'));
                const $main = $('<div class="assignment-result-main"></div>');
                const titleText = (state.store_name || state.text) + (state.store_postcode ? ' (' + state.store_postcode + ')' : '');
                $main.append($('<div class="assignment-result-title"></div>').text(titleText));
                const meta = [state.store_address || state.store_city, state.store_code].filter(Boolean).join(' · ');
                if (meta) {
                    $main.append($('<div class="assignment-result-meta"></div>').text(meta));
                }

                const $badge = $('<span class="assignment-badge"></span>');
                if (isCurrent) {
                    $badge.addClass('assignment-badge--current').text('Current');
                } else if (matched) {
                    $badge.addClass('assignment-badge--match').html('&#10003; Postcode Match');
                } else {
                    $badge.addClass('assignment-badge--manual').text('Manual Selection');
                }

                $row.append($main).append($badge).append($('<span class="assignment-row-check" aria-hidden="true">&#10003;</span>'));
                return $row;
            },
            templateSelection: function (state) {
                if (!state.id) return state.text;
                if (state.store_name) {
                    return state.store_postcode ? (state.store_name + ' (' + state.store_postcode + ')') : state.store_name;
                }
                return state.text;
            }
        });

        $assignSelect.on('select2:select', function (e) {
            const item = e.params.data || {};
            const matched = Boolean(item.postcode_match);
            const isChanging = hasAssignedStore && Number(item.id) !== Number(currentAssignedStoreId);

            $('#previewStoreName').text(item.store_name || item.text || '');
            $('#previewStoreAddress').text(item.store_address || 'Not provided');
            $('#previewStorePostcode').text(item.store_postcode || 'Not provided');
            $('#previewStorePhone').text(item.store_phone || 'Contact number not provided');
            $('#previewMatchBadge')
                .html(matched ? '&#10003; Postcode Match' : 'Manual Selection')
                .removeClass('assignment-badge--match assignment-badge--manual')
                .addClass(matched ? 'assignment-badge--match' : 'assignment-badge--manual');
            $('#assignStorePreviewCard').removeClass('d-none');

            const needsReason = !matched || isChanging;
            $('#assignStoreOverrideWrap').toggleClass('d-none', !needsReason);
            $('#assignStoreOverrideReason').prop('required', needsReason);
        });
    }

    $(document).on('click', '.js-open-assign-store', function () {
        $('#assignStoreErrors').addClass('d-none').empty();
        $('#assignStoreModalLabel').text(hasAssignedStore ? 'Change Store' : 'Assign Store');
        const modal = getAssignModal();
        if (modal) modal.show();
    });

    $('#assignStoreAjaxForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btnSaveStoreAssignment').prop('disabled', true);
        const origText = $btn.text();
        $btn.text('Saving...');
        $('#assignStoreErrors').addClass('d-none').empty();

        $.ajax({
            url: @json(route('admin.bookings.assign-store', $booking)),
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function (res) {
                hasAssignedStore = true;
                currentAssignedStoreId = res.store_id;

                $('#inlineStoreName').text(res.store_name);
                $('#inlineStoreAddress').text(res.store_address || 'Not provided');
                $('#inlineStorePostcode').text(res.store_postcode || 'Not provided');
                $('#inlineStorePhone').text(res.store_phone || 'Contact number not provided');
                $('#inlineStoreAssignedDetails').removeClass('d-none');
                $('#inlineStoreUnassignedBox').addClass('d-none');
                $('#btnOpenAssignStoreModal').text('Change Store');

                $('#cardStoreName').text(res.store_name);
                $('#cardStoreAddress').text(res.store_address || 'Not provided');
                $('#cardStorePostcode').text(res.store_postcode || 'Not provided');
                $('#cardStorePhone').text(res.store_phone || 'Contact number not provided');
                $('#cardStoreAssignedAt').text(res.assigned_at || 'Just now');
                $('#cardStoreMatchBadge')
                    .text(res.postcode_match ? 'Postcode Match' : 'Manual Selection')
                    .removeClass('bg-secondary-subtle text-secondary bg-success-subtle text-success bg-warning-subtle text-warning-emphasis')
                    .addClass(res.postcode_match ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis');
                $('#cardStoreDetails').removeClass('d-none');
                $('#cardStoreEmpty').addClass('d-none');
                $('#btnCardAssignStore').text('Change Store');
                $('#courierModalStoreName').text(res.store_name);

                if (res.can_complete_store_stage) {
                    $('#btnMarkStageDone').prop('disabled', false).removeAttr('title');
                }
                if (res.profit_summary && typeof refreshProfitSummaryCard === 'function') {
                    refreshProfitSummaryCard(res.profit_summary);
                }

                const modal = getAssignModal();
                if (modal) modal.hide();

                const toastEl = document.getElementById('assignStoreToast');
                if (toastEl && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                    $('#assignStoreToastBody').text(res.message || 'Store assigned successfully.');
                    bootstrap.Toast.getOrCreateInstance(toastEl).show();
                }
            },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors;
                let msg = xhr.responseJSON?.message || 'Unable to save store assignment.';
                if (errs) {
                    msg = Object.values(errs).map(arr => arr[0]).join(' ');
                }
                $('#assignStoreErrors').removeClass('d-none').text(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).text(origText);
            }
        });
    });

    // Courier Assignment Modal & AJAX
    const customerPostcode = @json($customerPostcode ?? '');
    let currentCollectionCourierId = @json($collectionAssignment?->delivery_person_id);
    let currentFinalCourierId = @json($finalDeliveryAssignment?->delivery_person_id);
    const deliveryFeeMinor = @json((int) ($financialAllocation['delivery_fee_minor'] ?? 0));
    const defaultColPaymentMinor = @json((int) ($financialAllocation['default_collection_payment_minor'] ?? 0));
    const defaultFinPaymentMinor = @json((int) ($financialAllocation['default_final_payment_minor'] ?? 0));
    const colTipShareMinor = @json((int) ($financialAllocation['collection_tip_share_minor'] ?? 0));
    const finTipShareMinor = @json((int) ($financialAllocation['final_tip_share_minor'] ?? 0));
    let savedColPaymentMinor = @json($collectionAssignment ? (int) $collectionAssignment->rate_minor : null);
    let savedFinPaymentMinor = @json($finalDeliveryAssignment ? (int) $finalDeliveryAssignment->rate_minor : null);
    const bookingCurrencySymbol = @json($currencyService->symbol($booking->currency));
    const $courierSelect = $('#assignCourierSelect');
    const courierModalEl = document.getElementById('assignCourierModal');

    function formatMinorClient(minor) {
        const sign = minor < 0 ? '-' : '';
        const abs = Math.abs(Math.round(minor));
        return sign + bookingCurrencySymbol + (abs / 100).toFixed(2);
    }

    function refreshProfitSummaryCard(p) {
        if (!p || !$('#bookingProfitSummaryCard').length) return;
        const canShow = !!(p.is_finalized || p.has_provisional_estimate);
        $('#profitSummaryStatusBadge')
            .text(p.status_label || '')
            .removeClass('bg-success-subtle text-success bg-primary-subtle text-primary bg-warning-subtle text-warning bg-secondary-subtle text-secondary')
            .addClass('bg-' + (p.status_badge || 'warning') + '-subtle text-' + (p.status_badge || 'warning'));
        $('#profitNotFinalizedNotice').toggleClass('d-none', canShow);
        $('#profitBreakdownContent').toggleClass('d-none', !canShow);
        if (!canShow || !p.formatted) return;

        $('#profitEligibleService').text(p.formatted.eligible_service_revenue);
        $('#profitStoreNet').text('-' + p.formatted.store_net_earnings);
        $('#profitStoreContribution').text(p.formatted.store_contribution)
            .toggleClass('text-success', p.store_contribution_minor >= 0)
            .toggleClass('text-danger', p.store_contribution_minor < 0);
        $('#profitDeliveryCharge').text(p.formatted.delivery_fee);
        $('#profitCollectionPay').text('-' + p.formatted.collection_payment);
        $('#profitFinalPay').text('-' + p.formatted.final_payment);
        $('#profitDeliveryLabel').text(p.delivery_contribution_minor < 0 ? 'Delivery Loss' : 'Delivery Profit');
        $('#profitDeliveryContribution').text(p.formatted.delivery_contribution)
            .toggleClass('text-success', p.delivery_contribution_minor >= 0)
            .toggleClass('text-danger', p.delivery_contribution_minor < 0);
        $('#profitServiceFee').text(p.formatted.service_fee);
        $('#profitOtherContribution').text(p.formatted.other_contribution)
            .toggleClass('text-success', p.other_contribution_minor >= 0)
            .toggleClass('text-danger', p.other_contribution_minor < 0);
        $('#profitCustomerTip').text(p.formatted.customer_tip);
        $('#profitAllocatedTips').text(p.formatted.allocated_tips);
        $('#profitRecognizedRevenue').text(p.formatted.recognized_net_revenue);
        $('#profitMarginPctValue').text(p.margin_percentage_formatted || 'N/A');

        const isPos = (p.total_profit_minor || 0) >= 0;
        $('#profitTotalBox').css({
            'background-color': isPos ? '#ECFDF5' : '#FFF1F2',
            'border-color': isPos ? '#A7F3D0' : '#FECACA'
        });
        $('#profitTotalValue').text(p.formatted.total_profit).css('color', isPos ? '#16A34A' : '#EF4444');
    }

    function updateCourierPaymentPreview() {
        const type = $('#assignCourierTypeInput').val();
        const isFinal = type === @json(\Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY);
        const rawVal = parseFloat($('#assignCourierDeliveryPayment').val());
        const enteredMinor = isNaN(rawVal) || rawVal < 0 ? 0 : Math.round(rawVal * 100);
        const legTipMinor = isFinal ? finTipShareMinor : colTipShareMinor;
        const otherLegMinor = isFinal
            ? (savedColPaymentMinor !== null ? savedColPaymentMinor : defaultColPaymentMinor)
            : (savedFinPaymentMinor !== null ? savedFinPaymentMinor : defaultFinPaymentMinor);
        const combinedPayoutMinor = enteredMinor + otherLegMinor;
        const marginMinor = deliveryFeeMinor - combinedPayoutMinor;

        $('#modalCourierTipShare').text(formatMinorClient(legTipMinor));
        $('#modalCourierTotalEarnings').text(formatMinorClient(enteredMinor + legTipMinor));
        $('#modalCombinedPayout').text(formatMinorClient(combinedPayoutMinor));
        $('#modalAdminMarginValue').text(formatMinorClient(marginMinor));

        const $box = $('#modalAllocationPreviewBox');
        if (marginMinor < 0) {
            $box.css({ 'background-color': '#FEF2F2', 'border-color': '#FECACA' });
            $('#modalAdminMarginLabel').text('Admin Delivery Shortfall').removeClass('text-dark').addClass('text-danger');
            $('#modalAdminMarginValue').removeClass('text-success').addClass('text-danger');
            $('#modalShortfallWarning').removeClass('d-none');
        } else {
            $box.css({ 'background-color': '#ECFDF5', 'border-color': '#A7F3D0' });
            $('#modalAdminMarginLabel').text('Admin Delivery Margin').removeClass('text-danger').addClass('text-dark');
            $('#modalAdminMarginValue').removeClass('text-danger').addClass('text-success');
            $('#modalShortfallWarning').addClass('d-none');
        }
    }

    $('#assignCourierDeliveryPayment').on('input change', updateCourierPaymentPreview);

    function getCourierModal() {
        if (!courierModalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return null;
        return bootstrap.Modal.getOrCreateInstance(courierModalEl);
    }

    if ($courierSelect.length && !$courierSelect.data('select2')) {
        $courierSelect.select2({
            dropdownParent: $('#assignCourierModal'),
            dropdownCssClass: 'assignment-select2-dropdown',
            width: '100%',
            allowClear: true,
            placeholder: 'Search delivery person by name, code, phone, or postcode...',
            minimumInputLength: 0,
            ajax: {
                url: @json(route('admin.bookings.couriers.search', $booking)),
                dataType: 'json',
                delay: 200,
                data: params => ({ q: params.term || '', assignment_type: $('#assignCourierTypeInput').val() }),
                processResults: data => {
                    $('#assignCourierNoMatchBanner').toggleClass('d-none', Boolean(data.has_postcode_match));
                    return { results: data.results || [] };
                }
            },
            templateResult: function (state) {
                if (!state.id) return state.text;
                const matched = Boolean(state.postcode_match);
                const activeType = $('#assignCourierTypeInput').val();
                const currentId = activeType === @json(\Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY)
                    ? currentFinalCourierId
                    : currentCollectionCourierId;
                const isCurrent = Boolean(currentId) && Number(state.id) === Number(currentId);

                const $row = $('<div class="assignment-result-row"></div>');
                $row.append($('<span class="assignment-result-icon assignment-result-icon--circle"><i class="mdi mdi-account-outline"></i></span>'));
                const $main = $('<div class="assignment-result-main"></div>');
                $main.append($('<div class="assignment-result-title"></div>').text((state.full_name || state.text) + (state.code ? ' (' + state.code + ')' : '')));
                const metaParts = [
                    state.phone && state.phone !== 'Not provided' ? state.phone : null,
                    state.store_name && state.store_name !== 'Unassigned' ? state.store_name : null
                ].filter(Boolean);
                if (metaParts.length) {
                    $main.append($('<div class="assignment-result-meta"></div>').text(metaParts.join(' · ')));
                }

                const $badge = $('<span class="assignment-badge"></span>');
                if (isCurrent) {
                    $badge.addClass('assignment-badge--current').text('Current');
                } else if (matched) {
                    $badge.addClass('assignment-badge--match').html('&#10003; Postcode Match');
                } else {
                    $badge.addClass('assignment-badge--manual').text('Manual Assignment');
                }

                $row.append($main).append($badge).append($('<span class="assignment-row-check" aria-hidden="true">&#10003;</span>'));
                return $row;
            },
            templateSelection: function (state) {
                if (!state.id) return state.text;
                if (state.full_name) {
                    return state.code ? (state.full_name + ' (' + state.code + ')') : state.full_name;
                }
                return state.text;
            }
        });

        $courierSelect.on('select2:select', function (e) {
            const item = e.params.data || {};
            const matched = Boolean(item.postcode_match);
            const activeType = $('#assignCourierTypeInput').val();
            const currentId = activeType === @json(\Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY)
                ? currentFinalCourierId
                : currentCollectionCourierId;
            const isReassigning = Boolean(currentId) && Number(item.id) !== Number(currentId);

            $('#previewCourierName').text(item.full_name || item.text || '');
            $('#previewCourierCode').text(item.code ? '(' + item.code + ')' : '');
            $('#previewCourierPhone').text(item.phone || 'Not provided');
            $('#previewCourierLoad').text(item.active_assignments_count ?? 0);
            $('#previewCourierBadge')
                .html(matched ? ('&#10003; Covers ' + (item.matched_postcode || customerPostcode || 'Postcode')) : 'Manual Assignment')
                .removeClass('assignment-badge--match assignment-badge--manual')
                .addClass(matched ? 'assignment-badge--match' : 'assignment-badge--manual');
            $('#assignCourierPreviewCard').removeClass('d-none');

            const needsReason = !matched || isReassigning;
            $('#assignCourierOverrideWrap').toggleClass('d-none', !needsReason);
            $('#assignCourierOverrideReason').prop('required', needsReason);
        });
    }

    $('.assignment-modal .modal-body').on('scroll', function () {
        if ($assignSelect.data('select2') && $assignSelect.data('select2').isOpen()) {
            $assignSelect.select2('close');
        }
        if ($courierSelect.data('select2') && $courierSelect.data('select2').isOpen()) {
            $courierSelect.select2('close');
        }
    });

    $(document).on('click', '.js-open-assign-courier', function () {
        const type = $(this).data('assignment-type');
        const isFinal = type === @json(\Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY);
        const existingId = isFinal ? currentFinalCourierId : currentCollectionCourierId;

        $('#assignCourierTypeInput').val(type);
        $('#assignCourierModalLabel').text((existingId ? 'Change ' : 'Assign ') + (isFinal ? 'Final Delivery Person' : 'Collection Delivery Person'));
        $('#assignCourierSubtitle').text(isFinal
            ? 'Assign an eligible delivery person to pick up cleaned items from the store and deliver to the customer.'
            : 'Assign an eligible delivery person to collect items from the customer and deliver to the assigned store.');
        $('#courierModalRouteText').text(isFinal
            ? ('Route: Assigned Store → Customer (' + (customerPostcode || 'Address') + ')')
            : ('Route: Customer (' + (customerPostcode || 'Address') + ') → Assigned Store'));

        $('#assignCourierErrors').addClass('d-none').empty();
        $('#assignCourierNoMatchBanner').addClass('d-none');
        $('#assignCourierPreviewCard').addClass('d-none');
        $('#assignCourierOverrideWrap').addClass('d-none');
        $('#assignCourierOverrideReason').val('').prop('required', false);
        if ($courierSelect.length) {
            $courierSelect.val(null).trigger('change');
        }

        const defaultLegMinor = isFinal ? defaultFinPaymentMinor : defaultColPaymentMinor;
        const currentLegMinor = isFinal
            ? (savedFinPaymentMinor !== null ? savedFinPaymentMinor : defaultLegMinor)
            : (savedColPaymentMinor !== null ? savedColPaymentMinor : defaultLegMinor);
        $('#modalDefaultLegPayment').text(formatMinorClient(defaultLegMinor));
        $('#assignCourierDeliveryPayment').val((currentLegMinor / 100).toFixed(2));
        updateCourierPaymentPreview();

        const modal = getCourierModal();
        if (modal) modal.show();
    });

    $('#assignCourierAjaxForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btnSaveCourierAssignment').prop('disabled', true);
        const origText = $btn.text();
        $btn.text('Saving...');
        $('#assignCourierErrors').addClass('d-none').empty();

        $.ajax({
            url: @json(route('admin.bookings.assign-courier', $booking)),
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function (res) {
                const isFinal = res.assignment_type === @json(\Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY);
                const badgeText = res.postcode_match ? ('Covers ' + (res.matched_postcode || customerPostcode || 'Postcode')) : 'Manual Assignment';
                const badgeClass = res.postcode_match ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis';

                if (isFinal) {
                    currentFinalCourierId = res.delivery_person_id;
                    if (typeof res.rate_minor === 'number') {
                        savedFinPaymentMinor = res.rate_minor;
                    }
                    $('#inlineFinalCourierName').text(res.courier_name);
                    $('#inlineFinalCourierPhone').text(res.courier_phone || 'Not provided');
                    $('#inlineFinalCourierStatus').text(res.status_label || 'Assigned');
                    $('#inlineFinalCourierDetails').removeClass('d-none');
                    $('#inlineFinalCourierUnassigned').addClass('d-none');
                    $('#inlineFinalCourierBox .js-open-assign-courier').text('Change Delivery Person');

                    $('#cardFinalName').html($('<span></span>').text(res.courier_name).html() + ' <span id="cardFinalCode" class="text-muted fw-normal">(' + $('<span></span>').text(res.courier_code || '').html() + ')</span>');
                    $('#cardFinalAddress').text(res.courier_address || 'Address not provided');
                    $('#cardFinalPhone').text(res.courier_phone || 'Not provided');
                    $('#cardFinalStatus').text(res.status_label || 'Assigned');
                    $('#cardFinalAssignedAt').text(res.assigned_at || 'Just now');
                    if (res.delivery_payment_formatted) $('#cardFinalPay').text(res.delivery_payment_formatted);
                    if (res.tip_share_formatted) $('#cardFinalTip').text(res.tip_share_formatted);
                    if (res.total_earnings_formatted) $('#cardFinalTotal').text(res.total_earnings_formatted);
                    $('#cardFinalBadge')
                        .text(badgeText)
                        .removeClass('bg-secondary-subtle text-secondary bg-success-subtle text-success bg-warning-subtle text-warning-emphasis')
                        .addClass(badgeClass);
                    $('#cardFinalDetails').removeClass('d-none');
                    $('#cardFinalEmpty').addClass('d-none');
                    $('#btnCardAssignFinal').text('Change Delivery Person');
                } else {
                    currentCollectionCourierId = res.delivery_person_id;
                    if (typeof res.rate_minor === 'number') {
                        savedColPaymentMinor = res.rate_minor;
                    }
                    $('#inlineCollectionCourierName').text(res.courier_name);
                    $('#inlineCollectionCourierPhone').text(res.courier_phone || 'Not provided');
                    $('#inlineCollectionCourierStatus').text(res.status_label || 'Assigned');
                    $('#inlineCollectionCourierDetails').removeClass('d-none');
                    $('#inlineCollectionCourierUnassigned').addClass('d-none');
                    $('#inlineCollectionCourierBox .js-open-assign-courier').text('Change Delivery Person');

                    $('#cardCollectionName').html($('<span></span>').text(res.courier_name).html() + ' <span id="cardCollectionCode" class="text-muted fw-normal">(' + $('<span></span>').text(res.courier_code || '').html() + ')</span>');
                    $('#cardCollectionAddress').text(res.courier_address || 'Address not provided');
                    $('#cardCollectionPhone').text(res.courier_phone || 'Not provided');
                    $('#cardCollectionStatus').text(res.status_label || 'Assigned');
                    $('#cardCollectionAssignedAt').text(res.assigned_at || 'Just now');
                    if (res.delivery_payment_formatted) $('#cardCollectionPay').text(res.delivery_payment_formatted);
                    if (res.tip_share_formatted) $('#cardCollectionTip').text(res.tip_share_formatted);
                    if (res.total_earnings_formatted) $('#cardCollectionTotal').text(res.total_earnings_formatted);
                    $('#cardCollectionBadge')
                        .text(badgeText)
                        .removeClass('bg-secondary-subtle text-secondary bg-success-subtle text-success bg-warning-subtle text-warning-emphasis')
                        .addClass(badgeClass);
                    $('#cardCollectionDetails').removeClass('d-none');
                    $('#cardCollectionEmpty').addClass('d-none');
                    $('#btnCardAssignCollection').text('Change Delivery Person');
                }

                if (res.financial_preview) {
                    $('#summaryColPayment').text(formatMinorClient(res.financial_preview.collection_payment_minor || 0));
                    $('#summaryFinPayment').text(formatMinorClient(res.financial_preview.final_payment_minor || 0));
                    const m = res.financial_preview.admin_delivery_margin_minor || 0;
                    const $marginEl = $('#summaryAdminMargin');
                    $marginEl.toggleClass('text-danger', m < 0).toggleClass('text-success', m >= 0);
                    $marginEl.html((m < 0 ? '-' : '') + formatMinorClient(Math.abs(m)) + (m < 0 ? ' <span class="badge bg-danger-subtle text-danger ms-1" style="font-size: 0.65rem;">Shortfall</span>' : ''));
                }
                if (res.profit_summary && typeof refreshProfitSummaryCard === 'function') {
                    refreshProfitSummaryCard(res.profit_summary);
                }

                if (res.can_complete_stage) {
                    $('#btnMarkStageDone').prop('disabled', false).removeAttr('title');
                }

                const modal = getCourierModal();
                if (modal) modal.hide();

                const toastEl = document.getElementById('assignStoreToast');
                if (toastEl && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                    $('#assignStoreToastBody').text(res.message || 'Delivery person assigned successfully.');
                    bootstrap.Toast.getOrCreateInstance(toastEl).show();
                }
            },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors;
                let msg = xhr.responseJSON?.message || 'Unable to save delivery person assignment.';
                if (errs) {
                    msg = Object.values(errs).map(arr => arr[0]).join(' ');
                }
                $('#assignCourierErrors').removeClass('d-none').text(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).text(origText);
            }
        });
    });

    @if($isInvoiceEligible)
    // Invoice Preview Modal & Isolated Print Iframe
    let currentInvoiceFormat = 'thermal';
    let currentThermalSize = '80mm';
    const invoiceModalEl = document.getElementById('printInvoiceModal');
    const invoicePreviewUrl = @json(route('admin.bookings.invoice.preview', $booking));
    const invoicePrintUrl = @json(route('admin.bookings.invoice.print', $booking));

    function getInvoiceModal() {
        if (!invoiceModalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return null;
        return bootstrap.Modal.getOrCreateInstance(invoiceModalEl);
    }

    function loadInvoicePreview() {
        $('#invoicePreviewError').addClass('d-none').empty();
        $('#invoicePreviewLoading').removeClass('d-none');
        $('#invoicePreviewPaper').addClass('d-none');
        $('#thermalPaperSizeGroup').toggleClass('d-none', currentInvoiceFormat !== 'thermal');

        $.ajax({
            url: invoicePreviewUrl,
            method: 'GET',
            dataType: 'json',
            data: {
                format: currentInvoiceFormat,
                paper_size: currentThermalSize
            },
            success: function (res) {
                $('#invoiceModalNumber').text(res.invoice_number || '-');
                $('#invoicePreviewPaper')
                    .removeClass('invoice-preview-paper--thermal invoice-preview-paper--a4 d-none')
                    .addClass(currentInvoiceFormat === 'a4' ? 'invoice-preview-paper--a4' : 'invoice-preview-paper--thermal')
                    .html(res.html || '');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Unable to load invoice preview.';
                $('#invoicePreviewError').removeClass('d-none').text(msg);
            },
            complete: function () {
                $('#invoicePreviewLoading').addClass('d-none');
            }
        });
    }

    $('#btnOpenPrintInvoiceModal').on('click', function () {
        const modal = getInvoiceModal();
        if (modal) modal.show();
        loadInvoicePreview();
    });

    $(document).on('click', '[data-invoice-format]', function () {
        currentInvoiceFormat = $(this).data('invoice-format');
        $('[data-invoice-format]').removeClass('active');
        $(this).addClass('active');
        loadInvoicePreview();
    });

    $(document).on('click', '[data-paper-size]', function () {
        currentThermalSize = $(this).data('paper-size');
        $('[data-paper-size]').removeClass('active');
        $(this).addClass('active');
        if (currentInvoiceFormat === 'thermal') {
            loadInvoicePreview();
        }
    });

    $('#btnConfirmPrintInvoice').on('click', function () {
        const frame = document.getElementById('invoicePrintFrame');
        if (!frame) return;
        const url = invoicePrintUrl
            + '?format=' + encodeURIComponent(currentInvoiceFormat)
            + '&paper_size=' + encodeURIComponent(currentThermalSize)
            + '&autoprint=1&_t=' + Date.now();
        frame.src = url;
    });
    @endif
});
</script>
@endpush
