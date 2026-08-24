/**
 * sedap-spa.js — Seamless AJAX Navigation for SeDaP Sidebar Links
 * Prevents full page reloads when clicking any sidebar button or navigation link.
 */
(function() {
    'use strict';

    function isSameOrigin(url) {
        try {
            const loc = window.location;
            const a = document.createElement('a');
            a.href = url;
            return a.hostname === loc.hostname && a.port === loc.port && a.protocol === loc.protocol;
        } catch (e) {
            return false;
        }
    }

    function loadPage(url, pushState = true) {
        // Show subtle loading state on main container if available
        const mainContainer = document.querySelector('.sedap-main') || document.querySelector('main') || document.querySelector('.sedap-content');
        if (mainContainer) {
            mainContainer.style.opacity = '0.6';
            mainContainer.style.transition = 'opacity 0.15s ease';
        }

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error('HTTP error ' + response.status);
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Find new main content
            const newMain = doc.querySelector('.sedap-main') || doc.querySelector('main') || doc.querySelector('.sedap-content');
            const currentMain = document.querySelector('.sedap-main') || document.querySelector('main') || document.querySelector('.sedap-content');

            if (newMain && currentMain) {
                currentMain.innerHTML = newMain.innerHTML;
                currentMain.style.opacity = '1';

                // Re-execute scripts in loaded HTML
                const scripts = Array.from(newMain.querySelectorAll('script'));
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    if (oldScript.src) {
                        newScript.src = oldScript.src;
                    } else {
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    }
                    document.body.appendChild(newScript);
                });
            } else {
                // Fallback to normal page load if layout structures differ
                window.location.href = url;
                return;
            }

            // Update title
            if (doc.title) {
                document.title = doc.title;
            }

            // Update History URL
            if (pushState) {
                window.history.pushState({ url: url }, doc.title, url);
            }

            // Update Active Link highlight in sidebar
            updateActiveSidebarLink(url);

            // Scroll main container or window to top
            if (currentMain) {
                currentMain.scrollTop = 0;
            }
            window.scrollTo(0, 0);
        })
        .catch(err => {
            console.warn('SPA navigation error, falling back to full reload:', err);
            if (mainContainer) mainContainer.style.opacity = '1';
            window.location.href = url;
        });
    }

    function updateActiveSidebarLink(targetUrl) {
        try {
            const targetPath = new URL(targetUrl, window.location.origin).pathname;
            document.querySelectorAll('.sedap-sidebar .nav-link').forEach(link => {
                const linkPath = new URL(link.href, window.location.origin).pathname;
                if (linkPath === targetPath) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        } catch (e) {}
    }

    // Event listener for sidebar link clicks
    document.addEventListener('click', function(e) {
        const link = e.target.closest('.sedap-sidebar a.nav-link');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        // Allow normal behavior for logout or links with explicit onclick confirm dialogs
        if (href.includes('logout.php') || link.getAttribute('onclick')) return;

        // Allow opening in new tab
        if (e.ctrlKey || e.shiftKey || e.metaKey || e.button !== 0) return;

        const fullUrl = link.href;
        if (isSameOrigin(fullUrl)) {
            e.preventDefault();
            loadPage(fullUrl, true);
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.url) {
            loadPage(e.state.url, false);
        } else {
            loadPage(window.location.href, false);
        }
    });
})();
