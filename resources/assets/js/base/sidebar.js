`use strict`;

export class SidebarController {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
    }

    open(key) {
        document.documentElement.dataset.sidebar = true;

        if (this.sidebar) {
            this.sidebar.dataset.key = key;
        }
    }

    close() {
        delete document.documentElement.dataset.sidebar;

        if (this.sidebar) {
            delete this.sidebar.dataset.key;
        }
    }
}

/** @type {SidebarController} */
const sc = new SidebarController();
export default sc;