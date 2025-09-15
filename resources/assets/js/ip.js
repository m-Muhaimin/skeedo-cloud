'use strict';

const IP_CACHE_KEY = 'ip_location_cache';
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

/**
 * @typedef {Object} IpLocationCurrency
 * @property {string} code - Currency code (e.g., "USD")
 * @property {string} name - Currency name (e.g., "United States Dollar")
 */

/**
 * @typedef {Object} IpLocationData
 * @property {number} ipVersion - IP version (4 or 6)
 * @property {string} ipAddress - IP address
 * @property {number} latitude - Geographical latitude
 * @property {number} longitude - Geographical longitude
 * @property {string} countryName - Full country name
 * @property {string} countryCode - Two-letter country code
 * @property {string} timeZone - Timezone offset (e.g., "+04:00")
 * @property {string} zipCode - Postal/ZIP code
 * @property {string} cityName - City name
 * @property {string} regionName - Region/State name
 * @property {boolean} isProxy - Whether IP is a proxy
 * @property {string} continent - Continent name
 * @property {string} continentCode - Two-letter continent code
 * @property {IpLocationCurrency} currency - Currency information
 * @property {string} language - Primary language
 * @property {string[]} timeZones - Array of timezone names
 * @property {string[]} tlds - Array of top-level domains
 */

export class IpService {
    /**
     * Get IP information for a specific IP or current user
     * @param {string|null} ip - IP address to look up (null for current user's IP)
     * @returns {Promise<IpLocationData|null>} IP location data or null if error occurs
     */
    static async getIpInfo(ip = null) {
        try {
            return await this.getIpLocation(ip);
        } catch (error) {
            console.error('Error handling IP location:', error);
            return null;
        }
    }

    /**
     * Fetch IP location data from API
     * @param {string|null} ip - IP address to look up (null for current user's IP)
     * @returns {Promise<IpLocationData>} IP location data
     * @throws {Error} When API request fails or returns invalid data
     */
    static async getIpLocation(ip = null) {
        // Check cache first
        const cache = this.getLocationFromCache(ip || 'current');
        if (cache) {
            return cache;
        }

        const response = await fetch(`https://free.freeipapi.com/api/json/${ip || ''}`);
        if (!response.ok) {
            throw new Error(`API returned ${response.status}`);
        }

        const data = await response.json();
        if (!data.latitude || !data.longitude) {
            throw new Error('Invalid location data received');
        }

        // Cache the result
        this.cacheLocation(ip || 'current', data);
        return data;
    }

    /**
     * Get cached location data for an IP
     * @param {string} ip - IP address or 'current' for current user
     * @returns {IpLocationData|null} Cached location data or null if not found/expired
     */
    static getLocationFromCache(ip) {
        const cache = JSON.parse(localStorage.getItem(IP_CACHE_KEY) || '{}');
        const now = Date.now();

        // Clean up all expired entries
        let hasExpired = false;
        for (const cachedIp in cache) {
            if ((now - cache[cachedIp].timestamp) >= CACHE_DURATION) {
                delete cache[cachedIp];
                hasExpired = true;
            }
        }

        if (hasExpired) {
            localStorage.setItem(IP_CACHE_KEY, JSON.stringify(cache));
        }

        const entry = cache[ip];
        return entry && (now - entry.timestamp) < CACHE_DURATION ? entry.data : null;
    }

    /**
     * Cache location data for an IP
     * @param {string} ip - IP address or 'current' for current user
     * @param {IpLocationData} data - Location data to cache
     */
    static cacheLocation(ip, data) {
        const cache = JSON.parse(localStorage.getItem(IP_CACHE_KEY) || '{}');

        cache[ip] = {
            data,
            timestamp: Date.now()
        };

        localStorage.setItem(IP_CACHE_KEY, JSON.stringify(cache));
    }
}
