#!/usr/bin/env node
/*
 * Snapshot the public marketing pages into a folder a static host can serve.
 *
 * The real app is PHP and MySQL, which Vercel cannot run, so this is what makes
 * the landing page live there: it fetches the rendered pages from a running
 * local server, rewrites the links to the pages that exist in the snapshot, and
 * copies the stylesheet and script. The exported pages are generated output,
 * not hand-edited copies, so re-run this after changing the app.
 *
 *   php -S 127.0.0.1:8123 router.php      # in one terminal
 *   node tools/export-static.mjs          # then this
 *
 * Usage: node tools/export-static.mjs [base] [outDir]
 *   base    defaults to http://127.0.0.1:8123
 *   outDir  defaults to site-preview
 */

import { mkdir, writeFile, copyFile } from "node:fs/promises";
import { dirname, join } from "node:path";

const base = (process.argv[2] || "http://127.0.0.1:8123").replace(/\/+$/, "");
const out = process.argv[3] || "site-preview";

/* The booth is already live on Vercel, so the snapshot sends people there for
   the one thing it cannot do itself. */
const boothUrl = "https://pixelpod-booth.vercel.app";

const pages = [
  ["index.php", "index.html"],
  ["services.php", "services.html"],
  ["customize.php", "customize.html"],
  ["contact.php", "contact.html"],
];

const asset = (name) => `${base}/public/${name}`;
const escapeRe = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

/* Booking, login, register and the dashboard all need PHP and a session, so
   they cannot exist in a snapshot: booking requests land on the contact page,
   and the account buttons are dropped rather than left pointing at a 404. */
function rewrite(html) {
  return html
    .replaceAll(asset("photobooth/index.php"), boothUrl)
    .replaceAll(asset("css/style.css"), "css/style.css")
    .replaceAll(asset("js/main.js"), "js/main.js")
    .replaceAll(asset("index.php"), "index.html")
    .replaceAll(asset("services.php"), "services.html")
    .replaceAll(asset("customize.php"), "customize.html")
    .replaceAll(asset("contact.php"), "contact.html")
    .replaceAll(new RegExp(`${escapeRe(asset("booking.php"))}(\\?[^"']*)?`, "g"), "contact.html")
    .replaceAll(new RegExp(`\\s*<a href="${escapeRe(base)}/public/(?:login|register)\\.php"[^>]*>[^<]*</a>`, "g"), "")
    .replaceAll(new RegExp(`${escapeRe(base)}/public/[\\w/.-]*\\.php`, "g"), "index.html")
    /* Root-relative leftovers, which come from JavaScript that builds its own
       URLs: customize.php assembles a booking link from a string. */
    .replaceAll(/\/public\/booking\.php(\?[^"']*)?/g, "contact.html")
    .replaceAll(/\/public\/[\w/.-]+\.php/g, "index.html")
    .replaceAll("/public/", "");
}

const failures = [];

for (const [from, to] of pages) {
  const url = `${base}/public/${from}`;
  let html;
  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    html = await response.text();
  } catch (error) {
    failures.push(`${from}: ${error.message}`);
    continue;
  }
  const target = join(out, to);
  await mkdir(dirname(target), { recursive: true });
  await writeFile(target, rewrite(html));
  const leftover = html.match(new RegExp(`${escapeRe(base)}/public/`, "g"));
  console.log(`  ${to.padEnd(16)} ${String(html.length).padStart(6)} bytes  ${leftover ? leftover.length + " link(s) rewritten" : ""}`);
}

await mkdir(join(out, "css"), { recursive: true });
await mkdir(join(out, "js"), { recursive: true });
await copyFile("public/css/style.css", join(out, "css/style.css"));
await copyFile("public/js/main.js", join(out, "js/main.js"));

if (failures.length) {
  console.error(`\nThese pages could not be fetched, so is the app running on ${base}?\n  ${failures.join("\n  ")}`);
  process.exit(1);
}
console.log(`\nSnapshot written to ${out}/. Serve it with: npx --yes vercel@latest deploy --prod --yes ${out}`);
