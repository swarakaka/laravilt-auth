<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Modal from '@laravilt/support/components/Modal.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { useSessionManagement } from '../../composables/useSessionManagement';
import { useLocalization } from '@/composables/useLocalization';

// Initialize localization
const { trans } = useLocalization();

const showModal = ref(false);
const showLogoutConfirm = ref(false);
const sessionToLogout = ref<string | null>(null);
const sessionManagement = useSessionManagement();
const sessionPassword = ref<string>('');

const handleOpenModal = async () => {
    showModal.value = true;
    await sessionManagement.fetchSessions();
};

const confirmLogoutSession = (sessionId: string) => {
    sessionToLogout.value = sessionId;
    showLogoutConfirm.value = true;
};

const handleLogoutSession = async () => {
    if (!sessionPassword.value || !sessionToLogout.value) return;

    try {
        await sessionManagement.logoutSession(sessionToLogout.value, sessionPassword.value);
        sessionPassword.value = '';
        showLogoutConfirm.value = false;
        sessionToLogout.value = null;
    } catch (error) {
        console.error('Failed to logout session:', error);
    }
};

const cancelLogoutSession = () => {
    showLogoutConfirm.value = false;
    sessionToLogout.value = null;
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
            <CardTitle>{{ trans('profile.sessions.title') }}</CardTitle>
            <CardDescription>
                {{ trans('profile.sessions.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.sessions.info') }}
            </p>
            <div class="mt-4 flex justify-end">
                <Button variant="outline" @click="handleOpenModal">{{ trans('profile.sessions.manage') }}</Button>
            </div>
        </CardContent>
    </Card>

    <!-- Sessions Modal -->
    <Modal v-model:open="showModal" :title="trans('profile.sessions.title')" :description="trans('profile.sessions.modal_description')" @close="handleCloseModal">
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.sessions.logout_info') }}
            </p>

            <div v-if="sessionManagement.loading.value && sessionManagement.sessions.value.length === 0" class="py-8 text-center">
                <div class="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                <p class="mt-2 text-sm text-muted-foreground">{{ trans('profile.sessions.loading') }}</p>
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
                            {{ session.is_current ? trans('profile.sessions.this_device') : `${session.device.browser} - ${session.device.platform}` }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ session.ip_address }} • {{ session.last_active_at }}
                        </p>
                    </div>
                    <span v-if="session.is_current" class="text-xs text-green-600 dark:text-green-400">{{ trans('profile.sessions.active_now') }}</span>
                    <Button
                        v-else
                        size="sm"
                        variant="ghost"
                        @click="confirmLogoutSession(session.id)"
                        :disabled="sessionManagement.loading.value"
                    >
                        {{ trans('common.logout') }}
                    </Button>
                </div>
            </div>

            <p v-if="sessionManagement.error.value" class="text-sm text-destructive">
                {{ sessionManagement.error.value }}
            </p>
        </div>
        <template #footer>
            <Button variant="outline" @click="handleCloseModal">{{ trans('common.close') }}</Button>
        </template>
    </Modal>

    <!-- Logout Session Confirmation Dialog -->
    <Dialog v-model:open="showLogoutConfirm">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ trans('profile.sessions.logout_session') }}</DialogTitle>
                <DialogDescription>
                    {{ trans('profile.sessions.logout_session_confirm') }}
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="logout-password">{{ trans('profile.sessions.confirm_password') }}</Label>
                    <Input
                        id="logout-password"
                        v-model="sessionPassword"
                        type="password"
                        :placeholder="trans('profile.sessions.password_placeholder')"
                    />
                </div>
                <p v-if="sessionManagement.error.value" class="text-sm text-destructive">
                    {{ sessionManagement.error.value }}
                </p>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="cancelLogoutSession">{{ trans('common.cancel') }}</Button>
                <Button
                    variant="destructive"
                    @click="handleLogoutSession"
                    :disabled="sessionManagement.loading.value || !sessionPassword"
                >
                    {{ sessionManagement.loading.value ? trans('profile.sessions.logging_out') : trans('common.logout') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
