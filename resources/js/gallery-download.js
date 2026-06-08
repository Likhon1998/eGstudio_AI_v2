import { encodeCanvasToBlob, imageToCanvas } from './gallery-image-encoders';

/**
 * Shared gallery image download picker.
 * All formats convert in the browser (no server GD required).
 */
export function galleryDownloadState() {
    const BROWSER_FORMATS = new Set(['jpeg', 'jpg', 'png', 'webp', 'gif', 'bmp', 'svg']);

    return {
        downloadPickerOpen: false,
        downloadImageUrl: '',
        downloadBaseName: 'gallery-image',
        isDownloading: false,
        downloadFormats: [
            { id: 'jpeg', label: 'JPEG', hint: '.jpg' },
            { id: 'jpg', label: 'JPG', hint: '.jpg' },
            { id: 'png', label: 'PNG', hint: 'lossless' },
            { id: 'webp', label: 'WebP', hint: 'modern' },
            { id: 'gif', label: 'GIF', hint: 'single frame' },
            { id: 'bmp', label: 'BMP', hint: 'bitmap' },
            { id: 'svg', label: 'SVG', hint: 'vector wrap' },
        ],

        openDownloadPicker(url, baseName) {
            if (!url) {
                this.galleryNotify('No image available to download.', 'error');
                return;
            }
            this.downloadImageUrl = url;
            this.downloadBaseName = baseName || 'gallery-image';
            this.downloadPickerOpen = true;
        },

        galleryNotify(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
        },

        safeFilename(base, ext) {
            const safe = (base || 'gallery-image')
                .replace(/[^a-z0-9_\-]+/gi, '-')
                .replace(/^-+|-+$/g, '') || 'gallery-image';
            return `${safe}.${ext}`;
        },

        triggerBlobDownload(blob, filename) {
            const blobUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);
        },

        async convertInBrowser(url, format) {
            const canvas = await imageToCanvas(url);
            return encodeCanvasToBlob(canvas, format);
        },

        async downloadInFormat(format) {
            if (!this.downloadImageUrl || this.isDownloading) {
                return;
            }

            this.isDownloading = true;
            this.galleryNotify('Preparing download…', 'info');

            const ext = format === 'jpg' || format === 'jpeg' ? 'jpg' : format;
            const filename = this.safeFilename(this.downloadBaseName, ext);

            try {
                if (!BROWSER_FORMATS.has(format)) {
                    throw new Error('Unknown download format.');
                }

                const blob = await this.convertInBrowser(this.downloadImageUrl, format);
                this.triggerBlobDownload(blob, filename);
                this.downloadPickerOpen = false;
                this.galleryNotify('Download started', 'success');
            } catch (err) {
                console.warn('Gallery download failed', err);
                this.galleryNotify(err?.message || 'Download failed. Try JPEG or PNG.', 'error');
            } finally {
                this.isDownloading = false;
            }
        },
    };
}

window.galleryDownloadState = galleryDownloadState;
