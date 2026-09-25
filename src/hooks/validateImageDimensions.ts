import type { CollectionBeforeValidateHook } from 'payload'
import { APIError } from 'payload'
import sharp from 'sharp'

/** Caps decoded pixel count per docs/CONTENT_MODEL.md ("cap decoded pixel count, e.g. 40 MP"). */
const MAX_DECODED_PIXELS = 40_000_000

/**
 * Re-decodes the raw uploaded bytes with sharp (not just trusting the
 * declared extension/mimetype) to confirm the file is really a
 * decodable raster image and to enforce the pixel-count cap. Runs in
 * addition to Payload's own built-in upload content-sniffing.
 */
export const validateImageDimensions: CollectionBeforeValidateHook = async ({ req }) => {
  const file = req.file
  if (!file?.data) return

  let width: number | undefined
  let height: number | undefined
  try {
    const metadata = await sharp(file.data).metadata()
    width = metadata.width
    height = metadata.height
  } catch {
    throw new APIError('This file could not be decoded as a supported image.', 400, null, true)
  }

  if (!width || !height) {
    throw new APIError('This file could not be decoded as a supported image.', 400, null, true)
  }

  if (width * height > MAX_DECODED_PIXELS) {
    throw new APIError(
      `Image is too large (${width}x${height} decoded pixels). Maximum is ${MAX_DECODED_PIXELS.toLocaleString()} pixels.`,
      400,
      null,
      true,
    )
  }
}
