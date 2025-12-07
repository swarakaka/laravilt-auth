<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout.vue';
import ActionButton from '@laravilt/actions/components/ActionButton.vue';
import { Key, Copy, CheckCircle2, AlertCircle } from 'lucide-vue-next';

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface Token {
    id: number;
    name: string;
    abilities: string[];
    last_used_at?: string;
    expires_at?: string;
    expires_at_human?: string;
    created_at: string;
    is_expired: boolean;
    deleteAction: any;
}

const props = defineProps<{
    page: PageData;
    createAction: any;
    revokeAllAction: any;
    tokens: Token[];
    availableAbilities: Record<string, string>;
    maxTokens: number;
    newToken?: string;
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}>();

const copiedToken = ref(false);

const copyToken = async () => {
    if (!props.newToken) return;

    try {
        await navigator.clipboard.writeText(props.newToken);
        copiedToken.value = true;
        setTimeout(() => {
            copiedToken.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy token:', err);
    }
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
            <div class="flex items-center justify-between">
                <header>
                    <h3 class="mb-0.5 text-base font-medium">
                        {{ page.heading }}
                    </h3>
                    <p v-if="page.subheading" class="text-sm text-muted-foreground">
                        {{ page.subheading }}
                    </p>
                </header>

                <ActionButton
                    v-bind="createAction"
                    :disabled="tokens.length >= maxTokens"
                />
            </div>

            <!-- New Token Display -->
            <div v-if="newToken">
                <div class="rounded-lg border border-green-200 bg-green-50 p-6 dark:border-green-900 dark:bg-green-950">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400" />
                        <h4 class="font-medium text-green-900 dark:text-green-100">Token Created Successfully</h4>
                    </div>
                    <p class="text-sm text-green-800 dark:text-green-200 mb-4">
                        Make sure to copy your token now. You won't be able to see it again!
                    </p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 rounded bg-white dark:bg-gray-900 px-3 py-2 text-sm font-mono border">
                            {{ newToken }}
                        </code>
                        <Button @click="copyToken" variant="outline" size="sm">
                            <Copy v-if="!copiedToken" class="h-4 w-4" />
                            <CheckCircle2 v-else class="h-4 w-4 text-green-600" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Token Limit Warning -->
            <div v-if="tokens.length >= maxTokens">
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-6 dark:border-yellow-900 dark:bg-yellow-950">
                <div class="flex items-start gap-2">
                    <AlertCircle class="h-5 w-5 text-yellow-600 dark:text-yellow-500 mt-0.5" />
                    <div>
                        <p class="font-medium text-yellow-900 dark:text-yellow-100">
                            Token Limit Reached
                        </p>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            You have reached the maximum of {{ maxTokens }} tokens. Revoke a token to create a new one.
                        </p>
                    </div>
                </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="tokens.length === 0" class="flex flex-col items-center justify-center py-12">
                <Key class="h-12 w-12 text-muted-foreground/50 mb-4" />
                <p class="text-lg font-medium mb-1">No API tokens yet</p>
                <p class="text-sm text-muted-foreground mb-4">
                    Create your first token to get started
                </p>
            </div>

            <!-- Tokens List -->
            <div v-else class="divide-y">
                <div v-for="token in tokens" :key="token.id" class="py-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-medium">{{ token.name }}</h4>
                                <Badge v-if="token.is_expired" variant="destructive">
                                    Expired
                                </Badge>
                                <Badge v-else-if="token.expires_at" variant="outline">
                                    Expires {{ token.expires_at_human }}
                                </Badge>
                            </div>

                            <div class="flex flex-wrap gap-1 mb-2">
                                <Badge
                                    v-for="ability in token.abilities"
                                    :key="ability"
                                    variant="secondary"
                                    class="text-xs"
                                >
                                    {{ ability === '*' ? 'Full Access' : availableAbilities[ability] || ability }}
                                </Badge>
                            </div>

                            <div class="flex gap-4 text-xs text-muted-foreground">
                                <span>Created {{ token.created_at }}</span>
                                <span v-if="token.last_used_at">
                                    Last used {{ token.last_used_at }}
                                </span>
                                <span v-else>Never used</span>
                            </div>
                        </div>

                        <ActionButton
                            v-bind="token.deleteAction"
                        />
                    </div>
                </div>
            </div>

            <!-- Revoke All Tokens -->
            <div v-if="tokens.length > 1" class="pt-6 border-t">
                <div class="mb-4">
                    <h4 class="font-medium mb-1">Revoke All Tokens</h4>
                    <p class="text-sm text-muted-foreground">
                        This will revoke all active tokens. Applications using these tokens will lose access.
                    </p>
                </div>

                <ActionButton
                    v-bind="revokeAllAction"
                    variant="destructive"
                    class="w-full"
                />
            </div>
        </section>
    </SettingsLayout>
</template>
