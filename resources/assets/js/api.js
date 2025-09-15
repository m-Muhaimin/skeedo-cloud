import Alpine from "alpinejs";
import { toast } from "./base/toast";
import { getJwtToken } from "./helpers";

/**
 * Extended options for the Fetch request.
 * 
 * @typedef {Object} ResponseAddition
 * @property {Object} data - The JSON response data.
 * 
 * @typedef {Response & ResponseAddition} ExtendedResponse
 */

/**
 * Extended options for the Fetch request.
 * 
 * @typedef {Object} FetchOptions
 * @property {Record<string, string>} [params] - Query parameter key-value pairs to be appended to the URL.
 * @property {'default' | 'no-store' | 'reload' | 'no-cache' | 'force-cache' | 'only-if-cached'} [cache] - Controls how the request can be cached.
 * @property {'high' | 'low' | 'auto'} [priority] - Indicates the importance of the request.
 * @property {'follow' | 'error' | 'manual'} [redirect] - Controls the behavior of following HTTP redirects.
 * @property {boolean|function(Response)} errorHandler=true - Whether to show a toast message on error.
 */

/**
 * Defines the API's structure for making HTTP requests.
 *
 * @typedef {Object} Api
 * @property {function(string|URL, Record<string, string>, RequestInit & FetchOptions): Promise<ExtendedResponse>} get - Makes a GET request using the Fetch API.
 * @property {function(string|URL, Record<string, any>|FormData|URLSearchParams, RequestInit & FetchOptions): Promise<ExtendedResponse>} post - Makes a POST request.
 * @property {function(string|URL, Record<string, any>|FormData|URLSearchParams, RequestInit & FetchOptions): Promise<ExtendedResponse>} patch - Makes a PATCH request.
 * @property {function(string|URL, Record<string, any>|FormData|URLSearchParams, RequestInit & FetchOptions): Promise<ExtendedResponse>} put - Makes a PUT request.
 * @property {function(string|URL, RequestInit & FetchOptions): Promise<ExtendedResponse>} delete - Makes a DELETE request.
 */

/**
 * Configuration options for the API client
 * @typedef {Object} ApiConfig
 * @property {boolean} toast - Whether to show toast messages on error
 */

export class ApiError extends Error {

    /**
     * @param {Response} response
     * @param {string} message 
     */
    constructor(response, message) {
        super(message);

        this.response = response;
    }
}

export class Api {
    /** @type {string|null} */
    base = null;

    /** @type {ApiConfig} */
    config = {
        toast: true
    };

    getDefaultHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    }

    /**
     * Makes an HTTP request using the Fetch API.
     *
     * @param {string} [method='GET'] - The HTTP method.
     * @param {string|URL} url - The URL to send the request to.
     * @param {RequestInit & FetchOptions} [options={}] - Additional options for the request.
     * @returns {Promise<ExtendedResponse>} A Promise that resolves to the Response object representing the response to the request.
     */
    async request(
        method = 'GET',
        url,
        options = {}
    ) {
        const opts = {
            method,
            headers: this.getDefaultHeaders() // Use method to avoid reference issues
        };

        const token = getJwtToken();

        if (token) {
            opts.headers['Authorization'] = `Bearer ${token}`;
        }

        if (Alpine.store('workspace')) {
            opts.headers['X-Workspace-Id'] = Alpine.store('workspace').id;
        }

        url = url instanceof URL ? url : new URL(
            ((this.base || '') + "/" + url).replace(/([^:]\/)\/+/g, "$1"),
            window.location.origin
        );

        if (options.params) {
            Object.keys(options.params).forEach(key => url.searchParams.append(key, options.params[key]));
            delete options.params;
        }

        options = { ...opts, ...options };
        if (options.body) {
            if (options.body instanceof FormData) {
                // FormData already sets the correct Content-Type with the boundary
                delete options.headers['Content-Type'];
            } else if (options.body instanceof URLSearchParams) {
                options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
            } else if (options.headers['Content-Type'] === 'application/json' && typeof options.body !== 'string') {
                options.body = JSON.stringify(options.body);
            }
        }

        return fetch(url, { ...opts, ...options }).then(async (response) => {
            let clone = response.clone();

            response.data = {};

            if (
                response.headers.get('Content-Type')
                && response.headers.get('Content-Type').toLocaleLowerCase().includes('application/json')
            ) {
                try {
                    response.data = await clone.json();
                } catch (error) {
                    // Do nothing, response is not JSON
                }
            }

            if (response.ok) {
                return response;
            }

            let message = response.data.message || null;

            if (this.config.toast && message) {
                toast.error(message);
                window.modal.close();
            }

            throw new ApiError(response, message || response.statusText);
        });
    }

    /**
     * Makes a GET request using the Fetch API.
     *
     * @param {string|URL} url - The URL to send the request to.
     * @param {Record<string, string>} [params={}] - Query parameter key-value pairs to be appended to the URL.
     * @param {RequestInit & FetchOptions} [options={}] - Additional options for the request.
     * @returns {Promise<ExtendedResponse>} A Promise that resolves to the Response object representing the response to the request.
     */
    get(url, params = {}, options = {}) {
        return this.request('GET', url, { ...options, params });
    }

    /**
     * Makes a POST request using the Fetch API.
     *
     * @param {string|URL} url - The URL to send the request to.
     * @param {Record<string, any>|FormData|URLSearchParams} [body={}] - The body of the request.
     * @param {RequestInit & FetchOptions} [options={}] - Additional options for the request.
     * @returns {Promise<ExtendedResponse>} A Promise that resolves to the Response object representing the response to the request.
     */
    post(url, body = {}, options = {}) {
        return this.request('POST', url, { ...options, body });
    }

    /**
     * Makes a PATCH request using the Fetch API.
     *
     * @param {string|URL} url - The URL to send the request to.
     * @param {Record<string, any>|FormData|URLSearchParams} [body={}] - The body of the request.
     * @param {RequestInit & FetchOptions} [options={}] - Additional options for the request.
     * @returns {Promise<ExtendedResponse>} A Promise that resolves to the Response object representing the response to the request.
     */
    patch(url, body = {}, options = {}) {
        return this.request('PATCH', url, { ...options, body });
    }

    /**
     * Makes a PUT request using the Fetch API.
     *
     * @param {string|URL} url - The URL to send the request to.
     * @param {Record<string, any>|FormData|URLSearchParams} [body={}] - The body of the request.
     * @param {RequestInit & FetchOptions} [options={}] - Additional options for the request.
     * @returns {Promise<ExtendedResponse>} A Promise that resolves to the Response object representing the response to the request.
     */
    put(url, body = {}, options = {}) {
        return this.request('PUT', url, { ...options, body });
    }

    /**
     * Makes a DELETE request using the Fetch API.
     *
     * @param {string|URL} url - The URL to send the request to.
     * @param {RequestInit & FetchOptions} [options={}] - Additional options for the request.
     * @returns {Promise<ExtendedResponse>} A Promise that resolves to the Response object representing the response to the request.
     */
    remove(url, options) {
        return this.request('DELETE', url, options);
    }

    /**
    * Makes a DELETE request using the Fetch API.
    *
    * @param {string|URL} url - The URL to send the request to.
    * @param {RequestInit & FetchOptions} [options={}] - Additional options for the request.
    * @returns {Promise<ExtendedResponse>} A Promise that resolves to the Response object representing the response to the request.
    */
    delete(url, options) {
        return this.remove(url, options);
    }
}