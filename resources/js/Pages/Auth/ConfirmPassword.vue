<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#111622] flex flex-col font-sans">
        <Head title="Подтверждение доступа" />

        <header class="w-full bg-[#161d2a] border-b border-[#1f293d] h-16 flex items-center px-6 justify-between shrink-0">
            <div class="flex items-center space-x-2">
                <span class="text-xl">🕊️</span>
                <span class="text-white font-bold text-lg tracking-wide">Dove</span>
            </div>
            <div class="text-gray-400 text-sm">
                Безопасность
            </div>
        </header>

        <div class="flex-1 flex flex-col justify-center items-center p-4 sm:p-6 bg-[#111622]">
            
            <div class="w-full max-w-[480px] bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                
                <div class="mb-6">
                    <div class="w-12 h-12 bg-[#16223f]/5 text-[#16223f] rounded-full flex items-center justify-center text-xl mb-3">
                        🔒
                    </div>
                    <h2 class="text-xl font-bold text-[#111622]">Подтверждение пароля</h2>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                        Вы пытаетесь войти в защищенную зону приложения. Пожалуйста, подтвердите свой текущий пароль перед продолжением.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="password" value="Ваш текущий пароль" class="text-[#111622] font-bold text-sm mb-1.5 block" />

                        <input
                            id="password"
                            type="password"
                            class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 focus:border-[#16223f] focus:ring-[#16223f] rounded-xl h-11 px-4 transition duration-150 outline-none shadow-none focus:ring-1"
                            placeholder="Введите пароль"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            autofocus
                        />

                        <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.password" />
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full flex justify-center items-center h-11 bg-[#16223f] hover:bg-[#1f2f54] active:bg-[#0f182e] text-white font-medium rounded-xl transition duration-150 text-sm tracking-wide"
                            :class="{ 'opacity-50 pointer-events-none': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Проверка...</span>
                            <span v-else>Подтвердить пароль</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>