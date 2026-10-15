/*
 * The camera half of the booth: one stream for the whole session, one frame per
 * shot, mirrored and finished with the chosen look.
 *
 * The constraints are the ones the PHP page asked for, unchanged, including the
 * ideal 1280x960: the strip geometry is built to the frame's own aspect ratio,
 * so asking for 4:3 is what keeps the strip proportions right.
 */

import { applyToCanvas } from './filters.js'

export const CONSTRAINTS = {
  video: {
    width: { ideal: 1280 },
    height: { ideal: 960 },
    facingMode: 'user',
  },
  audio: false,
}

/** The size a capture falls back to when the video has not reported one yet. */
const FALLBACK_WIDTH = 640
const FALLBACK_HEIGHT = 480

export class Camera {
  constructor(video) {
    this.video = video
    this.stream = null
  }

  get active() {
    return Boolean(this.stream)
  }

  /** Asks for the camera. Throws the browser's own error, as the page did. */
  async start() {
    this.stream = await navigator.mediaDevices.getUserMedia(CONSTRAINTS)
    this.video.srcObject = this.stream
    return this.stream
  }

  /** Every track, every time: a stream left running keeps the camera light on. */
  stop() {
    if (this.stream) {
      this.stream.getTracks().forEach((track) => track.stop())
      this.stream = null
    }
    this.video.srcObject = null
  }

  size() {
    return {
      width: this.video.videoWidth || FALLBACK_WIDTH,
      height: this.video.videoHeight || FALLBACK_HEIGHT,
    }
  }

  /**
   * One shot, as a canvas of its own.
   *
   * `scratch` is the hidden canvas the page always had. The frame is drawn into
   * it mirrored, the look is baked in through the same arithmetic the preview
   * uses, and the result is copied out so the next shot can reuse the scratch
   * without disturbing this one.
   */
  grab(scratch, filterId) {
    const { width, height } = this.size()
    scratch.width = width
    scratch.height = height

    const ctx = scratch.getContext('2d')
    ctx.save()
    ctx.translate(width, 0)
    ctx.scale(-1, 1)
    ctx.drawImage(this.video, 0, 0, width, height)
    ctx.restore()

    applyToCanvas(scratch, filterId)

    const frame = document.createElement('canvas')
    frame.width = width
    frame.height = height
    frame.getContext('2d').drawImage(scratch, 0, 0)
    return frame
  }
}
