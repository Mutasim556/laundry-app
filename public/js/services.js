(() => {
    'use strict';

    const form = document.querySelector('#service-filters');
    const results = document.querySelector('#service-results');
    const container = document.querySelector('#results-container');
    if (!form || !results || !container || !window.fetch || !window.AbortController) return;

    const errorBox = document.querySelector('#request-error');
    const status = document.querySelector('#catalog-status');
    const search = form.elements.q;
    let controller;
    let requestId = 0;
    let debounce;
    let pendingRequest;
    let committedUrl = new URL(window.location.href);

    const urlFromForm = () => {
        const url = new URL(form.action);
        for (const [key, value] of new FormData(form)) {
            const trimmed = String(value).trim();
            if (trimmed && !(key === 'sort' && trimmed === 'name')) url.searchParams.set(key, trimmed);
        }
        return url;
    };

    const syncControls = (url, category) => {
        form.elements.category.value = category || url.searchParams.get('category') || 'other-services';
        form.elements.item.value = url.searchParams.get('item') || '';
        search.value = url.searchParams.get('q') || '';
        form.elements.sort.value = url.searchParams.get('sort') || 'name';
        document.querySelectorAll('[data-category]').forEach(link => {
            const active = link.dataset.category === form.elements.category.value;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
    };

    const loadServices = async (url, { historyMode = 'push', focus = false, scroll = false } = {}) => {
        clearTimeout(debounce);
        controller?.abort();
        controller = new AbortController();
        const id = ++requestId;
        pendingRequest = { url, options: { historyMode, focus, scroll } };
        errorBox.hidden = true;
        container.classList.add('is-loading');
        container.setAttribute('aria-busy', 'true');
        results.setAttribute('inert', '');
        status.textContent = 'Loading services…';

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(`Unable to load services (${response.status})`);
            const data = await response.json();
            if (id !== requestId) return;
            if (typeof data.html !== 'string') throw new Error('Invalid catalog response');

            const canonical = new URL(response.url);
            results.innerHTML = data.html;
            syncControls(canonical, data.category);
            document.title = `${data.title} | Freshgo Laundry`;
            if (historyMode === 'push' && canonical.href !== committedUrl.href) history.pushState({}, '', canonical);
            else if (historyMode === 'replace' || (historyMode === 'none' && canonical.href !== window.location.href)) history.replaceState({}, '', canonical);
            committedUrl = canonical;
            status.textContent = `${data.total} ${data.total === 1 ? 'service' : 'services'} in ${data.title}.`;
            pendingRequest = null;
            container.classList.remove('is-loading');
            results.removeAttribute('inert');
            if (focus) document.querySelector('#results-title')?.focus({ preventScroll: true });
            if (scroll) container.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
        } catch (error) {
            if (error.name === 'AbortError' || id !== requestId) return;
            syncControls(committedUrl);
            if (historyMode === 'none') history.replaceState({}, '', committedUrl);
            errorBox.hidden = false;
            status.textContent = 'Services could not be loaded. Your previous results are still available.';
        } finally {
            if (id === requestId) {
                container.classList.remove('is-loading');
                container.setAttribute('aria-busy', 'false');
                results.removeAttribute('inert');
            }
        }
    };

    document.addEventListener('click', event => {
        const link = event.target.closest('[data-category], [data-item], [data-reset], [data-page]');
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        let url;
        if (link.hasAttribute('data-category')) {
            url = new URL(form.action);
            url.searchParams.set('category', link.dataset.category);
        } else if (link.hasAttribute('data-item')) {
            url = urlFromForm();
            url.searchParams.delete('page');
            if (link.dataset.item) url.searchParams.set('item', link.dataset.item);
            else url.searchParams.delete('item');
        } else {
            url = new URL(link.href);
        }
        loadServices(url, { focus: true, scroll: link.hasAttribute('data-page') });
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        loadServices(urlFromForm());
    });
    search.addEventListener('input', () => {
        clearTimeout(debounce);
        controller?.abort();
        // Invalidate a completed response too, so it cannot replace a newer search value.
        requestId++;
        container.classList.remove('is-loading');
        container.setAttribute('aria-busy', 'false');
        results.removeAttribute('inert');
        debounce = setTimeout(() => loadServices(urlFromForm(), { historyMode: 'replace' }), 300);
    });
    form.elements.sort.addEventListener('change', () => loadServices(urlFromForm()));
    document.querySelector('#retry-request').addEventListener('click', () => {
        if (pendingRequest) loadServices(pendingRequest.url, pendingRequest.options);
    });
    window.addEventListener('popstate', () => {
        syncControls(new URL(window.location.href));
        loadServices(new URL(window.location.href), { historyMode: 'none', focus: true });
    });
})();
