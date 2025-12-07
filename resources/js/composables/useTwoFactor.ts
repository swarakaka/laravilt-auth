import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export interface TwoFactorData {
    secret?: string;
    qr_code?: string;
    recovery_codes?: string[];
}

export function useTwoFactor(panelId: string = 'user') {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const twoFactorData = ref<TwoFactorData | null>(null);
    const showRecoveryCodes = ref(false);

    const enable = async (method: string, password: string) => {
        loading.value = true;
        error.value = null;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`/${panelId}/profile/two-factor/enable`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ method, password }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to enable two-factor authentication');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            // The controller returns { message, data } where data contains the actual 2FA info
            twoFactorData.value = data.data || data;
            return data.data || data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const confirm = async (code: string) => {
        loading.value = true;
        error.value = null;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`/${panelId}/profile/two-factor/confirm`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ code }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to confirm two-factor authentication');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            showRecoveryCodes.value = true;

            // If recovery codes are in the current twoFactorData, use them
            if (twoFactorData.value?.recovery_codes) {
                // Keep existing recovery codes from enable response
            } else if (data.recovery_codes) {
                twoFactorData.value = { recovery_codes: data.recovery_codes };
            }

            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const disable = async (password: string) => {
        loading.value = true;
        error.value = null;

        try {
            // Get CSRF token from Inertia page props (shared by Laravel automatically)
            const page = usePage();
            const csrfToken = (page.props as any).csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`/${panelId}/profile/two-factor/disable`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ password }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to disable two-factor authentication');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            twoFactorData.value = null;

            // Reload the page to update the status
            router.reload();

            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const regenerateRecoveryCodes = async (password: string) => {
        loading.value = true;
        error.value = null;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`/${panelId}/profile/two-factor/recovery-codes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ password }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to regenerate recovery codes');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            showRecoveryCodes.value = true;
            twoFactorData.value = { recovery_codes: data.recovery_codes };

            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const reset = () => {
        twoFactorData.value = null;
        error.value = null;
        showRecoveryCodes.value = false;
    };

    return {
        loading,
        error,
        twoFactorData,
        showRecoveryCodes,
        enable,
        confirm,
        disable,
        regenerateRecoveryCodes,
        reset,
    };
}
