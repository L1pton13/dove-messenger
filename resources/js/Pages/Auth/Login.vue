<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#111622] flex flex-col font-sans">
        <Head title="Вход в аккаунт" />

        <header class="w-full bg-[#161d2a] border-b border-[#1f293d] h-16 flex items-center px-6 justify-between shrink-0">
            <div class="flex items-center space-x-2">
                <span class="text-xl">🕊️</span>
                <span class="text-white font-bold text-lg tracking-wide">Dove</span>
            </div>
            <div class="text-gray-400 text-sm">
                Авторизация
            </div>
        </header>

        <div class="flex-1 flex flex-col justify-center items-center p-4 sm:p-6 bg-[#111622]">
            
            <div class="w-full max-w-[480px] bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-[#111622]">Вход в систему</h2>
                    <p class="text-xs text-gray-500 mt-1">Введите свои данные для доступа к чатам</p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Электронная почта (Email)" class="text-[#111622] font-bold text-sm mb-1.5 block" />

                        <input
                            id="email"
                            type="email"
                            class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 focus:border-[#16223f] focus:ring-[#16223f] rounded-xl h-11 px-4 transition duration-150 outline-none shadow-none focus:ring-1"
                            placeholder="example@gmail.com"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Текущий пароль" class="text-[#111622] font-bold text-sm mb-1.5 block" />

                        <input
                            id="password"
                            type="password"
                            class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 focus:border-[#16223f] focus:ring-[#16223f] rounded-xl h-11 px-4 transition duration-150 outline-none shadow-none focus:ring-1"
                            placeholder=""
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />

                        <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer select-none">
                            <input 
                                type="checkbox"
                                name="remember" 
                                v-model="form.remember" 
                                class="rounded border-gray-300 bg-white text-[#16223f] focus:ring-[#16223f] focus:ring-offset-0 w-4 h-4 transition duration-150 checked:bg-[#16223f] checked:border-[#16223f]"
                            />
                            <span class="ms-2 text-xs text-gray-600 font-medium">Запомнить меня</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-gray-500 hover:text-[#16223f] underline transition duration-150"
                        >
                            Забыли пароль?
                        </Link>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full flex justify-center items-center h-11 bg-[#16223f] hover:bg-[#1f2f54] active:bg-[#0f182e] text-white font-medium rounded-xl transition duration-150 text-sm tracking-wide"
                            :class="{ 'opacity-50 pointer-events-none': form.processing }"
                            :disabled="form.processing"
                        >
                            Войти
                        </button>
                    </div>
                </form>

                <div class="text-center mt-6 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500">
                        Ещё нет профиля в Dove? 
                        <Link :href="route('register')" class="text-[#16223f] hover:underline font-bold ml-1">
                            Создать аккаунт
                        </Link>
                    </p>
                </div>

            </div>
        </div>
    </div>
</template>