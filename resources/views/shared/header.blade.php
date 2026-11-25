@include('shared.preloader')

<style>
    .desktop-nav-link {
        border-radius: 8px !important;
        text-decoration: none !important;
    }
    .desktop-nav-link:hover,
    .desktop-nav-link:focus {
        color: #171717 !important;
        background-color: #FFF8E5 !important;
        text-decoration: none !important;
    }
    .desktop-nav-link.active {
        color: #FFFFFF !important;
        background-color: #171717 !important;
        text-decoration: none !important;
    }
</style>

<!-- Mobile Header (< 992px) -->
<div class="d-lg-none px-3 pt-3 bg-white border-bottom">
    @include('shared.header-mobile')
</div>

<!-- Desktop Header (>= 992px) -->
<div class="d-none d-lg-block">
    @include('shared.header-desktop')
</div>
