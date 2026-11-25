<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Address Book | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
    <style>
        .fg-ab-hero {
            background: linear-gradient(135deg, #FFFDF7 0%, #FFF3C4 52%, #FFE596 100%);
            border: 1px solid #FDE68A;
            border-radius: 24px;
            box-shadow: 0 12px 28px rgba(255, 198, 41, 0.16);
            animation: fgAbFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .fg-ab-hero-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            background: linear-gradient(135deg, #F59E0B 0%, #EC4899 100%);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.28);
            animation: fgAbFloat 3.4s ease-in-out infinite;
            flex-shrink: 0;
        }
        .fg-ab-add-btn {
            background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
            border: 0;
            color: #171717;
            box-shadow: 0 8px 18px rgba(245, 158, 11, 0.28);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }
        .fg-ab-add-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 12px 24px rgba(245, 158, 11, 0.36);
            color: #171717;
        }
        .fg-ab-stat {
            border-radius: 16px;
            padding: 0.75rem 1rem;
            border: 1px solid transparent;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
            animation: fgAbFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .fg-ab-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }
        .fg-ab-card {
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            border-radius: 20px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            position: relative;
            transition: transform 0.26s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.26s ease, border-color 0.26s ease;
            animation: fgAbFadeUp 0.58s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .fg-ab-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.09);
        }
        .fg-ab-card.is-default {
            border-color: #F59E0B;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.16);
        }
        .fg-ab-card-bar {
            height: 4px;
            width: 100%;
        }
        .fg-ab-type-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .fg-ab-card:hover .fg-ab-type-icon {
            transform: scale(1.1) rotate(-5deg);
        }
        .fg-ab-contact-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 0.65rem 0.85rem;
        }
        .fg-ab-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #16A34A;
            display: inline-block;
            animation: fgAbPulse 1.8s ease-in-out infinite;
        }
        .fg-ab-action-btn {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .fg-ab-action-btn:hover {
            transform: translateY(-1px);
        }
        @keyframes fgAbFadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fgAbFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        @keyframes fgAbPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.35); opacity: 0.55; }
        }
        @media (prefers-reduced-motion: reduce) {
            .fg-ab-hero, .fg-ab-hero-icon, .fg-ab-stat, .fg-ab-card, .fg-ab-pulse-dot { animation: none !important; }
            .fg-ab-add-btn, .fg-ab-stat, .fg-ab-card, .fg-ab-type-icon, .fg-ab-action-btn { transition: none !important; }
        }
    </style>
</head>
<body class="booking-page-body">
    
    @include('shared.header')

    <main class="container pt-4 pt-md-5" style="padding-bottom: calc(116px + env(safe-area-inset-bottom, 0px));">
        <div class="booking-confirm-shell mx-auto">
            <!-- Colorful Header Banner -->
            <div class="fg-ab-hero p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="fg-ab-hero-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </span>
                    <div>
                        <h1 class="booking-screen-title mb-1">Address Book</h1>
                        <p class="booking-screen-subtitle mb-0">Manage your saved Home, Office, and Hotel collection addresses.</p>
                    </div>
                </div>
                <a href="{{ route('account.addresses.create') }}" class="btn fg-ab-add-btn fw-bold rounded-pill px-4 py-2">
                    + Add Address
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success small rounded-4 border-0 shadow-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger small rounded-4 border-0 shadow-sm">{{ $errors->first() }}</div>
            @endif

            @if ($addresses->isNotEmpty())
                @php
                    $totalCount = $addresses->count();
                    $hotelCount = $addresses->where('address_type', 'hotel')->count();
                    $homeOfficeCount = $totalCount - $hotelCount;
                    $hasDefault = $addresses->where('is_default', true)->isNotEmpty();
                @endphp
                <div class="row g-2 g-md-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="fg-ab-stat" style="background:#EFF6FF; border-color:#BFDBFE;">
                            <div class="small fw-semibold text-primary">Saved Addresses</div>
                            <div class="fs-4 fw-bold text-dark">{{ $totalCount }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fg-ab-stat" style="background:#FFFBEB; border-color:#FDE68A;">
                            <div class="small fw-semibold" style="color:#B45309;">Home &amp; Office</div>
                            <div class="fs-4 fw-bold text-dark">{{ $homeOfficeCount }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fg-ab-stat" style="background:#F5F3FF; border-color:#DDD6FE;">
                            <div class="small fw-semibold" style="color:#6D28D9;">Hotels</div>
                            <div class="fs-4 fw-bold text-dark">{{ $hotelCount }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fg-ab-stat" style="background:#ECFDF5; border-color:#A7F3D0;">
                            <div class="small fw-semibold text-success">Default Set</div>
                            <div class="fs-4 fw-bold text-dark">{{ $hasDefault ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($addresses->isEmpty())
                <div class="fg-ab-card text-center p-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 mb-3 fg-ab-hero-icon" style="width:60px; height:60px;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <h2 class="h5 fw-bold text-dark mb-2">No Saved Addresses Yet</h2>
                    <p class="text-secondary small mb-3">Add a UK address or hotel to speed up future bookings.</p>
                    <div>
                        <a href="{{ route('account.addresses.create') }}" class="btn fg-ab-add-btn fw-bold rounded-pill px-4 py-2">
                            Add Your First Address
                        </a>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($addresses as $idx => $address)
                        @php
                            $type = strtolower((string) $address->address_type);
                            $palette = match ($type) {
                                'hotel' => ['bar' => 'linear-gradient(90deg, #8B5CF6, #EC4899)', 'iconBg' => '#F5F3FF', 'iconCol' => '#7C3AED', 'badgeBg' => '#EDE9FE', 'badgeCol' => '#6D28D9'],
                                'office', 'work' => ['bar' => 'linear-gradient(90deg, #3B82F6, #06B6D4)', 'iconBg' => '#EFF6FF', 'iconCol' => '#2563EB', 'badgeBg' => '#DBEAFE', 'badgeCol' => '#1D4ED8'],
                                default => ['bar' => 'linear-gradient(90deg, #FFC629, #F59E0B)', 'iconBg' => '#FFFBEB', 'iconCol' => '#D97706', 'badgeBg' => '#FEF3C7', 'badgeCol' => '#B45309'],
                            };
                        @endphp
                        <div class="col-12 col-md-6">
                            <div class="fg-ab-card h-100 d-flex flex-column {{ $address->is_default ? 'is-default' : '' }}" style="animation-delay: {{ min($idx * 60, 300) }}ms;">
                                <div class="fg-ab-card-bar" style="background: {{ $palette['bar'] }};"></div>
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fg-ab-type-icon" style="background: {{ $palette['iconBg'] }}; color: {{ $palette['iconCol'] }};" aria-hidden="true">
                                                @if ($type === 'hotel')
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01"></path></svg>
                                                @elseif ($type === 'office' || $type === 'work')
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                                @else
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                                @endif
                                            </span>
                                            <div>
                                                <span class="badge rounded-pill text-uppercase fw-bold" style="background: {{ $palette['badgeBg'] }}; color: {{ $palette['badgeCol'] }}; font-size:0.68rem;">{{ $address->address_type }}</span>
                                                @if ($address->label)
                                                    <strong class="ms-1 text-dark small">{{ $address->label }}</strong>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($address->is_default)
                                            <span class="badge rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1 fw-bold" style="background:#ECFDF5; color:#15803D; border:1px solid #A7F3D0;">
                                                <span class="fg-ab-pulse-dot"></span> Default
                                            </span>
                                        @endif
                                    </div>

                                    <p class="small text-dark fw-semibold mb-1">{{ $address->display_summary }}</p>
                                    @if ($address->address_details)
                                        <p class="small text-muted mb-2">{{ $address->address_details }}</p>
                                    @endif

                                    @if ($address->hasContactDetails())
                                        <div class="fg-ab-contact-box small text-secondary mt-2">
                                            @if ($address->contact_full_name)
                                                <div><strong>Contact:</strong> {{ $address->contact_full_name }}</div>
                                            @endif
                                            @if ($address->contact_phone)
                                                <div><strong>Phone:</strong> {{ trim(($address->contact_dialing_code ?? '') . ' ' . $address->contact_phone) }}</div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="mt-auto pt-3 d-flex flex-wrap gap-2">
                                        @if (!$address->is_default)
                                            <form method="POST" action="{{ route('account.addresses.default', $address->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-3 fg-ab-action-btn">Set Default</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('account.addresses.edit', $address->id) }}" class="btn btn-sm btn-warning text-dark fw-semibold rounded-pill px-3 fg-ab-action-btn" style="background:#FFF8E5; border-color:#FCD34D;">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('account.addresses.destroy', $address->id) }}"
                                              onsubmit="return confirm('Delete this saved address? Historical confirmed bookings will remain unchanged.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 fg-ab-action-btn">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @include('shared.footer')
    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
