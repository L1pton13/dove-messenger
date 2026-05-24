<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#111622] flex flex-col font-sans">
        <Head title="Регистрация профиля" />

        <header class="w-full bg-[#161d2a] border-b border-[#1f293d] h-16 flex items-center px-6 justify-between shrink-0">
            <div class="flex items-center space-x-2">
                <span class="text-xl">🕊️</span>
                <span class="text-white font-bold text-lg tracking-wide">Dove</span>
            </div>
            <div class="text-gray-400 text-sm">
                Регистрация
            </div>
        </header>

        <div class="flex-1 flex flex-col justify-center items-center p-4 sm:p-6 bg-[#111622]">
            
            <div class="w-full max-w-[480px] bg-white rounded-2xl p-8 shadow-sm border border-gray-100 my-auto">
                
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-[#111622]">Создать аккаунт</h2>
                    <p class="text-xs text-gray-500 mt-1">Заполните поля, чтобы присоединиться к Dove</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Имя пользователя" class="text-[#111622] font-bold text-sm mb-1.5 block" />

                        <input
                            id="name"
                            type="text"
                            class="mt-1 block w-full bg-white border border-gray-200 text-gray-900 focus:border-[#16223f] focus:ring-[#16223f] rounded-xl h-11 px-4 transition duration-150 outline-none shadow-none focus:ring-1"
                            placeholder="Ваше имя"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                        />

                        <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Электронная почта (Email)" class="text-[#111622] font-bold text-sm mb-1.5 block" />

                        <input
                            id="email"
                            type="email"
                            class="mt-1 block w-full bg-white border border-gray-200 text-gray-900 focus:border-[#16223f] focus:ring-[#16223f] rounded-xl h-11 px-4 transition duration-150 outline-none shadow-none focus:ring-1"
                            placeholder="example@gmail.com"
                            v-model="form.email"
                            required
                            autocomplete="username"
                        />

                        <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Пароль" class="text-[#111622] font-bold text-sm mb-1.5 block" />

                        <input
                            id="password"
                            type="password"
                            class="mt-1 block w-full bg-white border border-gray-200 text-gray-900 focus:border-[#16223f] focus:ring-[#16223f] rounded-xl h-11 px-4 transition duration-150 outline-none shadow-none focus:ring-1"
                            placeholder="Минимум 8 символов"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                        />

                        <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Подтвердите пароль" class="text-[#111622] font-bold text-sm mb-1.5 block" />

                        <input
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full bg-white border border-gray-200 text-gray-900 focus:border-[#16223f] focus:ring-[#16223f] rounded-xl h-11 px-4 transition duration-150 outline-none shadow-none focus:ring-1"
                            placeholder="Повторите пароль"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />

                        <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center justify-between pt-3">
                        <Link
                            :href="route('login')"
                            class="text-xs text-gray-500 hover:text-[#16223f] underline transition duration-150 font-medium"
                        >
                            Уже зарегистрированы?
                        </Link>

                        <button
                            type="submit"
                            class="px-6 h-11 bg-[#16223f] hover:bg-[#1f2f54] active:bg-[#0f182e] text-white font-medium rounded-xl transition duration-150 text-sm tracking-wide shadow-none"
                            :class="{ 'opacity-50 pointer-events-none': form.processing }"
                            :disabled="form.processing"
                        >
                            Зарегистрироваться
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>