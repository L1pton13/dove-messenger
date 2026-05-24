<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-[#1f293d]">
                Обновление пароля
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Убедитесь, что ваш аккаунт использует длинный случайный пароль для безопасности.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <label for="current_password" class="block text-sm font-bold text-[#1f293d]">Текущий пароль</label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-slate-700 focus:ring-slate-700"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-[#1f293d]">Новый пароль</label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-slate-700 focus:ring-slate-700"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-bold text-[#1f293d]">Подтвердите новый пароль</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-slate-700 focus:ring-slate-700"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <button 
                    :disabled="form.processing"
                    type="submit"
                    class="px-5 py-2.5 bg-[#1f293d] hover:bg-slate-900 text-white font-medium rounded-xl transition shadow-md active:scale-[0.98]"
                >
                    Сохранить
                </button>

                <Transition enter-active-class="transition ease-in-out duration-300" enter-from-class="opacity-0" leave-active-class="transition ease-in-out duration-300" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-emerald-600 flex items-center space-x-1">
                        <span>✓</span> <span>Пароль изменен</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>