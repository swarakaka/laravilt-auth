<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Modal from '@laravilt/support/components/Modal.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { usePasskeys, prepareCreationOptions, arrayBufferToBase64url } from '../../composables/usePasskeys';
import { useLocalization } from '@/composables/useLocalization';

// Initialize localization
const { trans } = useLocalization();

const showModal = ref(false);
const showRegisterModal = ref(false);
const showDeleteConfirm = ref(false);
const passkeyToDelete = ref<string | null>(null);
const passkeys = usePasskeys();
const passkeyName = ref<string>('');

const handleOpenModal = async () => {
    showModal.value = true;
    await passkeys.fetchPasskeys();
};

const handleOpenRegisterModal = () => {
    showRegisterModal.value = true;
    passkeyName.value = '';
};

const handleRegisterPasskey = async () => {
    if (!passkeyName.value) return;

    try {
        // Get registration options from the server
        const options = await passkeys.getRegistrationOptions();

        console.log('Raw options from server:', JSON.stringify(options, null, 2));

        // Prepare options by converting base64 strings to ArrayBuffers
        const publicKeyOptions = prepareCreationOptions(options);

        console.log('Prepared options:', publicKeyOptions);

        // Start WebAuthn registration
        const credential = await navigator.credentials.create({
            publicKey: publicKeyOptions,
        }) as PublicKeyCredential;

        if (!credential) {
            throw new Error('Failed to create credential');
        }

        const response = credential.response as AuthenticatorAttestationResponse;

        // Register the passkey with the server using base64url encoding
        await passkeys.registerPasskey(passkeyName.value, {
            id: credential.id,
            rawId: arrayBufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: arrayBufferToBase64url(response.clientDataJSON),
                attestationObject: arrayBufferToBase64url(response.attestationObject),
            },
        });

        showRegisterModal.value = false;
        passkeyName.value = '';
    } catch (error: any) {
        console.error('Failed to register passkey:', error);
        passkeys.error.value = error.message || 'Failed to register passkey. Please try again.';
    }
};

const confirmDeletePasskey = (passkeyId: string) => {
    passkeyToDelete.value = passkeyId;
    showDeleteConfirm.value = true;
};

const handleDeletePasskey = async () => {
    if (!passkeyToDelete.value) return;

    try {
        await passkeys.deletePasskey(passkeyToDelete.value);
        showDeleteConfirm.value = false;
        passkeyToDelete.value = null;
    } catch (error) {
        console.error('Failed to delete passkey:', error);
    }
};

const cancelDeletePasskey = () => {
    showDeleteConfirm.value = false;
    passkeyToDelete.value = null;
};

const handleCloseModal = () => {
    showModal.value = false;
};

const handleCloseRegisterModal = () => {
    showRegisterModal.value = false;
    passkeyName.value = '';
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ trans('profile.passkeys.title') }}</CardTitle>
            <CardDescription>
                {{ trans('profile.passkeys.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.passkeys.info') }}
            </p>
            <div class="mt-4 flex justify-end">
                <Button @click="handleOpenModal">{{ trans('profile.passkeys.manage') }}</Button>
            </div>
        </CardContent>
    </Card>

    <!-- Passkeys Modal -->
    <Modal v-model:open="showModal" :title="trans('profile.passkeys.title')" :description="trans('profile.passkeys.modal_description')" @close="handleCloseModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.passkeys.biometric_info') }}
            </p>

            <!-- Loading State -->
            <div v-if="passkeys.loading.value && passkeys.passkeys.value.length === 0" class="py-8 text-center">
                <div class="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                <p class="mt-2 text-sm text-muted-foreground">{{ trans('profile.passkeys.loading') }}</p>
            </div>

            <!-- Passkeys List -->
            <div v-else-if="passkeys.passkeys.value.length > 0" class="space-y-2">
                <p class="text-sm font-medium">{{ trans('profile.passkeys.your_passkeys') }}</p>
                <div
                    v-for="passkey in passkeys.passkeys.value"
                    :key="passkey.id"
                    class="flex items-center gap-3 rounded-lg border p-3"
                >
                    <div class="flex size-10 items-center justify-center rounded-full bg-muted">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ passkey.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ trans('profile.passkeys.created') }} {{ passkey.created_at }}
                            <span v-if="passkey.last_used_at"> • {{ trans('profile.passkeys.last_used') }} {{ passkey.last_used_at }}</span>
                            <span v-else> • {{ trans('profile.passkeys.never_used') }}</span>
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="confirmDeletePasskey(passkey.id)"
                        :disabled="passkeys.loading.value"
                    >
                        {{ trans('common.delete') }}
                    </Button>
                </div>
            </div>

            <div v-else class="text-center py-4 text-sm text-muted-foreground">
                {{ trans('profile.passkeys.no_passkeys') }}
            </div>

            <p v-if="passkeys.error.value" class="text-sm text-destructive">
                {{ passkeys.error.value }}
            </p>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleCloseModal">{{ trans('common.close') }}</Button>
            <Button @click="handleOpenRegisterModal" :disabled="passkeys.loading.value">
                {{ trans('profile.passkeys.register_new') }}
            </Button>
        </template>
    </Modal>

    <!-- Register Passkey Modal -->
    <Modal v-model:open="showRegisterModal" :title="trans('profile.passkeys.register_title')" :description="trans('profile.passkeys.register_description')" @close="handleCloseRegisterModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.passkeys.name_hint') }}
            </p>

            <div class="space-y-2">
                <Label for="passkey-name">{{ trans('profile.passkeys.passkey_name') }}</Label>
                <Input
                    id="passkey-name"
                    v-model="passkeyName"
                    type="text"
                    :placeholder="trans('profile.passkeys.name_placeholder')"
                />
            </div>

            <p v-if="passkeys.error.value" class="text-sm text-destructive">
                {{ passkeys.error.value }}
            </p>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleCloseRegisterModal">{{ trans('common.cancel') }}</Button>
            <Button
                @click="handleRegisterPasskey"
                :disabled="passkeys.loading.value || !passkeyName"
            >
                {{ passkeys.loading.value ? trans('profile.passkeys.registering') : trans('profile.passkeys.register') }}
            </Button>
        </template>
    </Modal>

    <!-- Delete Passkey Confirmation Dialog -->
    <Dialog v-model:open="showDeleteConfirm">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ trans('profile.passkeys.delete_title') }}</DialogTitle>
                <DialogDescription>
                    {{ trans('profile.passkeys.confirm_delete') }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="cancelDeletePasskey">{{ trans('common.cancel') }}</Button>
                <Button
                    variant="destructive"
                    @click="handleDeletePasskey"
                    :disabled="passkeys.loading.value"
                >
                    {{ passkeys.loading.value ? trans('common.deleting') : trans('common.delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
