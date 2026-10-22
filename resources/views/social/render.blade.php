<!doctype html>
{{-- Bare render page for the VPS worker: one 1080x1920 stage, nothing else. --}}
<html lang="ur">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>Render #{{ $post->id }}</title>
    <link rel="stylesheet" href="{{ asset('social-assets/renderer.css') }}?v={{ @filemtime(public_path('social-assets/renderer.css')) }}">
    <style>html, body { margin: 0; padding: 0; background: #000; } #host { width: 1080px; height: 1920px; }</style>
</head>
<body>
<div id="host"></div>
<script src="{{ asset('social-assets/renderer.js') }}?v={{ @filemtime(public_path('social-assets/renderer.js')) }}"></script>
<script>
    // The worker waits for window.__social.ready, then calls seek(t) per frame
    // (video) or show(i) per slide (stills) and screenshots the page.
    window.__social = SocialRenderer.mount(document.getElementById('host'), {
        post: @json($post->renderPayload()),
        brand: @json($brand),
        mode: @json($post->isVideo() ? 'video' : 'static'),
    });
    window.__social.ready.then(() => { window.__socialReady = true; });
</script>
</body>
</html>
