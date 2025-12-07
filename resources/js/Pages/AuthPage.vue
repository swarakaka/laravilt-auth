<script setup lang="ts">
import { Head, Form, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import CardLayout from '@laravilt/panel/layouts/CardLayout.vue';
import SocialLogin from '@laravilt/auth/components/SocialLogin.vue';
import FormRenderer from '@laravilt/forms/components/FormRenderer.vue';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider.vue';
import ActionButton from '@laravilt/actions/components/ActionButton.vue';
import { computed } from 'vue';

interface Props {
    page: {
        heading: string;
        subheading?: string;
        schema: any[];
        headerActions?: any[];
    };
    formAction: string;
    formMethod: string;
    status?: string;
    canResetPassword?: boolean;
    canRegister?: boolean;
    canLogin?: boolean;
    resetPasswordUrl?: string;
    registerUrl?: string;
    loginUrl?: string;
    hiddenFields?: Record<string, any>;
    hasOtpInSession?: boolean;
    socialProviders?: string[];
    socialRedirectUrl?: string;
    hasTwoFactorRecovery?: boolean;
    recoveryUrl?: string;
    hasTwoFactorChallenge?: boolean;
    challengeUrl?: string;
    hasPasskeys?: boolean;
    passkeyLoginOptionsUrl?: string;
    passkeyLoginUrl?: string;
    hasMagicLinks?: boolean;
    layout?: 'panel' | 'card' | 'simple' | 'full' | 'settings';
}

const props = defineProps<Props>();

const submitButtonText = computed(() => (processing: boolean) => {
    if (processing) {
        if (props.formAction.includes('login')) return 'Signing In...';
        if (props.formAction.includes('register')) return 'Creating Account...';
        if (props.formAction.includes('forgot-password')) return 'Sending...';
        if (props.formAction.includes('reset-password')) return 'Resetting...';
        if (props.formAction.includes('otp')) return 'Verifying...';
        if (props.formAction.includes('two-factor')) return 'Verifying...';
        return 'Processing...';
    }

    if (props.formAction.includes('login')) return 'Sign In';
    if (props.formAction.includes('register')) return 'Create Account';
    if (props.formAction.includes('forgot-password')) return 'Send Reset Link';
    if (props.formAction.includes('reset-password')) return 'Reset Password';
    if (props.formAction.includes('otp')) return 'Verify Code';
    if (props.formAction.includes('two-factor')) return 'Verify Code';
    return 'Submit';
});

// Passkey login handler
const handlePasskeyLogin = async () => {
    if (!props.passkeyLoginOptionsUrl || !props.passkeyLoginUrl) {
        return;
    }

    try {
        console.log('Starting passkey login...');

        // Get WebAuthn assertion options from server
        const optionsResponse = await fetch(props.passkeyLoginOptionsUrl, {
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

        // Convert base64url strings to ArrayBuffer
        options.challenge = base64urlDecode(options.challenge);
        if (options.allowCredentials) {
            options.allowCredentials = options.allowCredentials.map((cred: any) => ({
                ...cred,
                id: base64urlDecode(cred.id)
            }));
        }

        // Get credential using WebAuthn API
        console.log('Calling navigator.credentials.get...');
        const credential = await navigator.credentials.get({
            publicKey: options
        }) as PublicKeyCredential;

        if (!credential) {
            throw new Error('No credential received');
        }

        console.log('Credential received:', credential.id);

        // Prepare assertion data for server
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

        console.log('Sending assertion to server...');

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('CSRF token not found in page');
            throw new Error('CSRF token not found. Please refresh the page and try again.');
        }

        // Send assertion to server
        const response = await fetch(props.passkeyLoginUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(assertionData)
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server response:', response.status, errorText);
            throw new Error(`Login failed: ${response.status}${response.status === 419 ? ' (CSRF token mismatch)' : ''}`);
        }

        const result = await response.json();
        console.log('Login successful:', result);

        // Redirect to dashboard
        if (result.redirect) {
            window.location.href = result.redirect;
        }

    } catch (error) {
        console.error('Passkey login failed:', error);
        alert('Failed to login with passkey: ' + (error as Error).message);
    }
};

// Helper functions for base64url encoding/decoding
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
    <CardLayout :title="page.heading" :description="page.subheading">
        <Head :title="page.heading" />

        <!-- Status Message -->
        <div
            v-if="status"
            class="mb-4 rounded-md bg-green-50 dark:bg-green-950 p-4 text-sm font-medium text-green-600 dark:text-green-400"
        >
            {{ status }}
        </div>

        <!-- Form -->
        <Form
            :action="formAction"
            :method="formMethod"
            #default="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <ErrorProvider :errors="errors">
                <!-- Hidden Fields -->
                <input
                    v-for="(value, key) in hiddenFields"
                    :key="key"
                    type="hidden"
                    :name="key"
                    :value="value"
                />

                <!-- Render Form Fields using FormRenderer -->
                <FormRenderer :schema="page.schema" />

            <!-- Submit Button - Use Actions if provided, otherwise fallback to manual button -->
            <div v-if="page.headerActions && page.headerActions.length > 0" class="mt-4">
                <ActionButton
                    v-for="action in page.headerActions"
                    :key="action.name"
                    v-bind="action"
                    type="submit"
                    :disabled="processing"
                />
            </div>
            <Button
                v-else
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                :tabindex="page.schema.length + 1"
                data-test="login-button"
            >
                {{ submitButtonText(processing) }}
            </Button>

            <!-- Footer Links -->
            <!-- Links for Login page -->
            <div v-if="formAction.includes('login') && canRegister && registerUrl" class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <Link
                    :href="registerUrl"
                    :tabindex="5"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Sign up
                </Link>
            </div>

            <!-- Links for Register page -->
            <div v-if="formAction.includes('register') && canLogin && loginUrl" class="text-center text-sm text-muted-foreground">
                Already have an account?
                <Link
                    :href="loginUrl"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Sign in
                </Link>
            </div>

            <!-- Links for Forgot Password page -->
            <div v-if="formAction.includes('forgot-password') && canLogin && loginUrl" class="text-center text-sm text-muted-foreground">
                Remember your password?
                <Link
                    :href="loginUrl"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Back to login
                </Link>
            </div>

            <!-- OTP Resend -->
            <div v-if="formAction.includes('otp') && hasOtpInSession" class="text-center text-sm">
                <p class="text-muted-foreground text-xs mb-2">
                    Didn't receive the code?
                </p>
                <Form :method="'POST'" :action="formAction.replace('/otp', '/otp/resend')" class="inline">
                    <Button type="submit" variant="link" class="p-0 h-auto">
                        Resend Code
                    </Button>
                </Form>
            </div>

            <!-- Two-Factor Recovery Code Link -->
            <div v-if="hasTwoFactorRecovery && recoveryUrl" class="text-center text-sm text-muted-foreground">
                Lost your device?
                <Link
                    :href="recoveryUrl"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Use a recovery code
                </Link>
            </div>

            <!-- Passkey Authentication Option -->
            <div v-if="hasPasskeys && passkeyLoginOptionsUrl && passkeyLoginUrl" class="text-center text-sm text-muted-foreground">
                or
                <button
                    type="button"
                    @click="handlePasskeyLogin"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Use a passkey
                </button>
            </div>

            <!-- Back to Two-Factor Challenge -->
            <div v-if="hasTwoFactorChallenge && challengeUrl" class="text-center text-sm text-muted-foreground">
                Have your device?
                <Link
                    :href="challengeUrl"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Use authentication code
                </Link>
            </div>
            </ErrorProvider>
        </Form>

        <!-- Social Login -->
        <SocialLogin
            v-if="socialProviders && socialProviders.length > 0"
            :providers="socialProviders"
            :redirectUrl="socialRedirectUrl"
        >
            Or continue with
        </SocialLogin>
    </CardLayout>
</template>
