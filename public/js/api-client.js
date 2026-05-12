class ApiClient {
    static instance = null;

    constructor() {
        if (ApiClient.instance) {
            return ApiClient.instance;
        }
        ApiClient.instance = this;
    }

    static getInstance() {
        if (!ApiClient.instance) {
            ApiClient.instance = new ApiClient();
        }
        return ApiClient.instance;
    }

    static async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };

        const response = await fetch(url, { ...defaultOptions, ...options });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Wystąpił błąd');
        }

        return response.json();
    }

    static async get(url) {
        return this.request(url, { method: 'GET' });
    }

    static async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    static async upload(url, formData) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                signal: controller.signal
            });

            clearTimeout(timeoutId);
            const text = await response.text();

            if (!response.ok) {
                try {
                    const error = JSON.parse(text);
                    throw new Error(error.error || 'Wystąpił błąd');
                } catch (e) {
                    throw new Error(text || 'Wystąpił błąd');
                }
            }

            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Nieprawidłowa odpowiedź serwera');
            }
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Przekroczono czas oczekiwania (30 sekund). Spróbuj ponownie.');
            }
            throw error;
        }
    }

    static async postFormData(url, formData) {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Wystąpił błąd');
        }

        return response.json();
    }
}
