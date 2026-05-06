/**
 * content-loader.js — ICA-TH 2026
 * Replaces firebase-loader.js. Fetches settings & content from PHP backend.
 */

const BASE = 'https://icahubthailand.org/admin/ica-cms-php';

/* ── Notice Banner CSS — production, injected once for all pages */
(function(){
    const s = document.createElement('style');
    s.textContent = '[data-notice="combined"]{display:none;margin-top:16px;width:100%;justify-content:center;}.announcement{border:1.5px solid #ef4444;border-radius:8px;padding:6px 20px;background:rgba(239,68,68,0.15);max-width:min(94vw,800px);box-sizing:border-box;color:#ffffff;font-weight:500;text-align:center;font-size:clamp(10px,1.1vw,13px);line-height:1.6;}.announcement .line{display:block;}@media(max-width:768px){.announcement{padding:6px 14px;max-width:90%;font-size:clamp(12px,3.5vw,14px);}}';
    document.head.appendChild(s);
})();

document.addEventListener('DOMContentLoaded', async () => {
    let s = {};
    try {
        const res = await fetch(BASE + '/api/settings.php?_=' + Date.now());
        if (res.ok) s = await res.json();
    } catch (e) { s = {}; }

    loadDates(s);
    loadAnnouncements(s);
    loadButtonLinks(s);
    injectNavLinks(s);
    loadPageContent();

    if (s['show_news'] === '1')    loadNews();
    if (s['show_gallery'] === '1') loadGallery();
    if (s['show_speakers'] === '1') loadSpeakers();
    if (s['show_sponsors'] === '1') loadSponsors();

    loadNavPages();

    // GA4
    if (s.ga4_id) {
        const s1 = document.createElement('script');
        s1.async = true;
        s1.src = 'https://www.googletagmanager.com/gtag/js?id=' + s.ga4_id;
        document.head.appendChild(s1);
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', s.ga4_id);
    }
});


/* ── Important Dates ─────────────────────────────────────────── */

/* LOCAL OVERRIDES — values here take priority over the CMS database.
   Update these when dates change and the CMS hasn't been updated yet. */
const DATE_OVERRIDES = {
    date_stdReg: '20 May 2026',   // Standard Registration deadline (was 31 May)
};

function loadDates(s) {
    const map = {
        callOpen: 'date_callOpen', subDeadline: 'date_subDeadline',
        notification: 'date_notification', earlyBird: 'date_earlyBird',
        stdReg: 'date_stdReg', techCheck: 'date_techCheck',
        reception: 'date_reception', day1: 'date_day1',
        day2: 'date_day2', proceedings: 'date_proceedings',
    };
    for (const [attr, key] of Object.entries(map)) {
        // Local overrides take priority over CMS value
        const val = DATE_OVERRIDES[key] || s[key];
        if (!val) continue;
        document.querySelectorAll(`[data-date="${attr}"]`).forEach(el => el.textContent = val);
    }
}


/* ── Announcements ───────────────────────────────────────────── */
function loadAnnouncements(s) {
    const parseNoticeFlag = (value, fallback = true) => {
        if (value === undefined || value === null || value === '') return fallback;
        if (typeof value === 'boolean') return value;
        if (typeof value === 'number') return value !== 0;
        if (typeof value === 'string') {
            const v = value.trim().toLowerCase();
            if (v === '0' || v === 'false' || v === 'off' || v === 'no') return false;
            if (v === '1' || v === 'true' || v === 'on' || v === 'yes') return true;
        }
        return fallback;
    };

    const showSub = parseNoticeFlag(s['notice_submission'], false);
    const showReg = parseNoticeFlag(s['notice_registration'], false);

    document.querySelectorAll('[data-notice="combined"]').forEach(el => {
        if (!showSub && !showReg) { el.style.display = 'none'; return; }
        el.style.display = 'flex';
        const div = el.querySelector('.announcement');
        if (!div) return;
        const lines = [];
        if (showSub) lines.push('The submission system will officially open on 31 March 2026.');
        if (showReg) lines.push('The registration system will officially open on 31 March 2026.');
        div.innerHTML = lines.map(l => `<span class="line">${l}</span>`).join('');
    });
}


/* ── Button Links ────────────────────────────────────────────── */
function loadButtonLinks(s) {
    const submitState   = s['btn_submit_state']   || 'disabled';
    const submitUrl     = s['btn_submit_url']     || '#';
    const registerState = s['btn_register_state'] || 'disabled';
    const registerUrl   = s['btn_register_url']   || '#';

    document.querySelectorAll('a.btn').forEach(btn => {
        const text = btn.textContent.trim();
        if (text === 'Submit Your Abstract') {
            btn.href = submitState === 'enabled' ? submitUrl : 'javascript:void(0)';
            btn.style.opacity = submitState === 'enabled' ? '' : '0.5';
            btn.style.cursor  = submitState === 'enabled' ? '' : 'not-allowed';
        }
        if (text === 'Register to Attend') {
            btn.href = registerState === 'enabled' ? registerUrl : 'javascript:void(0)';
            btn.style.opacity = registerState === 'enabled' ? '' : '0.5';
            btn.style.cursor  = registerState === 'enabled' ? '' : 'not-allowed';
        }
    });
}


/* ── News / Articles ─────────────────────────────────────────── */
async function loadNews() {
    const section = document.getElementById('cms-news-section');
    if (!section) return;

    try {
        const res  = await fetch(BASE + '/api/news.php?limit=3');
        const data = await res.json();
        if (!data.ok || !data.data.length) { section.style.display = 'none'; return; }

        section.style.display = '';
        const grid = section.querySelector('.article-grid');
        if (!grid) return;

        grid.innerHTML = data.data.map(n => {
            const img = n.featured_img
                ? `<div class="article-image"><img src="${n.featured_img}" alt="${esc(n.title)}" loading="lazy"></div>`
                : `<div class="article-image" style="background:linear-gradient(135deg,#1a3a5f,#274c77);display:flex;align-items:center;justify-content:center;">
                     <span style="font-size:40px;">📰</span></div>`;
            const excerpt = n.excerpt || '';
            const date    = n.published_at ? new Date(n.published_at).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : '';
            return `<div class="article-card">
                ${img}
                <h3>${esc(n.title)}</h3>
                ${excerpt ? `<p>${esc(excerpt)}</p>` : ''}
                <a href="page.php?slug=${esc(n.slug)}">Read more →</a>
                ${date ? `<div style="padding:0 25px 16px;font-size:12px;color:#94a3b8;">${date}</div>` : ''}
            </div>`;
        }).join('');
    } catch(e) { /* fail silently */ }
}


/* ── Gallery ─────────────────────────────────────────────────── */
async function loadGallery() {
    const section = document.getElementById('cms-gallery-section');
    if (!section) return;

    try {
        const res  = await fetch(BASE + '/api/gallery.php?limit=9');
        const data = await res.json();
        if (!data.ok || !data.data.length) { section.style.display = 'none'; return; }

        section.style.display = '';
        const grid = section.querySelector('.gallery-grid');
        if (!grid) return;

        grid.innerHTML = data.data.map(img => `
            <div class="gallery-item">
                <img src="${esc(img.url)}" alt="${esc(img.alt || img.title || '')}"
                     loading="lazy"
                     style="width:100%;height:220px;object-fit:cover;border-radius:12px;
                            box-shadow:0 4px 16px rgba(0,0,0,0.10);transition:transform .3s"
                     onmouseover="this.style.transform='scale(1.03)'"
                     onmouseout="this.style.transform='scale(1)'">
                ${img.title ? `<div style="font-size:13px;color:#64748b;margin-top:8px;text-align:center;">${esc(img.title)}</div>` : ''}
            </div>`).join('');
    } catch(e) { /* fail silently */ }
}


/* ── Nav Link Injection ──────────────────────────────────────── */
/*
 * FIX NOTES:
 * - Previously: News & Gallery were injected as TOP-LEVEL nav items (wrong).
 *   Speakers & Sponsors went to dropdown but had no deduplication guard.
 * - Previously: null values (key never saved) were not handled — nothing injected.
 * - Previously: cache-busting only on URL but api returned max-age=60 headers.
 * - Now: ALL 4 items inject into #confDropdown only.
 *   Each <li> carries data-dynamic="key" so duplicates are prevented
 *   and toggling OFF actually removes the element from the DOM.
 */
function injectNavLinks(s) {
    const dropdown = document.querySelector('#confDropdown');
    if (!dropdown) {
        console.warn('[ICA Nav] #confDropdown not found — skipping nav injection');
        return;
    }

    // Normalise: treat null/undefined/"0" as off, "1" as on
    const isOn = (val) => val === '1' || val === 1 || val === true;

    const ITEMS = [
        { key: 'show_news',     label: 'News & Updates', href: 'news.html'     },
        { key: 'show_gallery',  label: 'Gallery',        href: 'gallery.html'  },
        { key: 'show_speakers', label: 'Speakers',       href: 'speakers.html' },
        { key: 'show_sponsors', label: 'Sponsors',       href: 'sponsors.html' },
    ];

    ITEMS.forEach(({ key, label, href }) => {
        const existing = dropdown.querySelector(`[data-dynamic="${key}"]`);

        if (isOn(s[key])) {
            if (!existing) {                          // inject only if not already there
                const li = document.createElement('li');
                li.setAttribute('data-dynamic', key);
                li.innerHTML = `<a href="${href}">${label}</a>`;
                dropdown.appendChild(li);
                console.log(`[ICA Nav] Injected → ${label} (${href})`);
            }
        } else {
            if (existing) {                           // remove if toggled OFF
                existing.remove();
                console.log(`[ICA Nav] Removed  → ${label}`);
            }
        }
    });
}


/* ── Page Content (data-cms-section) ────────────────────────── */
async function loadPageContent() {
    const page = window.ICA_PAGE;
    if (!page) return;
    // Map ICA_PAGE to page_sections page name
    const pageMap = { about: 'about', venue: 'venue', contact: 'contact', submission: 'submission', registration: 'registration' };
    const pageName = pageMap[page];
    if (!pageName) return;
    try {
        const res = await fetch(BASE + '/api/page-content.php?page=' + pageName);
        const data = await res.json();
        for (const [key, content] of Object.entries(data)) {
            if (!content) continue;
            document.querySelectorAll(`[data-cms-section="${pageName}_${key}"]`).forEach(el => {
                el.innerHTML = content;
            });
        }
    } catch(e) { /* fail silently */ }
}


/* ── Speakers ────────────────────────────────────────────────── */
async function loadSpeakers() {
    const section = document.getElementById('cms-speakers-section');
    if (!section) return;
    try {
        const res  = await fetch(BASE + '/api/speakers.php');
        const data = await res.json();
        if (!data || !data.length) { section.style.display = 'none'; return; }
        section.style.display = '';
        const grid = section.querySelector('.speakers-grid');
        if (!grid) return;
        grid.innerHTML = data.map(sp => `
            <div class="speaker-card">
                ${sp.photo ? `<img src="${esc(sp.photo)}" alt="${esc(sp.name)}" loading="lazy">` : `<div class="sp-placeholder">👤</div>`}
                <div class="sp-body">
                    <h3>${esc(sp.name)}</h3>
                    ${sp.title ? `<div class="sp-title">${esc(sp.title)}</div>` : ''}
                    ${sp.affiliation ? `<div class="sp-aff">${esc(sp.affiliation)}</div>` : ''}
                </div>
            </div>`).join('');
    } catch(e) { /* fail silently */ }
}


/* ── Sponsors ────────────────────────────────────────────────── */
async function loadSponsors() {
    const section = document.getElementById('cms-sponsors-section');
    if (!section) return;
    try {
        const res  = await fetch(BASE + '/api/sponsors.php');
        const data = await res.json();
        if (!data || !data.length) { section.style.display = 'none'; return; }
        section.style.display = '';
        const grid = section.querySelector('.sponsors-grid');
        if (!grid) return;
        grid.innerHTML = data.map(sp => {
            const img = sp.logo_url ? `<img src="${esc(sp.logo_url)}" alt="${esc(sp.name)}" loading="lazy" style="height:56px;max-width:160px;object-fit:contain;">` : `<span style="font-weight:700;color:var(--primary);">${esc(sp.name)}</span>`;
            return sp.website_url
                ? `<a href="${esc(sp.website_url)}" target="_blank" rel="noopener" class="sponsor-item">${img}</a>`
                : `<div class="sponsor-item">${img}</div>`;
        }).join('');
    } catch(e) { /* fail silently */ }
}


/* ── Nav Pages (Conference Information dropdown) ─────────────── */
async function loadNavPages() {
    const dropdown = document.querySelector('#confDropdown');
    if (!dropdown) return;
    try {
        const res  = await fetch(BASE + '/api/pages.php');
        const data = await res.json();
        if (!data.ok || !data.data.length) return;
        data.data.forEach(p => {
            const li = document.createElement('li');
            li.innerHTML = `<a href="page.html?slug=${esc(p.slug)}">${esc(p.title)}</a>`;
            dropdown.appendChild(li);
        });
    } catch(e) { /* fail silently */ }
}


/* ── Helper ──────────────────────────────────────────────────── */
function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
