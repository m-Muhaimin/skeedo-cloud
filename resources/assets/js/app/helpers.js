'use strict';

import api from './api';

export function getCategoryList() {
    let categories = [];
    let fetchedAll = false;

    async function getList(cursor = null) {
        let params = {};

        if (cursor) {
            params.starting_after = cursor;
        }

        let response = await api.get('/categories', params);
        let content = await response.json();

        if (content.data.length == 0) {
            fetchedAll = true;
            return;
        }

        categories.push(...content.data);

        if (!fetchedAll) {
            getList(categories[categories.length - 1].id);
        }

        return categories;
    }

    return getList();
}

export function getPlanList() {
    let plans = [];
    let fetchedAll = false;

    async function getList(cursor = null) {
        let params = {
            'sort': 'price:asc'
        };

        if (cursor) {
            params.starting_after = cursor;
        }

        let response = await api.get('/billing/plans', params);
        let content = await response.json();

        if (content.data.length == 0) {
            fetchedAll = true;
            return;
        }

        plans.push(...content.data);

        if (!fetchedAll) {
            getList(plans[plans.length - 1].id);
        }

        return plans;
    }

    return getList();
}