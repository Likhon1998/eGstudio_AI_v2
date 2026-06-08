/**
 * Load an image URL into a canvas (same-origin fetch avoids CORS taint).
 */
export async function imageToCanvas(url) {
    const response = await fetch(url, { credentials: 'same-origin' });
    if (!response.ok) {
        throw new Error('Could not load image.');
    }

    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);

    try {
        const img = await new Promise((resolve, reject) => {
            const el = new Image();
            el.onload = () => resolve(el);
            el.onerror = () => reject(new Error('Invalid image data.'));
            el.src = objectUrl;
        });

        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            throw new Error('Canvas is not available.');
        }

        ctx.drawImage(img, 0, 0);
        return canvas;
    } finally {
        URL.revokeObjectURL(objectUrl);
    }
}

/**
 * 24-bit BMP (Windows bitmap) from canvas pixels.
 */
export function encodeBmpFromCanvas(canvas) {
    const ctx = canvas.getContext('2d');
    const { width, height } = canvas;
    const { data } = ctx.getImageData(0, 0, width, height);

    const bytesPerPixel = 3;
    const rowSize = ((width * bytesPerPixel + 3) >> 2) << 2;
    const pixelDataSize = rowSize * height;
    const fileSize = 54 + pixelDataSize;
    const buffer = new ArrayBuffer(fileSize);
    const view = new DataView(buffer);

    // BITMAPFILEHEADER
    view.setUint8(0, 0x42);
    view.setUint8(1, 0x4d);
    view.setUint32(2, fileSize, true);
    view.setUint32(6, 0, true);
    view.setUint32(10, 54, true);

    // BITMAPINFOHEADER
    view.setUint32(14, 40, true);
    view.setInt32(18, width, true);
    view.setInt32(22, height, true);
    view.setUint16(26, 1, true);
    view.setUint16(28, 24, true);
    view.setUint32(30, 0, true);
    view.setUint32(34, pixelDataSize, true);
    view.setInt32(38, 2835, true);
    view.setInt32(42, 2835, true);
    view.setUint32(46, 0, true);
    view.setUint32(50, 0, true);

    let offset = 54;
    for (let y = height - 1; y >= 0; y--) {
        for (let x = 0; x < width; x++) {
            const i = (y * width + x) * 4;
            view.setUint8(offset++, data[i + 2]); // B
            view.setUint8(offset++, data[i + 1]); // G
            view.setUint8(offset++, data[i]);     // R
        }
        const padding = rowSize - width * bytesPerPixel;
        for (let p = 0; p < padding; p++) {
            view.setUint8(offset++, 0);
        }
    }

    return new Blob([buffer], { type: 'image/bmp' });
}

/**
 * SVG wrapper with embedded PNG (same approach as server export).
 */
export function encodeSvgFromCanvas(canvas) {
    const { width, height } = canvas;
    const pngDataUrl = canvas.toDataURL('image/png');
    const base64 = pngDataUrl.split(',')[1] || '';

    const svg =
        '<?xml version="1.0" encoding="UTF-8"?>'
        + `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"`
        + ` width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">`
        + `<image width="${width}" height="${height}" xlink:href="data:image/png;base64,${base64}"/>`
        + '</svg>';

    return new Blob([svg], { type: 'image/svg+xml' });
}

/**
 * Single-frame GIF via gifenc (palette-quantized).
 */
export async function encodeGifFromCanvas(canvas) {
    const { GIFEncoder, quantize, applyPalette } = await import('gifenc');

    const ctx = canvas.getContext('2d');
    const { width, height } = canvas;
    const { data } = ctx.getImageData(0, 0, width, height);

    const palette = quantize(data, 256);
    const index = applyPalette(data, palette);

    const gif = GIFEncoder();
    gif.writeFrame(index, width, height, { palette, delay: 0 });
    gif.finish();

    return new Blob([gif.bytes()], { type: 'image/gif' });
}

export async function encodeCanvasToBlob(canvas, format) {
    const normalized = format === 'jpg' ? 'jpeg' : format;

    if (normalized === 'gif') {
        return await encodeGifFromCanvas(canvas);
    }
    if (normalized === 'bmp') {
        return encodeBmpFromCanvas(canvas);
    }
    if (normalized === 'svg') {
        return encodeSvgFromCanvas(canvas);
    }

    const mime = normalized === 'jpeg' ? 'image/jpeg'
        : normalized === 'png' ? 'image/png'
        : normalized === 'webp' ? 'image/webp'
        : null;

    if (!mime) {
        throw new Error(`Format "${format}" is not supported in the browser.`);
    }

    const ctx = canvas.getContext('2d');
    if (mime === 'image/jpeg') {
        const flat = document.createElement('canvas');
        flat.width = canvas.width;
        flat.height = canvas.height;
        const flatCtx = flat.getContext('2d');
        flatCtx.fillStyle = '#ffffff';
        flatCtx.fillRect(0, 0, flat.width, flat.height);
        flatCtx.drawImage(canvas, 0, 0);
        return canvasToBlob(flat, mime);
    }

    return canvasToBlob(canvas, mime);
}

function canvasToBlob(canvas, mime) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => (blob ? resolve(blob) : reject(new Error('Conversion failed.'))),
            mime,
            mime === 'image/png' ? undefined : 0.92,
        );
    });
}
