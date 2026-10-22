# Social Studio render worker

Renders approved Social Studio videos (and, as a fallback, image slides) for
app.fairtaxint.com. It runs on the Linux VPS because cPanel shared hosting
cannot run a browser.

How it works: every 15 seconds it asks the app for an approved post that needs
files, opens that post's render page in headless Chrome, steps the animation
frame by frame (30 fps), encodes an MP4 with ffmpeg, and uploads it back in
1 MB parts. A 30-second video takes about 2 minutes.

## Install (Ubuntu/Debian VPS, once)

```bash
# Node 20+, ffmpeg and the libraries headless Chrome needs
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs ffmpeg fonts-noto-core \
  libnss3 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 \
  libxdamage1 libxrandr2 libgbm1 libpango-1.0-0 libasound2

# A user to run it, and the code (copy this render-worker folder from the repo)
sudo useradd --system --create-home --shell /usr/sbin/nologin fti || true
sudo mkdir -p /opt/fti-social-render
sudo cp worker.js package.json fti-social-render.service .env.example /opt/fti-social-render/
cd /opt/fti-social-render
sudo cp .env.example .env
sudo nano .env            # paste RENDER_TOKEN from Social Studio > Settings > Render worker
sudo chown -R fti:fti /opt/fti-social-render
sudo -u fti npm install   # downloads puppeteer's own Chrome (~170 MB)
```

On Ubuntu 24.04 the sound package is `libasound2t64` instead of `libasound2`.

## Test, then run as a service

```bash
sudo -u fti node worker.js --once     # takes one job if there is one, prints what it did
sudo cp fti-social-render.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now fti-social-render
journalctl -u fti-social-render -f     # live log
```

Social Studio > Settings shows "Worker last checked in …" once it is talking
to the app.

## When something goes wrong

| Symptom | Fix |
|---|---|
| `HTTP 401 Bad render token` | The token was changed in Settings. Copy the new one into `.env`, `sudo systemctl restart fti-social-render`. |
| `Failed to launch the browser process` | A Chrome library is missing. Run the `apt-get install` line again; `ldd ~fti/.cache/puppeteer/chrome/*/chrome-linux64/chrome \| grep "not found"` names it. |
| `ffmpeg: not found` | `sudo apt-get install -y ffmpeg` |
| Post shows "Rendering failed" | The reason is on the post. Fix it, then press the re-render button (↻) on the post. |
| Stuck on "rendering" | The hourly `social:run` puts jobs that have not finished in 30 minutes back in the queue. |

Updating: copy the new `worker.js` over the old one and restart the service.
