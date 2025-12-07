<script setup lang="ts">
import AuthLinks from './AuthLinks.vue';
import SocialLogin from './SocialLogin.vue';

const props = defineProps<{
    forgotPasswordUrl?: string;
    registerUrl?: string;
    loginUrl?: string;
    canResetPassword?: boolean;
    canRegister?: boolean;
    canLogin?: boolean;
    mode?: 'login' | 'register' | 'forgot-password';
    socialProviders?: any[];
    socialRedirectUrl?: string;
}>();
</script>

<template>
    <div class="space-y-6">
        <!-- Auth Links -->
        <AuthLinks
            :forgot-password-url="forgotPasswordUrl"
            :register-url="registerUrl"
            :login-url="loginUrl"
            :can-reset-password="canResetPassword"
            :can-register="canRegister"
            :can-login="canLogin"
            :mode="mode"
        />

        <!-- Social Login (with divider if there are providers) -->
        <div v-if="socialProviders && socialProviders.length > 0">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t"></span>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground"
                        >Or continue with</span
                    >
                </div>
            </div>

            <div class="mt-4">
                <SocialLogin
                    :providers="socialProviders"
                    :redirectUrl="socialRedirectUrl"
                />
            </div>
        </div>
    </div>
</template>
