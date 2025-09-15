/**
 * PinController - Manages pinned items using localStorage with namespace support
 * 
 * This class provides functionality to pin/unpin items and check their pinned status.
 * Pinned items are persisted in the browser's localStorage, allowing them to survive
 * page refreshes and browser sessions. Each namespace maintains its own separate
 * collection of pinned items.
 * 
 * @example
 * const pinController = new PinController();
 * 
 * // Pin an item in the default namespace
 * pinController.pin('item-123');
 * 
 * // Pin an item in a specific namespace
 * pinController.pin('dashboard-widget', 'dashboard');
 * 
 * // Check if an item is pinned
 * if (pinController.isPinned('item-123')) {
 *     console.log('Item is pinned');
 * }
 * 
 * // Unpin an item
 * pinController.unpin('item-123');
 */
export class PinController {
    /**
     * Creates a new PinController instance
     */
    constructor() {
    }

    /**
     * Pins an item to the specified namespace
     * 
     * If the item is already pinned, this method does nothing. The item will be
     * persisted to localStorage and survive page refreshes.
     * 
     * @param {string} key - The unique identifier of the item to pin
     * @param {string} [namespace=null] - The namespace to pin the item in
     * 
     * @example
     * pinController.pin('user-123'); // Pins to default namespace
     * pinController.pin('widget-456', 'dashboard'); // Pins to dashboard namespace
     */
    pin(key, namespace = null) {
        const storageKey = this._getStorageKey(namespace);
        const pinnedItems = this._getPinnedItems(storageKey);

        if (!pinnedItems.includes(key)) {
            pinnedItems.unshift(key);
            this._savePinnedItems(storageKey, pinnedItems);
        }
    }

    /**
     * Unpins an item from the specified namespace
     * 
     * If the item is not pinned, this method does nothing. The change will be
     * persisted to localStorage immediately.
     * 
     * @param {string} key - The unique identifier of the item to unpin
     * @param {string} [namespace=null] - The namespace to unpin the item from
     * 
     * @example
     * pinController.unpin('user-123'); // Unpins from default namespace
     * pinController.unpin('widget-456', 'dashboard'); // Unpins from dashboard namespace
     */
    unpin(key, namespace = null) {
        const storageKey = this._getStorageKey(namespace);
        const pinnedItems = this._getPinnedItems(storageKey);

        const index = pinnedItems.indexOf(key);
        if (index > -1) {
            pinnedItems.splice(index, 1);
            this._savePinnedItems(storageKey, pinnedItems);
        }
    }

    toggle(key, namespace = 'default') {
        if (this.isPinned(key, namespace)) {
            this.unpin(key, namespace);
        } else {
            this.pin(key, namespace);
        }
    }

    /**
     * Checks if an item is pinned in the specified namespace
     * 
     * @param {string} key - The unique identifier of the item to check
     * @param {string} [namespace=null] - The namespace to check in
     * @returns {boolean} True if the item is pinned, false otherwise
     * 
     * @example
     * if (pinController.isPinned('user-123')) {
     *     console.log('User is pinned');
     * }
     * 
     * if (pinController.isPinned('widget-456', 'dashboard')) {
     *     console.log('Widget is pinned in dashboard');
     * }
     */
    isPinned(key, namespace = null) {
        const storageKey = this._getStorageKey(namespace);
        const pinnedItems = this._getPinnedItems(storageKey);

        return pinnedItems.includes(key);
    }

    /**
     * Gets the order index of a pinned item in the specified namespace
     * 
     * @param {string} key - The unique identifier of the item to check
     * @param {string} [namespace=null] - The namespace to check in
     * @returns {number} The order index (0-based), or -1 if not pinned
     * 
     * @example
     * const order = pinController.getPinOrder('user-123');
     * // Returns 0 for first pinned item, 1 for second, etc.
     */
    getPinOrder(key, namespace = null) {
        const storageKey = this._getStorageKey(namespace);
        const pinnedItems = this._getPinnedItems(storageKey);

        return pinnedItems.indexOf(key);
    }

    /**
     * Generates a storage key for the given namespace
     * 
     * @private
     * @param {string} namespace - The namespace identifier
     * @returns {string} The storage key for localStorage
     */
    _getStorageKey(namespace) {
        if (!namespace) {
            namespace = 'default';
        }

        return `pinned_items_${namespace}`;
    }

    /**
     * Retrieves pinned items from localStorage for the given storage key
     * 
     * @private
     * @param {string} storageKey - The localStorage key to retrieve data from
     * @returns {string[]} Array of pinned item keys, or empty array if none found
     */
    _getPinnedItems(storageKey) {
        try {
            const stored = localStorage.getItem(storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.warn('Failed to retrieve pinned items from localStorage:', error);
            return [];
        }
    }

    /**
     * Saves pinned items to localStorage for the given storage key
     * 
     * @private
     * @param {string} storageKey - The localStorage key to save data to
     * @param {string[]} pinnedItems - Array of pinned item keys to save
     */
    _savePinnedItems(storageKey, pinnedItems) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(pinnedItems));
        } catch (error) {
            console.error('Failed to save pinned items to localStorage:', error);
        }
    }
}

export const pins = new PinController();