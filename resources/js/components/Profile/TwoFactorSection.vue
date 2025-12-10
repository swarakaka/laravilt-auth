<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Form, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Modal from '@laravilt/support/components/Modal.vue';
import LaraviltForm from '@laravilt/forms/components/Form.vue';
import ActionButton from '../../../../../actions/resources/js/components/ActionButton.vue';
import { useTwoFactor } from '../../composables/useTwoFactor';
import { useLocalization } from '@/composables/useLocalization';

// Initialize localization
const { trans } = useLocalization();

interface TwoFactorProvider {
    name: string;
    label: string;
    icon: string;
    requiresSending: boolean;
    requiresConfirmation: boolean;
}

interface TwoFactorAction {
    name: string;
    label?: string;
    color?: string;
    icon?: string;
    url?: string;
    openUrlInNewTab?: boolean;
    requiresConfirmation?: boolean;
    modalHeading?: string;
    modalDescription?: string;
    modalSubmitActionLabel?: string;
    modalCancelActionLabel?: string;
    modalFormSchema?: any[];
    isDisabled?: boolean;
    isOutlined?: boolean;
    size?: string;
}

interface Props {
    twoFactorStatus?: {
        enabled: boolean;
        confirmed: boolean;
        method: string | null;
        available_providers?: TwoFactorProvider[];
        schemas?: {
            enable: any[];
            confirm: any[];
            disable: any[];
        };
        actions?: {
            enable: string | TwoFactorAction;
            confirm: string;
            disable: string | TwoFactorAction;
        };
        setup_data?: {
            qr_code?: string;
            secret?: string;
            recovery_codes?: string[];
            method?: string;
        };
    };
    enableAction?: TwoFactorAction;
    disableAction?: TwoFactorAction;
}

const props = defineProps<Props>();

const is2FAEnabled = computed(() => props.twoFactorStatus?.enabled || false);
const showModal = ref(false);
const twoFactor = useTwoFactor();
const twoFactorStep = ref<'enable' | 'setup' | 'verify' | 'recovery' | 'disable'>('enable');
const selectedMethod = ref<string>('totp');

const selectedProvider = computed(() =>
    props.twoFactorStatus?.available_providers?.find(p => p.name === selectedMethod.value)
);

// Initialize selected method when available providers are loaded
onMounted(() => {
    if (props.twoFactorStatus?.available_providers && props.twoFactorStatus.available_providers.length > 0) {
        selectedMethod.value = props.twoFactorStatus.available_providers[0].name;
    }
});

// Watch for setup data from session (after enable form submission)
watch(
    () => props.twoFactorStatus?.setup_data,
    (setupData) => {
        if (setupData && setupData.method) {
            // Update selected method from session
            selectedMethod.value = setupData.method;

            // Store in composable
            twoFactor.twoFactorData.value = {
                qr_code: setupData.qr_code,
                secret: setupData.secret,
                recovery_codes: setupData.recovery_codes,
            };

            // Determine next step based on provider
            const provider = props.twoFactorStatus?.available_providers?.find(p => p.name === setupData.method);

            if (provider?.requiresConfirmation) {
                twoFactorStep.value = 'setup';
            } else if (provider?.requiresSending) {
                twoFactorStep.value = 'verify';
            }
        }
    },
    { immediate: true, deep: true }
);

const handleEnableSuccess = () => {
    // The Inertia form will reload the page automatically with session data
    // The watch above will handle showing the next step
};

const handleConfirmSuccess = () => {
    twoFactorStep.value = 'recovery';
};

const handleDisableSuccess = () => {
    showModal.value = false;
    router.reload();
};

const handleCloseModal = () => {
    showModal.value = false;
    twoFactorStep.value = is2FAEnabled.value ? 'disable' : 'enable';
    twoFactor.reset();
};

const handleOpenModal = () => {
    twoFactorStep.value = is2FAEnabled.value ? 'disable' : 'enable';
    showModal.value = true;
};

const handleFinishRecoveryCodes = () => {
    showModal.value = false;
    router.reload();
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ trans('profile.two_factor.title') }}</CardTitle>
            <CardDescription>
                {{ trans('profile.two_factor.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="is2FAEnabled" class="space-y-3">
                <div class="flex items-center gap-2">
                    <div class="flex size-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-950">
                        <svg class="size-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ trans('profile.two_factor.enabled') }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ trans('profile.two_factor.method', { method: twoFactorStatus?.method?.toUpperCase() || 'TOTP' }) }}
                        </p>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                {{ trans('profile.two_factor.not_enabled') }}
            </p>
            <div class="mt-4 flex justify-end">
                <ActionButton
                    v-if="!is2FAEnabled && enableAction"
                    v-bind="enableAction"
                    @success="handleEnableSuccess"
                />
                <Button v-else :variant="is2FAEnabled ? 'outline' : 'default'" @click="handleOpenModal">
                    {{ is2FAEnabled ? trans('profile.two_factor.manage') : trans('profile.two_factor.enable') }}
                </Button>
            </div>
        </CardContent>
    </Card>

    <!-- Two-Factor Modal -->
    <Modal v-model:open="showModal" :title="is2FAEnabled ? trans('profile.two_factor.disable_title') : trans('profile.two_factor.enable_title')" @close="handleCloseModal">
        <!-- Step 1: Enable 2FA (select method and password using Form) -->
        <div v-if="twoFactorStep === 'enable' && twoFactorStatus?.schemas?.enable">
            <Form
                :action="twoFactorStatus.actions?.enable || '/two-factor/enable'"
                method="POST"
                @success="handleEnableSuccess"
                #default="{ errors, processing }"
            >
                <div class="space-y-4">
                    <LaraviltForm :schema="twoFactorStatus.schemas.enable" />
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" type="button" @click="handleCloseModal">{{ trans('common.cancel') }}</Button>
                    <Button type="submit" :disabled="processing || twoFactor.loading.value">
                        {{ processing || twoFactor.loading.value ? trans('profile.two_factor.enabling') : trans('profile.two_factor.continue') }}
                    </Button>
                </div>
            </Form>
        </div>

        <!-- Step 2: Scan QR code (only for TOTP) -->
        <div v-else-if="twoFactorStep === 'setup' && selectedProvider?.requiresConfirmation" class="space-y-4">
            <div class="text-center">
                <p class="text-sm text-muted-foreground mb-4">
                    {{ trans('profile.two_factor.scan_qr') }}
                </p>
                <div v-if="twoFactor.twoFactorData.value?.qr_code" v-html="twoFactor.twoFactorData.value.qr_code" class="flex justify-center"></div>
            </div>

            <div class="space-y-2">
                <Label for="secret">{{ trans('profile.two_factor.enter_manually') }}</Label>
                <Input
                    id="secret"
                    :model-value="twoFactor.twoFactorData.value?.secret"
                    readonly
                    class="font-mono"
                />
            </div>

            <Form
                :action="twoFactorStatus?.actions?.confirm || '/two-factor/confirm'"
                method="POST"
                @success="handleConfirmSuccess"
                #default="{ errors, processing }"
            >
                <div class="space-y-4">
                    <LaraviltForm :schema="twoFactorStatus?.schemas?.confirm || []" />
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" type="button" @click="handleCloseModal">{{ trans('common.cancel') }}</Button>
                    <Button type="submit" :disabled="processing || twoFactor.loading.value">
                        {{ processing || twoFactor.loading.value ? trans('profile.two_factor.verifying') : trans('profile.two_factor.verify_code') }}
                    </Button>
                </div>
            </Form>
        </div>

        <!-- Step 2b: Verify email code (for Email 2FA) -->
        <div v-else-if="twoFactorStep === 'verify' && selectedProvider?.requiresSending" class="space-y-4">
            <div class="rounded-lg bg-blue-50 dark:bg-blue-950 p-4">
                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    {{ trans('profile.two_factor.check_email') }}
                </p>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    {{ trans('profile.two_factor.code_sent') }}
                </p>
            </div>

            <Form
                :action="twoFactorStatus?.actions?.confirm || '/two-factor/confirm'"
                method="POST"
                @success="handleConfirmSuccess"
                #default="{ errors, processing }"
            >
                <div class="space-y-4">
                    <LaraviltForm :schema="twoFactorStatus?.schemas?.confirm || []" />
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" type="button" @click="handleCloseModal">{{ trans('common.cancel') }}</Button>
                    <Button type="submit" :disabled="processing || twoFactor.loading.value">
                        {{ processing || twoFactor.loading.value ? trans('profile.two_factor.verifying') : trans('profile.two_factor.verify_code') }}
                    </Button>
                </div>
            </Form>
        </div>

        <!-- Step 3: Show recovery codes -->
        <div v-else-if="twoFactorStep === 'recovery'" class="space-y-4">
            <div class="rounded-lg bg-amber-50 dark:bg-amber-950 p-4">
                <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                    {{ trans('profile.two_factor.save_recovery') }}
                </p>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                    {{ trans('profile.two_factor.recovery_warning') }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 p-4 bg-muted rounded-lg font-mono text-sm">
                <div v-for="code in twoFactor.twoFactorData.value?.recovery_codes" :key="code">
                    {{ code }}
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <Button @click="handleFinishRecoveryCodes">
                    {{ trans('profile.two_factor.saved_codes') }}
                </Button>
            </div>
        </div>

        <!-- Disable 2FA using Form -->
        <div v-else-if="twoFactorStep === 'disable' && twoFactorStatus?.schemas?.disable">
            <p class="text-sm text-muted-foreground mb-4">
                {{ trans('profile.two_factor.enter_password') }}
            </p>

            <Form
                :action="twoFactorStatus.actions?.disable || '/two-factor/disable'"
                method="POST"
                @success="handleDisableSuccess"
                #default="{ errors, processing }"
            >
                <div class="space-y-4">
                    <LaraviltForm :schema="twoFactorStatus.schemas.disable" />
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Button variant="outline" type="button" @click="handleCloseModal">{{ trans('common.cancel') }}</Button>
                    <Button variant="destructive" type="submit" :disabled="processing || twoFactor.loading.value">
                        {{ processing || twoFactor.loading.value ? trans('profile.two_factor.enabling') : trans('profile.two_factor.disable') }}
                    </Button>
                </div>
            </Form>
        </div>
    </Modal>
</template>
