import './bootstrap';

import Alpine from 'alpinejs';
import { galleryDownloadState } from './gallery-download';

window.Alpine = Alpine;
window.galleryDownloadState = galleryDownloadState;

Alpine.start();
