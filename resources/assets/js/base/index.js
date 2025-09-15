`use strict`;

import { width } from './scrollbar.js';

// Import custom HTML elements
import helper from '../redirect.js';
import { Modal, ModalController } from './modal.js';
import { Toast } from './toast.js';
import { ModeSwitcher } from './mode-switcher.js';
import { Uuid } from './uuid.js';
import { CopyElement } from './copy-element.js';
import { TimeElement } from './time-element.js';
import { MoneyElement } from './money-element.js';
import { CreditElement } from './credit-element.js';
import { FormElement } from './form-element.js';
import { BlurHash } from './blurhash.js';
import { WaveElement } from './wave.js';
import { ChartElement } from './chart.js';
import { MarkdownElement } from './markdown-element.js';
import { FilesizeElement } from './filesize-element.js';
import { DropzoneElement } from './dropzone-element.js';
import { AvatarElement } from './avatar-element.js';
import { DatePickerElement } from './datepicker-element.js';
import { PhoneInputElement } from './phone-input-element.js';
import sc from './sidebar.js';
import { pins } from './pin.js';

// Define custom elements
customElements.define('mode-switcher', ModeSwitcher);
customElements.define('toast-message', Toast);
customElements.define('modal-element', Modal);
customElements.define('x-uuid', Uuid)
customElements.define('x-copy', CopyElement);
customElements.define('x-time', TimeElement);
customElements.define('x-money', MoneyElement);
customElements.define('x-credit', CreditElement);
customElements.define('x-form', FormElement);
customElements.define('x-blurhash', BlurHash, { extends: 'canvas' });
customElements.define('x-wave', WaveElement);
customElements.define('x-chart', ChartElement);
customElements.define('x-markdown', MarkdownElement);
customElements.define('x-filesize', FilesizeElement);
customElements.define('x-dropzone', DropzoneElement);
customElements.define('x-avatar', AvatarElement);
customElements.define('x-datepicker', DatePickerElement);
customElements.define('x-phone-input', PhoneInputElement);

// Define singletons for custom elements
window.modal = new ModalController();
window.sidebar = sc;
window.pins = pins

// Set scrollbar width 
document.body.style.setProperty(`--scrollbar-width`, `0px`);

if (document.body.scrollHeight > document.body.clientHeight) {
    document.body.style.setProperty(`--scrollbar-width`, `${width}px`);
}

helper.checkRedirectPath();