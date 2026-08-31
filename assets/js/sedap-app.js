/**
 * SeDaP Application JavaScript
 * CoreUI Bootstrap 5 — Click-Only Sidebar Collapse (No Hover Expansion)
 */

/* ── Universal Sidebar Toggle ───────────────────────────────────── */
function sedapToggleSidebar() {
  const sidebarEl = document.getElementById('sidebar');
  if (!sidebarEl) return;

  if (typeof coreui !== 'undefined' && coreui.Sidebar) {
    const sidebarInstance = coreui.Sidebar.getOrCreateInstance(sidebarEl);
    if (window.innerWidth < 992) {
      sidebarInstance.toggle();
    } else {
      sidebarInstance.toggleNarrow();
      setTimeout(() => {
        const isNarrow = sidebarEl.classList.contains('sidebar-narrow');
        try { localStorage.setItem('sedap_sidebar_narrow', isNarrow ? '1' : '0'); } catch(e) {}
      }, 50);
    }
  } else {
    // Fallback if CoreUI JS is deferred
    if (window.innerWidth < 992) {
      sidebarEl.classList.toggle('show');
    } else {
      const isNarrow = sidebarEl.classList.toggle('sidebar-narrow');
      try { localStorage.setItem('sedap_sidebar_narrow', isNarrow ? '1' : '0'); } catch(e) {}
    }
  }
}

/* ── Dark Mode Toggle ──────────────────────────────────────────── */
function sedapToggleDark() {
  const html = document.documentElement;
  const isDark = html.getAttribute('data-coreui-theme') === 'dark';
  const newTheme = isDark ? 'light' : 'dark';
  html.setAttribute('data-coreui-theme', newTheme);

  const icon = document.getElementById('theme-icon');
  if (icon) icon.textContent = newTheme === 'dark' ? 'light_mode' : 'dark_mode';

  // Persist to PHP session via AJAX
  fetch('/sedap/sedap2.0/pages/shared/actions/set_dark_mode.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ dark_mode: newTheme === 'dark' ? 1 : 0 })
  }).catch(() => {});
}

/* ── Language Setting Function ─────────────────────────────────── */
function sedapSetLanguage(lang) {
  if (!lang || !['ms', 'en'].includes(lang)) return;

  fetch('/sedap/sedap2.0/pages/shared/actions/set_language.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ lang: lang })
  })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        sedapToast(lang === 'ms' ? 'Bahasa ditukar ke Bahasa Melayu' : 'Language switched to English', 'success');
        setTimeout(() => window.location.reload(), 600);
      }
    })
    .catch(() => {
      sedapToast('Ralat menukar bahasa / Failed to switch language', 'error');
    });
}

/* ── Notification Sound System ─────────────────────────────────── */
let globalAudioCtx = null;

function getAudioContext() {
  try {
    if (!globalAudioCtx) {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (AudioCtx) {
        globalAudioCtx = new AudioCtx();
      }
    }
    if (globalAudioCtx && globalAudioCtx.state === 'suspended') {
      globalAudioCtx.resume();
    }
  } catch (e) {}
  return globalAudioCtx;
}

// Auto-unlock audio context on user interaction
document.addEventListener('click', function unlockAudio() {
  getAudioContext();
}, { once: true, passive: true });

document.addEventListener('keydown', function unlockAudioKey() {
  getAudioContext();
}, { once: true, passive: true });

function sedapPlayNotificationSound() {
  const isEnabled = localStorage.getItem('sedap_sound_notification') !== '0';
  if (!isEnabled) return;

  try {
    const ctx = getAudioContext();
    if (!ctx) return;

    // Twin-tone crisp medical chime
    const now = ctx.currentTime;
    
    // Note 1 (587.33 Hz - D5)
    const osc1 = ctx.createOscillator();
    const gain1 = ctx.createGain();
    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(587.33, now);
    gain1.gain.setValueAtTime(0.2, now);
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
    osc1.connect(gain1);
    gain1.connect(ctx.destination);
    osc1.start(now);
    osc1.stop(now + 0.35);

    // Note 2 (880.00 Hz - A5)
    const osc2 = ctx.createOscillator();
    const gain2 = ctx.createGain();
    osc2.type = 'sine';
    osc2.frequency.setValueAtTime(880.00, now + 0.12);
    gain2.gain.setValueAtTime(0.25, now + 0.12);
    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
    osc2.connect(gain2);
    gain2.connect(ctx.destination);
    osc2.start(now + 0.12);
    osc2.stop(now + 0.55);
  } catch (e) {
    console.warn("Audio chime error:", e);
  }
}

function sedapToggleSound() {
  const switchEl = document.getElementById('soundNotificationSwitch');
  const isEnabled = switchEl ? switchEl.checked : true;
  localStorage.setItem('sedap_sound_notification', isEnabled ? '1' : '0');

  if (isEnabled) {
    sedapPlayNotificationSound();
    sedapToast('Bunyi notifikasi diaktifkan / Sound notifications enabled', 'success');
  } else {
    sedapToast('Bunyi notifikasi dinyahaktifkan / Sound notifications muted', 'info');
  }

  fetch('/sedap/sedap2.0/pages/shared/actions/set_sound_notification.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sound_notification: isEnabled ? 1 : 0 })
  }).catch(() => {});
}

function sedapTestSound() {
  sedapPlayNotificationSound();
  sedapToast('Bunyi notifikasi diuji / Sound notification tested', 'info');
}

/* ── Global Sidebar Unread Badge & Sound Poller ────────────────── */
let sedapGlobalPrevUnread = -1;

function sedapUpdateSidebarBadges(count) {
  const badges = document.querySelectorAll('.sidebar-chat-badge');
  badges.forEach(badge => {
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.classList.remove('d-none');
    } else {
      badge.classList.add('d-none');
    }
  });
}

async function sedapPollGlobalUnread() {
  try {
    const res = await fetch('/sedap/sedap2.0/pages/shared/actions/chat_api.php?action=get_unread_total');
    if (!res.ok) return;
    const data = await res.json();
    if (!data.ok) return;

    const count = parseInt(data.total_unread, 10) || 0;
    
    // Play sound if unread count increased and not on initial page load
    if (sedapGlobalPrevUnread !== -1 && count > sedapGlobalPrevUnread) {
      // Avoid double-chime if already on livechat.php (which has its own active conversation sound)
      const isLiveChatPage = window.location.pathname.includes('livechat.php');
      if (!isLiveChatPage && typeof sedapPlayNotificationSound === 'function') {
        sedapPlayNotificationSound();
      }
    }
    sedapGlobalPrevUnread = count;

    sedapUpdateSidebarBadges(count);
  } catch (e) {}
}

function sedapInitGlobalChatBadge() {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  sedapPollGlobalUnread();
  setInterval(sedapPollGlobalUnread, 3000);
}

// Explicit global attachment
window.sedapPlayNotificationSound = sedapPlayNotificationSound;
window.sedapToggleSound = sedapToggleSound;
window.sedapTestSound = sedapTestSound;
window.sedapToggleDark = sedapToggleDark;
window.sedapSetLanguage = sedapSetLanguage;
window.sedapToast = sedapToast;
window.sedapInitGlobalChatBadge = sedapInitGlobalChatBadge;
window.sedapPollGlobalUnread = sedapPollGlobalUnread;
window.sedapUpdateSidebarBadges = sedapUpdateSidebarBadges;

/* ── Seamless SPA Page Navigation Engine ─────────────────────────── */
let isNavigatingSPA = false;

function sedapGetMainContainer(doc = document) {
  return doc.querySelector('.body.flex-grow-1') || doc.querySelector('.body') || doc.querySelector('main') || doc.querySelector('.sedap-content') || doc.querySelector('.wrapper');
}

function sedapShowProgress() {
  let progress = document.getElementById('sedap-spa-progress');
  if (!progress) {
    progress = document.createElement('div');
    progress.id = 'sedap-spa-progress';
    progress.style.cssText = 'position:fixed;top:0;left:0;width:0%;height:3px;background:linear-gradient(90deg,#087383,#00d2ff);z-index:999999;transition:width 0.25s ease,opacity 0.3s ease;pointer-events:none;box-shadow:0 0 8px rgba(8,115,131,0.6);';
    document.body.appendChild(progress);
  }
  progress.style.opacity = '1';
  progress.style.width = '35%';
  setTimeout(() => {
    if (isNavigatingSPA && progress) progress.style.width = '75%';
  }, 100);
}

function sedapFinishProgress() {
  const progress = document.getElementById('sedap-spa-progress');
  if (progress) {
    progress.style.width = '100%';
    setTimeout(() => {
      progress.style.opacity = '0';
      setTimeout(() => {
        progress.style.width = '0%';
      }, 300);
    }, 150);
  }
}

async function sedapNavigateTo(url, pushHistory = true) {
  if (isNavigatingSPA) return;
  isNavigatingSPA = true;
  sedapShowProgress();

  const currentMain = sedapGetMainContainer(document);
  if (currentMain) {
    currentMain.style.transition = 'opacity 0.15s ease';
    currentMain.style.opacity = '0.5';
  }

  try {
    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'SedapSPA' }
    });

    if (!res.ok) {
      window.location.href = url;
      return;
    }

    const htmlText = await res.text();
    const parser = new DOMParser();
    const newDoc = parser.parseFromString(htmlText, 'text/html');

    // 1. Update document title
    if (newDoc.title) {
      document.title = newDoc.title;
    }

    // 2. Extract and swap main content container
    const newMain = sedapGetMainContainer(newDoc);
    if (newMain && currentMain) {
      currentMain.innerHTML = newMain.innerHTML;
      currentMain.style.opacity = '1';
    } else {
      window.location.href = url;
      return;
    }

    // 3. Inject new stylesheets from head if any (resolved against destination url)
    const existingHrefs = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(l => l.href);
    newDoc.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
      const rawHref = link.getAttribute('href');
      if (rawHref) {
        try {
          const fullHref = new URL(rawHref, url).href;
          if (!existingHrefs.includes(fullHref)) {
            const newLink = document.createElement('link');
            newLink.rel = 'stylesheet';
            newLink.href = fullHref;
            document.head.appendChild(newLink);
            existingHrefs.push(fullHref);
          }
        } catch(e) {}
      }
    });

    // 4. Update Sidebar active states
    sedapUpdateSidebarActiveLink(url);

    // 5. Update browser history
    if (pushHistory) {
      history.pushState({ url: url }, '', url);
    }

    // 6. Close mobile sidebar drawer if open
    if (window.innerWidth < 992) {
      const sidebarEl = document.getElementById('sidebar');
      if (sidebarEl && sidebarEl.classList.contains('show')) {
        sedapToggleSidebar();
      }
    }

    // 7. Scroll to top smoothly
    window.scrollTo({ top: 0, behavior: 'instant' });

    // 8. Re-evaluate and execute scripts in the newly loaded page
    await sedapExecutePageScripts(newDoc, url);

    // 9. Re-initialize CoreUI / Bootstrap components on new DOM
    sedapReinitializeComponents();

  } catch (err) {
    console.error("SPA navigation error:", err);
    window.location.href = url;
  } finally {
    isNavigatingSPA = false;
    sedapFinishProgress();
    if (currentMain) currentMain.style.opacity = '1';
  }
}

function sedapUpdateSidebarActiveLink(url) {
  try {
    const targetPath = new URL(url, window.location.href).pathname.replace(/\/+$/, '');
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
      const href = link.getAttribute('href');
      if (!href || href === '#' || href.startsWith('javascript:')) return;
      try {
        const linkPath = new URL(href, window.location.href).pathname.replace(/\/+$/, '');
        if (targetPath === linkPath || targetPath.endsWith(linkPath)) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      } catch(e) {}
    });
  } catch(e) {}
}

const sedapLoadedScriptKeys = new Set();

async function sedapExecutePageScripts(newDoc, targetUrl) {
  const scripts = Array.from(newDoc.querySelectorAll('script'));
  for (const oldScript of scripts) {
    const rawSrc = oldScript.getAttribute('src');
    if (rawSrc && (rawSrc.includes('coreui.bundle') || rawSrc.includes('sedap-app.js') || rawSrc.includes('tailwindcss'))) {
      continue;
    }

    if (rawSrc) {
      try {
        const urlObj = new URL(rawSrc, targetUrl);
        const scriptKey = urlObj.origin + urlObj.pathname;
        const fullSrc = urlObj.href;

        if (!sedapLoadedScriptKeys.has(scriptKey)) {
          sedapLoadedScriptKeys.add(scriptKey);
          await new Promise((resolve) => {
            const s = document.createElement('script');
            s.src = fullSrc;
            s.onload = () => resolve();
            s.onerror = () => resolve();
            document.body.appendChild(s);
          });
        }
      } catch(e) {}
    } else if (oldScript.textContent) {
      try {
        const s = document.createElement('script');
        s.textContent = oldScript.textContent;
        document.body.appendChild(s);
        setTimeout(() => s.remove(), 50);
      } catch(e) {
        console.error("Inline script eval error:", e);
      }
    }
  }

  window.dispatchEvent(new Event('sedap:page-loaded'));
  document.dispatchEvent(new Event('DOMContentLoaded'));
  window.dispatchEvent(new Event('DOMContentLoaded'));
}

function sedapReinitializeComponents() {
  if (typeof coreui !== 'undefined' && coreui.Tooltip) {
    document.querySelectorAll('[data-coreui-toggle="tooltip"]').forEach(el => {
      coreui.Tooltip.getOrCreateInstance(el);
    });
  }
  if (typeof coreui !== 'undefined' && coreui.Dropdown) {
    document.querySelectorAll('[data-coreui-toggle="dropdown"]').forEach(el => {
      coreui.Dropdown.getOrCreateInstance(el);
    });
  }
}

window.sedapNavigateTo = sedapNavigateTo;

/* ── Page Load Initializations ─────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  const sidebarEl = document.getElementById('sidebar');

  // 1. Restore Sidebar Narrow State on Desktop
  if (sidebarEl && window.innerWidth >= 992) {
    try {
      if (localStorage.getItem('sedap_sidebar_narrow') === '1') {
        sidebarEl.classList.add('sidebar-narrow');
        sidebarEl.classList.remove('sidebar-narrow-unfoldable');
      }
    } catch (e) {}
  }

  // 2. Active Sidebar Link Highlighting
  const currentPath = window.location.pathname.replace(/\/+$/, '');
  sedapUpdateSidebarActiveLink(window.location.href);

  // 3. Sync Dark Mode Icon
  const theme = document.documentElement.getAttribute('data-coreui-theme') || 'light';
  const icon = document.getElementById('theme-icon');
  if (icon) icon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';

  // 4. Initialize Global Chat Badge & Background Sound Notifier
  sedapInitGlobalChatBadge();
});

// Intercept all sidebar links and internal navigation links
document.addEventListener('click', function (e) {
  const link = e.target.closest('a');
  if (!link) return;

  const href = link.getAttribute('href');
  if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
  if (link.hasAttribute('download') || link.getAttribute('target') === '_blank') return;
  if (link.getAttribute('data-no-spa') === 'true' || href.includes('logout.php') || href.includes('export')) return;

  try {
    const targetUrl = new URL(href, window.location.href);
    if (targetUrl.origin !== window.location.origin) return;
    if (!targetUrl.pathname.includes('/sedap/')) return;

    if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search) {
      if (targetUrl.hash) return;
      e.preventDefault();
      return;
    }

    // Only intercept internal portal pages (.php)
    if (targetUrl.pathname.endsWith('.php') || targetUrl.pathname.endsWith('/')) {
      e.preventDefault();
      sedapNavigateTo(targetUrl.href, true);
    }
  } catch (err) {}
});

// Support Browser Back and Forward buttons seamlessly
window.addEventListener('popstate', function () {
  sedapNavigateTo(window.location.href, false);
});

/* ── Toast Helper ──────────────────────────────────────────────── */
function sedapToast(message, type = 'success') {
  const toastContainer = document.getElementById('sedap-toast-container') || (() => {
    const c = document.createElement('div');
    c.id = 'sedap-toast-container';
    c.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;';
    document.body.appendChild(c);
    return c;
  })();

  const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
  const colors = { success: 'text-bg-success', error: 'text-bg-danger', warning: 'text-bg-warning', info: 'text-bg-info' };

  const toast = document.createElement('div');
  toast.className = `d-flex align-items-center gap-2 px-3 py-2 rounded shadow-sm ${colors[type] || colors.info}`;
  toast.style.cssText = 'animation:fadeInUp .25s ease;min-width:220px;';
  toast.innerHTML = `<span class="material-symbols-outlined" style="font-size:20px">${icons[type]||'info'}</span><span class="small">${message}</span>`;
  toastContainer.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}
