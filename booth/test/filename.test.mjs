import assert from 'node:assert/strict'
import { test } from 'node:test'

import { fileName } from '../src/download.js'

test('the strip and the grid are named for what they are', () => {
  assert.equal(fileName('strip', new Date(2026, 9, 15)), 'pixelpod-strip-2026-10-15.png')
  assert.equal(fileName('grid', new Date(2026, 9, 15)), 'pixelpod-grid-2026-10-15.png')
})

test('single digit months and days keep their leading zeroes', () => {
  assert.equal(fileName('grid', new Date(2026, 0, 5)), 'pixelpod-grid-2026-01-05.png')
})

test('the date comes from the visitor, not from a server', () => {
  // "Today" is the clock in this tab, so the file is stamped the day it was
  // taken rather than the day a server happened to receive it.
  const today = new Date()
  const stamp = fileName('strip')
  const expected = [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0'),
  ].join('-')
  assert.equal(stamp, `pixelpod-strip-${expected}.png`)
})
