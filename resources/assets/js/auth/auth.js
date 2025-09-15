'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { jwtDecode } from 'jwt-decode';
import helper from '../redirect';
import { toast } from '../base/toast';
import { ApiError } from '../api';
import { getCookie } from '../helpers';
import { IpService } from '../ip';

export function authView() {
    Alpine.data('auth', (appPath = '/app') => ({
        isProcessing: false,
        success: false,

        init() {
            helper.saveRedirectPath();
        },

        async submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let fd = new FormData(this.$refs.form);
            this.$refs.form.querySelectorAll('input[type="checkbox"]').forEach((element) => {
                fd.append(element.name, element.checked ? '1' : '0');
            });

            fd.append('locale', document.documentElement.lang);

            try {
                const ipinfo = await IpService.getIpInfo();
                fd.append('ip', ipinfo.ipAddress);
                fd.append('country_code', ipinfo.countryCode);
                fd.append('city_name', ipinfo.cityName);
            } catch (error) {
            }

            const data = {};
            fd.forEach((value, key) => (data[key] = value));

            let ref = getCookie('ref');
            if (ref) {
                data.ref = ref;

                // Remove the ref cookie
                document.cookie = `ref=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
            }

            api.post(this.$refs.form.dataset.apiPath, data)
                .then(response => {
                    if (response.data.jwt) {
                        const jwt = response.data.jwt;
                        const payload = jwtDecode(jwt);

                        // Save the JWT to local storage 
                        // to be used for future api requests
                        localStorage.setItem('jwt', jwt);

                        // Redirect user to the app or admin dashboard
                        let path = payload.is_admin ? '/admin' : appPath;

                        // If the user was redirected to the login page
                        let redirectPath = helper.getRedirectPath();
                        if (redirectPath) {
                            // Redirect the user to the path they were trying to access
                            path = redirectPath;
                        }

                        // Redirect the user to the path
                        window.location.href = path;

                        // Response should include the user cookie (autosaved) 
                        // for authenticatoin of the UI GET requests
                    } else {
                        this.isProcessing = true;
                        this.success = true;
                    }
                })
                .catch(error => {
                    if (error instanceof ApiError) {
                        let msg = null;

                        if (error.response.status == 401) {
                            msg = "Invalid credentials. Please try again.";
                        }

                        if (error.response.status == 409 && error.response.data.param == 'email') {
                            msg = "The email you entered is already taken.";
                        }

                        if (msg) {
                            toast.error(msg);
                        }
                    }

                    if (window.captcha) {
                        window.captcha.reset();
                    }

                    this.isProcessing = false;
                });
        },

        async ipinfo() {
            try {
                let info = await IpService.getIpInfo();

                let data = {
                    ip: info.ipAddress,
                    country_code: info.countryCode,
                    city_name: info.cityName
                };

                document.cookie = `ipinfo=${JSON.stringify(data)};path=/`;
            } catch (error) {
            }
        },
    }));
}