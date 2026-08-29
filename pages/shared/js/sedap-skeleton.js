/**
 * sedap-skeleton.js — Skeleton Loader Utility
 * SeDaP Application — pages/shared/js/sedap-skeleton.js
 *
 * Usage:
 *   SedapSkeleton.show('#my-container');   // show skeleton inside container
 *   SedapSkeleton.hide('#my-container');   // restore real content
 *   SedapSkeleton.pageLoader(true|false);  // full-page loading overlay
 *
 * HTML snippets helpers:
 *   SedapSkeleton.card(rows)       → returns skeleton-card HTML
 *   SedapSkeleton.table(rows,cols) → returns skeleton table-row HTML
 *   SedapSkeleton.stat(count)      → returns skeleton stat cards HTML
 */

const SedapSkeleton = (() => {
    'use strict';

    // ── Inject page-loader overlay on first call ──────────────────────────
    let _loaderEl = null;
    function _ensureLoader() {
        if (!_loaderEl) {
            _loaderEl = document.createElement('div');
            _loaderEl.className = 'page-loader hidden';
            _loaderEl.innerHTML = `
                <span class="material-symbols-outlined" style="font-size:48px;color:var(--primary);
                    font-variation-settings:'FILL' 1;animation:spin 1s linear infinite;display:inline-block;">
                    health_and_safety
                </span>
                <div class="page-loader-text">Memuatkan… / Loading…</div>
            `;
            document.body.appendChild(_loaderEl);
        }
    }

    // ── Page-level full-screen loader ─────────────────────────────────────
    function pageLoader(show) {
        _ensureLoader();
        if (show) {
            _loaderEl.classList.remove('hidden');
        } else {
            _loaderEl.classList.add('hidden');
        }
    }

    // ── Store original HTML before replacing with skeletons ───────────────
    const _originals = new WeakMap();

    // ── Show skeleton inside a container ─────────────────────────────────
    function show(selector, skeletonHTML = null) {
        const el = typeof selector === 'string'
            ? document.querySelector(selector)
            : selector;
        if (!el) return;
        if (!_originals.has(el)) _originals.set(el, el.innerHTML);
        el.innerHTML = skeletonHTML || _defaultSkeleton();
        el.classList.add('loading');
    }

    // ── Restore real content ──────────────────────────────────────────────
    function hide(selector) {
        const el = typeof selector === 'string'
            ? document.querySelector(selector)
            : selector;
        if (!el) return;
        if (_originals.has(el)) {
            el.innerHTML = _originals.get(el);
            _originals.delete(el);
        }
        el.classList.remove('loading');
        el.classList.add('loaded');
        setTimeout(() => el.classList.remove('loaded'), 300);
    }

    // ── Pre-built skeleton HTML generators ───────────────────────────────

    /** Default generic skeleton (title + 3 text lines) */
    function _defaultSkeleton() {
        return `
            <div style="padding:1rem;">
                <div class="skeleton skeleton-title" style="width:40%;"></div>
                <div class="skeleton skeleton-text" style="width:90%;"></div>
                <div class="skeleton skeleton-text" style="width:75%;"></div>
                <div class="skeleton skeleton-text" style="width:60%;"></div>
            </div>`;
    }

    /** Skeleton stat cards grid
     *  @param {number} count - number of stat cards
     */
    function stat(count = 4) {
        return Array.from({ length: count }, () => `
            <div class="skeleton-stat">
                <div class="skeleton skeleton-text" style="width:55%;"></div>
                <div class="skeleton skeleton-title" style="width:35%;"></div>
                <div class="skeleton skeleton-text" style="width:70%;"></div>
            </div>`).join('');
    }

    /** Skeleton content card with header + rows
     *  @param {number} rows - number of text rows inside card
     */
    function card(rows = 4) {
        const bodyRows = Array.from({ length: rows }, (_, i) =>
            `<div class="skeleton skeleton-text" style="width:${90 - i * 8}%;"></div>`
        ).join('');
        return `
            <div class="skeleton-card">
                <div class="sk-header">
                    <div class="skeleton skeleton-avatar"></div>
                    <div style="flex:1;">
                        <div class="skeleton skeleton-text" style="width:50%;"></div>
                        <div class="skeleton skeleton-text" style="width:35%;"></div>
                    </div>
                    <div class="skeleton skeleton-badge"></div>
                </div>
                ${bodyRows}
            </div>`;
    }

    /** Skeleton table rows
     *  @param {number} rows - number of table rows
     *  @param {number} cols - number of columns per row
     */
    function table(rows = 5, cols = 5) {
        return Array.from({ length: rows }, () => `
            <div class="skeleton-row">
                ${Array.from({ length: cols }, (_, i) =>
                    `<div class="skeleton skeleton-text" style="flex:${i === 0 ? 2 : 1};"></div>`
                ).join('')}
            </div>`).join('');
    }

    /** Skeleton list (avatar + 2 lines each)
     *  @param {number} count - number of list items
     */
    function list(count = 5) {
        return Array.from({ length: count }, () => `
            <div class="skeleton-row">
                <div class="skeleton skeleton-avatar"></div>
                <div style="flex:1;">
                    <div class="skeleton skeleton-text" style="width:60%;"></div>
                    <div class="skeleton skeleton-text" style="width:40%;"></div>
                </div>
                <div class="skeleton skeleton-badge"></div>
            </div>`).join('');
    }

    // ── Auto-hide after fetch ─────────────────────────────────────────────
    /**
     * Wrap a fetch() call — shows skeleton before, hides after.
     * @param {string|Element} selector   container to show skeleton in
     * @param {string}         url        fetch URL
     * @param {function}       onSuccess  callback(responseText, container)
     * @param {string}         [type]     'card'|'table'|'list'|'stat' for preset
     */
    async function fetchWithSkeleton(selector, url, onSuccess, type = 'card') {
        const presets = { card, table, list, stat };
        const skHtml = (presets[type] || card)();
        show(selector, skHtml);
        try {
            const res = await fetch(url);
            const text = await res.text();
            hide(selector);
            onSuccess(text, typeof selector === 'string'
                ? document.querySelector(selector) : selector);
        } catch (e) {
            hide(selector);
            console.error('SedapSkeleton fetch error:', e);
        }
    }

    // ── Public API ────────────────────────────────────────────────────────
    return { pageLoader, show, hide, card, table, list, stat, fetchWithSkeleton };
})();

// Auto-hide page loader when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    SedapSkeleton.pageLoader(false);
});
