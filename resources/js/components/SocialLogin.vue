<script setup lang="ts">
import { computed, onMounted } from 'vue';

interface SocialProvider {
    name: string;
    label: string;
    icon: string;
    colorClasses: string;
}

const props = defineProps<{
    providers?: SocialProvider[];
    redirectUrl?: string;
}>();

const enabledProviders = computed(() => {
    return props.providers || [];
});

onMounted(() => {
    console.log('SocialLogin mounted', {
        providers: props.providers,
        redirectUrl: props.redirectUrl,
        enabledProviders: enabledProviders.value
    });
});

const getProviderUrl = (provider: SocialProvider) => {
    if (!props.redirectUrl) {
        return `/auth/${provider.name}/redirect`;
    }

    // Replace :provider placeholder with actual provider name
    return props.redirectUrl.replace(':provider', provider.name);
};
</script>

<template>
    <div v-if="enabledProviders.length > 0" class="space-y-3">
        <div v-if="$slots.default" class="relative">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t" />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">
                    <slot />
                </span>
            </div>
        </div>

        <div class="flex items-center justify-center gap-3">
            <a
                v-for="provider in enabledProviders"
                :key="provider.name"
                :href="getProviderUrl(provider)"
                :title="provider.label"
                class="group relative inline-flex h-10 w-10 items-center justify-center rounded-md border transition-all hover:scale-110 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                :class="provider.colorClasses"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor">
                    <path :d="provider.icon" />
                </svg>

                <!-- Tooltip -->
                <span
                    class="pointer-events-none absolute -top-10 left-1/2 -translate-x-1/2 rounded-md bg-gray-900 px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700"
                >
                    {{ provider.label }}
                </span>
            </a>
        </div>
    </div>
</template>
