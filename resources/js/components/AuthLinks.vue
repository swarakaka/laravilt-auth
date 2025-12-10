<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useLocalization } from '@laravilt/support/composables';

const { trans } = useLocalization();

const props = defineProps<{
    forgotPasswordUrl?: string;
    registerUrl?: string;
    loginUrl?: string;
    canResetPassword?: boolean;
    canRegister?: boolean;
    canLogin?: boolean;
    mode?: 'login' | 'register' | 'forgot-password';
}>();
</script>

<template>
    <div class="space-y-4">
        <!-- Login page links -->
        <template v-if="mode === 'login'">
            <div
                v-if="canRegister && registerUrl"
                class="text-center text-sm text-muted-foreground"
            >
                {{ trans('laravilt-auth::auth.login.no_account') }}
                <Link
                    :href="registerUrl"
                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    {{ trans('laravilt-auth::auth.login.sign_up') }}
                </Link>
            </div>
        </template>

        <!-- Register page links -->
        <div
            v-else-if="mode === 'register' && canLogin && loginUrl"
            class="text-center text-sm text-muted-foreground"
        >
            {{ trans('laravilt-auth::auth.register.have_account') }}
            <Link
                :href="loginUrl"
                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
            >
                {{ trans('laravilt-auth::auth.register.sign_in') }}
            </Link>
        </div>

        <!-- Forgot password page links -->
        <div
            v-else-if="mode === 'forgot-password' && canLogin && loginUrl"
            class="text-center text-sm text-muted-foreground"
        >
            {{ trans('laravilt-auth::auth.forgot_password.remember_password') }}
            <Link
                :href="loginUrl"
                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
            >
                {{ trans('laravilt-auth::auth.forgot_password.back_to_login') }}
            </Link>
        </div>
    </div>
</template>
