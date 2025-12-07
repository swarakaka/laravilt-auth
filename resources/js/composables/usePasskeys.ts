import { ref } from 'vue';

export interface Passkey {
    id: string;
    name: string;
    created_at: string;
    last_used_at: string | null;
}

export interface PasskeyRegistrationOptions {
    challenge: string;
    rp: {
        name: string;
        id: string;
    };
    user: {
        id: string;
        name: string;
        displayName: string;
    };
    pubKeyCredParams: any[];
    timeout: number;
    attestation: string;
}

export function usePasskeys(panelId: string = 'user') {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const passkeys = ref<Passkey[]>([]);

    const fetchPasskeys = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/passkeys`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to fetch passkeys');
            }

            passkeys.value = data.passkeys || [];
            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const getRegistrationOptions = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/passkeys/register-options`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to get registration options');
            }

            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const registerPasskey = async (name: string, credential: any) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/passkeys`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ name, credential }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to register passkey');
            }

            await fetchPasskeys();
            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deletePasskey = async (passkeyId: string) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/passkeys/${passkeyId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete passkey');
            }

            passkeys.value = passkeys.value.filter(p => p.id !== passkeyId);
            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteAllPasskeys = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/passkeys`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete all passkeys');
            }

            passkeys.value = [];
            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        passkeys,
        fetchPasskeys,
        getRegistrationOptions,
        registerPasskey,
        deletePasskey,
        deleteAllPasskeys,
    };
}
