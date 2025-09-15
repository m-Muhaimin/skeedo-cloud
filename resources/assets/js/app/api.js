'use strict';

import { Api } from "../api";

let api = new Api();
api.base = '/api';

/**
 * Exports an instance of the Api class.
 * 
 * @type {Api}
 */
export default api;