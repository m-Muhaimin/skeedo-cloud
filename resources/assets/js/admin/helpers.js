'use strict';

import api from './api';

export function listResources(endpoint, params = {}) {
    let resources = [];
    let fetchedAll = false;

    async function getList(cursor = null) {
        if (cursor) {
            params.starting_after = cursor;
        }

        let response = await api.get(endpoint, params);

        if (response.data.data.length == 0) {
            fetchedAll = true;
            return;
        }

        resources.push(...response.data.data);

        if (!fetchedAll) {
            await getList(resources[resources.length - 1].id);
        }

        return resources;
    }

    return getList();
}