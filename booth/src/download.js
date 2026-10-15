/*
 * Getting the finished strip out of the tab.
 *
 * The PHP build answered this with a session code, a QR image fetched from
 * api.qrserver.com and a link to the first saved photo. There is no server now,
 * so the file is named here, attached to the download link the result screen
 * already showed, and offered to the system share sheet when the browser can
 * take a file at all.
 */

/** `pixelpod-strip-2026-10-15.png`, dated from the visitor's own clock. */
export function fileName(kind, date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `pixelpod-${kind}-${year}-${month}-${day}.png`
}

function asFile(blob, name) {
  return new File([blob], name, { type: 'image/png' })
}

/** Safari only learned to share files in 15, so the button waits for this. */
export function canShare(blob, name) {
  return (
    typeof navigator !== 'undefined' &&
    typeof navigator.canShare === 'function' &&
    navigator.canShare({ files: [asFile(blob, name)] })
  )
}

/**
 * Points a link at the blob and returns the object URL behind it, so the
 * caller can show the same image in the preview and revoke it on reset.
 */
export function setDownload(anchor, blob, name) {
  const url = URL.createObjectURL(blob)
  anchor.href = url
  anchor.download = name
  return url
}

export function releaseDownload(url) {
  if (url) URL.revokeObjectURL(url)
}

/**
 * Hands the strip to the share sheet. A visitor who cancels the sheet rejects
 * the promise, which is a decision rather than a failure, so it is reported as
 * "not shared" instead of being thrown at the page.
 */
export async function share(blob, name, title = 'A PixelPod strip') {
  if (!canShare(blob, name)) return false
  try {
    await navigator.share({ files: [asFile(blob, name)], title })
    return true
  } catch {
    return false
  }
}
