'use strict';

import Alpine from 'alpinejs';

Alpine.data('datepicker', (options = {}) => ({
    options: {
        value: null,
        min: null,
        max: null,
        locale: 'en-US'
    },

    showDatepicker: false,
    datepickerValue: '',

    month: '',
    year: '',
    no_of_days: [],
    blankdays: [],
    selectedDate: null,

    init() {
        this.options = { ...this.options, ...options };

        if (this.options.min) {
            this.options.min = new Date(this.options.min * 1000);
        }

        if (this.options.max) {
            this.options.max = new Date(this.options.max * 1000);
        }

        this.selectedDate = this.options.value ? new Date(this.options.value * 1000) : new Date();
        this.month = this.selectedDate.getMonth();
        this.year = this.selectedDate.getFullYear();

        if (this.options.value) {
            this.setValue(this.selectedDate);
        }

        this.getNoOfDays();
    },

    handleFocusOut() {
        setTimeout(() => {
            const activeElement = document.activeElement;
            if (!this.$root.contains(activeElement)) {
                this.showDatepicker = false;
            }
        }, 0);
    },

    getMonthName(month) {
        const formatter = new Intl.DateTimeFormat(this.options.locale, { month: 'long' });
        const result = formatter.format(new Date(2024, month, 1));

        // Check if the result is not in M01 format
        if (!result.match(/^M\d{2}$/)) {
            return result;
        }

        // Ultimate fallback
        return new Intl.DateTimeFormat('en-US', { month: 'long' })
            .format(new Date(2024, month, 1));
    },

    getWeekDays() {
        const formatter = new Intl.DateTimeFormat(this.options.locale, { weekday: 'short' });

        return [...Array(7)].map((_, day) => {
            const result = formatter.format(new Date(2024, 0, day));

            // Check if the result is not in numeric format
            if (!result.match(/^\d+$/)) {
                return result;
            }

            // Fallback to English if we get numeric format
            return new Intl.DateTimeFormat('en-US', {
                weekday: 'short'
            }).format(new Date(2024, 0, day));
        });
    },

    isToday(date) {
        const today = new Date();
        const d = new Date(this.year, this.month, date);
        return today.toDateString() === d.toDateString();
    },

    isSelected(date) {
        if (!this.selectedDate) return false;
        const d = new Date(this.year, this.month, date);
        return this.selectedDate.toDateString() === d.toDateString();
    },

    isDisabled(date) {
        const d = new Date(this.year, this.month, date);

        if (this.options.min && d < this.options.min) return true;
        if (this.options.max && d > this.options.max) return true;

        return false;
    },

    selectDate(day) {
        // Create date in local timezone
        const localDate = new Date(this.year, this.month, day);

        if (this.isDisabled(localDate)) return;

        this.setValue(localDate);
        this.selectedDate = localDate;
        this.showDatepicker = false;
    },

    setValue(localDate) {
        // Create UTC midnight for storage
        const utcDate = new Date(Date.UTC(
            localDate.getFullYear(),
            localDate.getMonth(),
            localDate.getDate(),
            0, 0, 0, 0
        ));

        const timestamp = Math.floor(utcDate.getTime() / 1000);
        this.options.value = timestamp;
        this.$root.parentElement.setValue(timestamp);

        // For display: use local date
        let format = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        };

        const formatter = new Intl.DateTimeFormat(this.options.locale, format);
        const result = formatter.format(localDate);
        this.datepickerValue = result.match(/M\d+/)
            ? new Intl.DateTimeFormat('en-US', format).format(localDate)
            : result;
    },

    clearValue() {
        this.options.value = null;
        this.$root.parentElement.setValue(null);
        this.datepickerValue = '';
    },

    getNoOfDays() {
        let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();

        // Get the day of week for the first day of the month (0 = Sunday, 6 = Saturday)
        let dayOfWeek = new Date(this.year, this.month, 1).getDay();

        // Create array for blank days
        let blankdaysArray = [];
        for (var i = 0; i < dayOfWeek; i++) {
            blankdaysArray.push(i);
        }

        // Create array for days in month
        let daysArray = [];
        for (var i = 1; i <= daysInMonth; i++) {
            daysArray.push(i);
        }

        this.blankdays = blankdaysArray;
        this.no_of_days = daysArray;
    },

    isFirstMonth() {
        if (!this.options.min) return false;
        return this.month === this.options.min.getMonth() &&
            this.year === this.options.min.getFullYear();
    },

    isLastMonth() {
        if (!this.options.max) return false;
        return this.month === this.options.max.getMonth() &&
            this.year === this.options.max.getFullYear();
    },

    previousMonth() {
        // Don't go before min date
        if (this.options.min) {
            const newDate = new Date(this.year, this.month - 1, 1);
            if (newDate < this.options.min) return;
        }

        if (this.month === 0) {
            this.month = 11;
            this.year--;
        } else {
            this.month--;
        }

        this.getNoOfDays();
    },

    nextMonth() {
        // Don't go after max date
        if (this.options.max) {
            const newDate = new Date(this.year, this.month + 1, 1);
            if (newDate > this.options.max) return;
        }

        if (this.month === 11) {
            this.month = 0;
            this.year++;
        } else {
            this.month++;
        }
        this.getNoOfDays();
    }
}))

export class DatePickerElement extends HTMLElement {
    options = {
        value: null,
        min: null,
        max: null,
        locale: 'en-US'
    };

    static observedAttributes = [
        'value',
        'data-value',

        'min',
        'data-min',

        'max',
        'data-max',

        'placeholder',
        'lang'
    ];

    constructor() {
        super();
    }

    setValue(value) {
        this.options.value = value;

        this.dispatchEvent(new Event('change'));
    }

    getValue() {
        return this.options.value;
    }

    connectedCallback() {
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.render();
    }

    render() {
        this.options.value = this.getAttribute('value') || this.getAttribute('data-value') || null;
        this.options.min = this.getAttribute('min') || this.getAttribute('data-min') || null;
        this.options.max = this.getAttribute('max') || this.getAttribute('data-max') || null;
        this.options.locale = this.getAttribute('lang') || document.documentElement.lang || 'en';

        let placeholder = this.getAttribute('placeholder') || null;
        let data = JSON.stringify(this.options);

        this.innerHTML = `
            <div class="relative" x-data='datepicker(${data})' @click.outside="showDatepicker = false" @focusout="handleFocusOut">
                <div class="relative">
                    <input type="text" id="${this.id || ''}" class="pe-11 cursor-pointer input" readonly x-ref="input" placeholder="${placeholder || ''}" @focus="showDatepicker = true" @click="showDatepicker = true" x-model="datepickerValue"/>
                    <template x-if="options.value">
                        <button type="button" @click="clearValue()" class="block absolute end-3 top-1/2 text-xl -translate-y-1/2 ti ti-x text-content-dimmed hover:text-content"></button>
                    </template>

                    <template x-if="!options.value">
                        <i class="block absolute end-3 top-1/2 text-2xl -translate-y-1/2 pointer-events-none ti ti-calendar text-content-dimmed"></i>
                    </template>
        
                    <div class="p-4 w-72 text-base menu" :data-open="showDatepicker" tabindex="0" @mousedown.prevent @click="$refs.input.focus()">
                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-center">
                                <div class="flex gap-1 items-center text-lg">
                                    <span x-text="getMonthName(month)" class="font-bold capitalize"></span>
                                    <span x-text="year" class="font-normal text-content-dimmed"></span>
                                </div>

                                <div class="flex items-center">
                                    <button type="button" class="text-2xl transition duration-100 ease-in-out cursor-pointer ti ti-chevron-left text-content-dimmed hover:text-content rtl:rotate-180" :disabled="isFirstMonth()" @click="previousMonth()"></button>
                                    <button type="button" class="text-2xl transition duration-100 ease-in-out cursor-pointer ti ti-chevron-right text-content-dimmed hover:text-content rtl:rotate-180" :disabled="isLastMonth()" @click="nextMonth()"></button>
                                </div>
                            </div>

                            <div class="grid grid-cols-7 gap-1 text-xs text-center text-content-dimmed">
                                <template x-for="(day, index) in getWeekDays()" :key="index">
                                    <div x-text="day"></div>
                                </template>
                            </div>

                            <hr/>

                            <div class="grid grid-cols-7 gap-1 text-sm text-center">
                                <template x-for="blankday in blankdays">
                                    <div class="pt-[100%]"></div>
                                </template>

                                <template x-for="(date, dateIndex) in no_of_days" :key="dateIndex">
                                    <div class="relative pt-[100%]">
                                        <template x-if="isToday(date)">
                                            <div class="absolute bottom-0 left-1/2 w-1 h-1 rounded-full -translate-x-1/2 bg-failure"></div>
                                        </template>

                                        <button type="button" @click="selectDate(date)" x-text="date" class="flex absolute inset-0 justify-center items-center rounded-lg transition delay-100 hover:delay-0 hover:scale-125 hover:bg-line-dimmed" :disabled="isDisabled(date)" :class="isSelected(date) ? 'font-bold' : ''"></button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    }
}
