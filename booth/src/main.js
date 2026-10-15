/*
 * The flow, ported from the script at the bottom of public/photobooth/index.php.
 *
 * The functions keep their original names on purpose, so the two files can be
 * read side by side: selectLayout, selectFilter, applyFilterToVideo,
 * startCamera, buildShotDots, startCountdown, capturePhoto, showScreen,
 * restartSetup, startOver, resetState.
 *
 * Three things had to change. Inline onclick attributes cannot reach a function
 * inside an ES module, so the controls are wired with listeners. savePhotos()
 * posted to a localhost endpoint that no longer exists, so it became develop(),
 * which composes the strip in the tab. And showResult() no longer builds a QR
 * code out of a session code, because there is no session to point at.
 */

import { Camera } from './capture.js'
import { FILTERS, DEFAULT_FILTER, cssClassFor, nameFor } from './filters.js'
import { compose, toBlob, layoutFor } from './strip.js'
import { fileName, setDownload, releaseDownload, canShare, share } from './download.js'

// Frame timings from the original script, unchanged.
const COUNTDOWN_SECONDS = 3
const FLASH_MS = 200
const DEVELOP_DELAY_MS = 800

/*
 * The footer is set in Playfair Display, so the composite waits for the webfont
 * rather than baking a fallback face into the file. The timeout is there so a
 * font that never arrives cannot hold the strip hostage.
 */
const FONT_WAIT_MS = 1500

const byId = (id) => document.getElementById(id)

const dom = {
  filterRow: byId('filterRow'),
  error: byId('cameraError'),
  errorDetail: byId('cameraErrorDetail'),
  layoutChoices: [...document.querySelectorAll('.layout-choice')],
  video: byId('pbVideo'),
  countdownOverlay: byId('countdownOverlay'),
  countdownNum: byId('countdownNum'),
  flash: byId('flashAnim'),
  shotDots: byId('shotDots'),
  shotStatus: byId('shotStatus'),
  captureBtn: byId('captureBtn'),
  layoutLabel: byId('layoutLabel'),
  filterLabel: byId('filterLabel'),
  preview: byId('photoPreview'),
  downloadBtn: byId('downloadBtn'),
  shareBtn: byId('shareBtn'),
}

const camera = new Camera(dom.video)

// The state the original script kept, kept in the same shape.
let selectedLayout = 'strip'
let selectedFilter = DEFAULT_FILTER
let totalShots = 3
let currentShot = 0
// One canvas per shot, in the order they were taken.
let frames = []
let countdownTimer = null
// The object URL currently behind the download link and the preview.
let downloadUrl = null
// The finished strip, so the share button has something to hand over.
let finished = null

/*
 * The filter pills used to be rendered by a PHP foreach over a `filters` table.
 * They come from the static list now, which is the one place the six looks are
 * defined, and each is a real button so the keyboard can reach it.
 */
function buildFilterPills() {
  dom.filterRow.replaceChildren(
    ...FILTERS.map((filter) => {
      const pill = document.createElement('button')
      pill.type = 'button'
      pill.className = filter.id === selectedFilter ? 'filter-pill active' : 'filter-pill'
      pill.dataset.filter = filter.id
      pill.textContent = filter.name
      pill.setAttribute('aria-pressed', String(filter.id === selectedFilter))
      return pill
    }),
  )
}

function selectLayout(button) {
  dom.layoutChoices.forEach((choice) => {
    const active = choice === button
    choice.classList.toggle('active', active)
    choice.setAttribute('aria-pressed', String(active))
  })
  selectedLayout = button.dataset.layout
  totalShots = layoutFor(selectedLayout).shots
}

function selectFilter(button) {
  dom.filterRow.querySelectorAll('.filter-pill').forEach((pill) => {
    const active = pill === button
    pill.classList.toggle('active', active)
    pill.setAttribute('aria-pressed', String(active))
  })
  selectedFilter = button.dataset.filter
  applyFilterToVideo()
}

/* The live preview wears the same class it always did. The class is paint only;
   the frame itself is finished by the pixel pipeline in filters.js. */
function applyFilterToVideo() {
  dom.video.className = ''
  const cssClass = cssClassFor(selectedFilter)
  if (cssClass) dom.video.classList.add(cssClass)
}

async function startCamera() {
  try {
    await camera.start()
    dom.error.hidden = true
    buildShotDots()
    dom.layoutLabel.textContent = selectedLayout === 'strip' ? 'Strip \u00b7 3 shots' : 'Grid \u00b7 4 shots'
    dom.filterLabel.textContent = 'Filter: ' + nameFor(selectedFilter)
    showScreen('screenCamera')
  } catch (e) {
    /*
     * Was an alert(), kept verbatim from the PHP page. An alert names the
     * problem once and then leaves the visitor looking at a button that did
     * nothing, and it is the one state the page had no way to show. The same
     * message now goes into the page, with the browser's own words beside it,
     * and it stays until a start attempt works.
     */
    dom.errorDetail.textContent = e && e.message ? String(e.message) : 'The camera did not answer.'
    dom.error.hidden = false
  }
}

function buildShotDots() {
  dom.shotDots.replaceChildren(
    ...Array.from({ length: totalShots }, (_, i) => {
      const dot = document.createElement('div')
      dot.className = i === 0 ? 'shot-dot current' : 'shot-dot'
      dot.id = 'dot-' + i
      dot.textContent = String(i + 1)
      return dot
    }),
  )
}

function startCountdown() {
  if (currentShot >= totalShots) return
  dom.captureBtn.disabled = true
  dom.countdownOverlay.style.display = 'flex'

  let count = COUNTDOWN_SECONDS
  dom.countdownNum.textContent = String(count)

  countdownTimer = setInterval(() => {
    count--
    if (count > 0) {
      dom.countdownNum.textContent = String(count)
      // Re-trigger the animation: clear it, force a reflow, put it back.
      dom.countdownNum.style.animation = 'none'
      void dom.countdownNum.offsetHeight
      dom.countdownNum.style.animation = 'popIn .8s ease'
    } else {
      clearInterval(countdownTimer)
      countdownTimer = null
      dom.countdownOverlay.style.display = 'none'
      capturePhoto()
    }
  }, 1000)
}

function capturePhoto() {
  const frame = camera.grab(byId('pbCanvas'), selectedFilter)
  frames.push(frame)

  dom.flash.classList.add('go')
  setTimeout(() => dom.flash.classList.remove('go'), FLASH_MS)

  const dot = byId('dot-' + currentShot)
  dot.classList.remove('current')
  dot.classList.add('taken')
  const thumb = document.createElement('img')
  thumb.src = frame.toDataURL('image/png')
  thumb.alt = ''
  dot.replaceChildren(thumb)

  currentShot++

  if (currentShot < totalShots) {
    const nextDot = byId('dot-' + currentShot)
    if (nextDot) nextDot.classList.add('current')
    dom.shotStatus.textContent = `Great! Ready for shot ${currentShot + 1} of ${totalShots}`
    dom.captureBtn.disabled = false
  } else {
    dom.shotStatus.textContent = 'All shots done. Putting your strip together...'
    setTimeout(develop, DEVELOP_DELAY_MS)
  }
}

/*
 * Was savePhotos(). The camera is released first, because the strip is drawn
 * from frames already in hand and there is no reason to keep the light on while
 * the compositor works.
 */
async function develop() {
  showScreen('screenLoading')
  camera.stop()

  await Promise.race([
    document.fonts ? document.fonts.ready : Promise.resolve(),
    new Promise((resolve) => setTimeout(resolve, FONT_WAIT_MS)),
  ])

  const blob = await toBlob(compose(frames, { kind: selectedLayout, date: new Date() }))
  showResult(blob)
}

function showResult(blob) {
  const kind = layoutFor(selectedLayout).kind
  const name = fileName(kind)

  releaseDownload(downloadUrl)
  downloadUrl = setDownload(dom.downloadBtn, blob, name)
  // The same object URL behind the link, so the preview is exactly the file.
  dom.preview.src = downloadUrl
  dom.preview.alt = selectedLayout === 'strip' ? 'Your finished photo strip' : 'Your finished photo grid'

  finished = { blob, name }
  dom.shareBtn.hidden = !canShare(blob, name)

  showScreen('screenResult')
}

function showScreen(id) {
  ['screenSetup', 'screenCamera', 'screenLoading', 'screenResult'].forEach((screen) => {
    byId(screen).style.display = screen === id ? 'block' : 'none'
  })
}

function restartSetup() {
  camera.stop()
  resetState()
  showScreen('screenSetup')
}

function startOver() {
  resetState()
  showScreen('screenSetup')
}

function resetState() {
  dom.error.hidden = true
  currentShot = 0
  frames = []
  finished = null
  clearInterval(countdownTimer)
  countdownTimer = null
  dom.countdownOverlay.style.display = 'none'
  dom.video.srcObject = null
  dom.preview.removeAttribute('src')
  releaseDownload(downloadUrl)
  downloadUrl = null
}

buildFilterPills()
showScreen('screenSetup')

dom.layoutChoices.forEach((choice) => choice.addEventListener('click', () => selectLayout(choice)))
dom.filterRow.addEventListener('click', (event) => {
  const pill = event.target.closest('.filter-pill')
  if (pill) selectFilter(pill)
})
byId('startBtn').addEventListener('click', startCamera)
dom.captureBtn.addEventListener('click', startCountdown)
byId('restartBtn').addEventListener('click', restartSetup)
byId('takeAnotherBtn').addEventListener('click', startOver)
dom.shareBtn.addEventListener('click', () => {
  if (finished) share(finished.blob, finished.name)
})
