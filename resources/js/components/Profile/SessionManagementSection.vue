<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Modal from '@laravilt/support/components/Modal.vue';
import { Separator } from '@/components/ui/separator';
import { useSessionManagement } from '../../composables/useSessionManagement';

const showModal = ref(false);
const sessionManagement = useSessionManagement();
const sessionPassword = ref<string>('');

const handleOpenModal = async () => {
    showModal.value = true;
    await sessionManagement.fetchSessions();
};

const handleLogoutSession = async (sessionId: string) => {
    if (!sessionPassword.value) return;

    try {
        await sessionManagement.logoutSession(sessionId, sessionPassword.value);
        sessionPassword.value = '';
    } catch (error) {
        console.error('Failed to logout session:', error);
    }
};

const handleLogoutOthers = async () => {
    if (!sessionPassword.value) return;

    try {
        await sessionManagement.logoutOthers(sessionPassword.value);
        showModal.value = false;
        sessionPassword.value = '';
    } catch (error) {
        console.error('Failed to logout other sessions:', error);
    }
};

const handleCloseModal = () => {
    showModal.value = false;
    sessionPassword.value = '';
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Active Sessions</CardTitle>
            <CardDescription>
                Manage and log out your active sessions on other browsers and devices.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                You can see all devices where you're currently logged in and log out of any session.
            </p>
            <div class="mt-4 flex justify-end">
                <Button variant="outline" @click="handleOpenModal">Manage Sessions</Button>
            </div>
        </CardContent>
    </Card>

    <!-- Sessions Modal -->
    <Modal v-model:open="showModal" title="Active Sessions" description="Manage your browser sessions on other devices" @close="handleCloseModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                If necessary, you may log out of all of your other browser sessions across all of your devices.
            </p>

            <div v-if="sessionManagement.loading.value && sessionManagement.sessions.value.length === 0" class="py-8 text-center">
                <div class="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                <p class="mt-2 text-sm text-muted-foreground">Loading sessions...</p>
            </div>

            <div v-else-if="sessionManagement.sessions.value.length > 0" class="space-y-3">
                <div
                    v-for="session in sessionManagement.sessions.value"
                    :key="session.id"
                    class="flex items-center gap-3 rounded-lg border p-3"
                >
                    <div class="flex size-10 items-center justify-center rounded-full bg-muted">
                        <svg class="size-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" :d="sessionManagement.getDeviceIcon(session.device.device_type)" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">
                            {{ session.is_current ? 'This Device' : `${session.device.browser} - ${session.device.platform}` }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ session.ip_address }} • {{ session.last_active_at }}
                        </p>
                    </div>
                    <span v-if="session.is_current" class="text-xs text-green-600 dark:text-green-400">Active now</span>
                    <Button
                        v-else
                        size="sm"
                        variant="ghost"
                        @click="handleLogoutSession(session.id)"
                        :disabled="sessionManagement.loading.value"
                    >
                        Logout
                    </Button>
                </div>
            </div>

            <p v-if="sessionManagement.error.value" class="text-sm text-destructive">
                {{ sessionManagement.error.value }}
            </p>

            <Separator />

            <div class="space-y-2">
                <Label for="session-password">Confirm Password</Label>
                <Input
                    id="session-password"
                    v-model="sessionPassword"
                    type="password"
                    placeholder="Enter your password"
                />
                <p class="text-xs text-muted-foreground">
                    Enter your password to log out from other sessions
                </p>
            </div>
        </div>
        <template #footer>
            <Button variant="outline" @click="handleCloseModal">Close</Button>
            <Button
                variant="destructive"
                @click="handleLogoutOthers"
                :disabled="sessionManagement.loading.value || !sessionPassword || sessionManagement.sessions.value.filter(s => !s.is_current).length === 0"
            >
                {{ sessionManagement.loading.value ? 'Logging out...' : 'Log Out Other Sessions' }}
            </Button>
        </template>
    </Modal>
</template>
