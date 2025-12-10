<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import LaraviltForm from '@laravilt/forms/components/Form.vue';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider.vue';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout.vue';
import { CheckCircle2, Shield, Key, Download } from 'lucide-vue-next';
import { useLocalization } from '@laravilt/support/composables';

const { trans } = useLocalization();

const isLoading = ref(true);

onMounted(() => {
    // Small delay to ensure smooth transition
    setTimeout(() => {
        isLoading.value = false;
    }, 100);
});

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

const props = defineProps<{
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
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

// Transform breadcrumbs to frontend format
const transformedBreadcrumbs = computed(() => {
    if (!props.breadcrumbs) return [];
    return props.breadcrumbs.map(item => ({
        title: item.label,
        href: item.url || '#',
    }));
});

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
        :breadcrumbs="transformedBreadcrumbs"
        :navigation="clusterNavigation"
        :title="clusterTitle"
        :description="clusterDescription"
        :loading="isLoading"
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
                    <div class="flex-1 text-start">
                        <h4 class="font-medium">{{ trans('laravilt-auth::auth.profile.two_factor.enable_title') }}</h4>
                        <p class="text-sm text-muted-foreground mt-1">
                            {{ trans('laravilt-auth::auth.profile.two_factor.description') }}
                        </p>
                    </div>
                </div>

                <ErrorProvider>
                    <LaraviltForm :schema="enableSchema" />
                </ErrorProvider>
            </div>

            <!-- Confirm Two-Factor Setup -->
            <div v-if="currentStep === 'confirm'" class="space-y-6 pt-6">
                <div class="space-y-1 text-start">
                    <h4 class="font-medium">
                        {{ qrCode ? trans('laravilt-auth::auth.profile.two_factor.scan_qr_title') : (twoFactorMethod === 'email' ? trans('laravilt-auth::auth.profile.two_factor.check_email') : trans('laravilt-auth::auth.profile.two_factor.verify_code')) }}
                    </h4>
                    <p class="text-sm text-muted-foreground">
                        {{ qrCode ? trans('laravilt-auth::auth.profile.two_factor.scan_qr') : (twoFactorMethod === 'email' ? trans('laravilt-auth::auth.profile.two_factor.code_sent') : trans('laravilt-auth::auth.profile.two_factor.check_email_code')) }}
                    </p>
                </div>

                <div v-if="qrCode" class="flex flex-col items-center gap-4 py-4">
                    <div v-html="qrCode" class="rounded-lg border p-4 bg-white dark:bg-neutral-950" />

                    <div v-if="secret" class="w-full rounded-lg bg-muted p-3">
                        <p class="text-center text-xs text-muted-foreground mb-1">
                            {{ trans('laravilt-auth::auth.profile.two_factor.enter_manually') }}
                        </p>
                        <p class="text-center font-mono text-sm font-medium" dir="ltr">
                            {{ secret }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4 pt-6 border-t dark:border-neutral-900">
                    <div class="space-y-1 text-start">
                        <h4 class="font-medium">{{ trans('laravilt-auth::auth.profile.two_factor.verify_setup') }}</h4>
                        <p class="text-sm text-muted-foreground">
                            {{ qrCode ? trans('laravilt-auth::auth.profile.two_factor.enter_code_app') : trans('laravilt-auth::auth.profile.two_factor.enter_code_email') }}
                        </p>
                    </div>

                    <ErrorProvider>
                        <LaraviltForm :schema="confirmSchema" />
                    </ErrorProvider>
                </div>
            </div>

            <!-- Two-Factor Enabled -->
            <div v-if="currentStep === 'enabled'" class="space-y-6">
                <!-- Status -->
                <Alert class="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950">
                    <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                    <AlertDescription class="text-green-800 dark:text-green-200">
                        <span class="font-medium">{{ trans('laravilt-auth::auth.profile.two_factor.enabled') }}</span>
                        <span v-if="twoFactorMethod" class="block mt-1">
                            {{ trans('laravilt-auth::auth.profile.two_factor.using_method', { method: twoFactorMethod === 'totp' ? trans('laravilt-auth::auth.profile.two_factor.method_totp') : trans('laravilt-auth::auth.profile.two_factor.method_email') }) }}
                        </span>
                    </AlertDescription>
                </Alert>

                <!-- Recovery Codes -->
                <div v-if="recoveryCodes && recoveryCodes.length" class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500/10 shrink-0">
                            <Key class="h-5 w-5 text-yellow-600 dark:text-yellow-500" />
                        </div>
                        <div class="flex-1 text-start">
                            <h4 class="font-medium">{{ trans('laravilt-auth::auth.profile.two_factor.recovery_codes_title') }}</h4>
                            <p class="text-sm text-muted-foreground mt-1">
                                {{ trans('laravilt-auth::auth.profile.two_factor.recovery_codes_desc') }}
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
                            {{ trans('laravilt-auth::auth.profile.two_factor.click_to_view') }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            @click="showRecoveryCodes = !showRecoveryCodes"
                            variant="outline"
                            class="flex-1"
                        >
                            {{ showRecoveryCodes ? trans('laravilt-auth::auth.profile.two_factor.hide_codes') : trans('laravilt-auth::auth.profile.two_factor.show_codes') }}
                        </Button>
                        <Button
                            v-if="showRecoveryCodes"
                            @click="downloadRecoveryCodes"
                            variant="outline"
                        >
                            <Download class="h-4 w-4 me-2" />
                            {{ trans('laravilt-auth::auth.profile.two_factor.download') }}
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
                            {{ processing ? trans('laravilt-auth::auth.profile.two_factor.regenerating') : trans('laravilt-auth::auth.profile.two_factor.regenerate_codes') }}
                        </Button>
                    </Form>
                </div>

                <!-- Disable Two-Factor -->
                <div class="space-y-4 border-t pt-6 dark:border-neutral-900 text-start">
                    <h4 class="font-medium">{{ trans('laravilt-auth::auth.profile.two_factor.disable_title') }}</h4>
                    <p class="text-sm text-muted-foreground">
                        {{ trans('laravilt-auth::auth.profile.two_factor.disable_desc') }}
                    </p>

                    <ErrorProvider>
                        <LaraviltForm :schema="disableSchema" />
                    </ErrorProvider>
                </div>
            </div>
        </section>
    </SettingsLayout>
</template>
