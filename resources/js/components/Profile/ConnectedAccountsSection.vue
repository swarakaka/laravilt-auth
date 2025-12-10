<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Modal from '@laravilt/support/components/Modal.vue';
import { useConnectedAccounts } from '../../composables/useConnectedAccounts';
import { useLocalization } from '@/composables/useLocalization';

// Initialize localization
const { trans } = useLocalization();

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
    if (!confirm(trans('profile.connected_accounts.confirm_disconnect', { provider }))) {
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
            <CardTitle>{{ trans('profile.connected_accounts.title') }}</CardTitle>
            <CardDescription>
                {{ trans('profile.connected_accounts.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.connected_accounts.info') }}
            </p>
            <div class="mt-4 flex justify-end">
                <Button variant="outline" @click="handleOpenModal">{{ trans('profile.connected_accounts.manage') }}</Button>
            </div>
        </CardContent>
    </Card>

    <!-- Connected Accounts Modal -->
    <Modal v-model:open="showModal" :title="trans('profile.connected_accounts.title')" :description="trans('profile.connected_accounts.modal_description')" @close="handleCloseModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.connected_accounts.social_info') }}
            </p>

            <!-- Loading State -->
            <div v-if="connectedAccounts.loading.value && connectedAccounts.providers.value.length === 0" class="py-8 text-center">
                <div class="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                <p class="mt-2 text-sm text-muted-foreground">{{ trans('profile.connected_accounts.loading') }}</p>
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
                            {{ trans('profile.connected_accounts.connected_as') }} {{ provider.account.name || provider.account.email }}
                        </p>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ trans('profile.connected_accounts.not_connected') }}
                        </p>
                    </div>
                    <Button
                        v-if="provider.connected"
                        size="sm"
                        variant="ghost"
                        @click="handleDisconnectAccount(provider.name)"
                        :disabled="connectedAccounts.loading.value"
                    >
                        {{ trans('profile.connected_accounts.disconnect') }}
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        @click="handleConnectAccount(provider.name)"
                        :disabled="connectedAccounts.loading.value"
                    >
                        {{ trans('profile.connected_accounts.connect') }}
                    </Button>
                </div>
            </div>

            <div v-else class="text-center py-4 text-sm text-muted-foreground">
                {{ trans('profile.connected_accounts.no_providers') }}
            </div>

            <p v-if="connectedAccounts.error.value" class="text-sm text-destructive">
                {{ connectedAccounts.error.value }}
            </p>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleCloseModal">{{ trans('common.close') }}</Button>
        </template>
    </Modal>
</template>
