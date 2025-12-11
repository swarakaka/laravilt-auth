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
    excludeCredentials?: Array<{
        id: string;
        type: string;
        transports?: string[];
    }>;
}

/**
 * Check if a string is a valid hex string
 */
function isHexString(str: string): boolean {
    return /^[0-9a-fA-F]+$/.test(str) && str.length % 2 === 0;
}

/**
 * Convert a hex string to Uint8Array
 */
function hexToUint8Array(hex: string): Uint8Array {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16);
    }
    return bytes;
}

/**
 * Convert a base64url, base64, or hex string to a Uint8Array
 * Also handles ArrayBuffer and Uint8Array inputs
 */
function stringToUint8Array(input: string | ArrayBuffer | Uint8Array): Uint8Array {
    // Already a Uint8Array
    if (input instanceof Uint8Array) {
        return input;
    }

    // ArrayBuffer - wrap it
    if (input instanceof ArrayBuffer) {
        return new Uint8Array(input);
    }

    // Must be a string
    if (!input || typeof input !== 'string') {
        console.error('Invalid input for stringToUint8Array:', input, typeof input);
        throw new Error(`Invalid input: ${typeof input}`);
    }

    // Check if it's a hex string (like user.id from Laragear)
    if (isHexString(input)) {
        return hexToUint8Array(input);
    }

    // Otherwise treat as base64url/base64
    // Convert base64url to base64
    let base64Str = input.replace(/-/g, '+').replace(/_/g, '/');

    // Add padding if needed
    while (base64Str.length % 4) {
        base64Str += '=';
    }

    try {
        const binaryString = atob(base64Str);
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes;
    } catch (e) {
        console.error('Failed to decode base64:', input, e);
        throw e;
    }
}

// Alias for backwards compatibility
const base64ToUint8Array = stringToUint8Array;

/**
 * Convert ArrayBuffer to base64url string
 */
function arrayBufferToBase64url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

/**
 * Prepare WebAuthn creation options by converting base64 strings to ArrayBuffers
 */
function prepareCreationOptions(options: any): PublicKeyCredentialCreationOptions {
    const publicKey = options.publicKey || options;

    // Build the prepared options
    const prepared: PublicKeyCredentialCreationOptions = {
        rp: publicKey.rp,
        challenge: stringToUint8Array(publicKey.challenge),
        user: {
            ...publicKey.user,
            id: stringToUint8Array(publicKey.user.id),
        },
        pubKeyCredParams: publicKey.pubKeyCredParams,
    };

    // Add optional fields
    if (publicKey.timeout) {
        prepared.timeout = publicKey.timeout;
    }

    if (publicKey.attestation) {
        prepared.attestation = publicKey.attestation;
    }

    if (publicKey.authenticatorSelection) {
        prepared.authenticatorSelection = publicKey.authenticatorSelection;
    }

    // TEMPORARILY SKIP excludeCredentials to test if registration works
    // This allows duplicate passkey registration but helps debug
    console.log('Skipping excludeCredentials for testing. Server sent:', publicKey.excludeCredentials);

    return prepared;
}

export { prepareCreationOptions, arrayBufferToBase64url, base64ToUint8Array, stringToUint8Array };

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
