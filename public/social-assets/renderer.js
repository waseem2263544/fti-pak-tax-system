/*
 * Social Studio renderer.
 *
 * Draws a Social Studio post as Fair Tax branded 1080x1920 slides, and plays
 * a video post as a timeline of animated scenes. The same code runs in three
 * places, so what is approved is exactly what is exported:
 *   - the review page (live preview and in-browser PNG export),
 *   - the bare render page the VPS worker opens,
 *   - the worker's frame capture, which calls seek(t) for every frame.
 *
 * Animation is a pure function of time t - no CSS transitions, no timers -
 * so a frame captured at t is identical however long the capture takes.
 *
 *   const r = SocialRenderer.mount(hostEl, { post, brand, mode: 'video' });
 *   await r.ready;  r.seek(2.5);  r.duration;
 *   const r = SocialRenderer.mount(hostEl, { post, brand, mode: 'static', only: 0 });
 */
(function (global) {
    'use strict';

    // ------------------------------------------------------------ easing
    const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
    const E = {
        outExpo: x => (x >= 1 ? 1 : 1 - Math.pow(2, -10 * x)),
        outCubic: x => 1 - Math.pow(1 - x, 3),
        inOutCubic: x => (x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2),
        // damped spring with a little overshoot - the "slam"
        spring: x => (x >= 1 ? 1 : 1 - Math.exp(-6.5 * x) * Math.cos(9.5 * x)),
    };
    const TRANSITION = 0.35; // seconds a new scene takes to fade in over the old one

    // ------------------------------------------------------------ icons
    const ICON = {
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7"/></svg>',
        cross: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
        warn: '<svg viewBox="0 0 48 48"><path d="M24 4L45 41H3z" fill="#F0484D"/><path d="M24 17v12" stroke="#fff" stroke-width="4.5" stroke-linecap="round"/><circle cx="24" cy="35" r="2.8" fill="#fff"/></svg>',
        arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>',
        wa: '<svg viewBox="0 0 32 32" fill="currentColor"><path d="M16 3C8.8 3 3 8.7 3 15.8c0 2.5.7 4.9 2 7L3 29l6.4-2c2 1.1 4.3 1.7 6.6 1.7 7.2 0 13-5.7 13-12.8S23.2 3 16 3zm0 23.4c-2.1 0-4.1-.6-5.9-1.6l-.4-.3-3.8 1.2 1.2-3.7-.3-.4c-1.2-1.8-1.8-3.8-1.8-5.9C5 9.8 10 5.1 16 5.1S27 9.8 27 15.8 22 26.4 16 26.4zm6-7.9c-.3-.2-2-1-2.3-1.1-.3-.1-.5-.2-.8.2-.2.3-.9 1.1-1.1 1.3-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.7-1.7-1-.9-1.7-2-1.9-2.3-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.4.3-.6.1-.2 0-.4 0-.6l-1.1-2.6c-.3-.7-.6-.6-.8-.6h-.7c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.4 1.4 3.6c.2.2 2.4 3.7 5.9 5.1 2.9 1.1 3.5.9 4.1.9.6-.1 2-.8 2.3-1.6.3-.8.3-1.5.2-1.6-.1-.1-.3-.2-.6-.4z"/></svg>',
    };

    const DEFAULT_ROOM = { cover: 'navy', point: 'paper', list: 'paper', steps: 'paper', stat: 'blue', warning: 'navy', cta: 'navy' };

    // ------------------------------------------------------------ dom helpers
    function el(tag, cls, parent) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (cls && /(^|\s)ft-u(\s|$)/.test(cls)) n.setAttribute('dir', 'rtl');
        if (parent) parent.appendChild(n);
        return n;
    }

    /** Text with **emphasis** marks; everything else is plain text, never HTML. */
    function rich(node, text) {
        String(text || '').split('**').forEach((part, i) => {
            if (!part) return;
            if (i % 2) el('em', null, node).textContent = part;
            else node.appendChild(document.createTextNode(part));
        });
        return node;
    }

    function anim(node, type, delay, dur) {
        node.dataset.a = type;
        node.dataset.d = delay;
        node.dataset.u = dur;
        return node;
    }

    // ------------------------------------------------------------ slide builders
    function buildScene(slide, i, ctx) {
        const layout = slide.layout || 'point';
        const room = slide.room || DEFAULT_ROOM[layout] || 'paper';
        const scene = el('div', 'ft-scene ft-room-' + room + ' ft-l-' + layout);
        if (layout === 'cta') scene.classList.add('ft-cta');

        // backdrop
        const orbA = el('div', 'ft-orb', scene);
        Object.assign(orbA.style, { width: '900px', height: '900px', top: '-260px', right: '-320px' });
        const orbB = el('div', 'ft-orb', scene);
        Object.assign(orbB.style, { width: '700px', height: '700px', bottom: '-200px', left: '-300px' });
        anim(orbA, 'drift', 0, slide.duration || 3);
        if (slide.ghost && (layout === 'cover' || layout === 'stat' || layout === 'warning')) {
            const g = el('div', 'ft-ghost', scene);
            g.textContent = slide.ghost;
            anim(g, 'drift', 0, slide.duration || 3);
        }
        el('div', 'ft-grain', scene);

        // chrome
        const head = el('div', 'ft-head', scene);
        if (layout !== 'cta') {
            const logo = el('img', 'ft-logo', head);
            logo.src = room === 'paper' ? ctx.brand.logoOnLight
                : room === 'blue' ? (ctx.brand.logoOnBlue || ctx.brand.logoOnDark) : ctx.brand.logoOnDark;
            logo.alt = '';
        } else {
            el('span', null, head);
        }
        if (ctx.format === 'carousel') {
            el('div', 'ft-count', head).textContent = String(i + 1).padStart(2, '0') + ' / ' + String(ctx.count).padStart(2, '0');
        }
        if (ctx.mode === 'video') {
            const bar = el('div', 'ft-progress', scene);
            el('i', null, bar);
            scene._progress = bar.firstChild;
        }

        const body = el('div', 'ft-body', scene);
        body.setAttribute('dir', 'rtl'); // an attribute survives export cloning; CSS direction alone does not
        const builders = { cover, point, list: listSlide, steps: listSlide, stat, warning, cta };
        (builders[layout] || point)(body, slide, ctx, scene);

        if (layout !== 'cta') {
            const foot = el('div', 'ft-foot', scene);
            const ico = el('span', null, foot);
            ico.innerHTML = ICON.wa;
            ico.firstChild.setAttribute('class', 'ft-wa-ico');
            el('span', null, foot).textContent = ctx.brand.whatsapp;
            el('span', 'dot', foot);
            el('span', null, foot).textContent = ctx.brand.website;
            anim(foot, 'fade', 0.9, 0.6);
        }

        if (ctx.format === 'carousel' && i === 0 && ctx.count > 1) {
            const sw = el('div', 'ft-swipe ft-u', scene);
            el('span', null, sw).textContent = 'آگے دیکھیں';
            const a = el('span', null, sw);
            a.innerHTML = ICON.arrow;
        }
        return scene;
    }

    function kicker(body, slide, extra) {
        if (!slide.kicker) return;
        anim(rich(el('div', 'ft-kicker ft-u' + (extra ? ' ' + extra : ''), body), slide.kicker), 'pop', 0.1, 0.5);
    }

    function heading(body, slide, size, delay) {
        if (!slide.heading) return null;
        return anim(rich(el('h2', 'ft-h ft-u ' + (size || ''), body), slide.heading), 'rise', delay == null ? 0.2 : delay, 0.8);
    }

    function para(body, text, delay) {
        if (!text) return;
        anim(rich(el('p', 'ft-p ft-u', body), text), 'rise', delay, 0.7);
    }

    function cover(body, slide) {
        kicker(body, slide);
        heading(body, slide, 'xl', 0.25);
        anim(el('div', 'ft-bar', body), 'grow', 0.6, 0.7);
        para(body, slide.body, 0.75);
    }

    function point(body, slide) {
        kicker(body, slide);
        heading(body, slide, '', 0.15);
        anim(el('div', 'ft-bar', body), 'grow', 0.5, 0.6);
        para(body, slide.body, 0.6);
    }

    function listSlide(body, slide, ctx) {
        kicker(body, slide);
        heading(body, slide, 'md', 0.15);
        const steps = slide.layout === 'steps';
        const list = el('ul', 'ft-list' + (steps ? ' ft-steps' : ''), body);
        (slide.points || []).forEach((p, n) => {
            const li = el('li', 'ft-li ft-u', list);
            const mark = el('span', 'mark', li);
            if (steps) mark.textContent = String(n + 1);
            else mark.innerHTML = ICON.check;
            rich(el('span', 'txt', li), p).setAttribute('dir', 'rtl');
            anim(li, 'slide', 0.55 + n * 0.32, 0.6);
        });
        para(body, slide.body, 0.55 + (slide.points || []).length * 0.32);
    }

    function stat(body, slide) {
        kicker(body, slide);
        if (slide.stat && slide.stat.value) {
            const v = el('div', 'ft-stat', body);
            v.textContent = slide.stat.value;
            anim(v, 'count', 0.15, 1.1);
            if (slide.stat.label) anim(rich(el('p', 'ft-stat-label ft-u', body), slide.stat.label), 'rise', 0.55, 0.7);
        }
        if (slide.heading && slide.heading !== (slide.stat && slide.stat.label)) heading(body, slide, 'md', 0.7);
        para(body, slide.body, 0.85);
    }

    function warning(body, slide) {
        const ico = el('div', 'ft-warn-ico', body);
        ico.innerHTML = ICON.warn;
        anim(ico, 'slam', 0.05, 0.9);
        kicker(body, slide, 'warn');
        heading(body, slide, 'md', 0.25);
        anim(el('div', 'ft-bar warn', body), 'grow', 0.55, 0.6);
        if (slide.points && slide.points.length) {
            const list = el('ul', 'ft-list', body);
            slide.points.forEach((p, n) => {
                const li = el('li', 'ft-li ft-u warn', list);
                el('span', 'mark', li).innerHTML = ICON.cross;
                rich(el('span', 'txt', li), p).setAttribute('dir', 'rtl');
                anim(li, 'slide', 0.65 + n * 0.3, 0.6);
            });
        }
        para(body, slide.body, 0.7 + (slide.points || []).length * 0.3);
    }

    function cta(body, slide, ctx) {
        const logo = el('img', 'ft-cta-logo', body);
        logo.src = ctx.brand.logoOnDark;
        logo.alt = '';
        anim(logo, 'slam', 0.05, 1.0);
        const h = heading(body, slide, 'md', 0.4);
        if (h) h.style.textAlign = 'center';
        para(body, slide.body, 0.65);
        const btn = el('div', 'ft-wa-btn', body);
        btn.innerHTML = ICON.wa;
        el('span', null, btn).textContent = ctx.brand.whatsapp;
        anim(btn, 'pulse', 0.85, 0.7);
        const site = el('div', 'ft-site', body);
        site.textContent = ctx.brand.website;
        anim(site, 'fade', 1.1, 0.6);
        if (ctx.brand.tagline) anim(Object.assign(el('div', 'ft-tagline', body), { textContent: ctx.brand.tagline }), 'fade', 1.3, 0.6);
    }

    // ------------------------------------------------------------ animation
    function countText(text, k) {
        const m = String(text).match(/\d[\d,]*(\.\d+)?/);
        if (!m) return text;
        const raw = m[0].replace(/,/g, '');
        const decimals = (raw.split('.')[1] || '').length;
        const target = parseFloat(raw);
        let v = (target * k).toFixed(decimals);
        if (m[0].includes(',')) v = Number(v).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        return text.slice(0, m.index) + v + text.slice(m.index + m[0].length);
    }

    /** Put one element into its state at local time lt (Infinity = settled). */
    function applyAnim(node, lt) {
        const type = node.dataset.a;
        const d = parseFloat(node.dataset.d) || 0;
        const u = parseFloat(node.dataset.u) || 0.6;
        const p = lt === Infinity ? 1 : clamp((lt - d) / u);
        const s = node.style;
        switch (type) {
            case 'rise': {
                const k = E.outExpo(p);
                s.opacity = clamp(p * 2.2);
                s.transform = 'translateY(' + (1 - k) * 70 + 'px)';
                break;
            }
            case 'slide': { // list rows come in from the reading side (right)
                const k = E.outExpo(p);
                s.opacity = clamp(p * 2);
                s.transform = 'translateX(' + (1 - k) * 90 + 'px)';
                break;
            }
            case 'fade':
                s.opacity = E.outCubic(p);
                break;
            case 'pop': {
                const k = E.spring(p);
                s.opacity = clamp(p * 3);
                s.transform = 'scale(' + (0.6 + 0.4 * k) + ')';
                s.transformOrigin = 'right center';
                break;
            }
            case 'slam': {
                const k = E.spring(p);
                s.opacity = clamp(p * 3);
                s.transform = 'scale(' + (1.6 - 0.6 * k) + ')';
                break;
            }
            case 'grow':
                s.transform = 'scaleX(' + E.outExpo(p) + ')';
                break;
            case 'count': {
                if (node._full == null) node._full = node.textContent;
                const k = E.spring(p);
                s.opacity = clamp(p * 3);
                s.transform = 'scale(' + (1.25 - 0.25 * k) + ')';
                s.transformOrigin = 'right bottom';
                node.textContent = lt === Infinity ? node._full : countText(node._full, E.outExpo(clamp(p * 1.15)));
                break;
            }
            case 'pulse': {
                const k = E.spring(p);
                const beat = lt === Infinity ? 0 : Math.max(0, Math.sin((lt - d - u) * Math.PI * 1.6)) * (p >= 1 ? 1 : 0);
                s.opacity = clamp(p * 3);
                s.transform = 'scale(' + ((0.7 + 0.3 * k) * (1 + 0.035 * beat)) + ')';
                break;
            }
            case 'drift': {
                const k = lt === Infinity ? 1 : clamp(lt / u);
                s.transform = 'translateX(' + (k - 1) * 60 + 'px)';
                break;
            }
        }
    }

    // ------------------------------------------------------------ fitting
    function fitScene(scene) {
        const body = scene.querySelector('.ft-body');
        if (!body) return;
        let fit = 1;
        scene.style.setProperty('--fit', fit);
        while (body.scrollHeight > body.clientHeight + 1 && fit > 0.5) {
            fit -= 0.03;
            scene.style.setProperty('--fit', fit.toFixed(3));
        }
        // the big number must fit on one line
        scene.querySelectorAll('.ft-stat').forEach(n => {
            let size = parseFloat(getComputedStyle(n).fontSize);
            while (n.scrollWidth > n.clientWidth + 1 && size > 90) {
                size -= 6;
                n.style.fontSize = size + 'px';
            }
        });
    }

    function loadFonts(stage) {
        const probes = ['700 40px "FT Geist"', '400 40px "FT Geist"', '700 40px ' + stage._urdu, '400 40px ' + stage._urdu];
        const loads = probes.map(f => document.fonts.load(f, 'ابپ 0').catch(() => null));
        const imgs = Array.from(stage.querySelectorAll('img')).map(img => img.complete ? null : new Promise(r => { img.onload = img.onerror = r; }));
        return Promise.all(loads.concat(imgs)).then(() => document.fonts.ready);
    }

    // ------------------------------------------------------------ mount
    function mount(host, opts) {
        const post = opts.post || {};
        const brand = opts.brand || {};
        const mode = opts.mode || (post.format === 'video' ? 'video' : 'static');
        const slides = post.slides || [];
        const indices = opts.only != null ? [opts.only] : slides.map((_, i) => i);

        const stage = el('div', 'ft-stage');
        if (brand.urduFont === 'naskh') stage.classList.add('urdu-naskh');
        stage._urdu = brand.urduFont === 'naskh' ? '"FT Naskh"' : '"FT Nastaliq"';
        const c = brand.colors || {};
        Object.keys(c).forEach(k => stage.style.setProperty('--' + k, c[k]));
        host.appendChild(stage);

        const ctx = { brand, mode, format: post.format, count: slides.length };
        const scenes = indices.map(i => {
            const sc = buildScene(slides[i], i, ctx);
            sc._duration = Math.max(1, parseFloat(slides[i].duration) || 3);
            stage.appendChild(sc);
            return sc;
        });

        let t0 = 0;
        scenes.forEach(sc => { sc._start = t0; t0 += sc._duration; });
        const duration = t0;

        const api = {
            stage, scenes, duration,
            ready: null,
            /** Static frame of one slide, every element settled. */
            show(n) {
                scenes.forEach((sc, i) => {
                    sc.style.visibility = i === n ? 'visible' : 'hidden';
                    sc.style.opacity = 1;
                    sc.style.transform = 'none';
                    sc.querySelectorAll('[data-a]').forEach(nd => applyAnim(nd, Infinity));
                });
            },
            /** Video frame at time t (seconds). */
            seek(t) {
                scenes.forEach((sc, i) => {
                    const lt = t - sc._start;
                    const visible = (lt >= (i === 0 ? 0 : -TRANSITION) && lt < sc._duration) || (i === scenes.length - 1 && lt >= sc._duration);
                    sc.style.visibility = visible ? 'visible' : 'hidden';
                    if (!visible) return;
                    if (lt < 0) { // fading in over the previous scene
                        const k = E.outCubic(clamp(1 + lt / TRANSITION));
                        sc.style.opacity = k;
                        sc.style.transform = 'translateY(' + (1 - k) * 80 + 'px)';
                    } else {
                        sc.style.opacity = 1;
                        sc.style.transform = 'none';
                    }
                    sc.style.zIndex = i;
                    sc.querySelectorAll('[data-a]').forEach(nd => applyAnim(nd, Math.max(0, lt)));
                    if (sc._progress) sc._progress.style.width = (clamp(t / duration) * 100).toFixed(2) + '%';
                });
            },
        };

        api.ready = loadFonts(stage).then(() => {
            scenes.forEach(sc => {
                sc.style.visibility = 'visible';
                sc.querySelectorAll('[data-a]').forEach(nd => applyAnim(nd, Infinity));
                fitScene(sc);
            });
            if (mode === 'video') api.seek(0); else api.show(0);
            return api;
        });

        return api;
    }

    /*
     * Fonts for export, as data URLs. Built from renderer.css's own font files
     * rather than letting the exporter scan every stylesheet on the page -
     * the app also loads Bootstrap Icons and Plex from CDNs, which would be
     * fetched and embedded into every PNG for nothing.
     */
    let embedCss = null;
    function fontEmbedCss() {
        if (embedCss) return embedCss;
        const link = document.querySelector('link[href*="renderer.css"]');
        const base = link ? new URL('.', link.href).href : new URL('social-assets/', location.href).href;
        const faces = [
            ['FT Geist', 'fonts/Geist-Variable.woff2', '100 900'],
            ['FT Nastaliq', 'fonts/noto-nastaliq-urdu-arabic-400-normal.woff2', '400'],
            ['FT Nastaliq', 'fonts/noto-nastaliq-urdu-arabic-700-normal.woff2', '700'],
            ['FT Naskh', 'fonts/noto-naskh-arabic-arabic-400-normal.woff2', '400'],
            ['FT Naskh', 'fonts/noto-naskh-arabic-arabic-700-normal.woff2', '700'],
        ];
        const toData = url => fetch(url).then(r => r.blob()).then(b => new Promise(res => {
            const fr = new FileReader(); fr.onload = () => res(fr.result); fr.readAsDataURL(b);
        }));
        embedCss = Promise.all(faces.map(([fam, file, w]) => toData(base + file).then(d =>
            "@font-face{font-family:'" + fam + "';src:url(" + d + ") format('woff2');font-weight:" + w + ";}"
        ))).then(parts => parts.join('\n'));
        return embedCss;
    }

    /**
     * PNG data URL of whatever the stage currently shows.
     * Uses modern-screenshot (vendor/modern-screenshot.js). html-to-image was
     * tried first and dropped: it cloned flex items at width 0, which pushed
     * the Urdu list text to the wrong edge in exported files.
     */
    function toPng(stage) {
        return fontEmbedCss().then(css => global.modernScreenshot.domToPng(stage, {
            width: 1080, height: 1920, scale: 1, font: { cssText: css },
        }));
    }

    global.SocialRenderer = { mount, toPng, E };
})(window);
