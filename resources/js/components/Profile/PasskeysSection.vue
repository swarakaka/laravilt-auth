<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Modal from '@laravilt/support/components/Modal.vue';
import { usePasskeys } from '../../composables/usePasskeys';

const showModal = ref(false);
const showRegisterModal = ref(false);
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

        // Start WebAuthn registration
        const credential = await navigator.credentials.create({
            publicKey: options.publicKey,
        }) as PublicKeyCredential;

        if (!credential) {
            throw new Error('Failed to create credential');
        }

        // Register the passkey with the server
        await passkeys.registerPasskey(passkeyName.value, {
            id: credential.id,
            rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
            type: credential.type,
            response: {
                clientDataJSON: btoa(String.fromCharCode(...new Uint8Array((credential.response as AuthenticatorAttestationResponse).clientDataJSON))),
                attestationObject: btoa(String.fromCharCode(...new Uint8Array((credential.response as AuthenticatorAttestationResponse).attestationObject))),
            },
        });

        showRegisterModal.value = false;
        passkeyName.value = '';
    } catch (error: any) {
        console.error('Failed to register passkey:', error);
        passkeys.error.value = error.message || 'Failed to register passkey. Please try again.';
    }
};

const handleDeletePasskey = async (passkeyId: string) => {
    if (!confirm('Are you sure you want to delete this passkey? You will no longer be able to use it to sign in.')) {
        return;
    }

    try {
        await passkeys.deletePasskey(passkeyId);
    } catch (error) {
        console.error('Failed to delete passkey:', error);
    }
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
            <CardTitle>Passkeys</CardTitle>
            <CardDescription>
                Use passkeys for secure, passwordless authentication on supported devices.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                Passkeys provide a more secure and convenient way to sign in without a password.
            </p>
            <div class="mt-4 flex justify-end">
                <Button @click="handleOpenModal">Manage Passkeys</Button>
            </div>
        </CardContent>
    </Card>

    <!-- Passkeys Modal -->
    <Modal v-model:open="showModal" title="Passkeys" description="Manage your passkeys for passwordless authentication" @close="handleCloseModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Passkeys are a secure and convenient way to sign in without a password. They use your device's biometric authentication or PIN.
            </p>

            <!-- Loading State -->
            <div v-if="passkeys.loading.value && passkeys.passkeys.value.length === 0" class="py-8 text-center">
                <div class="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                <p class="mt-2 text-sm text-muted-foreground">Loading passkeys...</p>
            </div>

            <!-- Passkeys List -->
            <div v-else-if="passkeys.passkeys.value.length > 0" class="space-y-2">
                <p class="text-sm font-medium">Your Passkeys</p>
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
                            Created {{ passkey.created_at }}
                            <span v-if="passkey.last_used_at"> • Last used {{ passkey.last_used_at }}</span>
                            <span v-else> • Never used</span>
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="handleDeletePasskey(passkey.id)"
                        :disabled="passkeys.loading.value"
                    >
                        Delete
                    </Button>
                </div>
            </div>

            <div v-else class="text-center py-4 text-sm text-muted-foreground">
                No passkeys registered yet.
            </div>

            <p v-if="passkeys.error.value" class="text-sm text-destructive">
                {{ passkeys.error.value }}
            </p>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleCloseModal">Close</Button>
            <Button @click="handleOpenRegisterModal" :disabled="passkeys.loading.value">
                Register New Passkey
            </Button>
        </template>
    </Modal>

    <!-- Register Passkey Modal -->
    <Modal v-model:open="showRegisterModal" title="Register New Passkey" description="Create a new passkey for passwordless authentication" @close="handleCloseRegisterModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Give your passkey a name to help you identify which device it's for (e.g., "MacBook Pro", "iPhone 15").
            </p>

            <div class="space-y-2">
                <Label for="passkey-name">Passkey Name</Label>
                <Input
                    id="passkey-name"
                    v-model="passkeyName"
                    type="text"
                    placeholder="My Device"
                />
            </div>

            <p v-if="passkeys.error.value" class="text-sm text-destructive">
                {{ passkeys.error.value }}
            </p>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleCloseRegisterModal">Cancel</Button>
            <Button
                @click="handleRegisterPasskey"
                :disabled="passkeys.loading.value || !passkeyName"
            >
                {{ passkeys.loading.value ? 'Registering...' : 'Register Passkey' }}
            </Button>
        </template>
    </Modal>
</template>
