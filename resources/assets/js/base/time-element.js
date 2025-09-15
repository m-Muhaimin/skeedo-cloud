'use strict';

/**
 * Usage: 
 * <x-time datetime="{timestamp}" type="[datetime|date|time]"></x-time>
 */
export class TimeElement extends HTMLElement {
    static observedAttributes = ["datetime", "timezone"];

    constructor() {
        super();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.render();
    }

    render() {
        let timestamp = this.getAttribute('datetime') || this.textContent;
        if (!timestamp) return;

        if ((parseInt(timestamp, 10).toString() === timestamp) && timestamp.length === 10) {
            timestamp = parseInt(timestamp, 10) * 1000;
        }

        let timezone = this.getAttribute('timezone') || this.dataset.timezone || null;
        let date = new Date(timestamp);

        if (isNaN(date.getTime())) {
            return;
        }

        let type = this.getAttribute('type') || this.dataset.type || 'datetime';
        let lang = this.lang || document.documentElement.lang || 'en';
        let diff = Math.abs(Date.now() - timestamp);
        let format = {
            month: 'long',
            day: 'numeric',
        };

        if (timezone) {
            format.timeZone = timezone;
        }

        if (type == 'time') {
            format = {
                hour: '2-digit',
                minute: '2-digit',
            };
        } else if (type == 'date') {
            if (diff >= 86400000 * 365) {
                format.month = 'short'
                format.year = 'numeric';
            }
        } else {
            if (diff >= 86400000 * 365) {
                format.month = 'short'
                format.year = 'numeric';
            }

            format.hour = '2-digit';
            format.minute = '2-digit';
        }

        let formatter = new Intl.DateTimeFormat(lang, format);
        this.textContent = formatter.format(date);
        this.setAttribute('title', date.toUTCString());
    }
}