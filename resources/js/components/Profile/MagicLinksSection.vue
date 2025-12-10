<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocalization } from '@/composables/useLocalization';

// Initialize localization
const { trans } = useLocalization();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ trans('profile.magic_links.title') }}</CardTitle>
            <CardDescription>
                {{ trans('profile.magic_links.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p class="text-sm text-muted-foreground">
                {{ trans('profile.magic_links.info') }}
            </p>
            <div class="mt-4 rounded-lg bg-muted p-4">
                <p class="text-sm">
                    {{ trans('profile.magic_links.ready_to_use') }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>
