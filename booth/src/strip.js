/*
 * The file the booth hands over: three or four frames drawn onto one sheet of
 * cream paper, with a maroon hairline around each shot and a footer band
 * carrying the wordmark and the date.
 *
 * The gold rule above that band is the one thing the strip has to get exactly
 * right, and it took a browser to see that it did not. A hairline is stroked
 * centred on the frame's edge, so a frame whose bottom edge is the rule's own y
 * paints its 2px border over the rule: the sampled rows came back 154,91,45,
 * half maroon and half gold, and no gold row existed at all. FOOTER_GAP and the
 * half pixel the rule is stroked on are what a rendered strip needed.
 *
 * This is the part the PHP build never had. It saved each shot as its own PNG
 * and stacked them with CSS, then pointed "Download Strip" at the first one, so
 * the button named a thing the code never made. Everything here is arithmetic
 * on rectangles, which is why it is separated from the camera: the geometry can
 * be checked without a browser, and the composite is built by a canvas factory
 * the tests can substitute.
 */

export const PAPER = '#fdf8f3'
export const INK = '#4a0808'
export const FRAME_INK = '#6b0f0f'
export const RULE_INK = '#c9a84c'

/* A rasterisation detail rather than a measurement: see the rule below. */
const RULE_OFFSET = 0.5

export const DISPLAY_FONT = '"Playfair Display", Georgia, serif'
export const BODY_FONT = '"DM Sans", system-ui, sans-serif'

/** Paper margin on all four sides, and the width of the frame hairline. */
export const MARGIN = 44
export const BORDER = 2
/** Space between frames: between columns, and between rows. */
export const GUTTER = 18
/** The band at the bottom holding the wordmark and the date. */
export const FOOTER = 96
/**
 * Paper between the frame block and the gold rule. It is not decoration: the
 * frames are stroked with a 2px hairline centred on their edges, so a frame
 * that merely reaches the rule buries half of it under its own border.
 */
export const FOOTER_GAP = 24
export const OUT_WIDTH = 1200
/** A capture is 4:3 unless the camera says otherwise, which it is asked not to. */
export const DEFAULT_ASPECT = 4 / 3

export const LAYOUTS = {
  strip: { kind: 'strip', shots: 3, columns: 1, rows: 3 },
  grid: { kind: 'grid', shots: 4, columns: 2, rows: 2 },
}

export function layoutFor(kind) {
  return LAYOUTS[kind] ?? LAYOUTS.strip
}

/**
 * Every number the compositor needs, derived rather than typed twice. Passing
 * the capture's own aspect keeps the frames undistorted: a 16:9 stream makes
 * shorter frames instead of stretched faces.
 */
export function geometryFor(kind, aspect = DEFAULT_ASPECT) {
  const layout = layoutFor(kind)
  const usable = OUT_WIDTH - MARGIN * 2
  const frameW = Math.round((usable - GUTTER * (layout.columns - 1)) / layout.columns)
  const frameH = Math.round(frameW / aspect)
  const height = MARGIN * 2 + layout.rows * frameH + GUTTER * (layout.rows - 1) + FOOTER_GAP + FOOTER
  // The rule is the top edge of the footer band, one gap below the last frame.
  const ruleY = height - MARGIN - FOOTER
  return { ...layout, aspect, width: OUT_WIDTH, height, frameW, frameH, margin: MARGIN, gutter: GUTTER, footer: FOOTER, footerGap: FOOTER_GAP, ruleY, border: BORDER }
}

/** Where frame `index` sits, in the same order the shots were taken. */
export function frameRect(index, kind, aspect = DEFAULT_ASPECT) {
  const geo = geometryFor(kind, aspect)
  const column = index % geo.columns
  const row = Math.floor(index / geo.columns)
  return {
    x: geo.margin + column * (geo.frameW + geo.gutter),
    y: geo.margin + row * (geo.frameH + geo.gutter),
    w: geo.frameW,
    h: geo.frameH,
  }
}

/** A compact footer date, fixed to en-GB so the same day always reads the same. */
export function formatDate(date = new Date()) {
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(date)
}

function aspectOf(frame) {
  if (!frame) return DEFAULT_ASPECT
  const { width, height } = frame
  return width && height ? width / height : DEFAULT_ASPECT
}

function defaultCreateCanvas(width, height) {
  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height
  return canvas
}

/**
 * Draws the finished strip and returns the canvas.
 *
 * `createCanvas` exists so the layout can be tested without a DOM: the test
 * passes a factory returning a canvas whose context records every call, which
 * is enough to prove each frame landed on its own rectangle.
 */
export function compose(frames, { kind = 'strip', date = new Date(), aspect, createCanvas = defaultCreateCanvas } = {}) {
  const geo = geometryFor(kind, aspect ?? aspectOf(frames[0]))
  const canvas = createCanvas(geo.width, geo.height)
  const ctx = canvas.getContext('2d')

  ctx.fillStyle = PAPER
  ctx.fillRect(0, 0, geo.width, geo.height)

  frames.forEach((frame, i) => {
    const { x, y, w, h } = frameRect(i, kind, geo.aspect)
    ctx.drawImage(frame, x, y, w, h)
    ctx.strokeStyle = FRAME_INK
    ctx.lineWidth = BORDER
    ctx.strokeRect(x, y, w, h)
  })

  /*
   * The rule sits on top of the footer band, the width of the frame block, and
   * half a pixel down from the band's own edge. A 1px stroke on a whole
   * coordinate is divided between the two rows it straddles, which renders as
   * two half-tone lines rather than one gold hairline.
   */
  const ruleY = geo.ruleY + RULE_OFFSET
  ctx.beginPath()
  ctx.moveTo(MARGIN, ruleY)
  ctx.lineTo(geo.width - MARGIN, ruleY)
  ctx.strokeStyle = RULE_INK
  ctx.lineWidth = 1
  ctx.stroke()

  const textY = geo.ruleY + FOOTER / 2
  ctx.fillStyle = INK
  ctx.textBaseline = 'middle'
  ctx.textAlign = 'left'
  ctx.font = `700 34px ${DISPLAY_FONT}`
  ctx.fillText('PixelPod', MARGIN, textY)
  ctx.textAlign = 'right'
  ctx.font = `400 26px ${BODY_FONT}`
  ctx.fillText(formatDate(date), geo.width - MARGIN, textY)

  return canvas
}

/** The compositor's PNG, which is what the download and the share sheet get. */
export function toBlob(canvas) {
  return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'))
}
