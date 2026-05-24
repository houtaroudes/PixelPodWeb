<?php
$pageTitle = 'Customize Your Booth';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDB();

$sizes   = $pdo->query("SELECT * FROM photo_sizes WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$layouts = $pdo->query("SELECT * FROM layouts     WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$filters = $pdo->query("SELECT * FROM filters     WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$designs = $pdo->query("SELECT * FROM designs     WHERE is_active=1 ORDER BY sort_order")->fetchAll();

// Get design categories for filter tabs
$categories = $pdo->query("SELECT DISTINCT category FROM designs WHERE is_active=1")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
    /* Page Hero */
    .custom-hero {
        background: linear-gradient(135deg, var(--maroon-deep) 0%, var(--maroon) 100%);
        padding: 100px 0 60px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .custom-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at center, rgba(212, 170, 98, .12) 0%, transparent 70%);
    }

    .custom-hero h1 {
        font-family: var(--font-display);
        font-size: clamp(2rem, 5vw, 3.5rem);
        color: #fff;
        margin-bottom: 14px;
        position: relative;
    }

    .custom-hero h1 em {
        color: var(--gold-light);
        font-style: italic;
    }

    .custom-hero p {
        color: rgba(255, 255, 255, .7);
        font-size: 1.05rem;
        max-width: 560px;
        margin: 0 auto 32px;
        position: relative;
    }

    /* Section */
    .custom-section {
        padding: 80px 0;
        background: var(--bg);
    }

    .custom-section:nth-child(even) {
        background: var(--panel);
    }

    .section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-header h2 {
        font-family: var(--font-display);
        font-size: 2.2rem;
        color: var(--maroon-deep);
        margin-bottom: 10px;
    }

    .section-header h2 em {
        color: var(--maroon);
        font-style: italic;
    }

    .section-header p {
        color: var(--text-mid);
        font-size: .95rem;
    }

    .section-tag-sm {
        display: inline-block;
        background: rgba(107, 15, 15, .08);
        color: var(--maroon);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .15em;
        text-transform: uppercase;
        padding: 5px 14px;
        border-radius: 100px;
        margin-bottom: 12px;
    }

    /* Carousel */
    .carousel-wrap {
        position: relative;
        overflow: hidden;
        padding: 10px 0 20px;
    }

    .carousel-track {
        display: flex;
        gap: 24px;
        transition: transform .4s cubic-bezier(.25, .8, .25, 1);
        will-change: transform;
    }

    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid var(--maroon);
        color: var(--maroon);
        font-size: 1.2rem;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .12);
        transition: all .2s;
    }

    .carousel-btn:hover {
        background: var(--maroon);
        color: #fff;
    }

    .carousel-btn.prev {
        left: 0;
    }

    .carousel-btn.next {
        right: 0;
    }

    .carousel-outer {
        padding: 0 56px;
    }

    /* Cards */
    .custom-card {
        flex: 0 0 260px;
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        cursor: pointer;
        transition: transform .3s, box-shadow .3s, border-color .3s;
        border: 2.5px solid transparent;
        position: relative;
    }

    .custom-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 35px rgba(107, 15, 15, .15);
    }

    .custom-card.selected {
        border-color: var(--maroon);
        box-shadow: 0 0 0 4px rgba(107, 15, 15, .12);
    }

    .custom-card.selected::after {
        content: '✓';
        position: absolute;
        top: 10px;
        right: 10px;
        width: 28px;
        height: 28px;
        background: var(--maroon);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        font-weight: 700;
    }

    .card-img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
        background: #f3e8e8;
    }

    .card-img-placeholder {
        width: 100%;
        height: 180px;
        background: linear-gradient(135deg, #f3e8e8, #fdf0f0);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
    }

    .card-body {
        padding: 16px;
    }

    .card-name {
        font-weight: 700;
        font-size: .95rem;
        color: var(--maroon-deep);
        margin-bottom: 4px;
    }

    .card-desc {
        font-size: .78rem;
        color: var(--text-light);
        line-height: 1.5;
        margin-bottom: 8px;
    }

    .card-badge {
        display: inline-block;
        font-size: .7rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 100px;
        background: rgba(107, 15, 15, .08);
        color: var(--maroon);
    }

    .card-badge.green {
        background: rgba(34, 197, 94, .1);
        color: #166534;
    }

    .card-badge.gold {
        background: rgba(212, 170, 98, .15);
        color: #92620a;
    }

    /* Size card (multi-select) */
    .size-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
    }

    .size-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        border: 2.5px solid var(--border);
        cursor: pointer;
        transition: all .25s;
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
    }

    .size-card:hover {
        border-color: var(--maroon);
        transform: translateY(-3px);
    }

    .size-card.selected {
        border-color: var(--maroon);
        background: rgba(107, 15, 15, .03);
    }

    .size-card.selected::after {
        content: '✓';
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background: var(--maroon);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        font-weight: 700;
    }

    .size-icon {
        width: 56px;
        height: 56px;
        border-radius: 10px;
        background: linear-gradient(135deg, #fdf0f0, #f8d9d9);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }

    .size-name {
        font-weight: 700;
        font-size: .95rem;
        color: var(--maroon-deep);
    }

    .size-dim {
        font-size: .78rem;
        color: var(--text-light);
        margin: 2px 0;
    }

    .size-price {
        font-size: .82rem;
        font-weight: 700;
        color: var(--maroon);
    }

    /* Design category tabs */
    .cat-tabs {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 32px;
    }

    .cat-tab {
        padding: 8px 20px;
        border-radius: 100px;
        border: 2px solid var(--border);
        background: #fff;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s;
        color: var(--text-mid);
    }

    .cat-tab:hover,
    .cat-tab.active {
        background: var(--maroon);
        border-color: var(--maroon);
        color: #fff;
    }

    /* CTA bar */
    .cta-bar {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 2px solid var(--border);
        padding: 16px 0;
        z-index: 100;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, .08);
    }

    .cta-bar-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }

    .cta-summary {
        font-size: .88rem;
        color: var(--text-mid);
    }

    .cta-summary strong {
        color: var(--maroon-deep);
    }

    .cta-btns {
        display: flex;
        gap: 12px;
        align-items: center;
    }
</style>

<!-- Hero -->
<div class="custom-hero">
    <div style="position:relative;max-width:700px;margin:0 auto;padding:0 24px">
        <div class="section-tag-sm">Customize Your Experience</div>
        <h1>Make It <em>Yours</em></h1>
        <p>Browse our layouts, filters, and designs — then book your perfect PixelPod experience!</p>
        <a href="http://localhost/PixelPodWeb/public/booking.php" class="btn-primary" style="font-size:1rem;padding:14px 36px">
            Book Now →
        </a>
    </div>
</div>

<div style="max-width:1200px;margin:0 auto;padding:0 24px">

    <!-- SECTION 1: PHOTO SIZES (multi-select grid) -->
    <div class="custom-section">
        <div class="section-header">
            <div class="section-tag-sm">Step 1</div>
            <h2>Choose Your <em>Photo Size</em></h2>
            <p>Pick one or more print sizes — mix and match! Add-on prices apply.</p>
        </div>
        <div class="size-grid" id="sizeGrid">
            <?php foreach ($sizes as $s): ?>
                <div class="size-card" data-id="<?= $s['id'] ?>" data-price="<?= $s['addon_price'] ?>"
                    onclick="toggleSize(this)">
                    <div class="size-icon">
                        <?php
                        $icons = ['2x6' => '🎞️', '4x6' => '&#128444;', '5x7' => '&#128247;', 'Wallet' => '&#128179;', 'Digital' => '&#128241;'];
                        $icon  = '&#128248;';
                        foreach ($icons as $k => $v) {
                            if (stripos($s['name'], $k) !== false) {
                                $icon = $v;
                                break;
                            }
                        }
                        echo $icon;
                        ?>
                    </div>
                    <div>
                        <div class="size-name"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="size-dim"><?= htmlspecialchars($s['dimensions']) ?></div>
                        <div class="size-price">
                            <?= $s['addon_price'] > 0 ? '+₱' . number_format($s['addon_price'], 0) : '&#9989; Included' ?>
                        </div>
                        <div class="size-dim" style="margin-top:4px"><?= htmlspecialchars($s['description']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION 2: LAYOUTS (carousel, single-select) -->
    <div class="custom-section">
        <div class="section-header">
            <div class="section-tag-sm">Step 2</div>
            <h2>Pick a <em>Layout</em></h2>
            <p>How do you want your photos arranged on the print?</p>
        </div>
        <?php renderCarousel('layoutCarousel', $layouts, 'layout'); ?>
    </div>

    <!-- SECTION 3: FILTERS (carousel, single-select) -->
    <div class="custom-section">
        <div class="section-header">
            <div class="section-tag-sm">Step 3</div>
            <h2>Choose a <em>Filter</em></h2>
            <p>Set the mood — all filters are included at no extra cost!</p>
        </div>
        <?php renderCarousel('filterCarousel', $filters, 'filter'); ?>
    </div>

    <!-- SECTION 4: DESIGNS (category tabs + carousel) -->
    <div class="custom-section">
        <div class="section-header">
            <div class="section-tag-sm">Step 4</div>
            <h2>Pick a Frame <em>Design</em></h2>
            <p>Choose a border and decoration style that matches your event theme.</p>
        </div>

        <!-- Category filter tabs -->
        <div class="cat-tabs">
            <button class="cat-tab active" onclick="filterDesigns('all', this)">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-tab" onclick="filterDesigns('<?= $cat ?>', this)">
                    <?= ucfirst($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="carousel-outer" id="designCarouselOuter">
            <div class="carousel-wrap">
                <button class="carousel-btn prev" onclick="slideCarousel('designCarousel', -1)">‹</button>
                <div class="carousel-track" id="designCarousel">
                    <?php foreach ($designs as $d): ?>
                        <div class="custom-card"
                            data-id="<?= $d['id'] ?>"
                            data-type="design"
                            data-category="<?= $d['category'] ?>"
                            onclick="selectCard(this, 'design')">
                            <?php if ($d['sample_image']): ?>
                                <img class="card-img" src="<?= htmlspecialchars($d['sample_image']) ?>"
                                    alt="<?= htmlspecialchars($d['name']) ?>"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="card-img-placeholder" style="display:none">&#127912;</div>
                            <?php else: ?>
                                <div class="card-img-placeholder">&#127912;</div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="card-name"><?= htmlspecialchars($d['name']) ?></div>
                                <div class="card-desc"><?= htmlspecialchars($d['description']) ?></div>
                                <span class="card-badge"><?= ucfirst($d['category']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-btn next" onclick="slideCarousel('designCarousel', 1)">›</button>
            </div>
        </div>
    </div>

</div>

<!-- Sticky CTA Bar -->
<div class="cta-bar">
    <div class="cta-bar-inner">
        <div class="cta-summary">
            <strong id="summaryText">Nothing selected yet</strong>
            <div id="addonText" style="font-size:.78rem;color:var(--text-light);margin-top:2px"></div>
        </div>
        <div class="cta-btns">
            <button onclick="resetAll()" style="background:none;border:2px solid var(--border);padding:10px 20px;border-radius:8px;cursor:pointer;font-size:.85rem;color:var(--text-mid)">
                Reset All
            </button>
            <a href="http://localhost/PixelPodWeb/public/booking.php" id="bookBtn" class="btn-primary">
                Book with These Choices →
            </a>
        </div>
    </div>
</div>

<?php
// Render a carousel section
function renderCarousel(string $id, array $items, string $type): void
{
    $emojis = ['layout' => '&#128444;', 'filter' => '&#127912;'];
    $emoji  = $emojis[$type] ?? '&#128248;';
    echo '<div class="carousel-outer">';
    echo '<div class="carousel-wrap">';
    echo '<button class="carousel-btn prev" onclick="slideCarousel(\'' . $id . '\', -1)">‹</button>';
    echo '<div class="carousel-track" id="' . $id . '">';
    foreach ($items as $item) {
        $img = $item['sample_image'] ?? '';
        echo '<div class="custom-card" data-id="' . $item['id'] . '" data-type="' . $type . '" onclick="selectCard(this, \'' . $type . '\')">';
        if ($img) {
            echo '<img class="card-img" src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($item['name']) . '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">';
            echo '<div class="card-img-placeholder" style="display:none">' . $emoji . '</div>';
        } else {
            echo '<div class="card-img-placeholder">' . $emoji . '</div>';
        }
        echo '<div class="card-body">';
        echo '<div class="card-name">' . htmlspecialchars($item['name']) . '</div>';
        echo '<div class="card-desc">' . htmlspecialchars($item['description'] ?? '') . '</div>';
        if (isset($item['photos_count'])) {
            echo '<span class="card-badge green">' . $item['photos_count'] . ' shots</span>';
        }
        echo '</div></div>';
    }
    echo '</div>';
    echo '<button class="carousel-btn next" onclick="slideCarousel(\'' . $id . '\', 1)">›</button>';
    echo '</div></div>';
}
?>

<script>
    // State
    const state = {
        sizes: [],
        layout: null,
        filter: null,
        design: null
    };

    // Carousel sliding
    const offsets = {};

    function slideCarousel(id, dir) {
        const track = document.getElementById(id);
        const card = track.querySelector('.custom-card');
        if (!card) return;
        const cardW = card.offsetWidth + 24; // width + gap
        offsets[id] = (offsets[id] || 0) - dir * cardW * 2;
        // Clamp so we don't scroll too far
        const maxOff = -(track.scrollWidth - track.parentElement.offsetWidth);
        offsets[id] = Math.min(0, Math.max(maxOff, offsets[id]));
        track.style.transform = `translateX(${offsets[id]}px)`;
    }

    // Single-select cards (layout, filter, design)
    function selectCard(el, type) {
        document.querySelectorAll(`[data-type="${type}"]`).forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        state[type] = {
            id: el.dataset.id,
            name: el.querySelector('.card-name').textContent
        };
        updateSummary();
    }

    // Multi-select sizes
    function toggleSize(el) {
        el.classList.toggle('selected');
        const id = el.dataset.id;
        const price = parseFloat(el.dataset.price) || 0;
        const name = el.querySelector('.size-name').textContent;
        const idx = state.sizes.findIndex(s => s.id === id);
        if (idx > -1) {
            state.sizes.splice(idx, 1);
        } else {
            state.sizes.push({
                id,
                name,
                price
            });
        }
        updateSummary();
    }

    // Design category filter
    function filterDesigns(cat, btn) {
        document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('[data-type="design"]').forEach(card => {
            card.style.display = (cat === 'all' || card.dataset.category === cat) ? 'block' : 'none';
        });
        // Reset carousel offset
        offsets['designCarousel'] = 0;
        document.getElementById('designCarousel').style.transform = 'translateX(0)';
    }

    // Summary bar
    function updateSummary() {
        const addonTotal = state.sizes.reduce((a, s) => a + s.price, 0);
        const parts = [];
        if (state.sizes.length) parts.push(` ${state.sizes.map(s=>s.name).join(', ')}`);
        if (state.layout) parts.push(` ${state.layout.name}`);
        if (state.filter) parts.push(` ${state.filter.name}`);
        if (state.design) parts.push(` ${state.design.name}`);

        document.getElementById('summaryText').textContent =
            parts.length ? parts.join(' · ') : 'Nothing selected yet';
        document.getElementById('addonText').textContent =
            addonTotal > 0 ? `+₱${addonTotal.toLocaleString()} add-on for selected sizes` : '';

        // Pass selections to booking via URL params
        const params = new URLSearchParams();
        if (state.sizes.length) params.set('sizes', state.sizes.map(s => s.id).join(','));
        if (state.layout) params.set('layout', state.layout.id);
        if (state.filter) params.set('filter', state.filter.id);
        if (state.design) params.set('design', state.design.id);
        if (addonTotal > 0) params.set('addon', addonTotal);
        document.getElementById('bookBtn').href =
            'http://localhost/PixelPodWeb/public/booking.php?' + params.toString();
    }

    // Reset all
    function resetAll() {
        state.sizes = [];
        state.layout = null;
        state.filter = null;
        state.design = null;
        document.querySelectorAll('.custom-card.selected, .size-card.selected')
            .forEach(el => el.classList.remove('selected'));
        updateSummary();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>