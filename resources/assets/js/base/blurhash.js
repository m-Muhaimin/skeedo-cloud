'use strict';

import { decodeBlurHash, getBlurHashAverageColor } from 'fast-blurhash';

export class BlurHash extends HTMLCanvasElement {
    static observedAttributes = [
        'hash',
        'width',
        'height',
    ];

    constructor() {
        super();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.render();
    }

    render() {
        let hash = this.getAttribute('hash');
        let width = parseInt(this.getAttribute('width'), 10);
        let height = parseInt(this.getAttribute('height'), 10);
        let type = this.getAttribute('type') || 'blurhash';

        if (!hash || !width || !height) {
            return;
        }

        // draw it on canvas
        const ctx = this.getContext('2d');

        if (type === 'color') {
            // get average color of blurHash
            const color = getBlurHashAverageColor(hash, width, height);

            ctx.fillStyle = `rgb(${color[0]}, ${color[1]}, ${color[2]})`;
            ctx.fillRect(0, 0, width, height);
        } else {
            // decode blurHash image
            const pixels = decodeBlurHash(hash, width, height);

            const imageData = ctx.createImageData(width, height);
            imageData.data.set(pixels);
            ctx.putImageData(imageData, 0, 0);
        }
    }
}