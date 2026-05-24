<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Настройки профиля" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-100">
                Настройки профиля
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-[calc(100vh-64px)]">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                
                <div class="bg-white p-6 shadow-sm border border-gray-200/80 sm:rounded-2xl sm:p-8">
                    <div class="max-w-xl">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm border border-gray-200/80 sm:rounded-2xl sm:p-8">
                    <div class="max-w-xl">
                        <UpdatePasswordForm />
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm border border-gray-200/80 sm:rounded-2xl sm:p-8">
                    <div class="max-w-xl">
                        <DeleteUserForm />
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>