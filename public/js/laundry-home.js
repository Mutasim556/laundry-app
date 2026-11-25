/**
 * FreshGo Customer Homepage JavaScript
 * - Debounced AJAX Search (Categories, Services, Laundry Items)
 * - Accessible, responsive Category & Service Carousels with independent autoplay & pause-on-hover/focus
 */
document.addEventListener('DOMContentLoaded', () => {
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    // =========================================================================
    // 1. Dynamic AJAX Search
    // =========================================================================
    const searchWrap = document.getElementById('fgHomeSearchWrap');
    const searchInput = document.getElementById('fgHomeSearchInput');
    const searchDropdown = document.getElementById('fgHomeSearchDropdown');
    const searchSpinner = document.getElementById('fgHomeSearchSpinner');
    const searchClear = document.getElementById('fgHomeSearchClear');

    if (searchWrap && searchInput && searchDropdown) {
        const searchApi = searchInput.dataset.searchApi || '/api/home/search';
        const catalogUrl = searchInput.dataset.catalogUrl || '/services';
        let debounceTimer = null;
        let activeAbort = null;
        let activeIndex = -1;

        const closeDropdown = () => {
            searchDropdown.classList.add('d-none');
            searchInput.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        };

        const openDropdown = () => {
            searchDropdown.classList.remove('d-none');
            searchInput.setAttribute('aria-expanded', 'true');
        };

        const renderGroup = (title, items, badgeStyle) => {
            if (!items || !items.length) return '';
            const rows = items.map((item) => {
                const desc = item.description ? `<div class="small text-muted text-truncate">${escapeHtml(item.description)}</div>` : '';
                const price = item.price_label ? `<span class="small fw-bold text-nowrap d-none d-md-inline" style="color: #171717;">${escapeHtml(item.price_label)}</span>` : '';
                return `<a href="${escapeHtml(item.url)}" class="fg-search-item" role="option">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="fg-search-thumb"><img src="${escapeHtml(item.image_url)}" alt=""></span>
                        <div class="min-w-0">
                            <div class="fw-semibold small text-truncate">${escapeHtml(item.name)}</div>
                            ${desc}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        ${price}
                        <span class="badge rounded-pill" style="${badgeStyle}">${escapeHtml(item.type)}</span>
                    </div>
                </a>`;
            }).join('');
            return `<div class="fg-search-group-label">${escapeHtml(title)}</div>${rows}`;
        };

        const runSearch = async (query) => {
            if (activeAbort) activeAbort.abort();
            activeAbort = new AbortController();
            searchSpinner?.classList.remove('d-none');

            try {
                const url = `${searchApi}?q=${encodeURIComponent(query)}`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    signal: activeAbort.signal,
                });
                if (!res.ok) throw new Error('Search request failed');
                const data = await res.json();
                const groups = data.groups || {};
                const html =
                    renderGroup('Categories', groups.categories, 'background: #FFF8E5; color: #92400E;') +
                    renderGroup('Services', groups.services, 'background: #DBEAFE; color: #1D4ED8;') +
                    renderGroup('Laundry Items', groups.items, 'background: #DCFCE7; color: #166534;');

                if (!html) {
                    searchDropdown.innerHTML = `<div class="p-3 text-center small text-muted">
                        No matching categories, services, or items found for "<strong>${escapeHtml(query)}</strong>".
                        <div class="mt-2"><a href="${escapeHtml(catalogUrl)}?q=${encodeURIComponent(query)}" class="btn btn-sm btn-outline-warning rounded-pill">Search Full Catalog</a></div>
                    </div>`;
                } else {
                    searchDropdown.innerHTML = html;
                }
                activeIndex = -1;
                openDropdown();
            } catch (err) {
                if (err.name !== 'AbortError') {
                    searchDropdown.innerHTML = `<div class="p-3 text-center small text-danger">Unable to search right now. Please try again.</div>`;
                    openDropdown();
                }
            } finally {
                searchSpinner?.classList.add('d-none');
            }
        };

        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim();
            searchClear?.classList.toggle('d-none', q.length === 0);

            if (debounceTimer) clearTimeout(debounceTimer);
            if (q.length < 2) {
                if (activeAbort) activeAbort.abort();
                closeDropdown();
                return;
            }

            debounceTimer = setTimeout(() => runSearch(q), 300);
        });

        searchInput.addEventListener('keydown', (e) => {
            const options = Array.from(searchDropdown.querySelectorAll('.fg-search-item'));
            if (e.key === 'Escape') {
                closeDropdown();
                return;
            }
            if (e.key === 'ArrowDown' && options.length) {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % options.length;
                options.forEach((el, idx) => el.classList.toggle('is-active', idx === activeIndex));
                options[activeIndex]?.scrollIntoView({ block: 'nearest' });
                return;
            }
            if (e.key === 'ArrowUp' && options.length) {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + options.length) % options.length;
                options.forEach((el, idx) => el.classList.toggle('is-active', idx === activeIndex));
                options[activeIndex]?.scrollIntoView({ block: 'nearest' });
                return;
            }
            if (e.key === 'Enter') {
                if (activeIndex >= 0 && options[activeIndex]) {
                    e.preventDefault();
                    window.location.href = options[activeIndex].getAttribute('href');
                    return;
                }
                const q = searchInput.value.trim();
                window.location.href = q ? `${catalogUrl}?q=${encodeURIComponent(q)}` : catalogUrl;
            }
        });

        searchClear?.addEventListener('click', () => {
            searchInput.value = '';
            searchClear.classList.add('d-none');
            closeDropdown();
            searchInput.focus();
        });

        document.addEventListener('click', (e) => {
            if (!searchWrap.contains(e.target)) {
                closeDropdown();
            }
        });
    }

    // =========================================================================
    // 2. Independent Accessible Carousels (Categories & Services)
    // =========================================================================
    class FgCarousel {
        constructor(viewportEl, sectionEl) {
            this.viewport = viewportEl;
            this.track = viewportEl.querySelector('.fg-carousel-track');
            this.slides = Array.from(viewportEl.querySelectorAll('.fg-carousel-slide'));
            this.prevBtns = sectionEl ? Array.from(sectionEl.querySelectorAll('.js-carousel-prev')) : [];
            this.nextBtns = sectionEl ? Array.from(sectionEl.querySelectorAll('.js-carousel-next')) : [];
            this.intervalMs = parseInt(viewportEl.dataset.interval || '4200', 10);
            this.autoplayEnabled = viewportEl.dataset.autoplay !== '0';
            this.pauseOnHover = viewportEl.dataset.pauseHover !== '0';
            this.loopEnabled = viewportEl.dataset.loop !== '0';
            this.index = 0;
            this.timer = null;
            this.paused = false;
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.touchStartX = 0;

            if (!this.track || this.slides.length === 0) return;

            this.bindEvents();
            this.update();
            this.startAutoplay();
        }

        getPerView() {
            const w = window.innerWidth;
            if (w >= 1200) return 4;
            if (w >= 992) return 3;
            if (w >= 768) return 2;
            return parseInt(this.viewport.dataset.mobilePerView || '1', 10);
        }

        getMaxIndex() {
            return Math.max(0, this.slides.length - this.getPerView());
        }

        update() {
            const maxIdx = this.getMaxIndex();
            if (this.index > maxIdx) this.index = maxIdx;
            if (this.index < 0) this.index = 0;

            const canScroll = maxIdx > 0;
            this.prevBtns.forEach((btn) => { btn.disabled = !canScroll; });
            this.nextBtns.forEach((btn) => { btn.disabled = !canScroll; });

            if (!canScroll) {
                this.track.style.transform = 'translateX(0px)';
                return;
            }

            const slide = this.slides[this.index];
            const offset = slide ? slide.offsetLeft : 0;
            this.track.style.transform = `translateX(-${offset}px)`;
        }

        next() {
            const maxIdx = this.getMaxIndex();
            if (maxIdx <= 0) return;
            if (this.index >= maxIdx) {
                this.index = this.loopEnabled ? 0 : maxIdx;
            } else {
                this.index += 1;
            }
            this.update();
        }

        prev() {
            const maxIdx = this.getMaxIndex();
            if (maxIdx <= 0) return;
            if (this.index <= 0) {
                this.index = this.loopEnabled ? maxIdx : 0;
            } else {
                this.index -= 1;
            }
            this.update();
        }

        stopAutoplay() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }

        startAutoplay() {
            this.stopAutoplay();
            if (!this.autoplayEnabled || this.paused || this.reducedMotion || this.getMaxIndex() <= 0) return;
            this.timer = setInterval(() => this.next(), this.intervalMs);
        }

        bindEvents() {
            this.prevBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    this.prev();
                    this.startAutoplay();
                });
            });
            this.nextBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    this.next();
                    this.startAutoplay();
                });
            });

            if (this.pauseOnHover) {
                this.viewport.addEventListener('mouseenter', () => {
                    this.paused = true;
                    this.stopAutoplay();
                });
            }
            this.viewport.addEventListener('mouseleave', () => {
                this.paused = false;
                this.startAutoplay();
            });
            this.viewport.addEventListener('focusin', () => {
                this.paused = true;
                this.stopAutoplay();
            });
            this.viewport.addEventListener('focusout', () => {
                this.paused = false;
                this.startAutoplay();
            });

            this.viewport.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.next();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.prev();
                }
            });

            this.viewport.addEventListener('touchstart', (e) => {
                this.touchStartX = e.changedTouches[0]?.clientX || 0;
            }, { passive: true });

            this.viewport.addEventListener('touchend', (e) => {
                const endX = e.changedTouches[0]?.clientX || 0;
                const delta = endX - this.touchStartX;
                if (Math.abs(delta) > 40) {
                    if (delta < 0) this.next();
                    else this.prev();
                    this.startAutoplay();
                }
            }, { passive: true });

            window.addEventListener('resize', () => this.update());
        }
    }

    const catCarouselEl = document.getElementById('fgCategoryCarousel');
    if (catCarouselEl) {
        new FgCarousel(catCarouselEl, document.getElementById('fgCategorySection'));
    }

    const svcCarouselEl = document.getElementById('fgServiceCarousel');
    if (svcCarouselEl) {
        new FgCarousel(svcCarouselEl, document.getElementById('fgServiceSection'));
    }

    // Bottom Nav & Notification Bell Handlers
    const bottomNavItems = document.querySelectorAll('.mobile-bottom-nav .nav-tab-item');
    bottomNavItems.forEach((tab) => {
        tab.addEventListener('click', function () {
            bottomNavItems.forEach((t) => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    document.querySelectorAll('.js-notification-bell').forEach((bell) => {
        bell.addEventListener('click', function () {
            const indicator = this.querySelector('.notify-indicator');
            if (indicator) indicator.style.display = 'none';
        });
    });
});
