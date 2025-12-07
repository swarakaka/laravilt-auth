<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Magic Links</CardTitle>
            <CardDescription>
                Configure magic link authentication for passwordless sign-in via email.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                Magic links let you sign in by clicking a link sent to your email. This feature is automatically available when enabled in your authentication settings.
            </p>
            <div class="mt-4 rounded-lg bg-muted p-4">
                <p class="text-sm">
                    Magic link authentication is ready to use. When you sign in, you can choose to receive a magic link via email instead of entering your password.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
