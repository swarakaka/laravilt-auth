<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface Props {
    profileAction: string;
    profileSchema: any[];
    user: {
        name: string;
        email: string;
        email_verified_at?: string;
    };
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Profile Information</CardTitle>
            <CardDescription>
                Update your account's profile information and email address.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="profileAction"
                method="PATCH"
                #default="{ errors, processing }"
                class="space-y-4"
            >
                <div v-for="field in profileSchema" :key="field.name" class="space-y-2">
                    <Label :for="field.name">{{ field.label }}</Label>
                    <Input
                        :id="field.name"
                        :type="field.type || 'text'"
                        :name="field.name"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        :default-value="field.value || field.defaultValue"
                    />
                    <InputError :message="errors[field.name]" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </Form>

            <p
                v-if="!user.email_verified_at"
                class="mt-4 text-sm text-amber-600 dark:text-amber-400"
            >
                Your email address is unverified.
            </p>
        </CardContent>
    </Card>
</template>
