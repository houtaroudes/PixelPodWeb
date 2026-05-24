<?php
$pageTitle = 'PixelPod Photobooth';
require_once __DIR__ . '/../../includes/header.php';
$pdo     = getDB();
$filters = $pdo->query("SELECT * FROM filters WHERE is_active=1 ORDER BY sort_order")->fetchAll();
?>
<style>
    /* Photobooth Page Styles */
    .pb-hero {
        background: linear-gradient(135deg, #1a0505 0%, var(--maroon-deep) 50%, #2d0a0a 100%);
        padding: 90px 0 50px;
        text-align: center;
    }

    .pb-hero h1 {
        font-family: var(--font-display);
        font-size: clamp(2.2rem, 5vw, 3.8rem);
        color: #fff;
        margin-bottom: 12px;
    }

    .pb-hero h1 em {
        color: var(--gold-light);
        font-style: italic;
    }

    .pb-hero p {
        color: rgba(255, 255, 255, .65);
        font-size: 1rem;
        max-width: 500px;
        margin: 0 auto 30px;
    }

    /* Setup screen */
    .pb-setup {
        max-width: 700px;
        margin: 0 auto;
        padding: 50px 24px;
    }

    .pb-setup h2 {
        font-family: var(--font-display);
        font-size: 1.9rem;
        color: var(--maroon-deep);
        text-align: center;
        margin-bottom: 32px;
    }

    .layout-choices {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 32px;
    }

    .layout-choice {
        border: 3px solid var(--border);
        border-radius: 16px;
        padding: 28px 20px;
        text-align: center;
        cursor: pointer;
        transition: all .25s;
        background: #fff;
    }

    .layout-choice:hover {
        border-color: var(--maroon);
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(107, 15, 15, .12);
    }

    .layout-choice.active {
        border-color: var(--maroon);
        background: rgba(107, 15, 15, .04);
    }

    .layout-choice .icon {
        font-size: 3.5rem;
        margin-bottom: 12px;
    }

    .layout-choice h3 {
        font-family: var(--font-display);
        font-size: 1.3rem;
        color: var(--maroon-deep);
        margin-bottom: 6px;
    }

    .layout-choice p {
        font-size: .82rem;
        color: var(--text-light);
        margin: 0;
    }

    .layout-choice .shots-badge {
        display: inline-block;
        background: var(--maroon);
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        padding: 3px 12px;
        border-radius: 100px;
        margin-top: 8px;
    }

    .filter-row {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 28px;
        scrollbar-width: thin;
    }

    .filter-pill {
        flex-shrink: 0;
        padding: 8px 18px;
        border-radius: 100px;
        border: 2px solid var(--border);
        background: #fff;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s;
        color: var(--text-mid);
        white-space: nowrap;
    }

    .filter-pill:hover {
        border-color: var(--maroon);
        color: var(--maroon);
    }

    .filter-pill.active {
        background: var(--maroon);
        border-color: var(--maroon);
        color: #fff;
    }

    /* Camera screen */
    .pb-camera-wrap {
        display: none;
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 24px;
    }

    .pb-camera-inner {
        display: grid;
        grid-template-columns: 1fr 200px;
        gap: 24px;
        align-items: start;
    }

    @media(max-width:700px) {
        .pb-camera-inner {
            grid-template-columns: 1fr;
        }
    }

    /* Video preview */
    .video-container {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        background: #000;
        aspect-ratio: 4/3;
        box-shadow: 0 12px 40px rgba(0, 0, 0, .3);
    }

    #pbVideo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transform: scaleX(-1);
        /* mirror */
    }

    /* Filter overlays via CSS */
    #pbVideo.filter-bw {
        filter: grayscale(100%);
    }

    #pbVideo.filter-vintage {
        filter: sepia(60%) contrast(90%) brightness(110%);
    }

    #pbVideo.filter-glam {
        filter: brightness(115%) contrast(105%) saturate(120%);
    }

    #pbVideo.filter-film {
        filter: grayscale(30%) contrast(110%) brightness(95%);
    }

    #pbVideo.filter-vivid {
        filter: saturate(200%) contrast(110%);
    }

    /* Countdown overlay */
    .countdown-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, .5);
        z-index: 10;
        display: none;
    }

    .countdown-num {
        font-family: var(--font-display);
        font-size: 9rem;
        font-weight: 900;
        color: #fff;
        text-shadow: 0 4px 20px rgba(0, 0, 0, .5);
        animation: popIn .8s ease;
        line-height: 1;
    }

    @keyframes popIn {
        0% {
            transform: scale(2);
            opacity: 0;
        }

        50% {
            transform: scale(1.1);
            opacity: 1;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Flash animation when photo is taken */
    .flash-anim {
        position: absolute;
        inset: 0;
        background: #fff;
        z-index: 20;
        opacity: 0;
        pointer-events: none;
        transition: opacity .1s;
    }

    .flash-anim.go {
        opacity: 1;
    }

    /* Shot progress */
    .shot-progress {
        background: rgba(107, 15, 15, .06);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
        border: 1px solid rgba(107, 15, 15, .1);
    }

    .shot-progress h4 {
        font-size: .85rem;
        font-weight: 700;
        color: var(--maroon-deep);
        margin-bottom: 10px;
    }

    .shot-dots {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .shot-dot {
        width: 44px;
        height: 60px;
        border-radius: 6px;
        border: 2px dashed var(--border);
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: all .3s;
        overflow: hidden;
    }

    .shot-dot.taken {
        border: 2px solid var(--maroon);
        background: var(--maroon);
    }

    .shot-dot.taken img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .shot-dot.current {
        border: 2px solid var(--gold);
        animation: pulse 1s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(212, 170, 98, .4);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(212, 170, 98, 0);
        }
    }

    /* Camera controls */
    .cam-controls {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .btn-capture {
        width: 100%;
        padding: 16px;
        border-radius: 12px;
        background: var(--maroon);
        color: #fff;
        border: none;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-capture:hover:not(:disabled) {
        background: var(--maroon-dark);
        transform: translateY(-2px);
    }

    .btn-capture:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .btn-restart {
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        background: none;
        color: var(--text-mid);
        border: 2px solid var(--border);
        font-size: .88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-restart:hover {
        border-color: var(--maroon);
        color: var(--maroon);
    }

    /* Result screen */
    .pb-result-wrap {
        display: none;
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 24px;
        text-align: center;
    }

    .result-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        align-items: start;
        margin-bottom: 32px;
        text-align: left;
    }

    @media(max-width:700px) {
        .result-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Strip preview */
    .strip-preview {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .15);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .strip-preview img {
        width: 100%;
        display: block;
        border-bottom: 3px solid #f5f5f5;
    }

    .strip-preview img:last-child {
        border-bottom: none;
    }

    /* Grid preview */
    .grid-preview {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .15);
        overflow: hidden;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3px;
        background: #eee;
    }

    .grid-preview img {
        width: 100%;
        display: block;
        aspect-ratio: 4/3;
        object-fit: cover;
    }

    /* QR section */
    .qr-box {
        background: #fff;
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        text-align: center;
    }

    .qr-box img {
        width: 180px;
        height: 180px;
        margin: 0 auto 16px;
        display: block;
        border: 4px solid #f5f5f5;
        border-radius: 12px;
    }

    .qr-code-text {
        font-family: monospace;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--maroon);
        background: rgba(107, 15, 15, .06);
        padding: 8px 16px;
        border-radius: 8px;
        display: inline-block;
        margin-bottom: 12px;
    }

    .result-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Loading spinner */
    .pb-loading {
        display: none;
        text-align: center;
        padding: 60px 24px;
    }

    .spinner {
        width: 56px;
        height: 56px;
        border: 5px solid rgba(107, 15, 15, .1);
        border-top-color: var(--maroon);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<!-- Hero -->
<div class="pb-hero">
    <div style="max-width:600px;margin:0 auto;padding:0 24px">
        <div style="font-size:.72rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--gold-light);margin-bottom:12px">PixelPod Virtual Booth</div>
        <h1>Strike a Pose, <em>Capture the Moment</em></h1>
        <p>Take fun photobooth pictures right here in your browser — no app needed! Get your photos instantly with a QR code.</p>
    </div>
</div>

<div style="background:var(--bg);min-height:70vh">

    <!-- SCREEN 1: SETUP -->
    <div id="screenSetup" class="pb-setup">
        <h2>Choose Your Style</h2>

        <!-- Layout choice -->
        <div style="margin-bottom:10px;font-size:.8rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text-mid)">Layout</div>
        <div class="layout-choices">
            <div class="layout-choice active" data-layout="strip" onclick="selectLayout(this)">
                <div class="icon">🎞️</div>
                <h3>Photo Strip</h3>
                <p>Classic vertical strip — the timeless photobooth look</p>
                <span class="shots-badge">3 shots</span>
            </div>
            <div class="layout-choice" data-layout="grid" onclick="selectLayout(this)">
                <div class="icon">⊞</div>
                <h3>Photo Grid</h3>
                <p>Fun 2×2 grid — great for groups and parties!</p>
                <span class="shots-badge">4 shots</span>
            </div>
        </div>

        <!-- Filter choice -->
        <div style="margin-bottom:12px;font-size:.8rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text-mid)"> Filter</div>
        <div class="filter-row">
            <div class="filter-pill active" data-filter="natural" onclick="selectFilter(this)">Natural</div>
            <?php foreach ($filters as $f): ?>
                <?php if (strtolower($f['name']) === 'natural') continue; ?>
                <div class="filter-pill" data-filter="<?= strtolower(str_replace([' ', '&'], ['_', ''], $f['name'])) ?>" onclick="selectFilter(this)">
                    <?= htmlspecialchars($f['name']) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="btn-primary" style="width:100%;padding:16px;font-size:1.05rem;border:none;cursor:pointer" onclick="startCamera()">
            &#128248; Start Photobooth →
        </button>
        <p style="text-align:center;font-size:.8rem;color:var(--text-light);margin-top:12px">
            Your browser will ask for camera permission — click <strong>Allow</strong>
        </p>
    </div>

    <!-- SCREEN 2: CAMERA -->
    <div id="screenCamera" class="pb-camera-wrap">
        <div class="pb-camera-inner">

            <!-- Video feed -->
            <div>
                <div class="video-container" id="videoContainer">
                    <video id="pbVideo" autoplay playsinline muted></video>
                    <div class="countdown-overlay" id="countdownOverlay">
                        <div class="countdown-num" id="countdownNum">3</div>
                    </div>
                    <div class="flash-anim" id="flashAnim"></div>
                </div>
                <p style="text-align:center;font-size:.78rem;color:var(--text-light);margin-top:10px">
                    &#128247; Camera is mirrored so it feels natural your photo will be correct!
                </p>
            </div>

            <!-- Controls + progress -->
            <div>
                <!-- Shot progress -->
                <div class="shot-progress">
                    <h4>Shots Progress</h4>
                    <div class="shot-dots" id="shotDots"></div>
                    <div style="font-size:.78rem;color:var(--text-light);margin-top:10px" id="shotStatus">
                        Ready for shot 1!
                    </div>
                </div>

                <!-- Controls -->
                <div class="cam-controls">
                    <button class="btn-capture" id="captureBtn" onclick="startCountdown()">
                        &#128248; Take Photo
                    </button>
                    <div style="background:rgba(107,15,15,.06);border-radius:10px;padding:12px;font-size:.78rem;color:var(--text-mid);text-align:center">
                        <strong id="layoutLabel">Strip · 3 shots</strong><br>
                        <span id="filterLabel" style="color:var(--text-light)">Filter: Natural</span>
                    </div>
                    <button class="btn-restart" onclick="restartSetup()">
                        ← Change Style
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden canvas for capturing -->
        <canvas id="pbCanvas" style="display:none"></canvas>
    </div>

    <!-- SCREEN 3: LOADING -->
    <div id="screenLoading" class="pb-loading">
        <div class="spinner"></div>
        <div style="font-family:var(--font-display);font-size:1.5rem;color:var(--maroon-deep);margin-bottom:8px">Developing your photos...</div>
        <div style="color:var(--text-light);font-size:.9rem">Saving to server and generating your QR code 📱</div>
    </div>

    <!-- SCREEN 4: RESULT -->
    <div id="screenResult" class="pb-result-wrap">
        <div style="font-size:3rem;margin-bottom:12px">&#127881;</div>
        <h2 style="font-family:var(--font-display);font-size:2.2rem;color:var(--maroon-deep);margin-bottom:8px">Your Photos are Ready!</h2>
        <p style="color:var(--text-mid);margin-bottom:32px">Scan the QR code with your phone or download below!</p>

        <div class="result-grid">
            <!-- Photo preview (strip or grid) -->
            <div>
                <div style="font-weight:700;color:var(--maroon-deep);margin-bottom:12px;font-size:.9rem">&#128248; Your Photos</div>
                <div id="photoPreview"></div>
            </div>

            <!-- QR code -->
            <div>
                <div style="font-weight:700;color:var(--maroon-deep);margin-bottom:12px;font-size:.9rem">&#128199; Your QR Code</div>
                <div class="qr-box">
                    <img id="qrImg" src="" alt="QR Code">
                    <div class="qr-code-text" id="sessionCodeText"></div>
                    <p style="font-size:.78rem;color:var(--text-light);margin-bottom:16px">
                        Scan with your phone camera to view &amp; download your photos!
                    </p>
                    <div class="result-actions">
                        <a id="downloadBtn" href="#" download class="btn-primary" style="text-align:center;text-decoration:none;padding:12px">
                            &#128229; Download Strip
                        </a>
                        <a id="viewOnlineBtn" href="#" target="_blank"
                            style="display:block;padding:10px;border:2px solid var(--border);border-radius:8px;text-align:center;font-size:.85rem;font-weight:600;color:var(--text-mid);text-decoration:none">
                            &#128065; View Online
                        </a>
                        <button onclick="startOver()"
                            style="padding:10px;border:2px solid var(--border);border-radius:8px;background:none;font-size:.85rem;font-weight:600;color:var(--text-mid);cursor:pointer">
                            &#128248; Take Another
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- end bg -->

<script>
    // Photobooth JS
    // State
    let selectedLayout = 'strip';
    let selectedFilter = 'natural';
    let totalShots = 3;
    let currentShot = 0;
    let capturedPhotos = []; // base64 strings
    let stream = null;
    let countdownTimer = null;

    // Setup
    function selectLayout(el) {
        document.querySelectorAll('.layout-choice').forEach(e => e.classList.remove('active'));
        el.classList.add('active');
        selectedLayout = el.dataset.layout;
        totalShots = selectedLayout === 'strip' ? 3 : 4;
    }

    function selectFilter(el) {
        document.querySelectorAll('.filter-pill').forEach(e => e.classList.remove('active'));
        el.classList.add('active');
        selectedFilter = el.dataset.filter;
        // Apply filter to video preview if camera is active
        applyFilterToVideo();
    }

    function applyFilterToVideo() {
        const video = document.getElementById('pbVideo');
        video.className = '';
        if (selectedFilter !== 'natural') {
            const map = {
                'black_white': 'filter-bw',
                'black_&_white': 'filter-bw',
                'vintage': 'filter-vintage',
                'glam': 'filter-glam',
                'film_grain': 'filter-film',
                'vivid_pop': 'filter-vivid'
            };
            const cls = map[selectedFilter] || '';
            if (cls) video.classList.add(cls);
        }
    }

    // Start Camera
    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: {
                        ideal: 1280
                    },
                    height: {
                        ideal: 960
                    },
                    facingMode: 'user'
                },
                audio: false
            });
            const video = document.getElementById('pbVideo');
            video.srcObject = stream;
            applyFilterToVideo();

            // Build shot dots
            buildShotDots();

            // Update labels
            document.getElementById('layoutLabel').textContent =
                selectedLayout === 'strip' ? 'Strip · 3 shots' : 'Grid · 4 shots';
            document.getElementById('filterLabel').textContent =
                'Filter: ' + document.querySelector('.filter-pill.active').textContent.trim();

            showScreen('screenCamera');
        } catch (e) {
            alert('Could not access camera. Please allow camera permission and try again.\n\nError: ' + e.message);
        }
    }

    function buildShotDots() {
        const container = document.getElementById('shotDots');
        container.innerHTML = '';
        for (let i = 0; i < totalShots; i++) {
            const dot = document.createElement('div');
            dot.className = 'shot-dot' + (i === 0 ? ' current' : '');
            dot.id = 'dot-' + i;
            dot.textContent = (i + 1);
            container.appendChild(dot);
        }
    }

    // Countdown + Capture
    function startCountdown() {
        if (currentShot >= totalShots) return;
        const btn = document.getElementById('captureBtn');
        btn.disabled = true;

        const overlay = document.getElementById('countdownOverlay');
        const numEl = document.getElementById('countdownNum');
        overlay.style.display = 'flex';

        let count = 3;
        numEl.textContent = count;

        countdownTimer = setInterval(() => {
            count--;
            if (count > 0) {
                numEl.textContent = count;
                // Animate
                numEl.style.animation = 'none';
                numEl.offsetHeight; // reflow
                numEl.style.animation = 'popIn .8s ease';
            } else {
                clearInterval(countdownTimer);
                overlay.style.display = 'none';
                capturePhoto();
            }
        }, 1000);
    }

    function capturePhoto() {
        const video = document.getElementById('pbVideo');
        const canvas = document.getElementById('pbCanvas');
        const ctx = canvas.getContext('2d');

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;

        // Mirror + filter on canvas
        ctx.save();
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.restore();

        // Apply CSS filter via canvas
        if (selectedFilter !== 'natural') {
            const filterMap = {
                'black_white': 'grayscale(100%)',
                'black_&_white': 'grayscale(100%)',
                'vintage': 'sepia(60%) contrast(90%) brightness(110%)',
                'glam': 'brightness(115%) contrast(105%) saturate(120%)',
                'film_grain': 'grayscale(30%) contrast(110%) brightness(95%)',
                'vivid_pop': 'saturate(200%) contrast(110%)'
            };
            if (filterMap[selectedFilter]) {
                ctx.filter = filterMap[selectedFilter];
                ctx.drawImage(canvas, 0, 0);
            }
        }

        // Flash effect
        const flash = document.getElementById('flashAnim');
        flash.classList.add('go');
        setTimeout(() => flash.classList.remove('go'), 200);

        // Save photo
        const dataUrl = canvas.toDataURL('image/png');
        capturedPhotos.push(dataUrl);

        // Update dot
        const dot = document.getElementById('dot-' + currentShot);
        dot.classList.remove('current');
        dot.classList.add('taken');
        dot.innerHTML = `<img src="${dataUrl}">`;

        currentShot++;

        if (currentShot < totalShots) {
            // Next shot
            const nextDot = document.getElementById('dot-' + currentShot);
            if (nextDot) nextDot.classList.add('current');
            document.getElementById('shotStatus').textContent =
                `Great! Ready for shot ${currentShot + 1} of ${totalShots}`;
            document.getElementById('captureBtn').disabled = false;
        } else {
            // All shots done!
            document.getElementById('shotStatus').textContent = '✅ All shots done! Saving...';
            setTimeout(savePhotos, 800);
        }
    }

    // Save to server
    async function savePhotos() {
        showScreen('screenLoading');

        // Stop camera stream
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }

        try {
            const res = await fetch('http://localhost/PixelPodWeb/api/save_photos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    photos: capturedPhotos,
                    layout: selectedLayout,
                    filter_name: selectedFilter
                })
            });
            const data = await res.json();

            if (data.success) {
                showResult(data);
            } else {
                alert('Error saving photos: ' + data.message);
                showScreen('screenSetup');
            }
        } catch (e) {
            // Fallback: show result without server (offline mode)
            showResultOffline();
        }
    }

    // Show Result
    function showResult(data) {
        // Build photo preview
        const previewEl = document.getElementById('photoPreview');
        if (selectedLayout === 'strip') {
            const strip = document.createElement('div');
            strip.className = 'strip-preview';
            data.photos.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                strip.appendChild(img);
            });
            previewEl.innerHTML = '';
            previewEl.appendChild(strip);
        } else {
            const grid = document.createElement('div');
            grid.className = 'grid-preview';
            data.photos.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                grid.appendChild(img);
            });
            previewEl.innerHTML = '';
            previewEl.appendChild(grid);
        }

        // QR code
        document.getElementById('qrImg').src = data.qr_url;
        document.getElementById('sessionCodeText').textContent = data.session_code;
        document.getElementById('viewOnlineBtn').href = data.view_url;
        document.getElementById('downloadBtn').href = data.photos[0];

        showScreen('screenResult');
    }

    // Offline fallback (if server not available)
    function showResultOffline() {
        const previewEl = document.getElementById('photoPreview');
        if (selectedLayout === 'strip') {
            const strip = document.createElement('div');
            strip.className = 'strip-preview';
            capturedPhotos.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                strip.appendChild(img);
            });
            previewEl.innerHTML = '';
            previewEl.appendChild(strip);
        } else {
            const grid = document.createElement('div');
            grid.className = 'grid-preview';
            capturedPhotos.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                grid.appendChild(img);
            });
            previewEl.innerHTML = '';
            previewEl.appendChild(grid);
        }

        // Generate QR via API (just the URL in this case)
        const code = 'PPB-OFFLINE';
        const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(window.location.href);
        document.getElementById('qrImg').src = qrUrl;
        document.getElementById('sessionCodeText').textContent = code;
        document.getElementById('downloadBtn').href = capturedPhotos[0];
        document.getElementById('viewOnlineBtn').href = window.location.href;
        showScreen('screenResult');
    }

    // Navigation
    function showScreen(id) {
        ['screenSetup', 'screenCamera', 'screenLoading', 'screenResult'].forEach(s => {
            document.getElementById(s).style.display = s === id ? 'block' : 'none';
        });
    }

    function restartSetup() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        resetState();
        showScreen('screenSetup');
    }

    function startOver() {
        resetState();
        showScreen('screenSetup');
    }

    function resetState() {
        currentShot = 0;
        capturedPhotos = [];
        clearInterval(countdownTimer);
        document.getElementById('pbVideo').srcObject = null;
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>