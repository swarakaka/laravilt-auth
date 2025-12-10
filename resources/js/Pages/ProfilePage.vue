<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import PanelLayout from '@laravilt/panel/layouts/PanelLayout.vue';
import { Skeleton } from '@/components/ui/skeleton';
import ProfileInformationSection from '../components/Profile/ProfileInformationSection.vue';
import UpdatePasswordSection from '../components/Profile/UpdatePasswordSection.vue';
import TwoFactorSection from '../components/Profile/TwoFactorSection.vue';
import SessionManagementSection from '../components/Profile/SessionManagementSection.vue';
import ApiTokensSection from '../components/Profile/ApiTokensSection.vue';
import PasskeysSection from '../components/Profile/PasskeysSection.vue';
import MagicLinksSection from '../components/Profile/MagicLinksSection.vue';
import ConnectedAccountsSection from '../components/Profile/ConnectedAccountsSection.vue';
import DeleteAccountSection from '../components/Profile/DeleteAccountSection.vue';
import { useLocalization } from '@/composables/useLocalization';

const { trans } = useLocalization();

const isLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 100);
});

interface Props {
    page: {
        heading: string;
        profileSchema: any[];
        passwordSchema: any[];
        deleteSchema: any[];
    };
    profileAction: string;
    passwordAction: string;
    deleteAction: string;
    user: {
        name: string;
        email: string;
        email_verified_at?: string;
    };
    features?: {
        twoFactor?: boolean;
        sessionManagement?: boolean;
        apiTokens?: boolean;
        passkeys?: boolean;
        magicLinks?: boolean;
        connectedAccounts?: boolean;
        socialLogin?: boolean;
    };
    twoFactorStatus?: {
        enabled: boolean;
        confirmed: boolean;
        method: string | null;
    };
    enableAction?: any;
    disableAction?: any;
    status?: string;
}

defineProps<Props>();
</script>

<template>
    <PanelLayout>
        <Head :title="page.heading" />

        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="container max-w-4xl mx-auto">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold">{{ page.heading }}</h1>
                    <p class="text-muted-foreground">
                        {{ trans('profile.page.subheading') }}
                    </p>
                </div>

                <!-- Loading Skeleton -->
                <div v-if="isLoading" v-cloak class="grid gap-6">
                    <!-- Section Skeleton 1 -->
                    <div class="rounded-lg border p-6 space-y-4">
                        <Skeleton class="h-6 w-48" />
                        <Skeleton class="h-4 w-64" />
                        <div class="space-y-3 pt-4">
                            <Skeleton class="h-10 w-full" />
                            <Skeleton class="h-10 w-full" />
                        </div>
                        <Skeleton class="h-10 w-32" />
                    </div>

                    <!-- Section Skeleton 2 -->
                    <div class="rounded-lg border p-6 space-y-4">
                        <Skeleton class="h-6 w-48" />
                        <Skeleton class="h-4 w-64" />
                        <div class="space-y-3 pt-4">
                            <Skeleton class="h-10 w-full" />
                            <Skeleton class="h-10 w-full" />
                            <Skeleton class="h-10 w-full" />
                        </div>
                        <Skeleton class="h-10 w-32" />
                    </div>
                </div>

                <!-- Actual Content -->
                <div v-else v-cloak>
                    <!-- Status Messages -->
                    <div
                        v-if="status === 'profile-updated'"
                        class="mb-4 rounded-md bg-green-50 dark:bg-green-950 p-4 text-sm font-medium text-green-600 dark:text-green-400"
                    >
                        {{ trans('profile.page.profile_updated') }}
                    </div>

                    <div
                        v-if="status === 'password-updated'"
                        class="mb-4 rounded-md bg-green-50 dark:bg-green-950 p-4 text-sm font-medium text-green-600 dark:text-green-400"
                    >
                        {{ trans('profile.page.password_updated') }}
                    </div>

                    <div class="grid gap-6">
                    <!-- Profile Information -->
                    <ProfileInformationSection
                        :profile-action="profileAction"
                        :profile-schema="page.profileSchema"
                        :user="user"
                    />

                    <!-- Update Password -->
                    <UpdatePasswordSection
                        :password-action="passwordAction"
                        :password-schema="page.passwordSchema"
                    />

                    <!-- Two-Factor Authentication -->
                    <TwoFactorSection
                        v-if="features?.twoFactor"
                        :two-factor-status="twoFactorStatus"
                        :enable-action="enableAction"
                        :disable-action="disableAction"
                    />

                    <!-- Session Management -->
                    <SessionManagementSection v-if="features?.sessionManagement" />

                    <!-- API Tokens -->
                    <ApiTokensSection v-if="features?.apiTokens" />

                    <!-- Passkeys -->
                    <PasskeysSection v-if="features?.passkeys" />

                    <!-- Magic Links -->
                    <MagicLinksSection v-if="features?.magicLinks" />

                    <!-- Connected Accounts -->
                    <ConnectedAccountsSection v-if="features?.connectedAccounts || features?.socialLogin" />

                    <!-- Delete Account -->
                    <DeleteAccountSection
                        :delete-action="deleteAction"
                        :delete-schema="page.deleteSchema"
                    />
                    </div>
                </div>
            </div>
        </div>
    </PanelLayout>
</template>
