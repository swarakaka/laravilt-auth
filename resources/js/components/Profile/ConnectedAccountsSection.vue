<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Modal from '@laravilt/support/components/Modal.vue';
import { useConnectedAccounts } from '../../composables/useConnectedAccounts';

const showModal = ref(false);
const connectedAccounts = useConnectedAccounts();

const handleOpenModal = async () => {
    showModal.value = true;
    await connectedAccounts.fetchConnectedAccounts();
};

const handleConnectAccount = (provider: string) => {
    // Redirect to the OAuth provider
    window.location.href = `/auth/${provider}/redirect`;
};

const handleDisconnectAccount = async (provider: string) => {
    if (!confirm(`Are you sure you want to disconnect your ${provider} account?`)) {
        return;
    }

    try {
        await connectedAccounts.disconnectAccount(provider);
    } catch (error) {
        console.error('Failed to disconnect account:', error);
    }
};

const handleCloseModal = () => {
    showModal.value = false;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Connected Accounts</CardTitle>
            <CardDescription>
                Manage third-party accounts connected to your profile for social authentication.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                You can connect or disconnect social accounts like Google, GitHub, and more.
            </p>
            <div class="mt-4 flex justify-end">
                <Button variant="outline" @click="handleOpenModal">Manage Connections</Button>
            </div>
        </CardContent>
    </Card>

    <!-- Connected Accounts Modal -->
    <Modal v-model:open="showModal" title="Connected Accounts" description="Manage your social account connections" @close="handleCloseModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Connect your social accounts to sign in quickly and securely.
            </p>

            <!-- Loading State -->
            <div v-if="connectedAccounts.loading.value && connectedAccounts.providers.value.length === 0" class="py-8 text-center">
                <div class="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                <p class="mt-2 text-sm text-muted-foreground">Loading connected accounts...</p>
            </div>

            <!-- Providers List -->
            <div v-else-if="connectedAccounts.providers.value.length > 0" class="space-y-3">
                <div
                    v-for="provider in connectedAccounts.providers.value"
                    :key="provider.name"
                    class="flex items-center gap-3 rounded-lg border p-3"
                >
                    <div class="flex size-10 items-center justify-center rounded-full bg-muted">
                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24">
                            <path :d="connectedAccounts.getProviderIcon(provider.name)" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ provider.label }}</p>
                        <p v-if="provider.connected && provider.account" class="text-xs text-muted-foreground">
                            Connected as {{ provider.account.name || provider.account.email }}
                        </p>
                        <p v-else class="text-xs text-muted-foreground">
                            Not connected
                        </p>
                    </div>
                    <Button
                        v-if="provider.connected"
                        size="sm"
                        variant="ghost"
                        @click="handleDisconnectAccount(provider.name)"
                        :disabled="connectedAccounts.loading.value"
                    >
                        Disconnect
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        @click="handleConnectAccount(provider.name)"
                        :disabled="connectedAccounts.loading.value"
                    >
                        Connect
                    </Button>
                </div>
            </div>

            <div v-else class="text-center py-4 text-sm text-muted-foreground">
                No social providers configured.
            </div>

            <p v-if="connectedAccounts.error.value" class="text-sm text-destructive">
                {{ connectedAccounts.error.value }}
            </p>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleCloseModal">Close</Button>
        </template>
    </Modal>
</template>
