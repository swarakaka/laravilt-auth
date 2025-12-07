<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import FormRenderer from '@laravilt/forms/components/FormRenderer.vue';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider.vue';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout.vue';
import { CheckCircle2, Shield, Key, Download } from 'lucide-vue-next';

interface PageData {
    heading: string;
    subheading?: string | null;
}

const props = defineProps<{
    page: PageData;
    twoFactorEnabled: boolean;
    twoFactorMethod?: string | null;
    qrCode?: string | null;
    secret?: string | null;
    recoveryCodes?: string[] | null;
    needsConfirmation: boolean;
    enableAction: string;
    disableAction: string;
    confirmAction: string;
    cancelAction: string;
    regenerateAction: string;
    enableSchema: any[];
    confirmSchema: any[];
    disableSchema: any[];
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}>();

const showRecoveryCodes = ref(false);

const currentStep = computed(() => {
    // Show confirm step if QR code is present OR if confirmation is needed (email method)
    if (props.qrCode || props.needsConfirmation) return 'confirm';
    if (!props.twoFactorEnabled) return 'enable';
    return 'enabled';
});

const downloadRecoveryCodes = () => {
    if (!props.recoveryCodes) return;

    const content = props.recoveryCodes.join('\n');
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'recovery-codes.txt';
    link.click();
    URL.revokeObjectURL(url);
};
</script>

<template>
    <Head :title="page.heading" />

    <SettingsLayout
        :navigation="clusterNavigation"
        :title="clusterTitle"
        :description="clusterDescription"
    >
        <section class="max-w-2xl space-y-6">
            <!-- Page Header -->
            <header>
                <h3 class="mb-0.5 text-base font-medium">
                    {{ page.heading }}
                </h3>
                <p v-if="page.subheading" class="text-sm text-muted-foreground">
                    {{ page.subheading }}
                </p>
            </header>

            <!-- Enable Two-Factor -->
            <div v-if="currentStep === 'enable'" class="space-y-6">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 shrink-0">
                        <Shield class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h4 class="font-medium">Enable Two-Factor Authentication</h4>
                        <p class="text-sm text-muted-foreground mt-1">
                            Secure your account with an additional layer of protection
                        </p>
                    </div>
                </div>

                <ErrorProvider>
                    <FormRenderer :schema="enableSchema" />
                </ErrorProvider>
            </div>

            <!-- Confirm Two-Factor Setup -->
            <div v-if="currentStep === 'confirm'" class="space-y-6  pt-6">
                <div class="space-y-1">
                    <h4 class="font-medium">
                        {{ qrCode ? 'Scan QR Code' : (twoFactorMethod === 'email' ? 'Check Your Email' : 'Enter Verification Code') }}
                    </h4>
                    <p class="text-sm text-muted-foreground">
                        {{ qrCode ? 'Scan this QR code with your authenticator app' : (twoFactorMethod === 'email' ? 'We sent a 6-digit verification code to your email address' : 'Check your email for the verification code') }}
                    </p>
                </div>

                <div v-if="qrCode" class="flex flex-col items-center gap-4 py-4">
                    <div v-html="qrCode" class="rounded-lg border p-4 bg-white dark:bg-neutral-950" />

                    <div v-if="secret" class="w-full rounded-lg bg-muted p-3">
                        <p class="text-center text-xs text-muted-foreground mb-1">
                            Or enter this code manually
                        </p>
                        <p class="text-center font-mono text-sm font-medium">
                            {{ secret }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4 pt-6 border-t dark:border-neutral-900">
                    <div class="space-y-1">
                        <h4 class="font-medium">Verify Setup</h4>
                        <p class="text-sm text-muted-foreground">
                            {{ qrCode ? 'Enter the 6-digit code from your authenticator app' : 'Enter the 6-digit code we sent to your email' }}
                        </p>
                    </div>

                    <ErrorProvider>
                        <FormRenderer :schema="confirmSchema" />
                    </ErrorProvider>
                </div>
            </div>

            <!-- Two-Factor Enabled -->
            <div v-if="currentStep === 'enabled'" class="space-y-6">
                <!-- Status -->
                <Alert class="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950">
                    <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                    <AlertDescription class="text-green-800 dark:text-green-200">
                        <span class="font-medium">Two-factor authentication is enabled</span>
                        <span v-if="twoFactorMethod" class="block mt-1">
                            Using {{ twoFactorMethod === 'totp' ? 'Authenticator App' : 'Email' }} method
                        </span>
                    </AlertDescription>
                </Alert>

                <!-- Recovery Codes -->
                <div v-if="recoveryCodes && recoveryCodes.length" class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500/10 shrink-0">
                            <Key class="h-5 w-5 text-yellow-600 dark:text-yellow-500" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium">Recovery Codes</h4>
                            <p class="text-sm text-muted-foreground mt-1">
                                Save these codes in a secure location. Each code can only be used once.
                            </p>
                        </div>
                    </div>

                    <div v-if="showRecoveryCodes" class="grid grid-cols-2 gap-2 rounded-lg bg-muted p-4 max-h-48 overflow-y-auto">
                        <code
                            v-for="(code, index) in recoveryCodes"
                            :key="index"
                            class="text-center font-mono text-sm"
                        >
                            {{ code }}
                        </code>
                    </div>
                    <div v-else class="rounded-lg border border-dashed p-8 text-center">
                        <p class="text-sm text-muted-foreground">
                            Click below to view your recovery codes
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            @click="showRecoveryCodes = !showRecoveryCodes"
                            variant="outline"
                            class="flex-1"
                        >
                            {{ showRecoveryCodes ? 'Hide Codes' : 'Show Codes' }}
                        </Button>
                        <Button
                            v-if="showRecoveryCodes"
                            @click="downloadRecoveryCodes"
                            variant="outline"
                        >
                            <Download class="h-4 w-4 mr-2" />
                            Download
                        </Button>
                    </div>

                    <Form
                        :action="regenerateAction"
                        method="post"
                        #default="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="ghost"
                            size="sm"
                            :disabled="processing"
                            class="w-full"
                        >
                            {{ processing ? 'Regenerating...' : 'Generate New Recovery Codes' }}
                        </Button>
                    </Form>
                </div>

                <!-- Disable Two-Factor -->
                <div class="space-y-4 border-t pt-6 dark:border-neutral-900">
                    <h4 class="font-medium">Disable Two-Factor Authentication</h4>
                    <p class="text-sm text-muted-foreground">
                        This will remove the extra layer of security from your account
                    </p>

                    <ErrorProvider>
                        <FormRenderer :schema="disableSchema" />
                    </ErrorProvider>
                </div>
            </div>
        </section>
    </SettingsLayout>
</template>
