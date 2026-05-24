<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="min-h-screen bg-[#111622] flex flex-col font-sans text-gray-300 antialiased">
        <Head title="Добро пожаловать в Dove" />

        <header class="w-full bg-[#161d2a] border-b border-[#1f293d] h-16 flex items-center px-6 justify-between shrink-0">
            <div class="flex items-center space-x-2">
                <span class="text-xl">🕊️</span>
                <span class="text-white font-bold text-lg tracking-wide">Dove</span>
            </div>
            
            <nav v-if="canLogin" class="flex items-center space-x-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="text-sm font-medium bg-[#16223f] hover:bg-[#1f2f54] text-white px-4 h-9 flex items-center rounded-xl transition duration-150"
                >
                    В панель
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="text-gray-400 hover:text-white text-sm font-medium transition duration-150"
                    >
                        Войти
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="text-sm font-medium bg-[#16223f] hover:bg-[#1f2f54] text-white px-4 h-9 flex items-center rounded-xl transition duration-150"
                    >
                        Регистрация
                    </Link>
                </template>
            </nav>
        </header>

        <div class="flex-1 flex flex-col justify-center items-center p-4 sm:p-6 bg-[#111622]">
            <div class="w-full max-w-5xl my-6">
                
                <main class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                    
                    <div class="flex flex-col items-start gap-6 overflow-hidden bg-[#161d2a] rounded-2xl p-6 sm:p-8 shadow-sm border border-[#1f293d] md:row-span-2">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#16223f] text-white sm:size-14">
                            <span class="text-2xl">🕊️</span>
                        </div>
                        
                        <div>
                            <h2 class="text-xl font-bold text-white tracking-wide">
                                Dove Messenger – Свобода общения
                            </h2>
                            <p class="mt-3 text-sm text-gray-400 leading-relaxed">
                                Добро пожаловать в современный и абсолютно безопасный мессенджер с открытым исходным кодом. 
                                Мы верим в приватность, поэтому ваши сообщения защищены передовыми алгоритмами шифрования.
                            </p>
                            <p class="mt-4 text-xs text-gray-500">
                                Присоединяйтесь к нашему комьюнити, разворачивайте сервер на своем железе или просто общайтесь в нашей сети.
                            </p>
                        </div>
                    </div>

                    <a
                        href="https://github.com/L1pton13"
                        target="_blank"
                        class="flex items-start gap-4 bg-[#161d2a] rounded-2xl p-6 shadow-sm border border-[#1f293d] transition duration-150 hover:border-gray-600 focus:outline-none"
                    >
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#111622] border border-[#1f293d] sm:size-14">
                            <svg class="size-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.137 20.162 22 16.418 22 12c0-5.523-4.477-10-10-10z" />
                            </svg>
                        </div>

                        <div class="pt-0.5 flex-1">
                            <h2 class="text-base font-bold text-white flex items-center gap-1.5 tracking-wide">
                                Исходный код на GitHub
                                <svg class="size-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </h2>
                            <p class="mt-1.5 text-xs text-gray-400 leading-relaxed">
                                Проект разрабатывается открыто. Вы можете изучить архитектуру приложения или сообщить о багах.
                            </p>
                        </div>
                    </a>

                    <div class="flex flex-col justify-between bg-[#161d2a] rounded-2xl p-6 shadow-sm border border-[#1f293d]">
                        <div class="flex items-start gap-4">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#111622] border border-[#1f293d] sm:size-14">
                                <svg class="size-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L22 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>

                            <div class="pt-0.5">
                                <h2 class="text-base font-bold text-white tracking-wide">
                                    Контакты разработчика
                                </h2>
                                <p class="mt-1.5 text-xs text-gray-400 leading-relaxed">
                                    Остались вопросы, предложения по сотрудничеству или хотите обсудить кастомную разработку? Напишите мне:
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-[#1f293d] pt-4">
                            <a 
                                href="https://t.me/@l_1_p_t_o_n" 
                                target="_blank"
                                class="flex items-center text-xs font-medium text-gray-400 hover:text-white transition duration-150"
                            >
                                <span class="font-bold text-white mr-1">TG:</span> @l_1_p_t_o_n
                            </a>
                            <a 
                                href="mailto:evgeniy13yarkovich@gmail.com" 
                                class="flex items-center text-xs font-medium text-gray-400 hover:text-white transition duration-150"
                            >
                                <span class="font-bold text-white mr-1">Email:</span> evgeniy13yarkovich@gmail.com
                            </a>
                        </div>
                    </div>

                </main>

                <footer class="mt-12 text-center text-xs text-gray-500">
                    Dove Messenger разработан на Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </footer>

            </div>
        </div>
    </div>
</template>