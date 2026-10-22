#!/usr/bin/env node
/*
 * Social Studio render worker.
 *
 * Runs on the Linux VPS (shared hosting cannot run a browser). Every few
 * seconds it asks app.fairtaxint.com for an approved post that needs files,
 * opens that post's render page in headless Chromium and:
 *   - video:  steps the animation frame by frame (seek(t)), pipes the frames
 *             to ffmpeg, adds the music track or silence, uploads the MP4;
 *   - stills: screenshots each slide and uploads the PNGs (only for images
 *             and carousels the reviewer's browser did not export itself).
 *
 * Config (environment or .env next to this file):
 *   APP_URL        https://app.fairtaxint.com
 *   RENDER_TOKEN   from Social Studio > Settings > Render worker
 *   CHROME_PATH    optional; defaults to the Chrome that puppeteer downloads
 *   FPS            optional, default 30
 *   POLL_SECONDS   optional, default 15
 *
 *   node worker.js           run forever
 *   node worker.js --once    take one job (or none) and exit
 */
'use strict';

const fs = require('fs');
const os = require('os');
const path = require('path');
const { spawn } = require('child_process');
const puppeteer = require('puppeteer');

loadDotEnv(path.join(__dirname, '.env'));
const APP_URL = (process.env.APP_URL || '').replace(/\/+$/, '');
const TOKEN = process.env.RENDER_TOKEN || '';
const FPS = parseInt(process.env.FPS || '30', 10);
const POLL = parseInt(process.env.POLL_SECONDS || '15', 10) * 1000;
const ONCE = process.argv.includes('--once');

if (!APP_URL || !TOKEN) {
    console.error('Set APP_URL and RENDER_TOKEN (see README.md).');
    process.exit(1);
}

let browser = null;

async function main() {
    log(`worker started - ${APP_URL}, ${FPS} fps`);
    for (;;) {
        let job = null;
        try {
            job = await nextJob();
            if (job) await runJob(job);
        } catch (err) {
            log('error: ' + (err.stack || err.message));
            if (job) await reportFailure(job, err).catch(() => {});
        }
        if (ONCE) break;
        if (!job) await sleep(POLL);
    }
    if (browser) await browser.close();
}

// ------------------------------------------------------------------ api

async function api(method, route, body) {
    const res = await fetch(APP_URL + '/api/social/' + route, {
        method,
        headers: { Authorization: 'Bearer ' + TOKEN, Accept: 'application/json' },
        body,
    });
    const text = await res.text();
    if (!res.ok) throw new Error(`${method} ${route} -> HTTP ${res.status}: ${text.slice(0, 300)}`);
    return text ? JSON.parse(text) : {};
}

async function nextJob() {
    const { job } = await api('GET', 'render-jobs/next');
    return job || null;
}

async function reportFailure(job, err) {
    const form = new FormData();
    form.append('error', String(err.message || err).slice(0, 1900));
    await api('POST', `render-jobs/${job.id}/fail`, form);
}

// ------------------------------------------------------------------ rendering

async function getBrowser() {
    if (browser && browser.connected) return browser;
    browser = await puppeteer.launch({
        headless: true,
        executablePath: process.env.CHROME_PATH || undefined,
        args: ['--no-sandbox', '--disable-dev-shm-usage', '--font-render-hinting=none', '--hide-scrollbars', '--force-color-profile=srgb'],
    });
    return browser;
}

async function openRenderPage(job) {
    const b = await getBrowser();
    const page = await b.newPage();
    await page.setViewport({ width: 1080, height: 1920, deviceScaleFactor: 1 });
    page.on('pageerror', e => log('page error: ' + e.message));
    await page.goto(job.render_url, { waitUntil: 'networkidle0', timeout: 60000 });
    await page.waitForFunction('window.__socialReady === true', { timeout: 60000 });
    return page;
}

async function runJob(job) {
    log(`job #${job.id} (${job.kind}, ${job.format}, rev ${job.revision})`);
    const started = Date.now();
    const page = await openRenderPage(job);
    try {
        if (job.kind === 'video') await renderVideo(job, page);
        else await renderStills(job, page);
    } finally {
        await page.close().catch(() => {});
    }
    log(`job #${job.id} done in ${((Date.now() - started) / 1000).toFixed(0)}s`);
}

async function renderStills(job, page) {
    // One request per slide: shared hosts cap uploads at a few MB.
    for (let i = 0; i < job.slides; i++) {
        await page.evaluate(n => window.__social.show(n), i);
        const png = await page.screenshot({ type: 'png', clip: { x: 0, y: 0, width: 1080, height: 1920 } });
        const form = new FormData();
        form.append('revision', String(job.revision));
        form.append('index', String(i));
        form.append('total', String(job.slides));
        form.append('image', new Blob([png], { type: 'image/png' }), `slide-${i + 1}.png`);
        const res = await api('POST', `render-jobs/${job.id}/complete`, form);
        if (res.stale) { log(`job #${job.id}: post changed while rendering; result discarded`); return; }
    }
}

async function renderVideo(job, page) {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), `social-${job.id}-`));
    try {
        const duration = await page.evaluate(() => window.__social.duration);
        const frames = Math.ceil(duration * FPS);
        const silent = path.join(dir, 'silent.mp4');

        // 1. frames -> H.264, streamed straight into ffmpeg
        const ff = spawn('ffmpeg', ['-y', '-loglevel', 'error',
            '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'mjpeg', '-i', '-',
            '-c:v', 'libx264', '-preset', 'medium', '-crf', '18', '-pix_fmt', 'yuv420p',
            '-r', String(FPS), '-movflags', '+faststart', silent]);
        let ffErr = '';
        ff.stderr.on('data', d => { ffErr += d; });
        const ffDone = new Promise((resolve, reject) => ff.on('close', c => c === 0 ? resolve() : reject(new Error('ffmpeg: ' + ffErr.slice(-500)))));

        for (let f = 0; f < frames; f++) {
            await page.evaluate(t => window.__social.seek(t), f / FPS);
            const jpg = await page.screenshot({ type: 'jpeg', quality: 92, clip: { x: 0, y: 0, width: 1080, height: 1920 } });
            if (!ff.stdin.write(jpg)) await new Promise(r => ff.stdin.once('drain', r));
            if (f % (FPS * 5) === 0) log(`  frame ${f}/${frames}`);
        }
        ff.stdin.end();
        await ffDone;

        // 2. sound: the brand track faded out, or silence (some apps dislike no audio stream)
        const out = path.join(dir, 'final.mp4');
        let audioIn;
        if (job.music_url) {
            const music = path.join(dir, 'music');
            const res = await fetch(job.music_url, { headers: { Authorization: 'Bearer ' + TOKEN } });
            if (!res.ok) throw new Error('music download HTTP ' + res.status);
            fs.writeFileSync(music, Buffer.from(await res.arrayBuffer()));
            audioIn = ['-stream_loop', '-1', '-i', music];
        } else {
            audioIn = ['-f', 'lavfi', '-i', 'anullsrc=channel_layout=stereo:sample_rate=44100'];
        }
        const fadeStart = Math.max(0, duration - 1.5).toFixed(2);
        await run('ffmpeg', ['-y', '-loglevel', 'error', '-i', silent, ...audioIn,
            '-map', '0:v', '-map', '1:a', '-c:v', 'copy',
            '-af', `afade=t=in:d=0.3,afade=t=out:st=${fadeStart}:d=1.5`, '-c:a', 'aac', '-b:a', '160k',
            '-t', duration.toFixed(2), '-movflags', '+faststart', out]);

        // 3. upload in 1 MB chunks - shared hosts cap a single upload at a few MB
        const data = fs.readFileSync(out);
        const CHUNK = 1024 * 1024;
        const chunks = Math.ceil(data.length / CHUNK);
        log(`  uploading ${(data.length / 1048576).toFixed(1)} MB in ${chunks} part(s)`);
        for (let c = 0; c < chunks; c++) {
            const form = new FormData();
            form.append('revision', String(job.revision));
            form.append('chunk', String(c));
            form.append('chunks', String(chunks));
            form.append('part', new Blob([data.subarray(c * CHUNK, (c + 1) * CHUNK)], { type: 'application/octet-stream' }), `part-${c}`);
            const res = await api('POST', `render-jobs/${job.id}/complete`, form);
            if (res.stale) { log(`job #${job.id}: post changed while rendering; result discarded`); return; }
        }
    } finally {
        fs.rmSync(dir, { recursive: true, force: true });
    }
}

// ------------------------------------------------------------------ helpers

function run(cmd, args) {
    return new Promise((resolve, reject) => {
        const p = spawn(cmd, args);
        let err = '';
        p.stderr.on('data', d => { err += d; });
        p.on('close', c => c === 0 ? resolve() : reject(new Error(`${cmd} exited ${c}: ${err.slice(-500)}`)));
    });
}

function loadDotEnv(file) {
    if (!fs.existsSync(file)) return;
    for (const line of fs.readFileSync(file, 'utf8').split('\n')) {
        const m = line.match(/^\s*([A-Z_]+)\s*=\s*(.*?)\s*$/);
        if (m && !(m[1] in process.env)) process.env[m[1]] = m[2].replace(/^["']|["']$/g, '');
    }
}

const sleep = ms => new Promise(r => setTimeout(r, ms));
const log = msg => console.log(new Date().toISOString() + ' ' + msg);

process.on('SIGTERM', async () => { if (browser) await browser.close().catch(() => {}); process.exit(0); });
main();
