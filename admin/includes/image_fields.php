<?php
function renderImageTabs(string $prefix = 'add'): string {
    return <<<HTML
<div class="form-group">
    <label>Package Thumbnail <small style="color:var(--text-light);font-weight:400">(optional)</small></label>
    <div style="display:flex;border-radius:8px;overflow:hidden;border:1px solid var(--border);margin-bottom:10px">
        <button type="button" id="tab-up-{$prefix}" onclick="switchTab('{$prefix}','up')"
            style="flex:1;padding:9px;font-size:.82rem;font-weight:600;border:none;cursor:pointer;background:var(--maroon);color:#fff;transition:.2s">
            Upload File
        </button>
        <button type="button" id="tab-url-{$prefix}" onclick="switchTab('{$prefix}','url')"
            style="flex:1;padding:9px;font-size:.82rem;font-weight:600;border:none;cursor:pointer;background:var(--panel);color:var(--text-mid);transition:.2s">
            Paste URL
        </button>
    </div>
    <div id="pane-up-{$prefix}">
        <input type="file" name="image" accept="image/*" onchange="previewFile(this,'fp-{$prefix}')"
               style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;font-size:.85rem;background:var(--panel);cursor:pointer">
        <p style="font-size:.75rem;color:var(--text-light);margin-top:5px">JPG, PNG, WebP, GIF — max 5MB</p>
        <div id="fp-{$prefix}" style="display:none;margin-top:10px">
            <img style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
        </div>
    </div>
    <div id="pane-url-{$prefix}" style="display:none">
        <input type="text" name="image_url" placeholder="https://example.com/photo.jpg"
               oninput="previewUrl(this,'up-{$prefix}')"
               style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:.88rem;background:var(--panel);outline:none;font-family:inherit">
        <p style="font-size:.75rem;color:var(--text-light);margin-top:5px">Paste a direct link to any online image</p>
        <div id="up-{$prefix}" style="display:none;margin-top:10px">
            <img style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
        </div>
    </div>
</div>
<script>
function switchTab(prefix, tab) {
    const isUp = tab === 'up';
    document.getElementById('tab-up-'+prefix).style.background  = isUp  ? 'var(--maroon)' : 'var(--panel)';
    document.getElementById('tab-up-'+prefix).style.color       = isUp  ? '#fff'          : 'var(--text-mid)';
    document.getElementById('tab-url-'+prefix).style.background = !isUp ? 'var(--maroon)' : 'var(--panel)';
    document.getElementById('tab-url-'+prefix).style.color      = !isUp ? '#fff'          : 'var(--text-mid)';
    document.getElementById('pane-up-'+prefix).style.display    = isUp  ? 'block' : 'none';
    document.getElementById('pane-url-'+prefix).style.display   = !isUp ? 'block' : 'none';
}
function previewFile(input, wrapId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.getElementById(wrapId);
            wrap.querySelector('img').src = e.target.result;
            wrap.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function previewUrl(input, wrapId) {
    const wrap = document.getElementById(wrapId);
    const img  = wrap.querySelector('img');
    const url  = input.value.trim();
    if (url) {
        img.src     = url;
        img.onload  = () => wrap.style.display = 'block';
        img.onerror = () => wrap.style.display = 'none';
    } else {
        wrap.style.display = 'none';
    }
}
</script>
HTML;
}
