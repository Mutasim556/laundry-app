@php
    $footerCategories = \Modules\Admin\Models\ItemCategory::query()
        ->where('is_active', true)
        ->orderBy('id')
        ->limit(5)
        ->get(['id', 'name', 'slug']);
    $footerContact = app(\App\Services\ContactPageContentService::class)->getPublicPayload(false)['contact_info'] ?? [];
    $footerEmail = trim((string) ($footerContact['support_email'] ?? ''));
    $footerPhone = trim(($footerContact['phone_country_code'] ?? '') . ' ' . ($footerContact['phone_number'] ?? ''));
    $footerAvail = trim((string) ($footerContact['availability_text'] ?? ''));
@endphp

<!-- Shared Customer Desktop Footer -->
<footer class="desktop-footer d-none d-lg-block overflow-hidden" id="contact">
    <div class="container px-4" style="max-width: 1380px;">
        <div class="row g-4">
            <div class="col-lg-4">
                <img src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Logo" class="rounded-2 mb-3" style="height: 48px; background: white; padding: 4px;">
                <p class="text-secondary small">
                    Professional laundry &amp; dry cleaning service crafted for your peace of mind. We collect, we clean, and we deliver fresh clothes right back to your door.
                </p>
                <div class="d-flex align-items-center gap-2 pt-2">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">Freshgo Express</span>
                    <span class="small text-secondary">Available 7 Days a Week</span>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="text-white fw-bold mb-3">Services</h6>
                <ul class="list-unstyled">
                    @foreach ($footerCategories as $footCat)
                        <li><a href="{{ route('services.index', ['category' => $footCat->slug]) }}" class="footer-link">{{ $footCat->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="text-white fw-bold mb-3">Company</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('about') }}" class="footer-link">About Us</a></li>
                    <li><a href="{{ route('contact') }}" class="footer-link">Contact Us</a></li>
                    <li><a href="{{ route('services.index') }}" class="footer-link">Pricing &amp; Catalog</a></li>
                    <li><a href="{{ route('booking.services') }}" class="footer-link">Book a Collection</a></li>
                    <li><a href="{{ route('join-delivery-person') }}" class="footer-link">Join as a Delivery Person</a></li>
                    <li><a href="{{ route('admin.login') }}" class="footer-link">Admin Portal</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-4">
                <h6 class="text-white fw-bold mb-3">Get in Touch</h6>
                <p class="small text-secondary mb-2">{{ $footerContact['description'] ?? 'Have a question or special laundry request? Reach out to our care team.' }}</p>
                @if($footerEmail !== '')
                    <p class="small text-secondary mb-1"><strong>Email:</strong> <a href="mailto:{{ $footerEmail }}" class="footer-link d-inline">{{ $footerEmail }}</a></p>
                @endif
                @if($footerPhone !== '')
                    <p class="small text-secondary mb-1"><strong>Phone:</strong> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $footerPhone) }}" class="footer-link d-inline">{{ $footerPhone }}</a></p>
                @endif
                @if($footerAvail !== '')
                    <p class="small text-secondary">{{ $footerAvail }}</p>
                @endif
            </div>
        </div>
        <div class="border-top border-secondary pt-4 mt-5 text-center text-secondary small">
            &copy; {{ date('Y') }} FreshGo Laundry. All rights reserved. Professional Laundry &amp; Dry Cleaning.
        </div>
    </div>
</footer>
