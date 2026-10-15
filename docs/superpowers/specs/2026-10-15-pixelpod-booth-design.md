# PixelPod booth: browser-only rebuild

Design spec. Written 2026-10-15 for the owner's review, before any implementation.

## 1. Context

This repo is the PHP + MySQL PixelPodWeb site: a public side (home, services, booking, contact, dashboard) and an admin side (bookings, packages, customers, payments, inquiries, analytics, settings). The booth itself is `public/photobooth/index.php`, 959 lines, the largest file here, with `public/photobooth/view.php` as the page a session code pointed at.

What the booth does today, in order: a setup screen offering Photo Strip (3 shots) or Photo Grid (4 shots) and filter pills read from a `filters` table; a camera screen with the live preview, a 3 second countdown, a flash, shot dots that fill with thumbnails, and a capture button naming the layout, shot count and chosen look; a loading screen; a result screen with the shots stacked in CSS, a session code, a QR image and a download link.

Why it cannot work anywhere but the author's machine:

- `savePhotos()` posts to `http://localhost/PixelPodWeb/api/save_photos.php`, a hardcoded local path.
- `api/save_photos.php` writes each shot to `uploads/photos/` and returns more `http://localhost/...` URLs, plus a QR rendered by `api.qrserver.com`.
- `railway.json` records an earlier hosting plan; `pixelpodweb.vercel.app` answers 404 and the project no longer exists.

One finding drives this whole spec: **the booth has never produced a strip.** Each shot is saved as its own PNG, the result screen stacks them with CSS (`.strip-preview`, `.grid-preview`), and `downloadBtn` points at `photos[0]`, a single raw shot. The compositor is therefore new work, not a port, and without it the "Download Strip" button keeps promising something the code does not deliver.

## 2. Goal

A booth that runs with no server, keeps the design and the flow exactly as they are, and finally produces the single strip or grid image the interface has always promised.

## 3. Success criteria

1. Served as static files with no backend, a visitor picks strip (3 shots) or grid (4 shots), picks one of six looks, completes the countdown three or four times, and ends with one PNG.
2. The downloaded PNG is the composited output: frames, paper margins and footer band per the geometry module, never a single raw shot.
3. All six looks come from the pixel pipeline and are identical in every browser, with golden-value tests.
4. The page makes no request to any host other than its own origin: no `localhost`, no `api.qrserver.com`.
5. The PHP site is untouched: `git status` shows only additions (`booth/`, `docs/`, and one README section).
6. A refused or missing camera behaves exactly as it does today: the same `alert()` naming the error, setup screen still usable.
7. Deployed on Vercel from this repo with Root Directory `booth` and production branch `photoboothweb`.

## 4. Non-goals

Booking, services, customers, payments, analytics, the admin panel, accounts, sessions, server storage, galleries, share links, QR codes, printing integration, GIF or video output, AR props, and any look beyond the existing six. No PHP file is modified, refactored, moved or deleted.

## 5. Kept as it is

**Identity and tokens**, taken from `public/css/style.css`: maroon `#6b0f0f`, maroon-deep `#4a0808`, maroon-light `#8b1a1a`, gold `#c9a84c`, gold-light `#e8c97a`, cream `#fdf8f3`, cream-dark `#f5ede0`, white, text-dark `#1a0a0a`, text-mid `#5a3a3a`, text-light `#9a7575`, radii 8/16/24/40, maroon-tinted shadows, `--ease: 0.3s cubic-bezier(0.4, 0, 0.2, 1)`, Playfair Display for display and DM Sans for body.

**Booth styles** from the inline `<style>` block of `public/photobooth/index.php`: `.pb-hero`, `.pb-setup`, `.layout-choices`, `.layout-choice` (hover, active, `.icon`, `.shots-badge`), `.filter-row`, `.filter-pill` (hover, active), `.video-container`, the `#pbVideo` filter classes, `.countdown-overlay`, `.countdown-num` with its `popIn` animation, `.flash-anim`, `.shot-progress`, `.shot-dots`, `.shot-dot` (current, taken), `.cam-controls`, `.btn-capture`, `.btn-restart`, `.pb-loading`, `.spinner`, `.pb-result-wrap`, `.result-grid`.

**Markup and ids**: screens `screenSetup`, `screenCamera`, `screenLoading`, `screenResult`; `pbVideo`, `videoContainer`, `countdownOverlay`, `countdownNum`, `flashAnim`, `shotDots`, `shotStatus`, `captureBtn`, `layoutLabel`, `filterLabel`, `pbCanvas`, `photoPreview`, `downloadBtn`. The layout choices, the pills, the hero block and the "Shots Progress" panel keep their structure and copy.

**Behaviour**, matching the original line for line in intent: choosing a layout sets 3 or 4 shots; choosing a look filters the live preview; the capture button starts a 3 second countdown whose number re-triggers `popIn` by forcing a reflow, then captures; each capture mirrors the frame (`translate(width, 0)` then `scale(-1, 1)`), fires the flash class for 200 ms, fills that shot's dot with a thumbnail and advances; after the last shot the flow moves on by itself; "Change Style" and "Take Another" both stop the stream and reset state. Camera constraints stay `width: { ideal: 1280 }, height: { ideal: 960 }, facingMode: 'user', audio: false`.

**Copy** unchanged except where it promised the server or the QR (§6): "PixelPod Virtual Booth", "Strike a Pose, Capture the Moment", the layout descriptions, the layout and filter labels, "Shots Progress", "Ready for shot 1!", "Great! Ready for shot N of M", "All shots done! Saving...", the mirror note, "Developing your photos...", "Your Photos are Ready!", "Take Another", "Change Style".

## 6. Replaced

| Today | After |
|---|---|
| `$pdo->query("SELECT * FROM filters WHERE is_active=1 ORDER BY sort_order")` | A static list in `src/filters.js`: Natural, Black & White, Vintage, Glam, Film Grain, Vivid Pop, with the same slug keys the existing JS map already uses (`natural`, `black_white`, `vintage`, `glam`, `film_grain`, `vivid_pop`) |
| `fetch('http://localhost/PixelPodWeb/api/save_photos.php')` with the photos array, layout and filter | The local compositor; nothing is uploaded |
| QR image from `api.qrserver.com`, session code text, "View Online" link | Save and Share actions in the same column, under the same `.result-grid` |
| `ctx.filter` chains for the six looks | The pixel pipeline in `src/filters.js` |
| Hero line "Get your photos instantly with a QR code." | A line describing the download and share instead |
| Loading line "Saving to server and generating your QR code" | "Putting your strip together" |
| Result heading "Your QR Code" | "Save it" |
| `.qr-box`, `.qr-code-text`, `.strip-preview`, `.grid-preview` rules | Dropped with the markup they styled, since the preview is now the composited image |
| Camera failure `alert()` | Kept, unchanged, at the owner's request |

## 7. Added

**`src/strip.js`, pure geometry and compositing.** No DOM and no camera, so it unit tests as plain data.

- `layoutFor(kind)` returns `{ kind, shots, columns, rows }`: strip is `3 × 1`, grid is `2 × 2`.
- Geometry: output width 1200; paper margin 44 on every side; gutter 18 between frames; footer band 96 at the bottom; frame width is 1112 for the strip and 547 for each grid frame (`(1112 - 18) / 2`); frame height is the frame width times the capture aspect, rounded, which for the 1280 × 960 stream is 834 for the strip and 410 for grid frames; height is `44 + rows * frameH + (rows - 1) * 18 + 96 + 44`. The module exports these numbers and the tests assert the exact integers, so the layout cannot drift silently.
- Paper is cream `#fdf8f3`; each frame gets a hairline maroon `#6b0f0f` border at 2 px; a 1 px gold `#c9a84c` rule sits above the footer band.
- Footer: the wordmark "PixelPod" in Playfair Display on the left, the capture date on the right in DM Sans, both in maroon-deep on the cream paper.
- `compose(frames, { kind, date })` returns a canvas at those exact dimensions; `toBlob()` gives the PNG the download uses.

**`src/filters.js`, the six looks as pixel maths.** These are the chains copied from the booth's own style block, functions applied left to right exactly as written there. Filter functions do not commute, so the order is not a stylistic choice, it is the specification:

- Natural: identity.
- Black & White: `grayscale(100%)`.
- Vintage: `sepia(60%)` then `contrast(90%)` then `brightness(110%)`.
- Glam: `brightness(115%)` then `contrast(105%)` then `saturate(120%)`.
- Film Grain: `grayscale(30%)` then `contrast(110%)` then `brightness(95%)`.
- Vivid Pop: `saturate(200%)` then `contrast(110%)`.

The formulas implement the SVG filter primitives the CSS functions map to (grayscale, sepia and saturate are matrices, brightness and contrast are channel arithmetic), which is what makes them byte-identical across browsers and testable against golden pixels.

The module also exports `cssClassFor(id)` so the live preview keeps using the exact classes the original used, and `applyTo(imageData, id)` so the same look is baked into the captured frames. Formulas are explicit rather than delegated to `ctx.filter`, which is what makes them identical across browsers and testable.

**`src/capture.js`, the camera lifecycle.** `start()` requests the stream with the original constraints, attaches it to `pbVideo` and returns the stream; `stop()` releases every track; `grab(video, canvas, filterId)` draws the mirrored frame into the capture canvas, applies the look through the pixel pipeline, flashes, and returns the frame as an offscreen canvas kept in order for the compositor. It throws on failure exactly where the original's try/catch did, so the alert keeps its place.

**`src/download.js`, getting the image out.** `saveBlob(blob)` names the file `pixelpod-strip-YYYY-MM-DD.png` or `pixelpod-grid-YYYY-MM-DD.png`, creates an object URL and clicks a temporary `<a download>`, revoking the URL after. `shareBlob(blob)` uses `navigator.share` with the PNG file when `navigator.canShare({ files })` is available, and the Share button is hidden otherwise. A refused share is not an error, so the button does nothing rather than alerting.

**`src/main.js`, the flow.** One screen state machine replacing the inline script: state (`selectedLayout`, `selectedFilter`, `totalShots`, `currentShot`, `frames`), the four `showScreen` transitions, the countdown timer, dot painting, and the two reset paths. Plain functions, module scope, no framework.

**`booth/index.html`** carries the ported markup, `booth/package.json` and `booth/vite.config.js` carry the Vite build (Vite as the only dependency, no runtime dependencies), and `booth/README.md` records how to run and deploy it.

**One deliberate accessibility change.** The layout choices and filter pills are `div`s with `onclick`, so they cannot be reached by keyboard at all. They become `<button type="button">` elements carrying the same classes and the same look, with `aria-pressed` on the active one, inside a `.layout-choices` and `.filter-row` that already behave like a group. Visually nothing changes; this is the only intended deviation from verbatim fidelity, and it is called out here so it can be vetoed at review.

## 8. Layout in the repo

```
PixelPodWeb/
  admin/  api/  config/  includes/  public/  railway.json      untouched
  booth/
    index.html
    package.json
    vite.config.js
    README.md
    src/main.js
    src/capture.js
    src/filters.js
    src/strip.js
    src/download.js
    src/styles/base.css     tokens, type and the shared rules the booth uses,
                            lifted from public/css/style.css
    src/styles/booth.css    the booth rules from the PHP page's style block
    test/filters.test.mjs
    test/strip.test.mjs
    test/filename.test.mjs
  docs/superpowers/specs/2026-10-15-pixelpod-booth-design.md
```

`base.css` takes the `:root` tokens, the font stacks and only the shared rules the booth markup references (`.btn-primary`, `.btn-restore` if used, resets). It does not ship the site's nav, footer, services or dashboard rules, because there are no such pages in this build.

The repo `README.md` gains a short section naming `booth/`, its commands and its URL, since the README today does not even mention the booth page that exists.

## 9. Verification

**Unit, `node --test test/`:**
- `filters.test.mjs`: each look against golden pixels for a fixed 4 × 4 image (a mid grey, a saturated red, a skin tone and black), plus the identity look returning its input unchanged and every look leaving alpha untouched.
- `strip.test.mjs`: `layoutFor` shots and grid shape; exact output width and height for both layouts; frame width and height per layout; gutter and margin widths; that frame rectangles never overlap and never cross the footer band; that `compose` on stub frames produces a canvas of exactly those dimensions.
- `filename.test.mjs`: the date-stamped name for both layouts.

**Browser, `agent-browser` against the dev server, then again against the deployed URL:**
- `navigator.mediaDevices.getUserMedia` is patched in the page before Start, returning a canvas-backed stream that paints each shot a known solid colour. This makes the whole flow deterministic without needing a camera or Chromium flags: the real draw, filter and composite code runs on real frames.
- Assertions: the four screens appear in order; the pills and layout cards update the labels and the shot count; the countdown reaches zero and captures on its own; the dots fill with thumbnails; the result preview is the composited image; the composited canvas has the dimensions from the geometry module; each frame region contains the colour painted for that shot, which proves frame order and placement; the download link carries a `blob:` URL and the expected filename.
- Timing: the composite path is measured with `performance.now()` around filter plus compose for four frames. Budget: under 250 ms on the author's machine. The measured number is reported rather than assumed, and a miss is reopened as a performance task instead of quietly accepted.

**Manual, once, by the author:** the real camera on the real machine, including the permission prompt, the refused-permission alert and one downloaded strip opened in an image viewer. A headless browser cannot stand in for that.

**Deployment:** production deploy from this repo with Root Directory `booth`, then the patched-stream pass repeated against the live URL.

## 10. Deployment

A new Vercel project linked to `houtaroudes/PixelPodWeb` with Root Directory `booth`, framework Vite, build `npm run build`, output `dist`, production branch `photoboothweb` (the repo's default branch). Working name `pixelpod-booth`, giving `pixelpod-booth.vercel.app`; the dead `pixelpodweb.vercel.app` is not reused. Commits are authored and committed only as `HoutarouDes <houtaroudes@gmail.com>`, guarded by the workspace hook now installed in this clone.

## 11. Risks

- **iOS Safari**: `navigator.share` with files exists from 15 onward; where it does not, the Share button is hidden and the download link is the path. Camera access needs HTTPS, which Vercel provides.
- **Per-frame cost**: the pixel work runs over a 1280 × 960 frame, about 1.2 million pixels, four times per session. The maths is channel arithmetic, so the budget above is generous, but it is measured rather than assumed.
- **Fonts in the composite**: the footer uses Playfair Display, so compositing waits for `document.fonts.ready`, the same trick the flipbook build uses, and falls back to the serif stack if the font never loads.
- **Old history identity**: this repo's existing commits are authored `sacuezabryan <sacuezabryan@gmail.com>`, one of the drifts already logged in the workspace notes. New commits use the canonical identity; rewriting the old ones stays deferred and out of scope here, as it was for the other repos.
- **File size**: a 1200 × 2722 PNG is roughly one to three megabytes. Fine for download and share, and worth remembering if the strip is ever emailed.

## 12. Open question, with a default

The footer band's content: the default is the wordmark on the left and the capture date on the right, with a hairline gold rule above the band. Alternatives are the site URL, a "thank you" line, or an empty band. Confirm the default or name a change at review; everything else in this spec is settled. Until told otherwise the default stands: wordmark left, date right, hairline gold rule above the band.

## 13. Two findings from preparing the port

Both were measured rather than reasoned about, by rendering the real stylesheets in Chrome and reading computed styles, and both are decisions already taken. Neither needs to block anything, and each is one line to reverse.

**Three tokens the booth uses are defined nowhere.** `--border`, `--bg` and `--maroon-dark` appear in the booth's style block and across the site's pages, and no stylesheet in the repo ever defines them. The computed values confirm it: the variables resolve to empty, so `.layout-choice` renders with a `0px` `none` border, the same for `.btn-restart`, an untaken `.shot-dot` gets no dashed outline, the `background: var(--bg)` wrapper is transparent so the cream body shows through, and on hover `.btn-capture` keeps its maroon because `background: var(--maroon-dark)` is invalid. The design text clearly intends a soft border on the cards, pills and dots; the browser has never drawn one, which is why nobody noticed. **Decision: port verbatim.** The rebuild renders exactly what the site renders today, because the port copies the CSS text and the missing tokens stay missing. The one line that changes it is adding `--border: rgba(107, 15, 15, .15);` plus `--bg: var(--cream-dark);` and `--maroon-dark: var(--maroon-deep);` to `base.css`, which is worth doing in the original site at the same time rather than only here.

**The Black & White pill may have been doing nothing.** The PHP turns a filter's name into a slug with `strtolower(str_replace([' ', '&'], ['_', ''], $name))`, so a name of `Black & White` becomes `black__white`, and the booth's JavaScript maps only ever accept `black_white` or `black_&_white`. Neither matches the double underscore, and `black_&_white` cannot be produced by that slugifier from any name at all, so one of the six looks is at risk of applying no filter. The database is not in the repo, so this stays a possibility rather than a fact. The static list settles it by construction: the key is `black_white`, which the existing map already handles.

## 14. Queued after this: the portfolio feature

Decided 2026-10-15, and deliberately sequenced behind this build, because the point of the feature is a card that points at something a visitor can use.

- **Where:** `houtaroudes-portfolio` (v1) and `houtaroudes-portfolio-v2` together, the way St. Joseph Village was showcased on Sep 29.
- **What:** a hero screenshot captured from the rebuilt booth against the deployed URL, a highlighted slot on both, a build story in v2's story drawer, and the project counts bumped on both.
- **The point of the card:** PixelPodWeb is the only project in either portfolio with no demo, because PHP and MySQL do not run on Vercel. Today v1 and v2 both link the source only, and the flipbook says so outright (`reason: "No live deploy: PHP and MySQL do not run on Vercel."`, `embed: false`). This build is what removes that sentence, so the card gains its live link at the same time as its highlight.
- **Preflight:** `check-portfolios.mjs` at the workspace root exists because this very card once shipped a link answering 404 on every path. Its link check covers v1 and v2 only, so the new demo URL gets checked before the feature ships, and the checker is extended if the flipbook is ever touched.
- **Still open, to settle when the feature is built:** whether the card keeps calling it a school project, and whether the story is told as the rebuild (the PHP original against the browser version) rather than as the original project.
