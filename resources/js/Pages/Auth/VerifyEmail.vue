<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <div class="min-h-screen bg-[#111622] flex flex-col font-sans">
        <Head title="Подтверждение почты" />

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
            
            <div class="w-full max-w-[500px] bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-[#16223f]/5 text-[#16223f] rounded-full flex items-center justify-center text-xl mx-auto mb-3">
                        📩
                    </div>
                    <h2 class="text-xl font-bold text-[#111622]">Подтвердите ваш Email</h2>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                        Спасибо за регистрацию в Dove! Прежде чем начать общаться, пожалуйста, подтвердите свой адрес электронной почты, перейдя по ссылке, которую мы только что отправили вам.
                    </p>
                </div>

                <div
                    v-if="verificationLinkSent"
                    class="mb-5 text-xs font-medium text-green-600 bg-green-50 p-3 rounded-xl border border-green-200 leading-relaxed"
                >
                    Новая ссылка для подтверждения была отправлена на адрес электронной почты, который вы указали при регистрации.
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                        <button
                            type="submit"
                            class="w-full sm:w-auto px-5 flex justify-center items-center h-11 bg-[#16223f] hover:bg-[#1f2f54] active:bg-[#0f182e] text-white font-medium rounded-xl transition duration-150 text-xs tracking-wide shrink-0"
                            :class="{ 'opacity-50 pointer-events-none': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Отправка...</span>
                            <span v-else>Отправить письмо повторно</span>
                        </button>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-xs text-gray-400 hover:text-red-500 font-medium transition duration-150 underline decoration-dotted py-2"
                        >
                            Выйти из аккаунта
                        </Link>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>