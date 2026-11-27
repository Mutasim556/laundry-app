@extends('store-portal.layouts.app')

@section('title', 'Store Profile & Bank Accounts')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .sp-profile-wrap {
        max-width: 1280px;
        margin: 0 auto;
    }
    .sp-profile-header {
        background: linear-gradient(135deg, #FFF8E5 0%, #FFFFFF 70%);
        border: 1px solid #FDE68A;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }
    .sp-profile-avatar {
        width: 48px;
        height: 48px;
        border-radius: 13px;
        background: #FFC629;
        color: #171717;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(255, 198, 41, 0.28);
    }
    .sp-info-tile {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 11px;
        padding: 0.65rem 0.85rem;
        height: 100%;
    }
    .sp-info-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #64748B;
        margin-bottom: 0.15rem;
    }
    .sp-info-value {
        font-size: 0.875rem;
        font-weight: 600;
        color: #171717;
        word-break: break-word;
    }
    .sp-bank-card {
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.95rem 1.05rem;
        background: #FFFFFF;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        height: 100%;
    }
    .sp-bank-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }
    .sp-bank-card.is-default {
        border-color: #FFC629;
        background: linear-gradient(180deg, #FFFDF5 0%, #FFFFFF 100%);
    }
    .sp-profile-wrap .form-control,
    .sp-profile-wrap .form-select {
        min-height: 38px;
        border-radius: 9px;
        border-color: #CBD5E1;
        font-size: 0.875rem;
    }
    .sp-profile-wrap .form-control:focus,
    .sp-profile-wrap .form-select:focus {
        border-color: #FFC629;
        box-shadow: 0 0 0 0.2rem rgba(255, 198, 41, 0.22);
    }
    /* Select2 alignment inside Bootstrap 5 Modal */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #CBD5E1;
        border-radius: 9px;
        padding: 4px 8px;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        padding-left: 2px;
        color: #171717;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 6px;
    }
    .select2-container--open {
        z-index: 1065 !important;
    }
    .select2-dropdown {
        border-color: #CBD5E1;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        font-size: 0.875rem;
    }
    @media (prefers-reduced-motion: reduce) {
        .sp-bank-card {
            transition: none !important;
        }
        .sp-bank-card:hover {
            transform: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $storeStatus = strtolower((string) ($store->status ?? 'active'));
    $statusBadgeStyle = match ($storeStatus) {
        'active' => 'background:#DCFCE7; color:#166534; border:1px solid #BBF7D0;',
        'pending', 'pending_review' => 'background:#FEF3C7; color:#92400E; border:1px solid #FDE68A;',
        default => 'background:#F1F5F9; color:#475569; border:1px solid #CBD5E1;',
    };
    $publicPhone = trim(($store->phone_country_code ? $store->phone_country_code . ' ' : '') . ($store->phone ?? ''));
    $fullAddress = $store->full_address ?: implode(', ', array_filter([
        $store->address_line_1,
        $store->address_line_2,
        $store->city,
        $store->postcode,
        $store->country_name,
    ]));
    $publicLocation = implode(', ', array_filter([$store->city, $store->postcode, $store->country_name]));
@endphp

<div class="sp-profile-wrap">
    <!-- Compact Profile Header -->
    <div class="sp-profile-header mb-3 sp-anim-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <div class="sp-profile-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 9l2-5h14l2 5"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v8h14v-8"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h4 class="fw-bold mb-0 text-truncate" style="color: #171717;">Store Profile</h4>
                        <span class="badge rounded-pill px-2 py-1 text-capitalize" style="{{ $statusBadgeStyle }}">{{ str_replace('_', ' ', $storeStatus) }}</span>
                        @if(!empty($store->store_code))
                            <span class="badge bg-white text-dark border">{{ $store->store_code }}</span>
                        @endif
                    </div>
                    <div class="small fw-semibold mt-1" style="color: #171717;">
                        {{ $store->name }}
                        @if($publicLocation)
                            <span class="text-muted fw-normal">&middot; {{ $publicLocation }}</span>
                        @endif
                    </div>
                    <div class="text-muted" style="font-size: 0.8rem;">
                        Manage your store information, contact details, and withdrawal accounts.
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('store.account.index') }}" class="btn btn-sm btn-outline-dark">Store Account</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- Left Column: Store Information + Contact & Address -->
        <div class="col-12 col-lg-7 d-flex flex-column gap-3">
            <!-- Section 1: Store Information (Blue Accent) -->
            <div class="sp-card p-3 p-md-4 sp-anim-card">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="sp-icon-box" style="background: #EFF6FF; color: #3B82F6;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 9l2-5h14l2 5"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v8h14v-8"/>
                            </svg>
                        </span>
                        <div>
                            <h6 class="fw-bold mb-0" style="color: #171717;">Store Information</h6>
                            <div class="text-muted" style="font-size: 0.78rem;">Operational &amp; commercial profile managed by platform admin</div>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border">Admin Managed (Read-Only)</span>
                </div>

                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Store Name</div>
                            <div class="sp-info-value">{{ $store->name }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Store Code</div>
                            <div class="sp-info-value">{{ $store->store_code ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Status</div>
                            <div class="sp-info-value text-capitalize">{{ str_replace('_', ' ', $storeStatus) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Commission Rate</div>
                            <div class="sp-info-value">{{ $store->commission_rate !== null ? number_format((float) $store->commission_rate, 2) . '%' : 'Not configured' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Primary Currency</div>
                            <div class="sp-info-value">{{ $storeCurrency }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Associated Vendor</div>
                            <div class="sp-info-value">{{ $store->vendor?->name ?? 'Independent Store' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Contact Details & Address (Green Accent) -->
            <div class="sp-card p-3 p-md-4 sp-anim-card">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="sp-icon-box" style="background: #ECFDF5; color: #16A34A;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                        <div>
                            <h6 class="fw-bold mb-0" style="color: #171717;">Contact Details &amp; Location</h6>
                            <div class="text-muted" style="font-size: 0.78rem;">Public store contact &amp; service coverage address</div>
                        </div>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <div class="sp-info-tile">
                            <div class="sp-info-label d-flex align-items-center gap-1">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>Public Phone</span>
                            </div>
                            <div class="sp-info-value">{{ $publicPhone ?: 'Not provided' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="sp-info-tile">
                            <div class="sp-info-label d-flex align-items-center gap-1">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <span>Store Email</span>
                            </div>
                            <div class="sp-info-value">{{ $store->email ?: 'Not provided' }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Full Address</div>
                            <div class="sp-info-value">{{ $fullAddress ?: 'Not provided' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">City</div>
                            <div class="sp-info-value">{{ $store->city ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Postcode</div>
                            <div class="sp-info-value">{{ $store->postcode ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="sp-info-tile">
                            <div class="sp-info-label">Country</div>
                            <div class="sp-info-value">{{ $store->country_name ?: ($store->country_code ?: '-') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Account Information & Security (Purple Accent) -->
        <div class="col-12 col-lg-5">
            <div class="sp-card p-3 p-md-4 h-100 sp-anim-card d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="sp-icon-box" style="background: #F5F3FF; color: #8B5CF6;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                            <div>
                                <h6 class="fw-bold mb-0" style="color: #171717;">Account Information</h6>
                                <div class="text-muted" style="font-size: 0.78rem;">Your personal Store Portal login profile</div>
                            </div>
                        </div>
                        <span class="badge rounded-pill px-2 py-1 text-capitalize" style="background: #F5F3FF; color: #6D28D9; border: 1px solid #DDD6FE;">
                            {{ $storeUser->role ?? 'owner' }}
                        </span>
                    </div>

                    <div id="spProfileAlert" class="alert d-none py-2 small mb-3" role="status" aria-live="polite"></div>

                    <form id="spProfileForm" method="POST" action="{{ route('store.profile.update') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="spUserName" class="form-label small fw-semibold mb-1">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="spUserName" name="name" value="{{ old('name', $storeUser->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                            <div class="invalid-feedback" data-profile-field="name">@error('name'){{ $message }}@enderror</div>
                        </div>

                        <div class="mb-3">
                            <label for="spUserEmail" class="form-label small fw-semibold mb-1">Login Email (Managed by Admin)</label>
                            <input type="email" id="spUserEmail" value="{{ $storeUser->email }}" class="form-control bg-light text-muted" readonly disabled>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label for="spUserPhoneCode" class="form-label small fw-semibold mb-1">Code</label>
                                <input type="text" id="spUserPhoneCode" name="phone_country_code" value="{{ old('phone_country_code', $storeUser->phone_country_code) }}" class="form-control @error('phone_country_code') is-invalid @enderror" placeholder="+44">
                                <div class="invalid-feedback" data-profile-field="phone_country_code">@error('phone_country_code'){{ $message }}@enderror</div>
                            </div>
                            <div class="col-8">
                                <label for="spUserPhone" class="form-label small fw-semibold mb-1">Personal Phone</label>
                                <input type="text" id="spUserPhone" name="phone" value="{{ old('phone', $storeUser->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="7700900111">
                                <div class="invalid-feedback" data-profile-field="phone">@error('phone'){{ $message }}@enderror</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" id="spProfileSaveBtn" class="btn btn-sm sp-btn-yellow px-3 d-inline-flex align-items-center gap-1">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                <span>Save Profile</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Security Sub-card -->
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="sp-icon-box" style="width: 34px; height: 34px; background: #EDE9FE; color: #6D28D9;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                            </span>
                            <div>
                                <div class="small fw-bold" style="color: #171717;">Account Security</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Manage your password and protect your Store account.</div>
                                <div class="text-muted" style="font-size: 0.74rem;">Password: <span class="fw-bold text-dark">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span></div>
                            </div>
                        </div>
                        <button type="button" id="spOpenChangePasswordBtn" class="btn btn-sm btn-outline-dark">Change Password</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Store Bank Accounts (Amber Accent, AJAX) -->
    <div class="sp-card p-3 p-md-4 sp-anim-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="sp-icon-box" style="background: #FEF3C7; color: #D97706;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 20 7 4 7"/>
                    </svg>
                </span>
                <div>
                    <h6 class="fw-bold mb-0" style="color: #171717;">Store Bank Accounts</h6>
                    <div class="text-muted" style="font-size: 0.8rem;">Used for store withdrawal requests. Sensitive account numbers are encrypted at rest and masked.</div>
                </div>
            </div>
            <button type="button" class="btn btn-sm sp-btn-yellow px-3 d-inline-flex align-items-center gap-1" id="spOpenAddBankBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Add Bank Account</span>
            </button>
        </div>

        <div id="spBankAlertBox" class="d-none alert py-2 small mb-3" role="status" aria-live="polite"></div>

        <div id="spBankAccountsList" class="row g-2 g-md-3">
            @forelse($bankAccounts as $acc)
                <div class="col-12 col-md-6">
                    <div class="sp-bank-card {{ $acc['is_default'] ? 'is-default' : '' }}" data-account-id="{{ $acc['id'] }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="fw-bold d-flex flex-wrap align-items-center gap-1" style="color: #171717; font-size: 0.92rem;">
                                    <span>{{ $acc['bank_name'] }}</span>
                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">{{ $acc['country_code'] }} / {{ $acc['currency'] }}</span>
                                    @if($acc['is_default'])
                                        <span class="badge rounded-pill" style="background:#FFC629; color:#171717; font-size: 0.7rem;">Default</span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-nowrap" style="font-size: 0.7rem;">
                                {{ $acc['verification_label'] }}
                            </span>
                        </div>

                        <div class="small text-muted mb-3" style="word-break: break-word;">
                            <div>Holder: <strong class="text-dark">{{ $acc['account_holder_name'] }}</strong></div>
                            <div>Account: <strong class="text-dark">{{ $acc['account_number_masked'] }}</strong></div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-1 pt-2 border-top">
                            @if(!$acc['is_default'])
                                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 sp-bank-default-btn" data-id="{{ $acc['id'] }}">Set Default</button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 sp-bank-edit-btn d-inline-flex align-items-center gap-1" data-account='@json($acc)'>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                <span>Edit</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 sp-bank-delete-btn d-inline-flex align-items-center gap-1" data-id="{{ $acc['id'] }}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                <span>Archive</span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center text-muted py-4 border rounded-3 bg-light small">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="mb-2 text-secondary" aria-hidden="true"><line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 20 7 4 7"/></svg>
                        <div>No store bank accounts saved yet. Click <strong>Add Bank Account</strong> to add one.</div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="spChangePasswordModal" tabindex="-1" aria-labelledby="spChangePasswordTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 440px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFF8E5;">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="spChangePasswordTitle">Change Password</h6>
                    <div class="small text-muted">Update your account password securely.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="spChangePasswordForm" method="POST" action="{{ route('store.password.change.update') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-3 p-md-4">
                    <div id="spPasswordModalAlert" class="alert alert-danger py-2 small d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="spCurrentPassword" class="form-label small fw-semibold mb-1">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="spCurrentPassword" name="current_password" class="form-control" autocomplete="current-password" required>
                            <button type="button" class="btn btn-outline-secondary js-sp-toggle-pw" data-target="spCurrentPassword" aria-label="Show current password">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <div class="invalid-feedback" data-pw-field="current_password"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="spNewPassword" class="form-label small fw-semibold mb-1">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="spNewPassword" name="password" class="form-control" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary js-sp-toggle-pw" data-target="spNewPassword" aria-label="Show new password">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <div class="invalid-feedback" data-pw-field="password"></div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="spConfirmPassword" class="form-label small fw-semibold mb-1">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="spConfirmPassword" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary js-sp-toggle-pw" data-target="spConfirmPassword" aria-label="Show confirm password">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <div class="invalid-feedback" data-pw-field="password_confirmation"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="spPasswordSubmitBtn" class="btn btn-sm sp-btn-yellow px-3">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add / Edit Bank Account Modal -->
<div class="modal fade" id="spBankModal" tabindex="-1" aria-labelledby="spBankModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 580px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFF8E5;">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="spBankModalTitle">Add Store Bank Account</h6>
                    <div class="small text-muted">Account numbers are encrypted and masked in ordinary views.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="spBankForm" novalidate>
                <input type="hidden" id="spBankId" value="">
                <div class="modal-body p-3 p-md-4">
                    <div id="spBankModalAlert" class="alert alert-danger py-2 small d-none" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="spBankCountry" class="form-label small fw-semibold mb-1">Bank Country <span class="text-danger">*</span></label>
                            <select name="country_code" id="spBankCountry" class="form-select" style="width:100%;">
                                @foreach($countriesMeta as $code => $meta)
                                    <option value="{{ $code }}" {{ ($store->country_code ?: 'GB') === $code ? 'selected' : '' }}>
                                        {{ $meta['name'] }} ({{ $code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="country_code"></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="spBankCurrency" class="form-label small fw-semibold mb-1">Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="spBankCurrency" class="form-select">
                                @foreach($currencies as $curCode => $curMeta)
                                    <option value="{{ $curCode }}" {{ $storeCurrency === $curCode ? 'selected' : '' }}>
                                        {{ $curCode }} — {{ $curMeta['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="currency"></div>
                        </div>
                        <div class="col-12">
                            <label for="spBankHolder" class="form-label small fw-semibold mb-1">Account Holder Name <span class="text-danger">*</span></label>
                            <input type="text" name="account_holder_name" id="spBankHolder" class="form-control" value="{{ $store->name }}" required>
                            <div class="invalid-feedback" data-field="account_holder_name"></div>
                        </div>
                        <div class="col-12 col-sm-7">
                            <label for="spBankName" class="form-label small fw-semibold mb-1">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="spBankName" class="form-control" placeholder="e.g. Barclays, HSBC, Chase" required>
                            <div class="invalid-feedback" data-field="bank_name"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label for="spBankType" class="form-label small fw-semibold mb-1">Account Type</label>
                            <select name="account_type" id="spBankType" class="form-select">
                                <option value="">Select (Optional)</option>
                                <option value="business">Business</option>
                                <option value="current">Current</option>
                                <option value="checking">Checking</option>
                                <option value="savings">Savings</option>
                            </select>
                            <div class="invalid-feedback" data-field="account_type"></div>
                        </div>
                        <div class="col-12 col-sm-7">
                            <label for="spBankAccNum" class="form-label small fw-semibold mb-1">Account Number <span class="text-danger" id="spAccNumReq">*</span></label>
                            <input type="text" name="account_number" id="spBankAccNum" class="form-control" autocomplete="off">
                            <div class="form-text small" id="spAccEditHint" style="display:none;">Leave blank when editing to keep existing encrypted account number.</div>
                            <div class="invalid-feedback" data-field="account_number"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label for="spBankRouting" class="form-label small fw-semibold mb-1" id="spRoutingLabel">Sort Code</label>
                            <input type="text" name="routing_code" id="spBankRouting" class="form-control" autocomplete="off">
                            <div class="form-text small" id="spRoutingHint"></div>
                            <div class="invalid-feedback" data-field="routing_code"></div>
                        </div>
                        <div class="col-12 col-sm-7" id="spIbanGroup">
                            <label for="spBankIban" class="form-label small fw-semibold mb-1">IBAN (Optional)</label>
                            <input type="text" name="iban" id="spBankIban" class="form-control" autocomplete="off">
                            <div class="invalid-feedback" data-field="iban"></div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label for="spBankSwift" class="form-label small fw-semibold mb-1">SWIFT / BIC (Optional)</label>
                            <input type="text" name="swift_bic" id="spBankSwift" class="form-control" autocomplete="off">
                            <div class="invalid-feedback" data-field="swift_bic"></div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="spBankDefault">
                                <label class="form-check-label small fw-semibold" for="spBankDefault">Set as default withdrawal bank account</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm sp-btn-yellow px-3" id="spBankSubmitBtn">Save Bank Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
jQuery(function($) {
    const countriesMeta = @json($countriesMeta);
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const storeUrl = @json(route('store.bank-accounts.store'));
    const baseUrl = @json(url('store/bank-accounts'));
    const bankModalEl = document.getElementById('spBankModal');
    const bankModal = bootstrap.Modal.getOrCreateInstance(bankModalEl);

    if ($.fn.select2 && !$('#spBankCountry').hasClass('select2-hidden-accessible')) {
        $('#spBankCountry').select2({
            dropdownParent: $('#spBankModal'),
            width: '100%'
        });
    }

    // AJAX Store User Profile Update
    let profileSubmitting = false;
    $('#spProfileForm').on('submit', function(e) {
        e.preventDefault();
        if (profileSubmitting) return;
        profileSubmitting = true;

        const form = $(this);
        const btn = $('#spProfileSaveBtn');
        const origHtml = btn.html();
        const alertBox = $('#spProfileAlert');

        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger').text('');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving...');

        $.ajax({
            url: form.attr('action'),
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: form.serialize(),
            success: function(res) {
                alertBox.removeClass('d-none alert-danger').addClass('alert-success').text(res.message || 'Your store account profile has been updated.');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        form.find(`[name="${field}"]`).addClass('is-invalid');
                        form.find(`.invalid-feedback[data-profile-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    alertBox.removeClass('d-none alert-success').addClass('alert-danger').text(xhr.responseJSON?.message || 'Unable to update profile.');
                }
            },
            complete: function() {
                profileSubmitting = false;
                btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    function updateCountryFields(countryCode, autoSetCurrency = false) {
        const meta = countriesMeta[countryCode] || { routing_label: 'Routing / Branch Code', routing_required: false, routing_hint: '', iban_optional: true, currency: 'GBP' };
        $('#spRoutingLabel').html(meta.routing_label + (meta.routing_required ? ' <span class="text-danger">*</span>' : ' (Optional)'));
        $('#spRoutingHint').text(meta.routing_hint || '');
        $('#spIbanGroup').toggle(!!meta.iban_optional);
        if (autoSetCurrency && meta.currency) {
            $('#spBankCurrency').val(meta.currency);
        }
    }

    $('#spBankCountry').on('change', function() { updateCountryFields($(this).val(), true); });

    function clearFormErrors() {
        $('#spBankForm .is-invalid').removeClass('is-invalid');
        $('#spBankForm .invalid-feedback').text('');
        $('#spBankModalAlert').addClass('d-none').text('');
    }

    function showPageAlert(msg, type = 'success') {
        $('#spBankAlertBox').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    function renderAccounts(accounts) {
        const list = $('#spBankAccountsList');
        if (!accounts || !accounts.length) {
            list.html('<div class="col-12"><div class="text-center text-muted py-4 border rounded-3 bg-light small">No store bank accounts saved yet. Click <strong>Add Bank Account</strong> to add one.</div></div>');
            return;
        }
        let html = '';
        accounts.forEach(acc => {
            html += `
                <div class="col-12 col-md-6">
                    <div class="sp-bank-card ${acc.is_default ? 'is-default' : ''}" data-account-id="${acc.id}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="fw-bold d-flex flex-wrap align-items-center gap-1" style="color: #171717; font-size: 0.92rem;">
                                    <span>${escapeHtml(acc.bank_name)}</span>
                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">${escapeHtml(acc.country_code)} / ${escapeHtml(acc.currency)}</span>
                                    ${acc.is_default ? '<span class="badge rounded-pill" style="background:#FFC629;color:#171717;font-size:0.7rem;">Default</span>' : ''}
                                </div>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-nowrap" style="font-size: 0.7rem;">${escapeHtml(acc.verification_label)}</span>
                        </div>
                        <div class="small text-muted mb-3" style="word-break: break-word;">
                            <div>Holder: <strong class="text-dark">${escapeHtml(acc.account_holder_name)}</strong></div>
                            <div>Account: <strong class="text-dark">${escapeHtml(acc.account_number_masked)}</strong></div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-1 pt-2 border-top">
                            ${!acc.is_default ? `<button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 sp-bank-default-btn" data-id="${acc.id}">Set Default</button>` : ''}
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 sp-bank-edit-btn" data-account='${JSON.stringify(acc).replace(/'/g, "&#39;")}'>Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 sp-bank-delete-btn" data-id="${acc.id}">Archive</button>
                        </div>
                    </div>
                </div>
            `;
        });
        list.html(html);
    }

    $('#spOpenAddBankBtn').on('click', function() {
        clearFormErrors();
        $('#spBankId').val('');
        $('#spBankModalTitle').text('Add Store Bank Account');
        $('#spBankForm')[0].reset();
        $('#spBankCountry').val(@json($store->country_code ?: 'GB')).trigger('change');
        $('#spBankCurrency').val(@json($storeCurrency));
        $('#spBankHolder').val(@json($store->name));
        $('#spAccEditHint').hide();
        $('#spAccNumReq').show();
        bankModal.show();
    });

    $(document).on('click', '.sp-bank-edit-btn', function() {
        clearFormErrors();
        const acc = $(this).data('account');
        $('#spBankId').val(acc.id);
        $('#spBankModalTitle').text('Edit Store Bank Account (' + acc.account_number_masked + ')');
        $('#spBankCountry').val(acc.country_code).trigger('change');
        $('#spBankCurrency').val(acc.currency);
        $('#spBankHolder').val(acc.account_holder_name);
        $('#spBankName').val(acc.bank_name);
        $('#spBankType').val(acc.account_type || '');
        $('#spBankAccNum').val('');
        $('#spBankRouting').val('');
        $('#spBankIban').val('');
        $('#spBankSwift').val(acc.swift_bic || '');
        $('#spBankDefault').prop('checked', !!acc.is_default);
        $('#spAccEditHint').show();
        $('#spAccNumReq').hide();
        bankModal.show();
    });

    bankModalEl.addEventListener('hidden.bs.modal', function () {
        clearFormErrors();
    });

    let submitting = false;
    $('#spBankForm').on('submit', function(e) {
        e.preventDefault();
        if (submitting) return;
        submitting = true;
        clearFormErrors();
        const btn = $('#spBankSubmitBtn').prop('disabled', true).text('Saving...');
        const id = $('#spBankId').val();
        const url = id ? `${baseUrl}/${id}` : storeUrl;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function(res) {
                bankModal.hide();
                renderAccounts(res.accounts);
                showPageAlert(res.message || 'Store bank account saved.', 'success');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        $(`#spBankForm [name="${field}"]`).addClass('is-invalid');
                        $(`#spBankForm .invalid-feedback[data-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    $('#spBankModalAlert').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to save bank account.');
                }
            },
            complete: function() {
                submitting = false;
                btn.prop('disabled', false).text('Save Bank Account');
            }
        });
    });

    let actionBusy = false;
    $(document).on('click', '.sp-bank-default-btn', function() {
        if (actionBusy) return;
        actionBusy = true;
        const btn = $(this).prop('disabled', true).text('Updating...');
        const id = btn.data('id');
        $.ajax({
            url: `${baseUrl}/${id}/default`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) {
                renderAccounts(res.accounts);
                showPageAlert(res.message, 'success');
            },
            error: function(xhr) {
                showPageAlert(xhr.responseJSON?.message || 'Failed to update default account.', 'danger');
            },
            complete: function() {
                actionBusy = false;
            }
        });
    });

    $(document).on('click', '.sp-bank-delete-btn', function() {
        if (actionBusy || !confirm('Archive this bank account?')) return;
        actionBusy = true;
        const btn = $(this).prop('disabled', true).text('Archiving...');
        const id = btn.data('id');
        $.ajax({
            url: `${baseUrl}/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) {
                renderAccounts(res.accounts);
                showPageAlert(res.message, 'success');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.errors?.bank_account?.[0] || xhr.responseJSON?.message || 'Unable to archive bank account.';
                showPageAlert(msg, 'danger');
                btn.prop('disabled', false).text('Archive');
            },
            complete: function() {
                actionBusy = false;
            }
        });
    });

    updateCountryFields($('#spBankCountry').val(), false);

    // Change Password Modal & AJAX
    const pwModalEl = document.getElementById('spChangePasswordModal');
    const pwModal = pwModalEl ? bootstrap.Modal.getOrCreateInstance(pwModalEl) : null;
    const pwForm = $('#spChangePasswordForm');

    function resetPwModal() {
        if (!pwForm.length) return;
        pwForm[0].reset();
        pwForm.find('.is-invalid').removeClass('is-invalid');
        pwForm.find('.invalid-feedback').text('');
        $('#spPasswordModalAlert').addClass('d-none').text('');
        pwForm.find('input[type="text"].form-control').attr('type', 'password');
    }

    $('#spOpenChangePasswordBtn').on('click', function() {
        resetPwModal();
        pwModal?.show();
    });

    pwModalEl?.addEventListener('hidden.bs.modal', resetPwModal);

    $(document).on('click', '.js-sp-toggle-pw', function() {
        const targetId = $(this).data('target');
        const input = document.getElementById(targetId);
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        $(this).attr('aria-label', isHidden ? 'Hide password' : 'Show password');
    });

    let pwSubmitting = false;
    pwForm.on('submit', function(e) {
        e.preventDefault();
        if (pwSubmitting) return;
        pwSubmitting = true;

        pwForm.find('.is-invalid').removeClass('is-invalid');
        pwForm.find('.invalid-feedback').text('');
        $('#spPasswordModalAlert').addClass('d-none').text('');

        const btn = $('#spPasswordSubmitBtn');
        const origText = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Updating...');

        $.ajax({
            url: pwForm.attr('action'),
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: pwForm.serialize(),
            success: function(res) {
                pwModal?.hide();
                resetPwModal();
                $('#spProfileAlert')
                    .removeClass('d-none alert-danger')
                    .addClass('alert-success')
                    .text(res.message || 'Password updated successfully.');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        pwForm.find(`[name="${field}"]`).addClass('is-invalid');
                        pwForm.find(`.invalid-feedback[data-pw-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    $('#spPasswordModalAlert').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to update password.');
                }
            },
            complete: function() {
                pwSubmitting = false;
                btn.prop('disabled', false).text(origText);
            }
        });
    });
});
</script>
@endpush
