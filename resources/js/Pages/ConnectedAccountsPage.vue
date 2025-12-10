<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ActionButton from '@laravilt/actions/components/ActionButton.vue';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout.vue';
import { Github, Mail, Facebook, Twitter, Linkedin, Link as LinkIcon } from 'lucide-vue-next';

const isLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 100);
});

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface Account {
    id: string;
    provider: string;
    provider_id: string;
    name?: string;
    email?: string;
    avatar?: string;
    created_at: string;
}

interface Provider {
    name: string;
    label: string;
    connected: boolean;
    account?: Account;
    connectAction?: any;
    disconnectAction?: any;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

const props = defineProps<{
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    providers: Provider[];
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

const getProviderIcon = (provider: string) => {
    const icons: Record<string, any> = {
        github: Github,
        google: Mail,
        facebook: Facebook,
        twitter: Twitter,
        linkedin: Linkedin,
    };
    return icons[provider.toLowerCase()] || LinkIcon;
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
        <section class="max-w-2xl">
            <!-- Page Header -->
            <header>
                <h3 class="mb-0.5 text-base font-medium">
                    {{ page.heading }}
                </h3>
                <p v-if="page.subheading" class="text-sm text-muted-foreground">
                    {{ page.subheading }}
                </p>
            </header>

            <!-- Empty State -->
            <div v-if="!providers || providers.length === 0" class="flex flex-col items-center justify-center py-12">
                <LinkIcon class="h-12 w-12 text-muted-foreground/50 mb-4" />
                <p class="text-muted-foreground">
                    No social providers are currently configured.
                </p>
            </div>

            <!-- Providers List -->
            <div v-else class="divide-y">
                <div v-for="provider in providers" :key="provider.name" class="py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <component
                                :is="getProviderIcon(provider.name)"
                                class="h-8 w-8 text-muted-foreground"
                            />
                            <div>
                                <h4 class="font-medium">{{ provider.label }}</h4>
                                <p v-if="provider.connected && provider.account" class="text-sm text-muted-foreground">
                                    Connected as {{ provider.account.name || provider.account.email }}
                                </p>
                                <p v-else class="text-sm text-muted-foreground">
                                    Not connected
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Badge v-if="provider.connected" variant="outline" class="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400 dark:border-green-900">
                                Connected
                            </Badge>

                            <Button
                                v-if="provider.connectAction"
                                as="a"
                                :href="provider.connectAction.url"
                                size="sm"
                            >
                                {{ provider.connectAction.label }}
                            </Button>
                            <ActionButton
                                v-if="provider.disconnectAction"
                                v-bind="provider.disconnectAction"
                                size="sm"
                            />
                        </div>
                    </div>

                    <!-- Connected Account Details -->
                    <div
                        v-if="provider.connected && provider.account"
                        class="mt-4 pt-4 flex items-center gap-3"
                    >
                        <Avatar v-if="provider.account.avatar" class="h-10 w-10">
                            <AvatarImage :src="provider.account.avatar" />
                            <AvatarFallback>
                                {{ provider.account.name?.charAt(0).toUpperCase() || '?' }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1 min-w-0">
                            <p v-if="provider.account.name" class="text-sm font-medium truncate">
                                {{ provider.account.name }}
                            </p>
                            <p v-if="provider.account.email" class="text-xs text-muted-foreground truncate">
                                {{ provider.account.email }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Connected {{ provider.account.created_at }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </SettingsLayout>
</template>
