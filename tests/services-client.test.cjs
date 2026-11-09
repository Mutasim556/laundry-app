const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const source = readFileSync(require('node:path').join(__dirname, '../public/js/services.js'), 'utf8');

function catalog() {
    const listeners = new Map();
    const requests = [];
    const historyCalls = [];
    const timers = new Map();
    const node = () => ({
        value: '', hidden: false, textContent: '', innerHTML: 'Previous results', attributes: {},
        classList: { add() {}, remove() {}, toggle() {} },
        setAttribute(key, value) { this.attributes[key] = value; },
        removeAttribute(key) { delete this.attributes[key]; },
        addEventListener(event, fn) { this[event] = fn; },
        focus() {}, scrollIntoView() {},
    });
    const form = node();
    form.action = 'http://catalog.test/services';
    form.elements = Object.fromEntries(['category', 'item', 'q', 'sort'].map(key => [key, node()]));
    form.elements.category.value = 'wash-iron';
    form.elements.sort.value = 'name';
    const elements = Object.fromEntries(['service-results', 'results-container', 'request-error', 'catalog-status', 'results-title', 'retry-request'].map(key => [key, node()]));
    elements['request-error'].hidden = true;
    const document = {
        querySelector(selector) { return selector === '#service-filters' ? form : elements[selector.slice(1)]; },
        querySelectorAll() { return []; },
        addEventListener(event, fn) { listeners.set(event, fn); },
    };
    const window = {
        fetch: true, AbortController, location: { href: 'http://catalog.test/services?category=wash-iron' },
        addEventListener(event, fn) { listeners.set(event, fn); },
        matchMedia() { return { matches: true }; },
    };
    const history = {
        pushState(state, title, url) { historyCalls.push(['push', url.href]); window.location.href = url.href; },
        replaceState(state, title, url) { historyCalls.push(['replace', url.href]); window.location.href = url.href; },
    };
    let timerId = 0;
    runInNewContext(source, {
        document, window, history, URL, AbortController,
        FormData: class { constructor() { return Object.entries(form.elements).map(([key, element]) => [key, element.value]); } },
        setTimeout(fn) { timers.set(++timerId, fn); return timerId; },
        clearTimeout(id) { timers.delete(id); },
        fetch(url, options) { return new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })); },
    });
    return {
        form, elements, requests, historyCalls, window,
        click(attributes, url) {
            const link = {
                dataset: attributes,
                href: url || 'http://catalog.test/services',
                hasAttribute(key) { return Object.hasOwn(attributes, key.replace('data-', '')); },
            };
            listeners.get('click')({ target: { closest() { return link; } }, button: 0, preventDefault() {} });
        },
        flushSearch() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(fn => fn()); },
        backTo(url) { window.location.href = url; listeners.get('popstate')(); },
        async respond(index, html, total = 1) {
            const request = requests[index];
            request.resolve({ ok: true, url: request.url.href, json: async () => ({ html, total, category: request.url.searchParams.get('category'), title: 'Wash & Iron' }) });
            await new Promise(resolve => setImmediate(resolve));
        },
    };
}

test('category and item filters request fresh HTML without navigating the page', async () => {
    const app = catalog();
    app.click({ category: 'wash-fold' });
    assert.equal(app.requests[0].url.search, '?category=wash-fold');
    assert.equal(app.requests[0].options.headers.Accept, 'application/json');
    await app.respond(0, 'Fold results');
    assert.equal(app.elements['service-results'].innerHTML, 'Fold results');
    assert.equal(app.historyCalls[0][0], 'push');
    app.click({ item: '3' });
    assert.equal(app.requests[1].url.searchParams.get('item'), '3');
    assert.equal(app.requests[1].url.searchParams.get('category'), 'wash-fold');
});

test('late responses cannot overwrite the latest search results', async () => {
    const app = catalog();
    app.form.elements.q.value = 'shirt';
    app.form.elements.q.input();
    app.flushSearch();
    app.form.elements.q.value = 'duvet';
    app.form.elements.q.input();
    app.flushSearch();
    assert.equal(app.requests[0].options.signal.aborted, true);
    await app.respond(1, 'Duvet results');
    await app.respond(0, 'Old shirt results');
    assert.equal(app.elements['service-results'].innerHTML, 'Duvet results');
    assert.equal(app.form.elements.q.value, 'duvet');
    assert.equal(app.historyCalls.length, 1);
});

test('failed filters preserve previous results and retry the requested filter', async () => {
    const app = catalog();
    app.form.elements.sort.value = 'price-low';
    app.form.elements.sort.change();
    app.requests[0].reject(new Error('Offline'));
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(app.elements['request-error'].hidden, false);
    assert.equal(app.elements['service-results'].innerHTML, 'Previous results');
    assert.equal(app.elements['results-container'].attributes['aria-busy'], 'false');
    assert.equal(app.historyCalls.length, 0);
    app.elements['retry-request'].click();
    assert.equal(app.requests[1].url.searchParams.get('sort'), 'price-low');
    await app.respond(1, 'Sorted results');
    assert.equal(app.elements['request-error'].hidden, true);
    assert.equal(app.elements['service-results'].innerHTML, 'Sorted results');
});

test('Back navigation restores filters over ajax without creating another history entry', async () => {
    const app = catalog();
    app.backTo('http://catalog.test/services?category=wash-iron&item=7&sort=price-high&q=bed');
    await app.respond(0, 'Bed sheet results');
    assert.equal(app.form.elements.item.value, '7');
    assert.equal(app.form.elements.sort.value, 'price-high');
    assert.equal(app.form.elements.q.value, 'bed');
    assert.equal(app.elements['service-results'].innerHTML, 'Bed sheet results');
    assert.equal(app.historyCalls.length, 0);
});

test('failed Back navigation keeps the address consistent with the visible results', async () => {
    const app = catalog();
    app.backTo('http://catalog.test/services?category=wash-fold');
    app.requests[0].reject(new Error('Offline'));
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(app.window.location.href, 'http://catalog.test/services?category=wash-iron');
    assert.equal(app.form.elements.category.value, 'wash-iron');
    assert.equal(app.elements['service-results'].innerHTML, 'Previous results');
    app.elements['retry-request'].click();
    await app.respond(1, 'Fold results');
    assert.equal(app.window.location.href, 'http://catalog.test/services?category=wash-fold');
});
