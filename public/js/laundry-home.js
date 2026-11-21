/**
 * Laundry & Dry Cleaning Service Homepage JavaScript
 * Handles vanilla JS interactions:
 *  - Real-time client-side search across popular services
 *  - Interactive category selection
 *  - Notification toast & dropdown triggers
 *  - Smooth modal/offcanvas bindings
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile & Desktop Search Interaction
    const searchInputs = document.querySelectorAll('.js-laundry-search-input');
    const serviceCards = document.querySelectorAll('.js-service-card-item');

    searchInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            
            // Sync all search inputs across mobile and desktop
            searchInputs.forEach(otherInput => {
                if (otherInput !== input) {
                    otherInput.value = e.target.value;
                }
            });

            let visibleCount = 0;
            serviceCards.forEach(card => {
                const title = (card.getAttribute('data-name') || '').toLowerCase();
                const desc = (card.getAttribute('data-desc') || '').toLowerCase();
                const category = (card.getAttribute('data-category') || '').toLowerCase();

                if (!query || title.includes(query) || desc.includes(query) || category.includes(query)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Handle empty state message if needed
            const emptyNotice = document.getElementById('searchEmptyNotice');
            if (emptyNotice) {
                if (visibleCount === 0 && query !== '') {
                    emptyNotice.classList.remove('d-none');
                } else {
                    emptyNotice.classList.add('d-none');
                }
            }
        });

        // Enter key initiates navigation to /services with query
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                const targetUrl = this.getAttribute('data-search-url') || '/services';
                if (query) {
                    window.location.href = `${targetUrl}?q=${encodeURIComponent(query)}`;
                } else {
                    window.location.href = targetUrl;
                }
            }
        });
    });

    // 2. Filter Button Action - redirects to full catalog filter
    const filterButtons = document.querySelectorAll('.js-filter-toggle-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetUrl = this.getAttribute('data-url') || '/services';
            window.location.href = targetUrl;
        });
    });

    // 3. Bottom Nav Active State Handler
    const bottomNavItems = document.querySelectorAll('.mobile-bottom-nav .nav-tab-item');
    bottomNavItems.forEach(tab => {
        tab.addEventListener('click', function () {
            bottomNavItems.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // 4. Notification Bell Indicator click
    const notificationTriggers = document.querySelectorAll('.js-notification-bell');
    notificationTriggers.forEach(bell => {
        bell.addEventListener('click', function () {
            const indicator = this.querySelector('.notify-indicator');
            if (indicator) {
                indicator.style.display = 'none';
            }
        });
    });
});
