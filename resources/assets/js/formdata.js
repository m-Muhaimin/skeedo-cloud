
export class CustomFormData extends FormData {
    /**
     * @param {HTMLFormElement|Object||null} form 
     */
    constructor(form = null) {
        if (form instanceof HTMLFormElement) {
            super(form);
        } else {
            super();
        }

        if (form instanceof HTMLFormElement) {
            // Handle price inputs - convert to currency minor
            form.querySelectorAll('input[data-format="price"]').forEach((element) => {
                if (element.name && element.value) {
                    const fractionDigits = element.dataset.fractionDigits || 0;
                    const numericValue = parseFloat(element.value.replace(/[^0-9.]/g, ''));

                    if (!isNaN(numericValue)) {
                        const minorValue = (numericValue * Math.pow(10, fractionDigits)).toFixed(0);
                        this.set(element.name, minorValue);
                    }
                }
            });

            form.querySelectorAll('input[type="checkbox"]').forEach((element) => {
                if (element.name && !element.name.includes('[]')) {
                    if (element.checked === false) {
                        if (element.value === '1') {
                            this.set(element.name, '0');
                        } else if (element.value === '0') {
                            this.set(element.name, '1');
                        } else if (element.value === 'true') {
                            this.set(element.name, 'false');
                        } else if (element.value === 'false') {
                            this.set(element.name, 'true');
                        } else if (!element.hasAttribute('value')) {
                            this.set(element.name, '0');
                        }
                    } else {
                        this.set(element.name, element.hasAttribute('value') ? element.value : '1');
                    }
                }
            });
        } else if (!(form instanceof HTMLFormElement)) {
            for (const key in form) {
                if (form.hasOwnProperty(key)) {
                    const value = form[key];
                    this.append(key, value);
                }
            }
        }
    }

    async toJson() {
        const jsonObject = {};
        const filePromises = [];

        this.forEach((value, key) => {
            // Split the key into parts based on [] or [key]
            const keys = key.split(/\[(.*?)\]/).filter(Boolean);

            // Initialize a reference to traverse jsonObject
            let current = jsonObject;

            // Traverse through each key part
            for (let i = 0; i < keys.length; i++) {
                const k = keys[i];

                // If it's the last key part, assign the value
                if (i === keys.length - 1) {
                    // Check if the key already exists in jsonObject
                    if (Object.prototype.hasOwnProperty.call(current, k)) {
                        // If it's an array, push the value
                        if (!Array.isArray(current[k])) {
                            current[k] = [current[k]];
                        }
                        current[k].push(value);
                    } else {
                        // If it's a file input, append File object directly
                        if (value instanceof File) {
                            current[k] = value;

                            const filePromise = this.readFileAsBase64(value)
                                .then(base64String => {
                                    current[k] = base64String;
                                });

                            filePromises.push(filePromise);
                        } else {
                            // Otherwise, set the value
                            current[k] = value;
                        }
                    }
                } else {
                    // If the key doesn't exist or isn't an object, create an empty object
                    if (!current[k] || typeof current[k] !== 'object') {
                        current[k] = {};
                    }

                    // Move to the next level in jsonObject
                    current = current[k];
                }
            }
        });

        // Wait for all file read operations to complete
        await Promise.all(filePromises);
        return jsonObject;
    }

    readFileAsBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const base64String = e.target.result.replace("data:", "").replace(/^.+,/, "");
                resolve(base64String);
            };
            reader.onerror = function (error) {
                reject(error);
            };
            reader.readAsDataURL(file);
        });
    }
}