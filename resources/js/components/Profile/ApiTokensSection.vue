<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import Modal from '@laravilt/support/components/Modal.vue';
import { useApiTokens } from '../../composables/useApiTokens';
import { useLocalization } from '@/composables/useLocalization';

// Initialize localization
const { trans } = useLocalization();

const showModal = ref(false);
const showTokenCreated = ref(false);
const apiTokens = useApiTokens();
const tokenName = ref<string>('');
const tokenAbilities = ref<string[]>(['read']);

const handleOpenModal = async () => {
    showModal.value = true;
    showTokenCreated.value = false;
    await apiTokens.fetchTokens();
};

const handleCreateToken = async () => {
    if (!tokenName.value) return;

    try {
        await apiTokens.createToken(tokenName.value, tokenAbilities.value);
        showTokenCreated.value = true;
        tokenName.value = '';
        tokenAbilities.value = ['read'];
    } catch (error) {
        console.error('Failed to create token:', error);
    }
};

const handleDeleteToken = async (tokenId: number) => {
    if (!confirm(trans('profile.api_tokens.confirm_delete'))) {
        return;
    }

    try {
        await apiTokens.deleteToken(tokenId);
    } catch (error) {
        console.error('Failed to delete token:', error);
    }
};

const handleCloseTokenCreated = () => {
    showTokenCreated.value = false;
    apiTokens.clearNewToken();
};

const handleCloseModal = () => {
    showModal.value = false;
    showTokenCreated.value = false;
    tokenName.value = '';
    tokenAbilities.value = ['read'];
};

const copyToken = (token: string) => {
    navigator.clipboard.writeText(token);
};

const toggleAbility = (ability: string) => {
    if (tokenAbilities.value.includes(ability)) {
        tokenAbilities.value = tokenAbilities.value.filter(a => a !== ability);
    } else {
        tokenAbilities.value.push(ability);
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ trans('profile.api_tokens.title') }}</CardTitle>
            <CardDescription>
                {{ trans('profile.api_tokens.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.api_tokens.info') }}
            </p>
            <div class="mt-4 flex justify-end">
                <Button @click="handleOpenModal">{{ trans('profile.api_tokens.manage') }}</Button>
            </div>
        </CardContent>
    </Card>

    <!-- API Tokens Modal -->
    <Modal v-model:open="showModal" :title="trans('profile.api_tokens.title')" :description="trans('profile.api_tokens.modal_description')" @close="handleCloseModal">
        <div v-if="!showTokenCreated" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.api_tokens.third_party_info') }}
            </p>

            <!-- Token List -->
            <div v-if="apiTokens.loading.value && apiTokens.tokens.value.length === 0" class="py-8 text-center">
                <div class="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                <p class="mt-2 text-sm text-muted-foreground">{{ trans('profile.api_tokens.loading') }}</p>
            </div>

            <div v-else-if="apiTokens.tokens.value.length > 0" class="space-y-2">
                <p class="text-sm font-medium">{{ trans('profile.api_tokens.your_tokens') }}</p>
                <div
                    v-for="token in apiTokens.tokens.value"
                    :key="token.id"
                    class="flex items-center gap-3 rounded-lg border p-3"
                >
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ token.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ trans('profile.api_tokens.abilities') }}: {{ token.abilities.join(', ') }}
                            <span v-if="token.last_used_at"> • {{ trans('profile.api_tokens.last_used') }} {{ token.last_used_at }}</span>
                            <span v-else> • {{ trans('profile.api_tokens.never_used') }}</span>
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="handleDeleteToken(token.id)"
                        :disabled="apiTokens.loading.value"
                    >
                        {{ trans('common.delete') }}
                    </Button>
                </div>
            </div>

            <div v-else class="text-center py-4 text-sm text-muted-foreground">
                {{ trans('profile.api_tokens.no_tokens') }}
            </div>

            <!-- Create Token Form -->
            <div class="space-y-4 pt-4 border-t">
                <p class="text-sm font-medium">{{ trans('profile.api_tokens.create_new') }}</p>

                <div class="space-y-2">
                    <Label for="token-name">{{ trans('profile.api_tokens.token_name') }}</Label>
                    <Input
                        id="token-name"
                        v-model="tokenName"
                        type="text"
                        :placeholder="trans('profile.api_tokens.token_name_placeholder')"
                    />
                </div>

                <div class="space-y-2">
                    <Label>{{ trans('profile.api_tokens.permissions') }}</Label>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="read"
                                :checked="tokenAbilities.includes('read')"
                                @update:checked="() => toggleAbility('read')"
                            />
                            <Label for="read" class="font-normal cursor-pointer">{{ trans('profile.api_tokens.permission_read') }}</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="create"
                                :checked="tokenAbilities.includes('create')"
                                @update:checked="() => toggleAbility('create')"
                            />
                            <Label for="create" class="font-normal cursor-pointer">{{ trans('profile.api_tokens.permission_create') }}</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="update"
                                :checked="tokenAbilities.includes('update')"
                                @update:checked="() => toggleAbility('update')"
                            />
                            <Label for="update" class="font-normal cursor-pointer">{{ trans('profile.api_tokens.permission_update') }}</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="delete"
                                :checked="tokenAbilities.includes('delete')"
                                @update:checked="() => toggleAbility('delete')"
                            />
                            <Label for="delete" class="font-normal cursor-pointer">{{ trans('profile.api_tokens.permission_delete') }}</Label>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="apiTokens.error.value" class="text-sm text-destructive">
                {{ apiTokens.error.value }}
            </p>
        </div>

        <!-- Token Created Success View -->
        <div v-else class="space-y-4">
            <div class="rounded-lg bg-amber-50 dark:bg-amber-950 p-4">
                <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                    {{ trans('profile.api_tokens.save_token') }}
                </p>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                    {{ trans('profile.api_tokens.save_token_warning') }}
                </p>
            </div>

            <div class="space-y-2">
                <Label>{{ trans('profile.api_tokens.new_token') }}</Label>
                <div class="flex items-center gap-2">
                    <Input
                        :model-value="apiTokens.newToken.value?.plain_text_token"
                        readonly
                        class="font-mono text-sm"
                    />
                    <Button
                        variant="outline"
                        size="icon"
                        @click="copyToken(apiTokens.newToken.value?.plain_text_token || '')"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </Button>
                </div>
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleCloseModal">{{ trans('common.close') }}</Button>

            <Button
                v-if="!showTokenCreated"
                @click="handleCreateToken"
                :disabled="apiTokens.loading.value || !tokenName || tokenAbilities.length === 0"
            >
                {{ apiTokens.loading.value ? trans('profile.api_tokens.creating') : trans('profile.api_tokens.create') }}
            </Button>

            <Button
                v-else
                @click="handleCloseTokenCreated"
            >
                {{ trans('profile.api_tokens.copied_token') }}
            </Button>
        </template>
    </Modal>
</template>
