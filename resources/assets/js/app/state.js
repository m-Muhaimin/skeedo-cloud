'use strict';

import Alpine from 'alpinejs';

export function initState() {
    Alpine.store('user', window.state.user);
    Alpine.store('workspace', window.state.workspace);
}