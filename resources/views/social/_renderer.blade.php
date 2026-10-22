{{-- Renderer assets and a small helper to drop a scaled 1080x1920 stage into a box. --}}
@php $v = fn($f) => asset('social-assets/' . $f) . '?v=' . (@filemtime(public_path('social-assets/' . $f)) ?: 1); @endphp
<link rel="stylesheet" href="{{ $v('renderer.css') }}">
<script src="{{ $v('vendor/modern-screenshot.js') }}"></script>
<script src="{{ $v('renderer.js') }}"></script>
<script>
    window.SOCIAL_BRAND = @json($brand);
    /**
     * Mount a post into `box` (any width; height follows 9:16).
     * Returns the renderer instance; its stage stays 1080x1920 for export.
     */
    window.socialMount = function (box, post, opts) {
        opts = opts || {};
        box.classList.add('sx-frame');
        const inner = document.createElement('div');
        inner.className = 'sx-scale';
        box.appendChild(inner);
        const fit = () => { inner.style.transform = 'scale(' + (box.clientWidth / 1080) + ')'; };
        fit();
        new ResizeObserver(fit).observe(box);
        return SocialRenderer.mount(inner, Object.assign({ post: post, brand: window.SOCIAL_BRAND }, opts));
    };
</script>
<style>
    .sx-frame { position: relative; width: 100%; aspect-ratio: 9 / 16; overflow: hidden; border-radius: 14px; background: #0B2744;
        box-shadow: 0 1px 2px rgba(6,27,49,.12), 0 10px 30px rgba(6,27,49,.12); }
    .sx-scale { position: absolute; top: 0; left: 0; width: 1080px; height: 1920px; transform-origin: 0 0; }
</style>
