<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import CardLayout from '@laravilt/panel/layouts/CardLayout.vue';
import Form from '@laravilt/forms/components/Form.vue';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider.vue';
import ActionButton from '@laravilt/actions/components/ActionButton.vue';
import { useNotification } from '@laravilt/notifications/app.ts';
import { Hash, Fingerprint, Mail, KeyRound } from 'lucide-vue-next';
import { useLocalization } from '@laravilt/support/composables';

const { trans } = useLocalization();

interface Props {
    page: {
        heading: string;
        subheading?: string;
        headerActions?: any[];
        actionUrl: string;
    };
    schema: any[];
    hasTwoFactorRecovery?: boolean;
    recoveryUrl?: string;
    hasPasskeys?: boolean;
    passkeyLoginOptionsUrl?: string;
    passkeyLoginUrl?: string;
    hasMagicLinks?: boolean;
    magicLinkSendUrl?: string;
    userTwoFactorMethod?: string;
    resendUrl?: string;
}

const props = defineProps<Props>();

type AuthMethod = 'code' | 'passkey' | 'magic-link' | 'recovery';

const selectedMethod = ref<AuthMethod | null>(null);
const confirmedMethod = ref<AuthMethod | null>(null);
const sendingMagicLink = ref(false);
const resendingCode = ref(false);
const { notify } = useNotification();
const formRendererRef = ref<InstanceType<typeof Form> | null>(null);
const page = usePage();
const processing = ref(false);

// Handle continue button click
const handleContinue = () => {
    if (!selectedMethod.value) return;

    // If it's a form method, show the form
    if (selectedMethod.value === 'code' || selectedMethod.value === 'recovery') {
        confirmedMethod.value = selectedMethod.value;
        return;
    }

    // If it's an action method, execute immediately
    if (selectedMethod.value === 'passkey') {
        handlePasskeyLogin();
    } else if (selectedMethod.value === 'magic-link') {
        handleSendMagicLink();
    }
};

// Handle back button
const handleBack = () => {
    confirmedMethod.value = null;
    selectedMethod.value = null;
};

// Handle form submit
const handleFormSubmit = (event: Event) => {
    event.preventDefault();

    if (processing.value) return;

    // Get form data from Form
    let data: Record<string, any> = {};
    if (formRendererRef.value && typeof formRendererRef.value.getFormData === 'function') {
        data = formRendererRef.value.getFormData();
    } else {
        console.error('Form ref not available');
        return;
    }

    processing.value = true;

    // Submit via router to the current page (POST route)
    router.post(window.location.pathname, data, {
        preserveState: (page) => Object.keys(page.props.errors || {}).length > 0,
        preserveScroll: true,
        onError: (errors) => {
            console.error('Validation errors:', errors);
        },
        onSuccess: (page) => {
            // Check for redirect in response
            if (page?.props?.redirect) {
                window.location.href = page.props.redirect;
            }
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

// Count available authentication methods
const availableMethods = computed(() => {
    const methods: Array<{ id: AuthMethod; label: string; description: string; icon: any }> = [
        {
            id: 'code',
            label: trans('laravilt-auth::auth.two_factor_challenge.authenticator_code'),
            description: trans('laravilt-auth::auth.two_factor_challenge.authenticator_desc'),
            icon: Hash
        }
    ];

    if (props.hasPasskeys) {
        methods.push({
            id: 'passkey',
            label: trans('laravilt-auth::auth.two_factor_challenge.passkey'),
            description: trans('laravilt-auth::auth.two_factor_challenge.passkey_desc'),
            icon: Fingerprint
        });
    }

    if (props.hasMagicLinks) {
        methods.push({
            id: 'magic-link',
            label: trans('laravilt-auth::auth.two_factor_challenge.magic_link'),
            description: trans('laravilt-auth::auth.two_factor_challenge.magic_link_desc'),
            icon: Mail
        });
    }

    if (props.hasTwoFactorRecovery) {
        methods.push({
            id: 'recovery',
            label: trans('laravilt-auth::auth.two_factor_challenge.recovery_code'),
            description: trans('laravilt-auth::auth.two_factor_challenge.recovery_code_desc'),
            icon: KeyRound
        });
    }

    return methods;
});

// Passkey login handler
const handlePasskeyLogin = async () => {
    if (!props.passkeyLoginOptionsUrl || !props.passkeyLoginUrl) {
        return;
    }

    try {
        console.log('Starting passkey login...');

        const optionsResponse = await fetch(props.passkeyLoginOptionsUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            }
        });

        if (!optionsResponse.ok) {
            throw new Error(`Failed to fetch WebAuthn options: ${optionsResponse.status}`);
        }

        const options = await optionsResponse.json();

        options.challenge = base64urlDecode(options.challenge);
        if (options.allowCredentials) {
            options.allowCredentials = options.allowCredentials.map((cred: any) => ({
                ...cred,
                id: base64urlDecode(cred.id)
            }));
        }

        const credential = await navigator.credentials.get({
            publicKey: options
        }) as PublicKeyCredential;

        if (!credential) {
            throw new Error('No credential received');
        }

        const assertionResponse = credential.response as AuthenticatorAssertionResponse;
        const assertionData = {
            id: credential.id,
            rawId: arrayBufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: arrayBufferToBase64url(assertionResponse.clientDataJSON),
                authenticatorData: arrayBufferToBase64url(assertionResponse.authenticatorData),
                signature: arrayBufferToBase64url(assertionResponse.signature),
                userHandle: assertionResponse.userHandle ? arrayBufferToBase64url(assertionResponse.userHandle) : null
            }
        };

        const response = await fetch(props.passkeyLoginUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(assertionData)
        });

        if (!response.ok) {
            throw new Error(`Login failed: ${response.status}`);
        }

        const result = await response.json();

        if (result.redirect) {
            window.location.href = result.redirect;
        }

    } catch (error) {
        console.error('Passkey login failed:', error);
        notify({
            type: 'error',
            message: 'Failed to login with passkey: ' + (error as Error).message,
        });
    }
};

// Magic link handler
const handleSendMagicLink = async () => {
    if (!props.magicLinkSendUrl || sendingMagicLink.value) {
        return;
    }

    sendingMagicLink.value = true;

    try {
        const response = await fetch(props.magicLinkSendUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to send magic link');
        }

        const result = await response.json();
        notify({
            type: 'success',
            message: trans('laravilt-auth::auth.two_factor_challenge.magic_link_sent'),
        });

    } catch (error) {
        console.error('Failed to send magic link:', error);
        notify({
            type: 'error',
            message: trans('laravilt-auth::auth.two_factor_challenge.magic_link_error') + ': ' + (error as Error).message,
        });
    } finally {
        sendingMagicLink.value = false;
    }
};

// Resend 2FA email code handler
const handleResendCode = async () => {
    if (!props.resendUrl || resendingCode.value) {
        return;
    }

    resendingCode.value = true;

    try {
        const response = await fetch(props.resendUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || trans('laravilt-auth::auth.two_factor_challenge.resend_error'));
        }

        const result = await response.json();
        notify({
            type: 'success',
            message: trans('laravilt-auth::auth.two_factor_challenge.code_resent'),
        });

    } catch (error) {
        console.error('Failed to resend code:', error);
        notify({
            type: 'error',
            message: (error as Error).message,
        });
    } finally {
        resendingCode.value = false;
    }
};

// Check if user is using email 2FA
const isEmailTwoFactor = computed(() => props.userTwoFactorMethod === 'email');

// Helper functions
function base64urlDecode(base64url: string): ArrayBuffer {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, '=');
    const binary = atob(padded);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
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
</script>

<template>
    <CardLayout
        :title="trans('laravilt-auth::auth.two_factor_challenge.title')"
        :description="!confirmedMethod ? trans('laravilt-auth::auth.two_factor_challenge.choose_method') : undefined"
    >
        <Head :title="trans('laravilt-auth::auth.two_factor_challenge.title')" />

        <div class="space-y-4">
            <!-- Back Button (shown when a method has been confirmed) -->
            <button
                v-if="confirmedMethod"
                @click="handleBack"
                type="button"
                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ trans('laravilt-auth::auth.two_factor_challenge.back_to_methods') }}
            </button>

            <!-- Method Selection Screen (only show when no method is confirmed) -->
            <div v-if="!confirmedMethod" class="flex flex-col gap-3">

                <div class="grid grid-cols-1 gap-2">
                    <button
                        v-for="method in availableMethods"
                        :key="method.id"
                        type="button"
                        @click="selectedMethod = method.id"
                        class="relative flex flex-col items-start gap-2 rounded-lg border-2 p-4 text-start transition-all hover:bg-accent"
                        :class="{
                            'border-primary bg-accent': selectedMethod === method.id,
                            'border-muted': selectedMethod !== method.id
                        }"
                    >
                        <div class="flex items-center gap-3 w-full">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary shrink-0">
                                <component :is="method.icon" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0 text-start">
                                <p class="font-medium text-sm leading-none mb-1">
                                    {{ method.label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ method.description }}
                                </p>
                            </div>
                            <div
                                v-if="selectedMethod === method.id"
                                class="h-4 w-4 rounded-full bg-primary flex items-center justify-center shrink-0"
                            >
                                <svg class="h-3 w-3 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Continue Button -->
                <Button
                    @click="handleContinue"
                    :disabled="!selectedMethod"
                    class="w-full"
                    size="lg"
                >
                    {{ trans('laravilt-auth::auth.common.continue') }}
                </Button>
            </div>

            <!-- Authenticator Code Form -->
            <div v-if="confirmedMethod === 'code'">
                <form
                    @submit="handleFormSubmit"
                    class="flex flex-col gap-4"
                >
                    <ErrorProvider :errors="page.props.errors || {}">
                        <Form ref="formRendererRef" :schema="schema" />

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                        >
                            {{ processing ? trans('laravilt-auth::auth.two_factor_challenge.verify_loading') : trans('laravilt-auth::auth.two_factor_challenge.verify_button') }}
                        </Button>
                    </ErrorProvider>
                </form>

                <!-- Resend Code (only for email 2FA) -->
                <div v-if="isEmailTwoFactor" class="flex items-center justify-center gap-2 text-sm text-muted-foreground mt-4">
                    <span>{{ trans('laravilt-auth::auth.two_factor_challenge.didnt_receive') }}</span>
                    <button
                        type="button"
                        class="text-primary hover:underline cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="resendingCode"
                        @click="handleResendCode"
                    >
                        {{ resendingCode ? trans('laravilt-auth::auth.two_factor_challenge.resending') : trans('laravilt-auth::auth.two_factor_challenge.resend') }}
                    </button>
                </div>
            </div>

            <!-- Recovery Code Form -->
            <div v-if="confirmedMethod === 'recovery'" class="flex flex-col gap-4 items-center py-4">
                <div class="text-center space-y-2">
                    <p class="text-sm text-muted-foreground">
                        {{ trans('laravilt-auth::auth.two_factor_challenge.use_emergency_code') }}
                    </p>
                </div>
                <Button
                    as-child
                    variant="outline"
                    class="w-full"
                    size="lg"
                >
                    <Link :href="recoveryUrl">
                        <svg class="w-5 h-5 me-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        {{ trans('laravilt-auth::auth.two_factor_challenge.enter_recovery') }}
                    </Link>
                </Button>
            </div>
        </div>
    </CardLayout>
</template>
