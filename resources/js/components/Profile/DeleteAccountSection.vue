<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { useLocalization } from '@/composables/useLocalization';

// Initialize localization
const { trans } = useLocalization();

interface Props {
    deleteAction: string;
    deleteSchema: any[];
}

defineProps<Props>();
</script>

<template>
    <Card class="border-destructive">
        <CardHeader>
            <CardTitle class="text-destructive">{{ trans('profile.delete.title') }}</CardTitle>
            <CardDescription>
                {{ trans('profile.delete.description') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="deleteAction"
                method="DELETE"
                #default="{ errors, processing }"
                class="space-y-4"
                @submit="(e) => { if (!confirm(trans('profile.delete.confirm'))) e.preventDefault(); }"
            >
                <div v-for="field in deleteSchema" :key="field.name" class="space-y-2">
                    <Label :for="field.name">{{ field.label }}</Label>
                    <Input
                        :id="field.name"
                        :type="field.type || 'password'"
                        :name="field.name"
                        :placeholder="field.placeholder"
                        :required="field.required"
                    />
                    <InputError :message="errors[field.name]" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" variant="destructive" :disabled="processing">
                        {{ processing ? trans('profile.delete.deleting') : trans('profile.delete.delete') }}
                    </Button>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
