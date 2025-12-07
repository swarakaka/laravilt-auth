<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface Props {
    deleteAction: string;
    deleteSchema: any[];
}

defineProps<Props>();
</script>

<template>
    <Card class="border-destructive">
        <CardHeader>
            <CardTitle class="text-destructive">Delete Account</CardTitle>
            <CardDescription>
                Permanently delete your account and all of your data. This action cannot be undone.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="deleteAction"
                method="DELETE"
                #default="{ errors, processing }"
                class="space-y-4"
                @submit="(e) => { if (!confirm('Are you sure you want to delete your account? This action cannot be undone.')) e.preventDefault(); }"
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
                        {{ processing ? 'Deleting...' : 'Delete Account' }}
                    </Button>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
