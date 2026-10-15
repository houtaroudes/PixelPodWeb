import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

import { FILTERS, applyTo, chainFor, cssClassFor, nameFor } from '../src/filters.js'

/*
 * The reference table below was painted by a browser, not by this code.
 *
 * Six known pixels went into a canvas, each look was applied through the CSS
 * filter property with the chain copied out of the booth's style block, and the
 * result was read back with getImageData. Those numbers are what the booth
 * looked like, so they are what the pixel pipeline has to reproduce.
 *
 * The one place they do not agree exactly is rounding. The browser keeps float
 * through the chain and stores 8-bit at the end, which is what this module
 * does, but its own primitives round slightly differently. Measured: Black &
 * White is exact, most channels are off by one, and the worst case in this
 * table is two. Anything beyond that means the maths changed, not the rounding.
 */

const ROUNDING_TOLERANCE = 2

const SOURCE = [
  [128, 128, 128], // mid grey
  [220, 40, 30], // saturated red
  [224, 172, 148], // skin
  [0, 0, 0], // black
  [255, 255, 255], // white, which is where an unclamped chain gives itself away
  [30, 60, 120], // deep blue
]

const PAINTED_BY_CHROME = {
  black_white: [[128, 128, 128], [78, 78, 78], [181, 181, 181], [0, 0, 0], [255, 255, 255], [58, 58, 58]],
  vintage: [[167, 156, 135], [173, 94, 75], [249, 212, 174], [13, 13, 13], [255, 255, 255], [72, 80, 94]],
  glam: [[147, 147, 147], [255, 32, 18], [255, 198, 164], [0, 0, 0], [255, 255, 255], [22, 66, 153]],
  film_grain: [[121, 121, 121], [171, 40, 33], [208, 170, 152], [0, 0, 0], [242, 242, 242], [27, 49, 93]],
  vivid_pop: [[128, 128, 128], [255, 0, 0], [255, 166, 113], [0, 0, 0], [255, 255, 255], [0, 55, 187]],
}

/** A one-row ImageData stand-in, which is all applyTo ever reads. */
function imageData(pixels, alpha = 255) {
  return {
    width: pixels.length,
    height: 1,
    data: new Uint8ClampedArray(pixels.flatMap(([r, g, b]) => [r, g, b, alpha])),
  }
}

function pixelsOf(image) {
  const out = []
  for (let i = 0; i < image.data.length; i += 4) out.push([image.data[i], image.data[i + 1], image.data[i + 2]])
  return out
}

test('every look reproduces what the browser painted', () => {
  for (const [id, expected] of Object.entries(PAINTED_BY_CHROME)) {
    const got = pixelsOf(applyTo(imageData(SOURCE), id))
    assert.equal(got.length, expected.length, `${id}: wrong pixel count`)
    expected.forEach((want, i) => {
      want.forEach((channel, c) => {
        const delta = Math.abs(channel - got[i][c])
        assert.ok(
          delta <= ROUNDING_TOLERANCE,
          `${id} pixel ${i} channel ${c}: painted ${channel}, got ${got[i][c]} (delta ${delta})`,
        )
      })
    })
  }
})

test('Natural returns the pixels it was given, untouched', () => {
  const image = imageData(SOURCE)
  const before = [...image.data]
  assert.equal(applyTo(image, 'natural'), image)
  assert.deepEqual([...image.data], before)
})

test('an unknown look is treated as Natural rather than throwing', () => {
  const image = imageData(SOURCE)
  const before = [...image.data]
  applyTo(image, 'no-such-look')
  assert.deepEqual([...image.data], before)
})

test('no look touches the alpha channel', () => {
  for (const filter of FILTERS) {
    const image = imageData(SOURCE, 7)
    applyTo(image, filter.id)
    for (let i = 3; i < image.data.length; i += 4) {
      assert.equal(image.data[i], 7, `${filter.id} changed alpha at byte ${i}`)
    }
  }
})

test('the six looks are the two the page offers plus the four it offered before', () => {
  assert.equal(FILTERS.length, 6)
  assert.equal(new Set(FILTERS.map((f) => f.id)).size, 6, 'two looks share an id')
  assert.deepEqual(
    FILTERS.map((f) => f.id),
    ['natural', 'black_white', 'vintage', 'glam', 'film_grain', 'vivid_pop'],
  )
})

test('the chain for each look is the chain in the stylesheet', () => {
  assert.deepEqual(chainFor('vintage'), [['sepia', 0.6], ['contrast', 0.9], ['brightness', 1.1]])
  assert.deepEqual(chainFor('glam'), [['brightness', 1.15], ['contrast', 1.05], ['saturate', 1.2]])
  assert.deepEqual(chainFor('film_grain'), [['grayscale', 0.3], ['contrast', 1.1], ['brightness', 0.95]])
  assert.deepEqual(chainFor('vivid_pop'), [['saturate', 2], ['contrast', 1.1]])
  assert.deepEqual(chainFor('black_white'), [['grayscale', 1]])
  assert.deepEqual(chainFor('natural'), [])
})

/*
 * The preview class and the pipeline are two halves of one look, and they are
 * only ever in step if the table and the stylesheet agree. This reads the
 * stylesheet rather than trusting the table: every `#pbVideo.filter-*` rule in
 * booth.css has to be claimed by a look, and every look's class has to exist.
 */
test('the preview classes match the ones booth.css defines', () => {
  const css = readFileSync(new URL('../src/styles/booth.css', import.meta.url), 'utf8')
  const inStylesheet = new Set([...css.matchAll(/#pbVideo\.(filter-[a-z-]+)/g)].map((m) => m[1]))
  const inTable = new Set(FILTERS.map((f) => f.css).filter(Boolean))

  assert.deepEqual([...inStylesheet].sort(), [...inTable].sort())
  assert.equal(cssClassFor('natural'), null, 'Natural wears no class at all')
  assert.equal(nameFor('film_grain'), 'Film Grain')
  assert.equal(nameFor('nope'), 'Natural', 'an unknown look falls back to the first label')
})
