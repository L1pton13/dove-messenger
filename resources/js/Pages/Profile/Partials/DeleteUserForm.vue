<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-bold text-slate-800 dark:text-[#1f293d]">
                Удаление аккаунта
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                Как только ваш аккаунт будет удален, все его ресурсы и данные будут безвозвратно утеряны. Перед удалением, пожалуйста, скачайте любую важную информацию, которую вы хотите сохранить.
            </p>
        </header>

        <DangerButton 
            @click="confirmUserDeletion"
            class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition shadow-md active:scale-[0.98]"
        >
            Удалить аккаунт
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 text-left">
                <h2 class="text-lg font-bold text-slate-800 dark:text-gray-100">
                    Вы уверены, что хотите удалить свой аккаунт?
                </h2>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Это действие необратимо. Пожалуйста, введите ваш текущий пароль, чтобы подтвердить, что вы действительно хотите навсегда удалить этот профиль.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Пароль"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full md:w-3/4 rounded-xl border-gray-300 shadow-sm focus:border-slate-700 focus:ring-slate-700"
                        placeholder="Введите ваш пароль"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton 
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition"
                    >
                        Отмена
                    </SecondaryButton>

                    <DangerButton
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition shadow-sm"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Удалить окончательно
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>