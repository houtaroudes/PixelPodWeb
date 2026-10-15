import assert from 'node:assert/strict'
import { test } from 'node:test'

import {
  BORDER,
  FOOTER,
  FOOTER_GAP,
  FRAME_INK,
  GUTTER,
  INK,
  MARGIN,
  PAPER,
  RULE_INK,
  compose,
  formatDate,
  frameRect,
  geometryFor,
  layoutFor,
  toBlob,
} from '../src/strip.js'

/*
 * The compositor is the one piece the PHP build never had, so there is no old
 * behaviour to compare against: these numbers are the design. They are written
 * out here rather than derived from the same helper the code uses, because
 * asserting geometryFor() === geometryFor() would pass whatever it returned.
 */

const DATE = new Date(2026, 9, 15) // 15 October 2026
const FOUR_THREE = 4 / 3

/*
 * A canvas whose context records every call, including the style in force when
 * it happened. That is enough to prove each frame landed on its own rectangle
 * and that the paper, the hairline and the footer were the right colours,
 * without a browser anywhere near it.
 */
function recordingCanvas() {
  const created = []
  const createCanvas = (width, height) => {
    const ctx = {}
    ctx.calls = []
    const record = (op) => (...args) =>
      ctx.calls.push({
        op,
        args,
        fillStyle: ctx.fillStyle,
        strokeStyle: ctx.strokeStyle,
        lineWidth: ctx.lineWidth,
        font: ctx.font,
        textAlign: ctx.textAlign,
      })
    for (const op of ['fillRect', 'drawImage', 'strokeRect', 'beginPath', 'moveTo', 'lineTo', 'stroke', 'fillText']) {
      ctx[op] = record(op)
    }
    const canvas = {
      width,
      height,
      ctx,
      getContext: () => ctx,
      toBlob: (callback, type) => {
        canvas.blobType = type
        callback('a blob')
      },
    }
    created.push(canvas)
    return canvas
  }
  return { created, createCanvas }
}

function frames(count) {
  return Array.from({ length: count }, () => ({ width: 1280, height: 960 }))
}

test('a strip is three shots and a grid is four', () => {
  assert.deepEqual(layoutFor('strip'), { kind: 'strip', shots: 3, columns: 1, rows: 3 })
  assert.deepEqual(layoutFor('grid'), { kind: 'grid', shots: 4, columns: 2, rows: 2 })
  assert.equal(layoutFor('nonsense').kind, 'strip', 'an unknown layout falls back to the strip')
})

test('the strip geometry is the published one', () => {
  const strip = geometryFor('strip', FOUR_THREE)
  assert.equal(strip.width, 1200)
  assert.equal(strip.frameW, 1112, 'one frame wide, minus the two margins')
  assert.equal(strip.frameH, 834, '1112 at 4:3')
  assert.equal(strip.height, 2746, '44 + 3 x 834 + 2 x 18 + 24 + 96 + 44')
})

test('the grid geometry is the published one', () => {
  const grid = geometryFor('grid', FOUR_THREE)
  assert.equal(grid.width, 1200)
  assert.equal(grid.frameW, 547, 'two frames and one gutter inside the margins')
  assert.equal(grid.frameH, 410, '547 at 4:3')
  assert.equal(grid.height, 1046, '44 + 2 x 410 + 18 + 24 + 96 + 44')
})

test('a non 4:3 camera makes shorter frames, not stretched faces', () => {
  const wide = geometryFor('strip', 16 / 9)
  assert.equal(wide.frameW, 1112, 'the width is set by the paper')
  assert.equal(wide.frameH, 626, 'the height follows the capture instead')
  assert.equal(wide.height, 2122)
})

test('each shot gets its own rectangle, in the order taken', () => {
  assert.deepEqual(frameRect(0, 'strip', FOUR_THREE), { x: 44, y: 44, w: 1112, h: 834 })
  assert.deepEqual(frameRect(1, 'strip', FOUR_THREE), { x: 44, y: 896, w: 1112, h: 834 })
  assert.deepEqual(frameRect(2, 'strip', FOUR_THREE), { x: 44, y: 1748, w: 1112, h: 834 })

  assert.deepEqual(frameRect(0, 'grid', FOUR_THREE), { x: 44, y: 44, w: 547, h: 410 })
  assert.deepEqual(frameRect(1, 'grid', FOUR_THREE), { x: 609, y: 44, w: 547, h: 410 })
  assert.deepEqual(frameRect(2, 'grid', FOUR_THREE), { x: 44, y: 472, w: 547, h: 410 })
  assert.deepEqual(frameRect(3, 'grid', FOUR_THREE), { x: 609, y: 472, w: 547, h: 410 })
})

for (const kind of ['strip', 'grid']) {
  const geo = geometryFor(kind, FOUR_THREE)

  test(`${kind}: every frame sits inside the paper and clear of the footer`, () => {
    for (let i = 0; i < geo.shots; i++) {
      const { x, y, w, h } = frameRect(i, kind, FOUR_THREE)
      assert.ok(x >= MARGIN && y >= MARGIN, `${kind} frame ${i} crosses the top or left margin`)
      assert.ok(x + w <= geo.width - MARGIN, `${kind} frame ${i} crosses the right margin`)
      // The frames are stroked with a 2px hairline centred on their edges, so a
      // frame that merely reaches the rule buries half of it. The whole gap has
      // to stay clear, not just the frame.
      assert.ok(
        y + h + geo.footerGap <= geo.ruleY,
        `${kind} frame ${i} leaves a hairline's worth of paper on the gold rule`,
      )
    }
  })

  test(`${kind}: no two frames overlap`, () => {
    for (let a = 0; a < geo.shots; a++) {
      for (let b = a + 1; b < geo.shots; b++) {
        const first = frameRect(a, kind, FOUR_THREE)
        const second = frameRect(b, kind, FOUR_THREE)
        const apart =
          first.x + first.w <= second.x ||
          second.x + second.w <= first.x ||
          first.y + first.h <= second.y ||
          second.y + second.h <= first.y
        assert.ok(apart, `${kind} frames ${a} and ${b} overlap`)
      }
    }
  })

  test(`${kind}: the gaps are the gutters the design asks for`, () => {
    if (kind === 'grid') {
      assert.equal(frameRect(1, kind, FOUR_THREE).x - (frameRect(0, kind, FOUR_THREE).x + geo.frameW), GUTTER)
    }
    assert.equal(frameRect(geo.columns, kind, FOUR_THREE).y - (frameRect(0, kind, FOUR_THREE).y + geo.frameH), GUTTER)
  })
}

test('the gold rule is one gap below the frames, and lands in a single row', () => {
  for (const kind of ['strip', 'grid']) {
    const geo = geometryFor(kind, FOUR_THREE)
    assert.equal(geo.ruleY, geo.height - MARGIN - FOOTER, 'the rule is the top of the footer band')

    const last = frameRect(geo.shots - 1, kind, FOUR_THREE)
    assert.equal(last.y + last.h + FOOTER_GAP, geo.ruleY, `${kind}: the rule is not one gap below the frames`)
    assert.ok(geo.ruleY - (last.y + last.h) > BORDER, `${kind}: the rule is inside a frame hairline`)

    // A rendered strip measured 154,91,45 on the rule row, which is what half a
    // maroon hairline over half a gold rule looks like. The compositor section
    // below pins the half pixel that keeps the rule out of the hairline's way.
  }
})

test('the composite is one canvas at the paper size, with every frame on it', () => {
  const { created, createCanvas } = recordingCanvas()
  const canvas = compose(frames(3), { kind: 'strip', date: DATE, createCanvas })

  assert.equal(created.length, 1, 'the compositor should make exactly one canvas')
  assert.equal(canvas.width, 1200)
  assert.equal(canvas.height, 2746)

  const paper = canvas.ctx.calls[0]
  assert.equal(paper.op, 'fillRect')
  assert.deepEqual(paper.args, [0, 0, 1200, 2746])
  assert.equal(paper.fillStyle, PAPER, 'the paper has to be laid down first')

  const draws = canvas.ctx.calls.filter((call) => call.op === 'drawImage')
  assert.equal(draws.length, 3)
  draws.forEach((call, i) => {
    const { x, y, w, h } = frameRect(i, 'strip', FOUR_THREE)
    assert.deepEqual(call.args.slice(1), [x, y, w, h], `frame ${i} landed somewhere else`)
  })

  const borders = canvas.ctx.calls.filter((call) => call.op === 'strokeRect')
  assert.equal(borders.length, 3)
  for (const border of borders) {
    assert.equal(border.strokeStyle, FRAME_INK)
    assert.equal(border.lineWidth, BORDER)
  }

  const strokes = canvas.ctx.calls.filter((call) => call.op === 'stroke')
  assert.equal(strokes.length, 1, 'one rule above the footer, and nothing else stroked')
  assert.equal(strokes[0].strokeStyle, RULE_INK)
  assert.equal(strokes[0].lineWidth, 1)

  const ruleY = geometryFor('strip', FOUR_THREE).ruleY + 0.5
  const rule = canvas.ctx.calls.filter((call) => call.op === 'moveTo' || call.op === 'lineTo')
  assert.deepEqual(rule.map((call) => call.args[0]), [MARGIN, 1200 - MARGIN], 'the rule spans the frame block')
  for (const call of rule) {
    assert.equal(call.args[1], ruleY, 'the 1px rule is split across two rows unless it sits on a half pixel')
  }

  const texts = canvas.ctx.calls.filter((call) => call.op === 'fillText')
  assert.deepEqual(
    texts.map((call) => call.args),
    [
      ['PixelPod', MARGIN, 2746 - MARGIN - FOOTER + FOOTER / 2],
      [formatDate(DATE), 1200 - MARGIN, 2746 - MARGIN - FOOTER + FOOTER / 2],
    ],
  )
  for (const text of texts) assert.equal(text.fillStyle, INK)
})

test('a grid composite puts four frames on two rows', () => {
  const { createCanvas } = recordingCanvas()
  const canvas = compose(frames(4), { kind: 'grid', date: DATE, createCanvas })
  const draws = canvas.ctx.calls.filter((call) => call.op === 'drawImage')
  assert.equal(draws.length, 4)
  assert.equal(canvas.height, 1046)
  // drawImage takes the frame, then x, y, width, height.
  assert.deepEqual(draws.map((call) => call.args.slice(3, 5)), [[547, 410], [547, 410], [547, 410], [547, 410]])
})

test('the frames decide the aspect when the caller does not say', () => {
  const { createCanvas } = recordingCanvas()
  // A 16:9 capture, so the strip gets shorter frames than the 4:3 default.
  const wide = Array.from({ length: 3 }, () => ({ width: 1920, height: 1080 }))
  const canvas = compose(wide, { kind: 'strip', date: DATE, createCanvas })
  assert.equal(canvas.height, 2122)
})

test('the footer date is fixed to one format, whatever the machine reads', () => {
  assert.equal(formatDate(DATE), '15 Oct 2026')
  assert.equal(formatDate(new Date(2026, 0, 5)), '5 Jan 2026')
})

test('the composite is exported as a PNG', async () => {
  const { createCanvas } = recordingCanvas()
  const canvas = compose(frames(3), { kind: 'strip', date: DATE, createCanvas })
  const blob = await toBlob(canvas)
  assert.equal(blob, 'a blob')
  assert.equal(canvas.blobType, 'image/png')
})
