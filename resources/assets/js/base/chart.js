'use strict';

import ApexCharts from 'apexcharts'

function createSuperset(dataset, minDataPoints) {
    // Sort the dataset by date
    dataset.sort((a, b) => new Date(a.category) - new Date(b.category));

    const today = new Date();
    const end = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);

    if (dataset.length === 0) {
        const start = new Date(end);
        start.setDate(start.getDate() - (minDataPoints - 1));
        return fillDates(start, end);
    }

    // Calculate the start date based on the dataset and minDataPoints
    const datasetStart = new Date(dataset[0].category);
    const datasetEnd = new Date(dataset[dataset.length - 1].category);
    const daysToExtend = Math.max(0, minDataPoints - dataset.length);
    const start = new Date(datasetStart);
    start.setDate(start.getDate() - Math.floor(daysToExtend / 2));

    // Adjust the end date if necessary
    const adjustedEnd = new Date(datasetEnd);
    adjustedEnd.setDate(adjustedEnd.getDate() + Math.ceil(daysToExtend / 2));

    return fillDates(start, adjustedEnd, dataset);
}

function fillDates(start, end, dataset = []) {
    const dateMap = new Map(dataset.map(item => [item.category, item.value]));
    const result = [];
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const dateStr = d.toISOString().split('T')[0];
        result.push({
            category: dateStr,
            value: dateMap.has(dateStr) ? dateMap.get(dateStr) : 0
        });
    }

    return result;
}

function color(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name);
}

export class ChartElement extends HTMLElement {
    static observedAttributes = [
        'set'
    ];

    constructor() {
        super();

        this.chart = null;
        this.container = this.querySelector('[chart]') || this;
        this.rtl = this.isRtl();
    }

    isRtl() {
        // Check self and parents for dir attribute
        let element = this;
        while (element) {
            if (element.hasAttribute('dir')) {
                return element.getAttribute('dir') === 'rtl';
            }
            element = element.parentElement;
        }
        // Fall back to document direction
        return document.documentElement.dir === 'rtl';
    }

    disconnectedCallback() {
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.render();
    }

    getReadableDuration(duration) {
        let date = new Date(0);
        date.setSeconds(duration);

        if (duration > 3600) {
            return date.toISOString().substring(11, 19)
        }

        return date.toISOString().substring(14, 19)
    }

    seekTo(duration = 0) {
        this.wave.seekTo(duration / this.wave.getDuration());
    }

    render() {
        let set = JSON.parse(this.getAttribute('set') || '[]');
        set = createSuperset(set, 30);

        if (!this.chart) {
            this.chart = new ApexCharts(this.container, {
                series: [],
                chart: {
                    type: this.getAttribute('type') || 'bar',
                    height: '100%',
                    fontFamily: 'inherit',
                    foreColor: color('--color-content-dimmed'),
                    zoom: {
                        enabled: false
                    },
                    toolbar: {
                        show: false
                    },
                    animations: {
                        easing: 'easeout',
                        speed: 200,
                        dynamicAnimation: {
                            enabled: true,
                            speed: 200
                        }
                    },

                },
                colors: [
                    color('--color-content')
                ],
                grid: {
                    show: false,
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        borderRadiusApplication: 'end',
                        columnWidth: '95%',
                    }
                },
                xaxis: {
                    type: "datetime",
                    labels: {
                        style: {
                            fontFamily: 'inherit',
                            cssClass: 'text-xs',
                        },
                    },
                },
                yaxis: {
                    opposite: this.rtl,
                    labels: {
                        style: {
                            fontFamily: 'inherit',
                            cssClass: 'text-xs',
                        },
                        formatter: (value) => {
                            let lang = document.documentElement.lang || 'en';
                            let amount = parseFloat(value);

                            let options = {
                                style: 'decimal',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0,
                                trailingZeroDisplay: 'stripIfInteger'
                            };

                            let formatter = new Intl.NumberFormat(lang, options);
                            let text = formatter.format(amount);

                            if (text.length >= 5) {
                                formatter = new Intl.NumberFormat(lang, { ...options, notation: 'compact', compactDisplay: 'short' });
                                text = formatter.format(amount);
                            }

                            return text;
                        },
                    },
                },
                stroke: {
                    show: true,
                    width: 1
                },
                dataLabels: {
                    enabled: false,
                },
                tooltip: {
                    custom: ({ series, seriesIndex, dataPointIndex, w }) => {
                        let date = new Date(w.globals.seriesX[seriesIndex][dataPointIndex]);
                        let lang = document.documentElement.lang || 'en';

                        let formatter = new Intl.DateTimeFormat(lang, {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            timeZone: 'UTC'
                        });

                        return (
                            '<div class="badge">' +
                            "<span class='font-bold'>" +
                            formatter.format(date) +
                            ": " +
                            "</span>" +
                            series[seriesIndex][dataPointIndex] +
                            "</div>"
                        );
                    }
                }
            });

            this.chart.render();
        }

        let data = set.map(row => {
            return {
                x: row.category + ' GMT',
                y: row.value
            }
        });

        this.chart.updateSeries([
            {
                data: data
            }
        ]);
    }
}
