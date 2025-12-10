<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import LaraviltForm from '@laravilt/forms/components/Form.vue';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider.vue';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout.vue';
import { Monitor, Smartphone, Tablet, MapPin, Clock, Trash2, LogOut } from 'lucide-vue-next';

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

interface Device {
    browser: string;
    platform: string;
    device_type: 'desktop' | 'mobile' | 'tablet';
}

interface Session {
    id: string;
    ip_address: string;
    user_agent: string;
    last_activity: number;
    last_activity_human: string;
    is_current: boolean;
    device: Device;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

const props = defineProps<{
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    sessions: Session[];
    currentSessionId: string;
    logoutAction: string;
    revokeAction: string;
    schema: any[];
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

const showLogoutOthersDialog = ref(false);

// Close dialog when sessions change (after logout)
watch(() => props.sessions.length, () => {
    showLogoutOthersDialog.value = false;
});

const getDeviceIcon = (deviceType: string) => {
    const icons: Record<string, any> = {
        desktop: Monitor,
        mobile: Smartphone,
        tablet: Tablet,
    };
    return icons[deviceType] || Monitor;
};

const revokeSession = (sessionId: string) => {
    if (confirm('Are you sure you want to revoke this session?')) {
        router.delete(`${props.revokeAction}/${sessionId}`);
    }
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
            <div v-if="!sessions || sessions.length === 0" class="flex flex-col items-center justify-center py-12">
                <Monitor class="h-12 w-12 text-muted-foreground/50 mb-4" />
                <p class="text-muted-foreground">
                    No active sessions found.
                </p>
                <p class="text-sm text-muted-foreground">
                    Session tracking requires database session driver.
                </p>
            </div>

            <!-- Sessions List -->
            <div v-else class="divide-y">
                <div v-for="session in sessions" :key="session.id" class="py-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3 flex-1">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                <component
                                    :is="getDeviceIcon(session.device.device_type)"
                                    class="h-5 w-5 text-primary"
                                />
                            </div>

                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium">
                                        {{ session.device.browser }} on {{ session.device.platform }}
                                    </h4>
                                    <Badge v-if="session.is_current" variant="outline" class="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400 dark:border-green-900">
                                        This Device
                                    </Badge>
                                </div>

                                <div class="flex flex-col gap-1 text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1.5">
                                        <MapPin class="h-3.5 w-3.5" />
                                        <span>{{ session.ip_address }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <Clock class="h-3.5 w-3.5" />
                                        <span>Active {{ session.last_activity_human }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Button
                            v-if="!session.is_current"
                            @click="revokeSession(session.id)"
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:text-destructive"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Logout Other Sessions -->
            <div v-if="sessions && sessions.length > 1" class="pt-6 border-t">
                <div class="mb-4">
                    <h4 class="font-medium mb-1">Log Out Other Sessions</h4>
                    <p class="text-sm text-muted-foreground">
                        Log out all other browser sessions across all your devices
                    </p>
                </div>

                <Dialog v-model:open="showLogoutOthersDialog">
                    <DialogTrigger as-child>
                        <Button variant="destructive" class="w-full">
                            <LogOut class="h-4 w-4 mr-2" />
                            Log Out Other Sessions
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Confirm Logout</DialogTitle>
                            <DialogDescription>
                                Enter your password to log out all other browser sessions
                            </DialogDescription>
                        </DialogHeader>

                        <Form
                            :action="logoutAction"
                            method="delete"
                            #default="{ errors, processing }"
                        >
                            <ErrorProvider :errors="errors">
                                <div class="space-y-4">
                                    <LaraviltForm :schema="schema" />

                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                        class="w-full"
                                    >
                                        {{ processing ? 'Logging Out...' : 'Confirm and Log Out' }}
                                    </Button>
                                </div>
                            </ErrorProvider>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </section>
    </SettingsLayout>
</template>
