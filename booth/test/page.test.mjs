import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

/*
 * main.js reaches the page by id, so an id renamed in one file and not the
 * other is a null dereference on the first click, and nothing else here would
 * catch it: the modules are tested on their own, and the markup is never
 * parsed. These checks are source checks, and they are deliberately shallow.
 */

const read = (rel) => readFileSync(new URL(rel, import.meta.url), 'utf8')

/** Comments are stripped before the negative checks, so prose about the old
    localhost endpoint is not mistaken for a live call to one. */
function stripComments(source) {
  return source
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/(^|\s)\/\/[^\n]*/g, '$1')
}

const main = read('../src/main.js')
const html = read('../index.html')
const mainCode = stripComments(main)
const htmlCode = stripComments(html)

/** The literal ids main.js looks up, leaving out the ones it builds at runtime. */
function lookedUpIds() {
  const ids = []
  for (const match of main.matchAll(/byId\('([^']+)'\)/g)) {
    const rest = main.slice(match.index + match[0].length)
    if (rest.startsWith(' +')) continue // byId('dot-' + currentShot)
    ids.push(match[1])
  }
  return [...new Set(ids)]
}

test('every id main.js looks up exists in the page', () => {
  const ids = lookedUpIds()
  assert.ok(ids.length > 10, `only ${ids.length} ids found, the scan is probably broken`)
  for (const id of ids) {
    assert.match(html, new RegExp(`id="${id}"`), `index.html has no element with id="${id}"`)
  }
})

test('all four screens are in the page and none of them is a typo', () => {
  for (const screen of ['screenSetup', 'screenCamera', 'screenLoading', 'screenResult']) {
    assert.match(html, new RegExp(`id="${screen}"`))
    assert.match(main, new RegExp(`'${screen}'`))
  }
})

test('the layout cards are the two the geometry module knows', () => {
  const layouts = [...html.matchAll(/data-layout="([a-z]+)"/g)].map((m) => m[1])
  assert.deepEqual(layouts, ['strip', 'grid'])
})

test('the controls are buttons, so the keyboard can reach them', () => {
  assert.doesNotMatch(html, /onclick=/, 'inline handlers cannot see module scope, and are not keyboard reachable')
  assert.match(html, /class="layout-choice active" data-layout="strip" aria-pressed="true"/)
  assert.match(html, /role="group" aria-label="Layout"/)
  assert.match(html, /role="group" aria-label="Filter"/)
})

test('the page carries its own stylesheets and one module entry point', () => {
  const scripts = [...html.matchAll(/<script[^>]*src="([^"]+)"/g)].map((m) => m[1])
  assert.deepEqual(scripts, ['/src/main.js'])
  assert.match(html, /href="\/src\/styles\/base\.css"/)
  assert.match(html, /href="\/src\/styles\/booth\.css"/)
})

test('nothing in the page points at a host other than the fonts and its own origin', () => {
  const urls = [...html.matchAll(/https?:\/\/([^/"']+)/g)].map((m) => m[1])
  assert.deepEqual([...new Set(urls)], ['fonts.googleapis.com'], 'the booth talks to no other host')
  assert.doesNotMatch(mainCode, /\bfetch\(/, 'nothing is posted anywhere any more')
  assert.doesNotMatch(htmlCode, /localhost/, 'the hardcoded local paths are gone for good')
  assert.doesNotMatch(htmlCode + mainCode, /qrserver/, 'the QR service is gone with the session code')
})

/*
 * The icons. Every one of them used to be a pictograph: a film strip on the
 * layout card, a camera on the capture button, a party popper over the result.
 * An emoji is drawn by the visitor's own operating system, so the same page
 * showed a different booth on every machine, in colours this page never uses,
 * and nothing else in the suite would notice one coming back.
 */
test('no icon on the page is a pictograph', () => {
  const pictographs = /[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}]/u
  assert.doesNotMatch(htmlCode, pictographs, 'index.html carries an emoji, as a character or an entity')
  assert.doesNotMatch(mainCode, pictographs, 'the flow sets an emoji into the status line')
  assert.doesNotMatch(htmlCode, /&#\d+;/, 'the icons are drawn, or written as named entities')
})

test('every icon is drawn in the sprite the page carries', () => {
  const refs = [...html.matchAll(/<use href="#([^"]+)"/g)].map((match) => match[1])
  const symbols = [...html.matchAll(/<symbol id="([^"]+)"/g)].map((match) => match[1])
  const icons = [...html.matchAll(/<svg class="icon"[^>]*>/g)].map((match) => match[0])

  assert.ok(icons.length >= 6, `only ${icons.length} icons found, the scan is probably broken`)
  assert.equal(icons.length, refs.length, 'an icon is drawn with no reference to a symbol')
  for (const ref of refs) assert.ok(symbols.includes(ref), `nothing in the sprite defines #${ref}`)
  for (const symbol of symbols) assert.ok(refs.includes(symbol), `the sprite defines #${symbol}, which no icon draws`)
  for (const icon of icons) {
    assert.match(icon, /aria-hidden="true"/, 'a decorative icon must not become the accessible name')
    assert.match(icon, /focusable="false"/, 'an icon inside a control must not take focus')
  }
})

test('the icons are one set of line drawings, coloured by their label', () => {
  const css = read('../src/styles/booth.css')
  // Anchored to the start of a line so this reads the shared .icon rule, not
  // the more specific .layout-choice .icon one above it.
  const icon = /\n\.icon\s*\{([^}]*)\}/.exec(css)
  assert.ok(icon, 'booth.css should describe .icon')
  assert.match(icon[1], /fill:\s*none/, 'a line icon has no fill')
  assert.match(icon[1], /stroke:\s*currentColor/, 'an icon takes the colour of the label beside it')
  assert.match(icon[1], /stroke-width:/, 'the set shares one stroke weight')
  assert.match(css, /\.pb-icons\s*\{[^}]*display:\s*none/, 'the sprite is never drawn on its own')
})

/*
 * The fourth state. The port kept the PHP page's alert() for a refused camera,
 * which named the problem once and then left the visitor on a setup screen that
 * said nothing, beside a button that had appeared to do nothing. It is the one
 * state this page could not hold, and now it can.
 */
test('a refused camera reports itself in the page, not in an alert', () => {
  assert.doesNotMatch(mainCode, /\balert\(/, 'an alert is not a state the page keeps')
  assert.match(html, /<div class="pb-error" id="cameraError" role="alert" hidden>/)
  assert.match(main, /byId\('cameraErrorDetail'\)/, 'the message from the browser goes in beside it')
  assert.match(main, /dom\.error\.hidden = false/, 'the catch has to show it')
  assert.match(main, /dom\.error\.hidden = true/, 'and a start attempt has to clear it again')
  assert.match(read('../src/styles/booth.css'), /\.pb-error\s*\{[^}]*border-radius:/, 'the state needs a look')
})

test('no em dash or en dash reached the booth', () => {
  const sources = {
    'index.html': html,
    'main.js': main,
    'booth.css': read('../src/styles/booth.css'),
    'base.css': read('../src/styles/base.css'),
  }
  for (const [name, source] of Object.entries(sources)) {
    assert.doesNotMatch(source, /[\u2013\u2014]/, `${name} carries a dash this workspace bans`)
  }
})
