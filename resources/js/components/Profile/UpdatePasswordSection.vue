<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface Props {
    passwordAction: string;
    passwordSchema: any[];
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Update Password</CardTitle>
            <CardDescription>
                Ensure your account is using a long, random password to stay secure.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="passwordAction"
                method="PUT"
                #default="{ errors, processing }"
                class="space-y-4"
            >
                <div v-for="field in passwordSchema" :key="field.name" class="space-y-2">
                    <Label :for="field.name">{{ field.label }}</Label>
                    <Input
                        :id="field.name"
                        :type="field.type || 'password'"
                        :name="field.name"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        autocomplete="new-password"
                    />
                    <InputError :message="errors[field.name]" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Updating...' : 'Update Password' }}
                    </Button>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
