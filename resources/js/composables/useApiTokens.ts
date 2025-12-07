import { ref } from 'vue';

export interface ApiToken {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string;
    plain_text_token?: string;
}

export function useApiTokens(panelId: string = 'user') {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const tokens = ref<ApiToken[]>([]);
    const newToken = ref<ApiToken | null>(null);

    const fetchTokens = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/api-tokens`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to fetch API tokens');
            }

            tokens.value = data.tokens;
            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const createToken = async (name: string, abilities: string[]) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/api-tokens`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ name, abilities }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to create API token');
            }

            newToken.value = data.token;
            tokens.value.unshift(data.token);

            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateToken = async (tokenId: number, abilities: string[]) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/api-tokens/${tokenId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ abilities }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to update API token');
            }

            // Update the token in the list
            const index = tokens.value.findIndex(t => t.id === tokenId);
            if (index !== -1) {
                tokens.value[index] = data.token;
            }

            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteToken = async (tokenId: number) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/${panelId}/profile/api-tokens/${tokenId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete API token');
            }

            // Remove the token from the list
            tokens.value = tokens.value.filter(t => t.id !== tokenId);

            return data;
        } catch (err: any) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const clearNewToken = () => {
        newToken.value = null;
    };

    return {
        loading,
        error,
        tokens,
        newToken,
        fetchTokens,
        createToken,
        updateToken,
        deleteToken,
        clearNewToken,
    };
}
