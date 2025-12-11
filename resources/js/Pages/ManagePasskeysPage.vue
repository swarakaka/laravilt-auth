<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout.vue';
import { Fingerprint, Trash2, AlertCircle, Key, Plus } from 'lucide-vue-next';
import { useLocalization } from '@/composables/useLocalization';

const { trans } = useLocalization();

const isPageLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isPageLoading.value = false;
    }, 100);
});

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface Passkey {
    id: string;
    name: string;
    created_at: string;
    last_used_at?: string;
    deleteAction?: any;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

const props = defineProps<{
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    passkeys: Passkey[];
    registerOptionsUrl: string;
    registerUrl: string;
    canRegister: boolean;
    maxPasskeys: number;
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}>();

// Transform breadcrumbs to frontend format
const transformedBreadcrumbs = computed(() => {
    if (!props.breadcrumbs) return [];
    return props.breadcrumbs.map(item => ({
        title: item.label,
        href: item.url || '#',
    }));
});

const showModal = ref(false);
const passkeyName = ref('');
const isLoading = ref(false);

// Handle passkey registration
const handlePasskeyRegistration = async () => {
    if (!passkeyName.value.trim()) {
        alert('Please enter a name for your passkey');
        return;
    }

    isLoading.value = true;
    const formData = { name: passkeyName.value };
    try {
        console.log('Starting passkey registration with name:', formData.name);

        // Get WebAuthn creation options from server
        const optionsResponse = await fetch(props.registerOptionsUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            }
        });

        if (!optionsResponse.ok) {
            const text = await optionsResponse.text();
            console.error('Failed to fetch options:', optionsResponse.status, text);
            throw new Error(`Failed to fetch WebAuthn options: ${optionsResponse.status}`);
        }

        const options = await optionsResponse.json();

        console.log('Received WebAuthn options:', options);

        // Prepare options by converting strings to ArrayBuffers
        const publicKeyOptions = prepareWebAuthnOptions(options);

        // Create credential using WebAuthn API
        console.log('Calling navigator.credentials.create...');
        const credential = await navigator.credentials.create({
            publicKey: publicKeyOptions
        }) as PublicKeyCredential;

        if (!credential) {
            throw new Error('No credential received');
        }

        console.log('Credential created:', credential.id);

        // Prepare credential data for server
        const attestationResponse = credential.response as AuthenticatorAttestationResponse;
        const credentialData = {
            id: credential.id,
            rawId: arrayBufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: arrayBufferToBase64url(attestationResponse.clientDataJSON),
                attestationObject: arrayBufferToBase64url(attestationResponse.attestationObject),
            }
        };

        console.log('Sending credential to server...');

        // Send credential to server
        // Merge the credential data with the name at root level
        router.post(props.registerUrl, {
            ...credentialData,
            name: formData.name
        }, {
            preserveState: false,
            preserveScroll: false,
            onFinish: () => {
                isLoading.value = false;
                showModal.value = false;
                passkeyName.value = '';
            }
        });

    } catch (error) {
        console.error('Passkey registration failed:', error);
        alert('Failed to register passkey: ' + (error as Error).message);
        isLoading.value = false;
    }
};

// Check if a string is a valid hex string
function isHexString(str: string): boolean {
    return /^[0-9a-fA-F]+$/.test(str) && str.length % 2 === 0;
}

// Convert hex string to Uint8Array
function hexToUint8Array(hex: string): Uint8Array {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16);
    }
    return bytes;
}

// Convert base64url, base64, or hex string to Uint8Array
function stringToUint8Array(input: string): Uint8Array {
    if (!input || typeof input !== 'string') {
        throw new Error(`Invalid input: ${typeof input}`);
    }

    // Check if it's a hex string (like user.id from Laragear)
    if (isHexString(input)) {
        return hexToUint8Array(input);
    }

    // Otherwise treat as base64url/base64
    const base64 = input.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, '=');
    const binary = atob(padded);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

function arrayBufferToBase64url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    const base64 = btoa(binary);
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

// Prepare WebAuthn creation options
function prepareWebAuthnOptions(options: any): PublicKeyCredentialCreationOptions {
    const prepared: PublicKeyCredentialCreationOptions = {
        rp: options.rp,
        challenge: stringToUint8Array(options.challenge),
        user: {
            ...options.user,
            id: stringToUint8Array(options.user.id),
        },
        pubKeyCredParams: options.pubKeyCredParams,
    };

    if (options.timeout) {
        prepared.timeout = options.timeout;
    }

    if (options.attestation) {
        prepared.attestation = options.attestation;
    }

    if (options.authenticatorSelection) {
        prepared.authenticatorSelection = options.authenticatorSelection;
    }

    // Handle excludeCredentials - convert each id to Uint8Array
    if (options.excludeCredentials && Array.isArray(options.excludeCredentials)) {
        prepared.excludeCredentials = options.excludeCredentials
            .filter((cred: any) => cred && cred.id)
            .map((cred: any) => ({
                type: cred.type || 'public-key',
                id: stringToUint8Array(cred.id),
                transports: cred.transports,
            }));
    }

    return prepared;
}

const removePasskey = (passkeyId: string) => {
    if (confirm(trans('laravilt-auth::auth.profile.passkeys.confirm_delete'))) {
        router.delete(`${props.registerUrl.replace('/register', '')}/${passkeyId}`, {
            preserveState: false,
            preserveScroll: false,
        });
    }
};
</script>

<template>
    <Head :title="page.heading" />

    <SettingsLayout
        :breadcrumbs="transformedBreadcrumbs"
        :navigation="clusterNavigation"
        :title="clusterTitle"
        :description="clusterDescription"
        :loading="isPageLoading"
    >
        <section class="max-w-2xl space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <header>
                    <h3 class="mb-0.5 text-base font-medium">
                        {{ page.heading }}
                    </h3>
                    <p v-if="page.subheading" class="text-sm text-muted-foreground">
                        {{ page.subheading }}
                    </p>
                </header>

                <Dialog v-model:open="showModal">
                    <Button
                        @click="showModal = true"
                        :disabled="!canRegister || passkeys.length >= maxPasskeys"
                    >
                        <Plus class="h-4 w-4 mr-2" />
                        {{ trans('laravilt-auth::auth.profile.passkeys.register_new') }}
                    </Button>

                    <DialogContent>
                        <DialogHeader>
                            <div class="flex items-center justify-center w-12 h-12 mb-4 rounded-full bg-primary/10">
                                <Key class="h-6 w-6 text-primary" />
                            </div>
                            <DialogTitle>{{ trans('laravilt-auth::auth.profile.passkeys.register_title') }}</DialogTitle>
                            <DialogDescription>
                                {{ trans('laravilt-auth::auth.profile.passkeys.register_description') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-4 py-4">
                            <div class="space-y-2">
                                <Label for="passkey-name">{{ trans('laravilt-auth::auth.profile.passkeys.passkey_name') }}</Label>
                                <Input
                                    id="passkey-name"
                                    v-model="passkeyName"
                                    :placeholder="trans('laravilt-auth::auth.profile.passkeys.name_placeholder')"
                                    @keyup.enter="handlePasskeyRegistration"
                                />
                                <p class="text-sm text-muted-foreground">
                                    {{ trans('laravilt-auth::auth.profile.passkeys.name_hint') }}
                                </p>
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    @click="showModal = false"
                                    :disabled="isLoading"
                                >
                                    {{ trans('laravilt-auth::auth.common.cancel') }}
                                </Button>
                                <Button
                                    @click="handlePasskeyRegistration"
                                    :disabled="isLoading || !passkeyName.trim()"
                                >
                                    {{ isLoading ? trans('laravilt-auth::auth.profile.passkeys.registering') : trans('laravilt-auth::auth.profile.passkeys.register') }}
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Info Alert -->
            <div>
                <Card class="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950">
                    <CardContent>
                        <div class="flex items-start gap-2">
                            <Fingerprint class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                            <div>
                                <p class="font-medium text-blue-900 dark:text-blue-100 mb-1">
                                    {{ trans('laravilt-auth::auth.profile.passkeys.what_are_passkeys') }}
                                </p>
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    {{ trans('laravilt-auth::auth.profile.passkeys.what_are_passkeys_desc') }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Passkey Limit Warning -->
            <div v-if="passkeys.length >= maxPasskeys">
                <Card class="border-yellow-200 bg-yellow-50 dark:border-yellow-900 dark:bg-yellow-950">
                    <CardContent class="pt-6">
                        <div class="flex items-start gap-2">
                            <AlertCircle class="h-5 w-5 text-yellow-600 dark:text-yellow-500 mt-0.5" />
                            <div>
                                <p class="font-medium text-yellow-900 dark:text-yellow-100">
                                    {{ trans('laravilt-auth::auth.profile.passkeys.limit_reached') }}
                                </p>
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    {{ trans('laravilt-auth::auth.profile.passkeys.limit_reached_desc', { max: maxPasskeys }) }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="passkeys.length === 0">
                <Card>
                    <CardContent class="flex flex-col items-center justify-center py-12">
                        <Fingerprint class="h-12 w-12 text-muted-foreground/50 mb-4" />
                        <p class="text-lg font-medium mb-1">{{ trans('laravilt-auth::auth.profile.passkeys.no_passkeys_yet') }}</p>
                        <p class="text-sm text-muted-foreground mb-4">
                            {{ trans('laravilt-auth::auth.profile.passkeys.add_passkey_desc') }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Passkeys List -->
            <div v-else class="space-y-3">
                <Card v-for="passkey in passkeys" :key="passkey.id">
                    <CardContent class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                    <Fingerprint class="h-5 w-5 text-primary" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium mb-1">{{ passkey.name }}</h4>
                                    <div class="flex gap-4 text-xs text-muted-foreground">
                                        <span>{{ trans('laravilt-auth::auth.profile.passkeys.added') }} {{ passkey.created_at }}</span>
                                        <span v-if="passkey.last_used_at">
                                            {{ trans('laravilt-auth::auth.profile.passkeys.last_used') }} {{ passkey.last_used_at }}
                                        </span>
                                        <span v-else>{{ trans('laravilt-auth::auth.profile.passkeys.never_used') }}</span>
                                    </div>
                                </div>
                            </div>

                            <Button
                                @click="removePasskey(passkey.id)"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>
    </SettingsLayout>
</template>
