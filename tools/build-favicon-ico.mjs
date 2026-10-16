#!/usr/bin/env node
/*
 * Build public/favicon.ico from public/favicon.svg.
 *
 * A tab icon has to be one file, and that file is an ICO: a small directory of
 * PNG frames at 16, 32 and 48 pixels. The project has no build step and there
 * is no image tool on this machine, so this script is the rasteriser. It reads
 * the SVG, draws it into RGBA pixels, encodes each size as a PNG with zlib, and
 * packs the frames as ICO entries.
 *
 *   node tools/build-favicon-ico.mjs            rewrite public/favicon.ico
 *   node tools/build-favicon-ico.mjs --check    fail if the icon or a copy no
 *                                               longer matches the SVG
 *
 * It is a small renderer written against this one drawing rather than a
 * dependency: the icon is two rounded rectangles and three stroked paths. What
 * the SVG can express but this cannot draw exactly (a filled path, text, a
 * transform, a gradient or pattern paint, butt caps, miter joins) stops the run
 * with the reason, so a redrawn icon is never quietly rendered as something
 * else. Change the SVG, run this again, and commit both files.
 *
 * Replaces an earlier tools/favicon-ico.html, which rendered the frames on a
 * canvas in a browser and downloaded them for a second script to pack. That
 * needed a browser and a hand-off per change; this runs in one command.
 */

import { readFile, writeFile } from "node:fs/promises";
import { deflateSync, inflateSync } from "node:zlib";

const SVG = "public/favicon.svg";
const OUT = "public/favicon.ico";
const FRAMES = [16, 32, 48];

/* Deployed on their own, so each carries a copy of the icon. */
const COPIES = ["booth/public/favicon.ico", "site-preview/favicon.ico"];

/* Both of those serve the SVG as well, and the booth kept its own hand copy of
   it until this list existed, which is how a tab can go on showing an old mark
   after the source has changed. The source is written over them for the same
   reason the ICO is. */
const SVG_COPIES = ["booth/public/favicon.svg", "site-preview/favicon.svg"];

const fail = (message) => {
  console.error(message);
  process.exit(1);
};

/* git rewrites line endings on checkout in this workspace, so a copy can differ
   only in its carriage returns and still be the same drawing. */
const sameText = (a, b) => a === b || a.replace(/\r\n/g, "\n") === b.replace(/\r\n/g, "\n");

/* ------------------------------------------------------------------ colour */

/* Only what the icon uses plus the two named colours worth typing. Anything
   else is a mistake in the SVG, not a case to guess at. */
const NAMED = { black: "000000", white: "ffffff" };

function parseColor(value, where) {
  const text = String(value).trim().toLowerCase();
  if (text === "none" || text === "transparent") return null;
  if (text.startsWith("url(")) throw new Error(`${where}: ${value} is a gradient or pattern paint, which this renderer does not draw`);
  if (text === "currentcolor") throw new Error(`${where}: currentColor needs a CSS context an icon file does not have`);
  const named = NAMED[text];
  const hex = named || (/^#[0-9a-f]{3}$|^#[0-9a-f]{6}$/.test(text) ? text.slice(1) : null);
  if (!hex) throw new Error(`${where}: ${value} is not a #hex colour or black or white`);
  const full = hex.length === 3 ? hex.replace(/./g, (c) => c + c) : hex;
  return [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16));
}

/* -------------------------------------------------------------------- style */

const STYLE_KEYS = new Set([
  "fill", "fill-opacity", "stroke", "stroke-width", "stroke-opacity",
  "stroke-linecap", "stroke-linejoin", "opacity",
]);

/* Attributes this renderer would have to honour and does not, each of which
   would change the picture if it were ignored. */
const UNSUPPORTED = ["transform", "filter", "mask", "clip-path", "marker-start", "marker-mid", "marker-end"];

const DEFAULTS = {
  fill: "black", stroke: "none", "stroke-width": "1", opacity: "1",
  "fill-opacity": "1", "stroke-opacity": "1", "stroke-linecap": "butt", "stroke-linejoin": "miter",
};

/* Both quote styles, because an unsupported attribute in single quotes has to
   be found just as well as one in double quotes. */
const attrsOf = (text) => {
  const out = {};
  const re = /([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(?:"([^"]*)"|'([^']*)')/g;
  let match;
  while ((match = re.exec(text))) out[match[1]] = match[2] !== undefined ? match[2] : match[3];
  return out;
};

/* SVG gives a shape its parent's presentation attributes, then its own, then a
   style="" declaration on top. There is no stylesheet to consult here. */
function styleFrom(attrs, inherited, where) {
  const style = { ...inherited };
  for (const key of Object.keys(attrs)) {
    if (UNSUPPORTED.includes(key)) throw new Error(`${where}: ${key}="${attrs[key]}" is not supported, so the icon would be drawn wrong`);
    if (STYLE_KEYS.has(key)) style[key] = attrs[key];
  }
  if (attrs.style) {
    for (const declaration of attrs.style.split(";")) {
      const [name, value] = declaration.split(":");
      if (name && value && STYLE_KEYS.has(name.trim())) style[name.trim()] = value.trim();
    }
  }
  return style;
}

/* -------------------------------------------------------------- path parser */

/* Path data is a stream of a command letter and its numbers, where the two arc
   flags are single digits that may run into the next number ("0 013.6"). So the
   numbers are walked with a cursor rather than split with a regular expression. */
function tokensOf(d) {
  const tokens = [];
  let i = 0;
  const isDigit = (c) => c >= "0" && c <= "9";
  while (i < d.length) {
    const c = d[i];
    if (/[\s,]/.test(c)) { i += 1; continue; }
    if (/[a-zA-Z]/.test(c)) { tokens.push(c); i += 1; continue; }
    let j = i;
    if (d[j] === "+" || d[j] === "-") j += 1;
    while (isDigit(d[j])) j += 1;
    if (d[j] === ".") { j += 1; while (isDigit(d[j])) j += 1; }
    if (d[j] === "e" || d[j] === "E") {
      let k = j + 1;
      if (d[k] === "+" || d[k] === "-") k += 1;
      if (isDigit(d[k])) { k += 1; while (isDigit(d[k])) k += 1; j = k; }
    }
    const number = Number(d.slice(i, j));
    if (!Number.isFinite(number) || j === i) throw new Error(`path data: cannot read a number at "${d.slice(i, i + 8)}"`);
    tokens.push(number);
    i = j;
  }
  return tokens;
}

/* A subpath becomes a polyline. The length of a chord from the curve it
   replaces is capped at this, in user units: the icon is 32 units wide, so 0.08
   is a small fraction of a pixel even in the 48px frame. */
const TOLERANCE = 0.08;

const cubicAt = (p0, p1, p2, p3, t) => {
  const u = 1 - t;
  return [
    u * u * u * p0[0] + 3 * u * u * t * p1[0] + 3 * u * t * t * p2[0] + t * t * t * p3[0],
    u * u * u * p0[1] + 3 * u * u * t * p1[1] + 3 * u * t * t * p2[1] + t * t * t * p3[1],
  ];
};

const quadAt = (p0, p1, p2, t) => {
  const u = 1 - t;
  return [u * u * p0[0] + 2 * u * t * p1[0] + t * t * p2[0], u * u * p0[1] + 2 * u * t * p1[1] + t * t * p2[1]];
};

const distance = (a, b) => Math.hypot(a[0] - b[0], a[1] - b[1]);

const polygonLength = (points) => points.reduce((total, p, i) => (i ? total + distance(points[i - 1], p) : 0), 0);

/* Endpoint to centre conversion for an elliptical arc, from the SVG
   specification (implementation notes F.6.5). */
function arcPoints(from, to, rx, ry, phi, largeArc, sweep) {
  if (from[0] === to[0] && from[1] === to[1]) return [];
  rx = Math.abs(rx); ry = Math.abs(ry);
  if (!rx || !ry) return [to];
  const cos = Math.cos(phi), sin = Math.sin(phi);
  const hx = (from[0] - to[0]) / 2, hy = (from[1] - to[1]) / 2;
  const x1 = cos * hx + sin * hy, y1 = -sin * hx + cos * hy;
  const lambda = (x1 * x1) / (rx * rx) + (y1 * y1) / (ry * ry);
  if (lambda > 1) { const s = Math.sqrt(lambda); rx *= s; ry *= s; }
  const sign = largeArc !== sweep ? 1 : -1;
  const numerator = Math.max(0, rx * rx * ry * ry - rx * rx * y1 * y1 - ry * ry * x1 * x1);
  const denominator = rx * rx * y1 * y1 + ry * ry * x1 * x1;
  const coef = sign * Math.sqrt(numerator / denominator);
  const cxp = (coef * rx * y1) / ry, cyp = (-coef * ry * x1) / rx;
  const cx = cos * cxp - sin * cyp + (from[0] + to[0]) / 2;
  const cy = sin * cxp + cos * cyp + (from[1] + to[1]) / 2;
  const angle = (ux, uy, vx, vy) => {
    const dot = ux * vx + uy * vy;
    const len = Math.hypot(ux, uy) * Math.hypot(vx, vy);
    const a = Math.acos(Math.min(1, Math.max(-1, dot / (len || 1))));
    return ux * vy - uy * vx < 0 ? -a : a;
  };
  const start = angle(1, 0, (x1 - cxp) / rx, (y1 - cyp) / ry);
  let delta = angle((x1 - cxp) / rx, (y1 - cyp) / ry, (-x1 - cxp) / rx, (-y1 - cyp) / ry);
  if (!sweep && delta > 0) delta -= 2 * Math.PI;
  if (sweep && delta < 0) delta += 2 * Math.PI;
  const steps = Math.max(2, Math.ceil((Math.max(rx, ry) * Math.abs(delta)) / TOLERANCE));
  const points = [];
  for (let s = 1; s <= steps; s += 1) {
    const theta = start + (delta * s) / steps;
    points.push([
      cx + rx * Math.cos(theta) * cos - ry * Math.sin(theta) * sin,
      cy + rx * Math.cos(theta) * sin + ry * Math.sin(theta) * cos,
    ]);
  }
  return points;
}

/* Every subpath of one path element, each closed subpath carrying its closing
   segment, because a round join there is just another segment to measure from. */
function subpathsOf(d) {
  const tokens = tokensOf(d);
  const subpaths = [];
  let current = null, x = 0, y = 0, startX = 0, startY = 0;
  let cubicControl = null, quadControl = null, command = null, i = 0;
  const read = (count) => {
    const values = tokens.slice(i, i + count);
    if (values.length !== count || values.some((v) => typeof v !== "number")) throw new Error(`path data: "${command}" wants ${count} numbers`);
    i += count;
    return values;
  };
  const startSubpath = (px, py) => {
    current = [[px, py]];
    subpaths.push(current);
    x = startX = px; y = startY = py;
    cubicControl = quadControl = null;
  };
  const lineTo = (px, py) => {
    if (!current) throw new Error("path data: it starts with a drawing command instead of a move");
    current.push([px, py]);
    x = px; y = py;
  };

  while (i < tokens.length) {
    const token = tokens[i];
    if (typeof token === "string") { command = token; i += 1; }
    else if (!command) throw new Error("path data: a number appears before any command");
    const upper = command.toUpperCase();
    const relative = command !== upper;
    const dx = relative ? x : 0, dy = relative ? y : 0;
    switch (upper) {
      case "M": {
        const [a, b] = read(2);
        startSubpath(a + dx, b + dy);
        command = relative ? "l" : "L"; /* further pairs after M are line commands */
        cubicControl = quadControl = null;
        break;
      }
      case "L": { const [a, b] = read(2); lineTo(a + dx, b + dy); cubicControl = quadControl = null; break; }
      case "H": { const [a] = read(1); lineTo(a + dx, y); cubicControl = quadControl = null; break; }
      case "V": { const [a] = read(1); lineTo(x, a + dy); cubicControl = quadControl = null; break; }
      case "C": {
        const [a, b, c, e, f, g] = read(6);
        const p0 = [x, y], p1 = [a + dx, b + dy], p2 = [c + dx, e + dy], p3 = [f + dx, g + dy];
        const steps = Math.max(4, Math.ceil(polygonLength([p0, p1, p2, p3]) / TOLERANCE));
        for (let s = 1; s <= steps; s += 1) lineTo(...cubicAt(p0, p1, p2, p3, s / steps));
        cubicControl = p2; quadControl = null;
        break;
      }
      case "S": {
        const [c, e, f, g] = read(4);
        const p0 = [x, y];
        const p1 = cubicControl ? [2 * x - cubicControl[0], 2 * y - cubicControl[1]] : p0;
        const p2 = [c + dx, e + dy], p3 = [f + dx, g + dy];
        const steps = Math.max(4, Math.ceil(polygonLength([p0, p1, p2, p3]) / TOLERANCE));
        for (let s = 1; s <= steps; s += 1) lineTo(...cubicAt(p0, p1, p2, p3, s / steps));
        cubicControl = p2; quadControl = null;
        break;
      }
      case "Q": {
        const [a, b, c, e] = read(4);
        const p0 = [x, y], p1 = [a + dx, b + dy], p2 = [c + dx, e + dy];
        const steps = Math.max(4, Math.ceil(polygonLength([p0, p1, p2]) / TOLERANCE));
        for (let s = 1; s <= steps; s += 1) lineTo(...quadAt(p0, p1, p2, s / steps));
        quadControl = p1; cubicControl = null;
        break;
      }
      case "T": {
        const [c, e] = read(2);
        const p0 = [x, y];
        const p1 = quadControl ? [2 * x - quadControl[0], 2 * y - quadControl[1]] : p0;
        const p2 = [c + dx, e + dy];
        const steps = Math.max(4, Math.ceil(polygonLength([p0, p1, p2]) / TOLERANCE));
        for (let s = 1; s <= steps; s += 1) lineTo(...quadAt(p0, p1, p2, s / steps));
        quadControl = p1; cubicControl = null;
        break;
      }
      case "A": {
        const [rx, ry, rotation, largeArc, sweep, a, b] = read(7);
        const to = [a + dx, b + dy];
        if (largeArc !== 0 && largeArc !== 1) throw new Error(`path data: the arc large-arc flag is ${largeArc}, not 0 or 1`);
        if (sweep !== 0 && sweep !== 1) throw new Error(`path data: the arc sweep flag is ${sweep}, not 0 or 1`);
        for (const point of arcPoints([x, y], to, rx, ry, (rotation * Math.PI) / 180, largeArc, sweep)) lineTo(...point);
        cubicControl = quadControl = null;
        break;
      }
      case "Z": {
        if (current && (x !== startX || y !== startY)) lineTo(startX, startY);
        x = startX; y = startY;
        cubicControl = quadControl = null;
        break;
      }
      default: throw new Error(`path data: "${command}" is not a command this renderer knows`);
    }
  }
  return subpaths;
}

/* ---------------------------------------------------------------- distances */

/* Distance from a point to a segment, which is what a round stroke of constant
   width is: the set of points within half the width of the centreline. Round
   caps and round joins then need no special case, because the distance to the
   end of a segment and to a corner between two of them falls out of the same
   measurement. */
function segmentDistance(px, py, x1, y1, x2, y2) {
  const vx = x2 - x1, vy = y2 - y1;
  const lengthSquared = vx * vx + vy * vy;
  const t = lengthSquared ? Math.min(1, Math.max(0, ((px - x1) * vx + (py - y1) * vy) / lengthSquared)) : 0;
  return Math.hypot(px - (x1 + t * vx), py - (y1 + t * vy));
}

const polylineDistance = (segments) => (px, py) => {
  let best = Infinity;
  for (const [x1, y1, x2, y2] of segments) best = Math.min(best, segmentDistance(px, py, x1, y1, x2, y2));
  return best;
};

const roundedRectDistance = (x, y, w, h, r) => {
  const cx = x + w / 2, cy = y + h / 2, hw = w / 2, hh = h / 2, radius = Math.min(r, hw, hh);
  return (px, py) => {
    const qx = Math.abs(px - cx) - (hw - radius);
    const qy = Math.abs(py - cy) - (hh - radius);
    return Math.hypot(Math.max(qx, 0), Math.max(qy, 0)) + Math.min(Math.max(qx, qy), 0) - radius;
  };
};

const segmentsOf = (points) => {
  const segments = [];
  for (let i = 1; i < points.length; i += 1) segments.push([...points[i - 1], ...points[i]]);
  return segments;
};

/* --------------------------------------------------------------- the drawing */

const number = (attrs, key, fallback, where) => {
  if (attrs[key] === undefined) return fallback;
  const value = Number(attrs[key]);
  if (!Number.isFinite(value)) throw new Error(`${where}: ${key}="${attrs[key]}" is not a plain number`);
  return value;
};

/* A partly transparent shape is the one place a percentage would slip through
   as NaN, and NaN alpha paints nothing at all, so it is rejected here. */
function opacityOf(style, keys, where) {
  let alpha = 1;
  for (const key of keys) {
    const raw = style[key];
    if (raw === undefined || raw === "") continue;
    const value = Number(raw);
    if (!Number.isFinite(value) || value < 0 || value > 1) throw new Error(`${where}: ${key}="${raw}" is not a number from 0 to 1`);
    alpha *= value;
  }
  return alpha;
}

function fillPrimitive(distance, style, where) {
  const color = parseColor(style.fill, `${where} fill`);
  if (!color) return null;
  return { distance, color, alpha: opacityOf(style, ["opacity", "fill-opacity"], where) };
}

/* closedShape is for a rectangle, whose corners are drawn by rx: an outline of
   one has no caps to shape and no join that is not already an arc, so the butt
   and miter defaults are the same picture. */
function strokePrimitive(distance, style, where, closedShape = false) {
  const color = parseColor(style.stroke, `${where} stroke`);
  if (!color) return null;
  if (!closedShape && (style["stroke-linecap"] !== "round" || style["stroke-linejoin"] !== "round")) {
    throw new Error(`${where}: only round caps and round joins are supported (this renderer measures distance to the centreline)`);
  }
  const width = Number(style["stroke-width"]);
  if (!Number.isFinite(width) || width <= 0) throw new Error(`${where}: stroke-width="${style["stroke-width"]}" is not a positive number`);
  return { distance: (px, py) => Math.abs(distance(px, py)) - width / 2, color, alpha: opacityOf(style, ["opacity", "stroke-opacity"], where) };
}

function parseSvg(svg) {
  const clean = svg.replace(/<!--[\s\S]*?-->/g, "");
  const primitives = [];
  const stack = [{ ...DEFAULTS }];
  const viewBox = /<svg[^>]*\sviewBox\s*=\s*"([^"]*)"/.exec(clean);
  if (!viewBox) throw new Error("the root <svg> has no viewBox, so there is no scale to draw at");
  const box = viewBox[1].trim().split(/[\s,]+/).map(Number);
  if (box.length !== 4 || box.some((v) => !Number.isFinite(v))) throw new Error(`viewBox="${viewBox[1]}" is not four numbers`);
  if (box[2] !== box[3]) throw new Error(`viewBox is not square (${box[2]} by ${box[3]}), so one frame size cannot fit both sides`);
  const view = { x: box[0], y: box[1], size: box[2] };

  const re = /<(\/?)([a-zA-Z][\w:-]*)([^>]*?)(\/?)>/g;
  let match;
  while ((match = re.exec(clean))) {
    const closing = match[1] === "/";
    const name = match[2].toLowerCase();
    const attrs = attrsOf(match[3]);
    if (name === "svg" || name === "g") {
      if (closing) { if (stack.length > 1) stack.pop(); }
      else stack.push(styleFrom(attrs, stack[stack.length - 1], `<${name}>`));
      continue;
    }
    if (name === "title" || name === "desc" || closing) continue;
    const style = styleFrom(attrs, stack[stack.length - 1], `<${name}>`);
    if (name === "rect") {
      const where = "<rect>";
      const x = number(attrs, "x", 0, where), y = number(attrs, "y", 0, where);
      const w = number(attrs, "width", 0, where), h = number(attrs, "height", 0, where);
      if (w <= 0 || h <= 0) throw new Error(`${where}: width and height have to be positive`);
      const radius = attrs.rx !== undefined ? number(attrs, "rx", 0, where) : number(attrs, "ry", 0, where);
      const shape = roundedRectDistance(x, y, w, h, radius);
      const filled = fillPrimitive(shape, style, where);
      const stroked = strokePrimitive(shape, style, where, true);
      /* A filled and stroked rectangle paints the fill first, then the stroke. */
      if (filled) primitives.push(filled);
      if (stroked) primitives.push(stroked);
      continue;
    }
    if (name === "path") {
      const where = `<path d="${(attrs.d || "").slice(0, 24)}...">`;
      if (style.fill !== "none") {
        throw new Error(`${where}: this renderer strokes paths and fills rectangles, so a filled path would come out wrong`);
      }
      const subpaths = subpathsOf(attrs.d || "");
      if (!subpaths.length) throw new Error(`${where}: no path data`);
      for (const points of subpaths) {
        /* Stroke the subpath as it is written. A close command already put the
           starting point back on the end of it, so the closing segment is the
           last one and there is nothing to add here. */
        const stroked = strokePrimitive(polylineDistance(segmentsOf(points)), style, where);
        if (stroked) primitives.push(stroked);
      }
      continue;
    }
    throw new Error(`<${name}> is not a shape this renderer draws; it knows svg, g, title, desc, rect and path`);
  }
  if (!primitives.length) throw new Error("nothing in it would be drawn");
  return { view, primitives };
}

/* ------------------------------------------------------------------ raster */

/* How much of a pixel a shape covers decides its alpha. The distance test is
   exact, so a pixel whose centre is further from the edge than its own corner
   is entirely inside or entirely outside and needs no sampling at all; only
   pixels the edge passes through are sampled. That matters because the shapes
   here are thin: at 16px the 2.5 unit strokes are 1.25 pixels wide, so a single
   sample at the centre would round them away, and sampling every pixel at this
   resolution costs seconds. */
const SAMPLES = 16;
const SUBSAMPLES = SAMPLES * SAMPLES;
const HALF_DIAGONAL = Math.SQRT1_2; /* half of sqrt(2), the corner distance of a unit pixel */

function render(size, view, primitives) {
  const scale = size / view.size;
  const margin = HALF_DIAGONAL / scale; /* in user units, the reach of one pixel from its centre */
  const pixels = Buffer.alloc(size * size * 4);
  for (let py = 0; py < size; py += 1) {
    for (let px = 0; px < size; px += 1) {
      const centreX = view.x + (px + 0.5) / scale;
      const centreY = view.y + (py + 0.5) / scale;
      let r = 0, g = 0, b = 0, a = 0;
      for (const primitive of primitives) {
        const atCentre = primitive.distance(centreX, centreY);
        let coverage;
        if (atCentre >= margin) continue; /* the whole pixel is outside */
        if (atCentre <= -margin) coverage = 1; /* and here, the whole pixel is inside */
        else {
          let inside = 0;
          for (let sy = 0; sy < SAMPLES; sy += 1) {
            const uy = view.y + (py + (sy + 0.5) / SAMPLES) / scale;
            for (let sx = 0; sx < SAMPLES; sx += 1) {
              if (primitive.distance(view.x + (px + (sx + 0.5) / SAMPLES) / scale, uy) <= 0) inside += 1;
            }
          }
          coverage = inside / SUBSAMPLES;
        }
        const alpha = primitive.alpha * coverage;
        if (alpha <= 0) continue;
        const [sr, sg, sb] = primitive.color;
        const next = alpha + a * (1 - alpha);
        r = (sr * alpha + r * a * (1 - alpha)) / next;
        g = (sg * alpha + g * a * (1 - alpha)) / next;
        b = (sb * alpha + b * a * (1 - alpha)) / next;
        a = next;
      }
      const i = (py * size + px) * 4;
      pixels[i] = Math.round(Math.min(255, Math.max(0, r)));
      pixels[i + 1] = Math.round(Math.min(255, Math.max(0, g)));
      pixels[i + 2] = Math.round(Math.min(255, Math.max(0, b)));
      pixels[i + 3] = Math.round(Math.min(255, Math.max(0, a * 255)));
    }
  }
  return pixels;
}

/* --------------------------------------------------------------------- PNG */

const CRC_TABLE = (() => {
  const table = new Int32Array(256);
  for (let n = 0; n < 256; n += 1) {
    let c = n;
    for (let k = 0; k < 8; k += 1) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    table[n] = c;
  }
  return table;
})();

function crc32(buffer) {
  let c = -1;
  for (const byte of buffer) c = CRC_TABLE[(c ^ byte) & 0xff] ^ (c >>> 8);
  return (c ^ -1) >>> 0;
}

const PNG_SIGNATURE = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);

function chunk(type, data) {
  const head = Buffer.alloc(8);
  head.writeUInt32BE(data.length, 0);
  head.write(type, 4, "ascii");
  const crc = Buffer.alloc(4);
  crc.writeUInt32BE(crc32(Buffer.concat([head.subarray(4), data])), 0);
  return Buffer.concat([head, data, crc]);
}

function encodePng(size, pixels) {
  const stride = size * 4;
  const raw = Buffer.alloc((stride + 1) * size);
  for (let y = 0; y < size; y += 1) {
    raw[y * (stride + 1)] = 0; /* filter type 0: the rows are mostly flat, so zlib does the work */
    pixels.copy(raw, y * (stride + 1) + 1, y * stride, (y + 1) * stride);
  }
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(size, 0);
  ihdr.writeUInt32BE(size, 4);
  ihdr[8] = 8;  /* bit depth */
  ihdr[9] = 6;  /* colour type: truecolour with alpha */
  return Buffer.concat([
    PNG_SIGNATURE,
    chunk("IHDR", ihdr),
    chunk("IDAT", deflateSync(raw, { level: 9 })),
    chunk("IEND", Buffer.alloc(0)),
  ]);
}

const paeth = (a, b, c) => {
  const p = a + b - c;
  const pa = Math.abs(p - a), pb = Math.abs(p - b), pc = Math.abs(p - c);
  return pa <= pb && pa <= pc ? a : pb <= pc ? b : c;
};

/* Decoding exists so --check can compare the committed icon by pixels rather
   than by bytes: the compressed bytes depend on the zlib build, the picture
   does not. It handles what this script and a browser canvas both write, which
   is 8-bit truecolour, not interlaced. */
function decodePng(buffer, from) {
  if (!buffer.subarray(0, 8).equals(PNG_SIGNATURE)) throw new Error(`${from}: not a PNG`);
  let offset = 8;
  let header = null;
  const idat = [];
  while (offset + 8 <= buffer.length) {
    const length = buffer.readUInt32BE(offset);
    const type = buffer.toString("ascii", offset + 4, offset + 8);
    const data = buffer.subarray(offset + 8, offset + 8 + length);
    if (type === "IHDR") {
      header = { width: data.readUInt32BE(0), height: data.readUInt32BE(4), depth: data[8], color: data[9], interlace: data[12] };
    } else if (type === "IDAT") idat.push(data);
    else if (type === "IEND") break;
    offset += 12 + length;
  }
  if (!header) throw new Error(`${from}: no IHDR chunk`);
  if (header.depth !== 8 || header.interlace !== 0 || (header.color !== 6 && header.color !== 2)) {
    throw new Error(`${from}: only 8-bit truecolour PNG frames can be read, found depth ${header.depth}, colour type ${header.color}, interlace ${header.interlace}`);
  }
  const channels = header.color === 6 ? 4 : 3;
  const raw = inflateSync(Buffer.concat(idat));
  const stride = header.width * channels;
  const pixels = Buffer.alloc(header.width * header.height * 4);
  let previous = Buffer.alloc(stride);
  for (let y = 0; y < header.height; y += 1) {
    const start = y * (stride + 1);
    const filter = raw[start];
    const line = Buffer.from(raw.subarray(start + 1, start + 1 + stride));
    for (let i = 0; i < stride; i += 1) {
      const left = i >= channels ? line[i - channels] : 0;
      const up = previous[i];
      const upLeft = i >= channels ? previous[i - channels] : 0;
      if (filter === 1) line[i] = (line[i] + left) & 0xff;
      else if (filter === 2) line[i] = (line[i] + up) & 0xff;
      else if (filter === 3) line[i] = (line[i] + ((left + up) >> 1)) & 0xff;
      else if (filter === 4) line[i] = (line[i] + paeth(left, up, upLeft)) & 0xff;
      else if (filter !== 0) throw new Error(`${from}: row ${y} uses filter ${filter}, which PNG does not define`);
    }
    for (let x = 0; x < header.width; x += 1) {
      const to = (y * header.width + x) * 4;
      pixels[to] = line[x * channels];
      pixels[to + 1] = line[x * channels + 1];
      pixels[to + 2] = line[x * channels + 2];
      pixels[to + 3] = channels === 4 ? line[x * channels + 3] : 255;
    }
    previous = line;
  }
  return { size: header.width, pixels };
}

/* --------------------------------------------------------------------- ICO */

/* An ICO is a header, a directory with one 16-byte entry per frame, then the
   frame data. A frame of 256 pixels or more is written as 0, and each entry
   says how many bytes of it follow and where they start. */
function encodeIco(frames) {
  const header = Buffer.alloc(6);
  header.writeUInt16LE(0, 0);  /* reserved */
  header.writeUInt16LE(1, 2);  /* type 1: icon */
  header.writeUInt16LE(frames.length, 4);
  const directory = Buffer.alloc(16 * frames.length);
  let offset = header.length + directory.length;
  frames.forEach((frame, index) => {
    const entry = directory.subarray(index * 16, (index + 1) * 16);
    entry[0] = frame.size >= 256 ? 0 : frame.size;
    entry[1] = frame.size >= 256 ? 0 : frame.size;
    entry[2] = 0; /* palette size, unused at 32 bits per pixel */
    entry[3] = 0;
    entry.writeUInt16LE(1, 4);   /* colour planes */
    entry.writeUInt16LE(32, 6);  /* bits per pixel */
    entry.writeUInt32LE(frame.png.length, 8);
    entry.writeUInt32LE(offset, 12);
    offset += frame.png.length;
  });
  return Buffer.concat([header, directory, ...frames.map((frame) => frame.png)]);
}

function readIco(buffer, from) {
  if (buffer.length < 6 || buffer.readUInt16LE(0) !== 0 || buffer.readUInt16LE(2) !== 1) {
    throw new Error(`${from}: not an ICO file`);
  }
  const count = buffer.readUInt16LE(4);
  const frames = [];
  for (let index = 0; index < count; index += 1) {
    const entry = buffer.subarray(6 + index * 16, 22 + index * 16);
    const length = entry.readUInt32LE(8);
    const offset = entry.readUInt32LE(12);
    const size = entry[0] || 256;
    frames.push({ size, ...decodePng(buffer.subarray(offset, offset + length), `${from} frame ${index}`) });
  }
  return frames;
}

/* -------------------------------------------------------------------- main */

/* Two frames are compared by what a screen would show, which is each colour
   multiplied by its own alpha. The stored colour of a nearly transparent pixel
   is the renderer's business alone, and for --check both sides come out of this
   same code, so the comparison is exact either way. */
function compare(stored, fresh) {
  if (stored.length !== fresh.length) return `it holds ${stored.length} frame(s) and the SVG renders ${fresh.length}`;
  for (let index = 0; index < fresh.length; index += 1) {
    const before = stored[index], after = fresh[index];
    if (before.size !== after.size) return `frame ${index} is ${before.size}px where the SVG renders ${after.size}px`;
    if (before.pixels.length !== after.pixels.length) return `the ${before.size}px frame holds ${before.pixels.length / 4} pixels and the SVG renders ${after.size * after.size}`;
    let differing = 0, worst = 0;
    for (let i = 0; i < after.pixels.length; i += 4) {
      let delta = Math.abs(before.pixels[i + 3] - after.pixels[i + 3]);
      for (let channel = 0; channel < 3; channel += 1) {
        const shown = Math.round((before.pixels[i + channel] * before.pixels[i + 3]) / 255);
        const then = Math.round((after.pixels[i + channel] * after.pixels[i + 3]) / 255);
        delta = Math.max(delta, Math.abs(shown - then));
      }
      if (delta) { differing += 1; worst = Math.max(worst, delta); }
    }
    if (differing) return `frame ${before.size} differs in ${differing} of ${after.size * after.size} pixels, by up to ${worst} of 255`;
  }
  return null;
}

/* A refusal from the renderer is a message about the SVG, not a crash, so the
   reason is printed on its own line and the drawing is left alone. */
try {
  const svg = await readFile(SVG, "utf8").catch(() => fail(`${SVG} could not be read`));
  const { view, primitives } = parseSvg(svg);

  const rendered = FRAMES.map((size) => ({ size, pixels: render(size, view, primitives) }));
  const frames = rendered.map((frame) => ({ size: frame.size, png: encodePng(frame.size, frame.pixels) }));

  /* Read the frames back before writing them, so a file no browser could open
     is never the one that lands on disk. */
  for (const frame of frames) {
    const back = decodePng(frame.png, "the frame just encoded");
    if (back.size !== frame.size || !back.pixels.equals(rendered.find((r) => r.size === frame.size).pixels)) {
      fail(`the ${frame.size}px frame did not survive its own PNG round trip, so nothing was written`);
    }
  }

  const ico = encodeIco(frames);
  const existing = await readFile(OUT).then((buffer) => buffer, () => null);

  if (process.argv.includes("--check")) {
    if (!existing) fail(`${OUT} does not exist. Run without --check to build it.`);
    const difference = compare(readIco(existing, OUT), rendered);
    if (difference) fail(`${OUT} no longer matches ${SVG}: ${difference}.\nRun without --check to rebuild it.`);
    const stale = [];
    for (const copy of COPIES) {
      const there = await readFile(copy).catch(() => null);
      if (there && !there.equals(ico)) stale.push(copy);
    }
    for (const copy of SVG_COPIES) {
      const there = await readFile(copy, "utf8").catch(() => null);
      if (there !== null && !sameText(there, svg)) stale.push(copy);
    }
    if (stale.length) fail(`These copies of the icon are out of date: ${stale.join(", ")}.\nRun without --check to rebuild them.`);
    console.log(`${OUT} matches ${SVG} (frames ${FRAMES.join(", ")}px).`);
  } else {
    if (existing) {
      const difference = compare(readIco(existing, `${OUT} (the file being replaced)`), rendered);
      console.log(difference
        ? `The icon on disk differed: ${difference}.`
        : `The icon on disk was already what ${SVG} renders.`);
    }
    await writeFile(OUT, ico);
    console.log(`Wrote ${OUT}: ${frames.length} frames (${FRAMES.join(", ")}px), ${ico.length} bytes.`);
    /* The booth and the static snapshot deploy on their own and each keeps its
       own copy of the icon, so they are refreshed here rather than left to
       drift. A folder that is not in the checkout is not created. */
    let refreshed = 0;
    for (const copy of COPIES) {
      const there = await readFile(copy).catch(() => null);
      if (there && !there.equals(ico)) { await writeFile(copy, ico); refreshed += 1; }
    }
    for (const copy of SVG_COPIES) {
      const there = await readFile(copy, "utf8").catch(() => null);
      if (there !== null && !sameText(there, svg)) { await writeFile(copy, svg); refreshed += 1; }
    }
    if (refreshed) console.log(`Refreshed ${refreshed} copy of the icon: ${[...COPIES, ...SVG_COPIES].join(", ")}.`);
  }
} catch (error) {
  fail(`${SVG}: ${error.message}`);
}
