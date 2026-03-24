<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const searchQuery = ref('');
const searchResult = ref(null);

const searchContact = async () => {
    if (searchQuery.value.length < 3) return;
    
    try {
        const response = await axios.get(`/search-contact?tag=${searchQuery.value}`);
        searchResult.value = response.data;
    } catch (error) {
        searchResult.value = null;
    }
};

const startChat = async (userId) => {
    try {
        const response = await axios.post('/conversations', { user_id: userId });
        const conversation = response.data;
        
        console.log('Чат готов к работе:', conversation);
        
        searchResult.value = null;
        searchQuery.value = '';
        
        // В следующем шаге мы добавим сюда переменную activeChat = conversation.id
        // чтобы правая панель поняла, какой чат открывать
    } catch (error) {
        console.error('Не удалось создать чат:', error);
    }
};

</script>

<template>
    <Head title="Чаты" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex h-[70vh] bg-white overflow-hidden shadow-sm sm:rounded-lg">
                
                <div class="w-1/3 border-r border-gray-200 flex flex-col">
                    <div class="p-4 border-b">
                        <input 
                            v-model="searchQuery"
                            @input="searchContact"
                            type="text" 
                            placeholder="Поиск по @тегу..." 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500"
                        />
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        <p 
                            v-if="searchResult" 
                            @click="startChat(searchResult.id)" 
                            class="p-2 bg-indigo-50 rounded-lg cursor-pointer hover:bg-indigo-100 transition shadow-sm border border-indigo-100"
                        >
                            Найдено: <span class="font-bold">{{ searchResult.name }}</span> (@{{ searchResult.tag }})
                        </p>

                        <p v-else class="text-gray-500 text-sm italic">
                            Здесь будут твои чаты...
                        </p>
                    </div>
                </div>

                <div class="w-2/3 flex flex-col bg-gray-50">
                    <div class="flex-1 flex items-center justify-center text-gray-400">
                        Выберите чат, чтобы начать общение 🕊️
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>