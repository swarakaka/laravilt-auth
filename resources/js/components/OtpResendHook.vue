<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useLocalization } from '@laravilt/support/composables';
import { Button } from '@laravilt/support/components/ui/button';

const { trans } = useLocalization();

const props = defineProps<{
    resendUrl: string;
    expiresAt?: number | null;
}>();

const isResending = ref(false);
const countdown = ref(0);
const isExpired = ref(false);

// Calculate remaining time
const updateCountdown = () => {
    if (!props.expiresAt) {
        isExpired.value = true;
        return;
    }

    const now = Math.floor(Date.now() / 1000);
    const remaining = props.expiresAt - now;

    if (remaining <= 0) {
        countdown.value = 0;
        isExpired.value = true;
    } else {
        countdown.value = remaining;
        isExpired.value = false;
    }
};

// Format countdown as mm:ss
const formattedCountdown = computed(() => {
    const minutes = Math.floor(countdown.value / 60);
    const seconds = countdown.value % 60;
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

// Resend OTP
const resendOtp = () => {
    isResending.value = true;
    router.post(props.resendUrl, {}, {
        preserveScroll: true,
        onFinish: () => {
            isResending.value = false;
        },
    });
};

let intervalId: number | null = null;

onMounted(() => {
    updateCountdown();
    intervalId = window.setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <div class="flex flex-col items-center gap-4 text-center">
        <!-- Countdown Timer -->
        <div v-if="!isExpired && countdown > 0" class="text-sm text-muted-foreground">
            {{ trans('laravilt-auth::auth.otp.expires_in') }}
            <span class="font-mono font-medium text-foreground">{{ formattedCountdown }}</span>
        </div>

        <!-- Expired Message -->
        <div v-else class="text-sm text-destructive">
            {{ trans('laravilt-auth::auth.otp.code_expired') }}
        </div>

        <!-- Resend Section -->
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <span>{{ trans('laravilt-auth::auth.otp.didnt_receive') }}</span>
            <Button
                type="button"
                variant="link"
                size="sm"
                class="h-auto p-0 text-primary cursor-pointer"
                :disabled="isResending"
                @click="resendOtp"
            >
                {{ isResending ? trans('laravilt-auth::auth.otp.resending') : trans('laravilt-auth::auth.otp.resend') }}
            </Button>
        </div>
    </div>
</template>
