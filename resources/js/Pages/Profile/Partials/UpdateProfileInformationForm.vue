<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Используем computed, чтобы данные пользователя реактивно обновлялись везде при ответе сервера
const page = usePage();
const user = computed(() => page.props.auth.user);

const imagePreview = ref(null);
const fileInput = ref(null); // Ссылка на сам DOM-элемент инпута для его сброса

const form = useForm({
    _method: 'patch',
    name: user.value.name,
    email: user.value.email,
    tag: user.value.tag,
    avatar: null,
});

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const updateProfileInformation = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.avatar = null;
            imagePreview.value = null;
            if (fileInput.value) {
                fileInput.value.value = ''; // Полностью очищаем нативный инпут файла
            }
        },
    });
};

const deleteAvatar = () => {
    router.delete(route('profile.avatar.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            imagePreview.value = null;
            if (fileInput.value) {
                fileInput.value.value = ''; // Очищаем нативный инпут файла
            }
        }
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-[#1f293d] dark:text-[#1f293d]">
                Личная информация
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                Обновите данные своего профиля, аватар, уникальный тег и адрес электронной почты.
            </p>
        </header>

        <form @submit.prevent="updateProfileInformation" class="mt-6 space-y-6" enctype="multipart/form-data">
            
            <div>
                <label class="block text-sm font-bold text-[#1f293d]">Аватар профиля</label>
                
                <div class="mt-2 flex items-center space-x-5">
                    <div class="w-16 h-16 rounded-xl bg-slate-700 flex items-center justify-center text-white font-bold text-xl overflow-hidden shadow-sm shrink-0">
                        <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                        <img v-else-if="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" />
                        <span v-else>{{ user.name[0] }}</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input 
                            type="file" 
                            id="avatar" 
                            ref="fileInput"
                            accept="image/*" 
                            class="hidden" 
                            @change="handleAvatarChange"
                        />
                        <label 
                            for="avatar" 
                            class="cursor-pointer px-4 py-2 bg-white border border-gray-300 text-[#1f293d] rounded-xl font-medium text-xs shadow-sm hover:bg-gray-50 transition active:scale-[0.98]"
                        >
                            Выбрать изображение
                        </label>

                        <button 
                            v-if="user.avatar_url || imagePreview"
                            type="button"
                            @click="deleteAvatar"
                            class="px-4 py-2 bg-red-50 border border-red-200 text-red-600 rounded-xl font-medium text-xs shadow-sm hover:bg-red-100 transition active:scale-[0.98]"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div>
                <label for="name" class="block text-sm font-bold text-[#1f293d]">Имя</label>
                <input
                    id="name"
                    type="text"
                    class="mt-1 block w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-slate-700 focus:ring-slate-700"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="block text-sm font-bold text-[#1f293d]">Электронная почта (Email)</label>
                <input
                    id="email"
                    type="email"
                    class="mt-1 block w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-slate-700 focus:ring-slate-700"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <label for="tag" class="block text-sm font-bold text-[#1f293d]">Уникальный тег (@tag)</label>
                <input
                    id="tag"
                    type="text"
                    class="mt-1 block w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-slate-700 focus:ring-slate-700"
                    v-model="form.tag" 
                    required
                    autocomplete="username" 
                />
                <InputError class="mt-2" :message="form.errors.tag" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Ваш адрес электронной почты не подтвержден.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none"
                    >
                        Нажмите здесь, чтобы повторно отправить письмо.
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                    Новая ссылка отправлена на ваш email.
                </div>
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
                        <span>✓</span> <span>Сохранено</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>