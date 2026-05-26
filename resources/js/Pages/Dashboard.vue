<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import emojiRegex from 'emoji-regex';

const props = defineProps({
    conversations: Array
});

const searchQuery = ref('');
const newMessageText = ref('');
const searchResult = ref(null);
const selectedChatId = ref(null);
const messagesContainer = ref(null);
const firstUnreadMessageId = ref(null);

// Реактивные переменные для файлов
const selectedFile = ref(null);
const fileInput = ref(null);

const localConversations = ref([...props.conversations]);

// Переменные для смайликов
const showEmojiPicker = ref(false);
const emojiPickerRef = ref(null);

const emojiList = {
    "Улыбки": ["😀", "😃", "😄", "😁", "😆", "😅", "😂", "🤣", "😊", "😇", "🙂", "🙃", "😉", "😌", "😍", "🥰", "😘", "😗", "😙", "😚", "😋", "😛", "😝", "😜", "🤪", "🤨", "🧐", "🤓", "😎", "🥸", "🤩", "🥳", "😏", "😒", "😞", "😔", "😟", "😕", "🙁", "☹️", "😣", "😖", "😫", "😩", "🥺", "😢", "😭", "😤", "😠", "😡", "🤬", "🤯", "😳", "🥵", "🥶", "😱", "😨", "😰", "😥", "😓", "🤗", "🤔", "🤭", "🤫", "🤥", "😶", "😐", "😑", "😬", "🙄", "😯", "😦", "😧", "😮", "😲", "🥱", "😴", "🤤", "😪", "😵", "🤐", "🥴", "🤢", "🤮", "🤧", "😷", "🤒", "🤕"],
    "Жесты/Люди": ["👋", "🤚", "🖐️", "✋", "🖖", "👌", "🤌", "🤏", "✌️", "🤞", "🤟", "🤘", "🤙", "👈", "👉", "👆", "🖕", "👇", "☝️", "👍", "👎", "✊", "👊", "🤛", "🤜", "👏", "🙌", "👐", "🤲", "🤝", "🙏", "✍️", "💅", "🤳", "💪", "🦾", "🦿", "🦵", "🦶", "👂", "🦻", "👃", "🧠", "𫠗", "𫠘", "🦷", "🦴", "👀", "👁️"],
    "Сердца": ["❤️", "🧡", "💛", "💚", "💙", "💜", "🖤", "🤍", "🤎", "💔", "❤️‍🔥", "❤️‍🩹", "❣️", "💕", "💞", "💓", "💗", "💖", "💘", "💝", "💟"]
};

const addEmoji = (emoji) => {
    newMessageText.value += emoji;
    document.getElementById('message-field-input')?.focus();
};

const isSingleEmoji = (text) => {
    if (!text) return false;
    const trimmed = text.trim();
    const regex = emojiRegex();
    const matches = trimmed.match(regex);
    return matches !== null && matches.length === 1 && matches[0] === trimmed;
};

// Функции для обработки выбора файлов
const onFileSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
    }
};

const clearSelectedFile = () => {
    selectedFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const toast = ref({ show: false, title: '', body: '', time: '', timeoutId: null });
const contextMenu = ref({ show: false, x: 0, y: 0, message: null });
const replyingToMessage = ref(null);
const showHeaderMenu = ref(false);
const confirmModal = ref({ show: false, title: '', message: '', actionType: null });

const sortedConversations = computed(() => {
    return [...localConversations.value].sort((a, b) => {
        const timeA = a.messages && a.messages.length > 0 ? new Date(a.messages[a.messages.length - 1].created_at).getTime() : new Date(a.updated_at).getTime();
        const timeB = b.messages && b.messages.length > 0 ? new Date(b.messages[b.messages.length - 1].created_at).getTime() : new Date(b.updated_at).getTime();
        return timeB - timeA;
    });
});

const getUnreadCount = (chat) => {
    if (!chat || !chat.messages || !Array.isArray(chat.messages)) return 0;
    const myId = usePage().props.auth.user.id;
    return chat.messages.filter(m => m && m.sender_id !== myId && !m.is_read).length;
};

const showAppleNotification = (title, body) => {
    if (toast.value.timeoutId) clearTimeout(toast.value.timeoutId);
    const currentTime = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    toast.value.title = title;
    toast.value.body = body;
    toast.value.time = currentTime;
    toast.value.show = true;
    toast.value.timeoutId = setTimeout(() => { toast.value.show = false; }, 4000);
};

const markChatAsRead = async (chatId) => {
    try {
        await axios.post(`/conversations/${chatId}/read`);
        const chat = localConversations.value.find(c => c.id === chatId);
        if (chat && chat.messages) {
            const myId = usePage().props.auth.user.id;
            chat.messages.forEach(m => { if (m.sender_id !== myId) m.is_read = true; });
        }
    } catch (error) {
        console.error(error);
    }
};

const selectChat = (id) => {
    replyingToMessage.value = null; 
    showHeaderMenu.value = false;
    showEmojiPicker.value = false;
    clearSelectedFile();
    const chat = localConversations.value.find(c => c.id === id);
    if (chat && chat.messages) {
        const myId = usePage().props.auth.user.id;
        const firstUnread = chat.messages.find(m => m.sender_id !== myId && !m.is_read);
        firstUnreadMessageId.value = firstUnread ? firstUnread.id : null;
    } else {
        firstUnreadMessageId.value = null;
    }
    selectedChatId.value = id;
    markChatAsRead(id);
}

const activeChat = computed(() => {
    if (!selectedChatId.value) return null;
    return localConversations.value.find(chat => chat.id === selectedChatId.value);
});

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const openContextMenu = (event, msg) => {
    if (msg.is_loading) return; 
    const menuWidth = 150;
    let posX = event.clientX;
    let posY = event.clientY;
    if (window.innerWidth - posX < menuWidth) posX = posX - menuWidth;
    contextMenu.value = { show: true, x: posX, y: posY, message: msg };
};

const closeContextMenu = () => { contextMenu.value.show = false; };

const startReply = () => {
    replyingToMessage.value = contextMenu.value.message;
    closeContextMenu();
};

const deleteMessage = async () => {
    const msg = contextMenu.value.message;
    closeContextMenu();
    try {
        await axios.delete(`/messages/${msg.id}`);
        if (activeChat.value) {
            activeChat.value.messages = activeChat.value.messages.filter(m => m.id !== msg.id);
        }
    } catch (error) {
        console.error("Ошибка при удалении сообщения", error);
    }
};

const triggerClearChatForMe = () => {
    showHeaderMenu.value = false;
    confirmModal.value = {
        show: true,
        title: 'Очистить историю',
        message: 'Вы уверены, что хотите очистить историю сообщений для себя? Чат останется в списке, но сообщения исчезнут с экрана.',
        actionType: 'clearForMe'
    };
};

const triggerDeleteChatForMe = () => {
    showHeaderMenu.value = false;
    confirmModal.value = {
        show: true,
        title: 'Удалить чат',
        message: 'Вы уверены, что хотите удалить чат? Он исчезнет из вашего списка, его история очистится для вас, а все ваши собственные сообщения будут безвозвратно удалены для обоих участников.',
        actionType: 'deleteChatForMe'
    };
};

const triggerClearOwnForEveryone = () => {
    showHeaderMenu.value = false;
    confirmModal.value = {
        show: true,
        title: 'Удалить мои сообщения',
        message: 'Вы уверены, что хотите полностью удалить все ВАШИ сообщения из этого чата для обоих участников?',
        actionType: 'clearOwnForEveryone'
    };
};

const handleCustomConfirm = async () => {
    const type = confirmModal.value.actionType;
    confirmModal.value.show = false;

    if (type === 'clearForMe') {
        try {
            await axios.post(`/conversations/${selectedChatId.value}/clear-for-me`);
            if (activeChat.value) activeChat.value.messages = [];
        } catch (error) {
            console.error(error);
        }
    } 
    else if (type === 'deleteChatForMe') {
        const chatIdToDelete = selectedChatId.value;
        try {
            await axios.post(`/conversations/${chatIdToDelete}/clear-own-for-everyone`);
            await axios.post(`/conversations/${chatIdToDelete}/clear-for-me`, { hidden: true });
            localConversations.value = localConversations.value.filter(c => c.id !== chatIdToDelete);
            selectedChatId.value = null;
            firstUnreadMessageId.value = null;
        } catch (error) {
            console.error("Ошибка при полном удалении чата:", error);
        }
    } 
    else if (type === 'clearOwnForEveryone') {
        try {
            await axios.post(`/conversations/${selectedChatId.value}/clear-own-for-everyone`);
            if (activeChat.value && activeChat.value.messages) {
                const myId = usePage().props.auth.user.id;
                activeChat.value.messages = activeChat.value.messages.filter(m => m && Number(m.sender_id) !== Number(myId));
            }
        } catch (error) {
            console.error(error);
        }
    }
};

const getMessageSenderName = (msg) => {
    const myId = usePage().props.auth.user.id;
    if (msg.sender_id === myId) return usePage().props.auth.user.name;
    return activeChat.value?.users[0]?.name || 'Собеседник';
};

const globalClickHandler = (event) => {
    closeContextMenu(); 
    showHeaderMenu.value = false;
    if (showEmojiPicker.value && emojiPickerRef.value && !emojiPickerRef.value.contains(event.target)) {
        showEmojiPicker.value = false;
    }
};

watch(selectedChatId, (newChatId, oldChatId) => {
    if (oldChatId) window.Echo.leave(`chat.${oldChatId}`);

    if (newChatId) {
        window.Echo.leave(`chat.${newChatId}`);
        scrollToBottom();

        window.Echo.private(`chat.${newChatId}`)
            .listen('.message.sent', (e) => {
                if (activeChat.value) {
                    if (!activeChat.value.messages) activeChat.value.messages = [];
                    const myId = usePage().props.auth.user.id;
                    if (Number(e.message.sender_id) === Number(myId)) return; 
                    
                    const exists = activeChat.value.messages.some(m => m.id === e.message.id);
                    if (!exists) {
                        activeChat.value.messages.push(e.message);
                        scrollToBottom();
                        markChatAsRead(newChatId);
                    }
                }
            })
            .listen('.messages.read', (e) => {
                if (activeChat.value && activeChat.value.id === e.conversationId) {
                    activeChat.value.messages.forEach(m => { if (m.sender_id !== e.readerId) m.is_read = true; });
                }
            })
            .listen('.message.deleted', (e) => {
                if (activeChat.value && activeChat.value.id === e.conversationId) {
                    activeChat.value.messages = activeChat.value.messages.filter(m => m.id !== e.messageId);
                }
            })
            .listen('.messages.cleared_own', (e) => {
                if (activeChat.value && activeChat.value.id === e.conversationId) {
                    activeChat.value.messages = activeChat.value.messages.filter(m => m.sender_id !== e.senderId);
                }
                let leftChat = localConversations.value.find(c => c.id === e.conversationId);
                if (leftChat && leftChat.messages) {
                    leftChat.messages = leftChat.messages.filter(m => m.sender_id !== e.senderId);
                }
            });
    }
});

onMounted(() => {
    const myId = usePage().props.auth.user.id;
    
    window.Echo.private(`user.${myId}`)
        .listen('.conversation.updated', (e) => {
            let chat = localConversations.value.find(c => c.id === e.message.conversation_id);
            if (!chat) {
                const rawConversation = e.message.conversation;
                const filteredUsers = rawConversation.users.filter(u => u.id !== myId);
                chat = { id: rawConversation.id, users: filteredUsers, messages: [] };
                localConversations.value.push(chat);
            }
            if (chat) {
                if (!chat.messages) chat.messages = [];
                const exists = chat.messages.some(m => m.id === e.message.id);
                if (!exists) {
                    
                    // 1. ПРОВЕРКА НА АКТИВНОСТЬ ЧАТА:
                    if (selectedChatId.value === chat.id) {
                        // Если чат открыт — сообщение прочитано
                        e.message.is_read = true;
                        chat.messages.push(e.message);
                        
                        scrollToBottom();
                        markChatAsRead(chat.id);
                    } else {
                        // Если чат закрыт — сообщение НЕ прочитано (зажигаем кружочек)
                        if (e.message.sender_id !== myId) {
                            e.message.is_read = false;
                        }
                        chat.messages.push(e.message);

                        // 2. ГАРАНТИРОВАННЫЙ ВЫЗОВ СИСТЕМНОГО УВЕДОМЛЕНИЯ
                        // Показываем уведомление, только если сообщение прислал нам другой человек
                        if (e.message.sender_id !== myId) {
                            const notificationBody = e.message.file_path 
                                ? (e.message.file_type?.startsWith('image/') ? '🖼️ Фотография' : (e.message.file_type?.startsWith('video/') ? '🎥 Видео' : '📎 Файл')) 
                                : e.message.body;
                                
                            showAppleNotification(e.message.sender?.name || 'Новое сообщение', notificationBody);
                        }
                    }
                }
            }
        })
        .listen('.messages.cleared_own', (e) => {
            let chatInList = localConversations.value.find(c => Number(c.id) === Number(e.conversationId));
            if (chatInList && chatInList.messages && Array.isArray(chatInList.messages)) {
                chatInList.messages = chatInList.messages.filter(m => m && Number(m.sender_id) !== Number(e.senderId));
            }
            if (activeChat.value && Number(activeChat.value.id) === Number(e.conversationId)) {
                if (activeChat.value.messages && Array.isArray(activeChat.value.messages)) {
                    activeChat.value.messages = activeChat.value.messages.filter(m => m && Number(m.sender_id) !== Number(e.senderId));
                }
            }
        })
        .listen('.messsage.deleted', (e) => {
            let chatInList = localConversations.value.find(c => c.id === e.conversationId);
            if (chatInList && chatInList.messages) {
                chatInList.messages = chatInList.messages.filter(m => m.id !== e.messageId);
            }
            if (activeChat.value && activeChat.value.id === e.conversationId) {
                activeChat.value.messages = activeChat.value.messages.filter(m => m.id !== e.messageId);
            }
        });
    
    window.addEventListener('click', globalClickHandler);
});

onUnmounted(() => {
    window.removeEventListener('click', globalClickHandler);
});

const isSearched = ref(false);

const searchContact = async () => {
    const query = searchQuery.value.trim();
    if (!query || query.length < 2) {
        searchResult.value = null;
        isSearched.value = false;
        return;
    }
    try {
        const response = await axios.get(`/search-contact?name=${query}`);
        if (!response.data || !response.data.id) {
            searchResult.value = null;
        } else {
            searchResult.value = response.data;
        }
        isSearched.value = true;
    } catch (error) {
        searchResult.value = null;
        isSearched.value = true;
    }
};

const startChat = async (userId) => {
    if (!userId) return;
    try {
        const response = await axios.post('/conversations', { user_id: userId });
        const conversation = response.data;
        searchResult.value = null;
        searchQuery.value = '';
        isSearched.value = false;
        
        if (!conversation.messages) conversation.messages = [];
        if (!conversation.updated_at) conversation.updated_at = new Date().toISOString();
        if (!conversation.pivot) conversation.pivot = { is_hidden: 0, cleared_at: null };

        const exists = localConversations.value.some(c => c.id === conversation.id);
        if (!exists) {
            localConversations.value.push(conversation);
        } else {
            const index = localConversations.value.findIndex(c => c.id === conversation.id);
            if (index !== -1) localConversations.value[index] = conversation;
        }
        selectChat(conversation.id);
    } catch (error) {
        console.error("Ошибка при открытии чата:", error);
    }
};

const sendMessage = async () => {
    const hasText = newMessageText.value && newMessageText.value.trim();
    const hasFile = selectedFile.value;

    if ((!hasText && !hasFile) || !selectedChatId.value) {
        newMessageText.value = '';
        return;
    }

    let textToSend = newMessageText.value;
    const chatId = selectedChatId.value;
    const myId = usePage().props.auth.user.id;

    if (replyingToMessage.value) {
        const senderName = getMessageSenderName(replyingToMessage.value);
        textToSend = `↪️ Ответ для ${senderName}: "${replyingToMessage.value.body}"\n\n${textToSend}`;
    }

    // Мгновенно очищаем поля ввода и сохраняем ссылки во временные переменные
    newMessageText.value = '';
    replyingToMessage.value = null;
    const fileToUpload = selectedFile.value;
    clearSelectedFile();

    // Временный ID для анимации загрузки (прелоадера) файла
    const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

    // Если прикреплен файл — пушим временный объект с прогрессом в массив сообщений
    if (fileToUpload && activeChat.value && selectedChatId.value === chatId) {
        if (!activeChat.value.messages) activeChat.value.messages = [];
        activeChat.value.messages.push({
            id: tempId,
            conversation_id: chatId,
            sender_id: myId,
            body: textToSend || null,
            file_path: null,
            file_type: fileToUpload.type,
            is_loading: true,
            progress: 0,
            created_at: new Date().toISOString()
        });
        scrollToBottom();
    }

    try {
        const formData = new FormData();
        formData.append('conversation_id', chatId);
        if (textToSend) formData.append('body', textToSend);
        if (fileToUpload) formData.append('file', fileToUpload);

        const response = await axios.post('/messages', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (progressEvent) => {
                if (!fileToUpload) return;
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                
                if (activeChat.value && selectedChatId.value === chatId) {
                    const msg = activeChat.value.messages.find(m => m.id === tempId);
                    if (msg) msg.progress = percentCompleted;
                }
            }
        });

        const serverData = response.data;

        if (activeChat.value && selectedChatId.value === chatId) {
            if (fileToUpload) {
                // Если был файл — заменяем прелоадер реальным объектом с сервера
                const index = activeChat.value.messages.findIndex(m => m.id === tempId);
                if (index !== -1) {
                    if (Array.isArray(serverData)) {
                        activeChat.value.messages.splice(index, 1, ...serverData);
                    } else {
                        activeChat.value.messages[index] = serverData;
                    }
                }
            } else {
                // Если просто обычный текст — пушим стандартно в конец
                if (Array.isArray(serverData)) {
                    activeChat.value.messages.push(...serverData);
                } else {
                    activeChat.value.messages.push(serverData);
                }
                scrollToBottom();
            }
        }
    } catch (error) {
        console.error("Ошибка при отправке сообщения:", error);
        if (activeChat.value && selectedChatId.value === chatId && fileToUpload) {
            const msg = activeChat.value.messages.find(m => m.id === tempId);
            if (msg) {
                msg.is_loading = false;
                msg.body = "❌ Ошибка при отправке файла";
            }
        }
    }
};
</script>

<template>
    <Head title="Чаты" />

    <AuthenticatedLayout>
        
        <Transition name="toast-top">
            <div v-if="toast.show" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-sm bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-gray-800/50 rounded-2xl p-4 shadow-[0_10px_30px_rgba(0,0,0,0.08)] flex items-center space-x-4">
                <div class="w-10 h-10 bg-slate-800 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-md select-none">
                    {{ toast.title[0] }}
                </div>
                <div class="flex-1 text-left min-w-0">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate pr-2">{{ toast.title }}</h4>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium whitespace-nowrap">{{ toast.time }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1 break-words">{{ toast.body }}</p>
                </div>
                <button @click="toast.show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 leading-none transition text-sm">✕</button>
            </div>
        </Transition>

        <Transition name="toast-top">
            <div v-if="confirmModal.show" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-gray-800/50 rounded-2xl p-4 shadow-[0_10px_30px_rgba(0,0,0,0.08)] flex items-start space-x-4">
                <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-950/50 rounded-full flex items-center justify-center text-red-600 dark:text-red-400 font-bold text-lg select-none">
                    ⚠️
                </div>
                <div class="flex-1 text-left pt-0.5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ confirmModal.title }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">{{ confirmModal.message }}</p>
                    <div class="flex space-x-3 mt-3">
                        <button @click="handleCustomConfirm" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition shadow-sm">Удалить</button>
                        <button @click="confirmModal.show = false" class="px-4 py-1.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-lg transition">Отмена</button>
                    </div>
                </div>
                <button @click="confirmModal.show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition text-sm p-1 leading-none">✕</button>
            </div>
        </Transition>

        <div v-if="confirmModal.show" class="fixed inset-0 bg-black/5 backdrop-blur-[2px] z-40" @click="confirmModal.show = false"></div>

        <div v-if="contextMenu.show" :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }" class="fixed z-50 min-w-[140px] bg-white/90 backdrop-blur-md border border-gray-200/60 shadow-xl rounded-xl p-1.5 flex flex-col space-y-0.5 text-left text-sm" @click.stop>
            <button @click="startReply" class="w-full text-left px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100/80 flex items-center space-x-2">
                <span>↪️</span> <span>Ответить</span>
            </button>

            <a v-if="contextMenu.message.file_path" 
                :href="'/storage/' + contextMenu.message.file_path" 
                :download="contextMenu.message.body || 'file'"
                @click="closeContextMenu"
                class="w-full text-left px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100/80 flex items-center space-x-2 calculation-none">
                <span>📥</span> <span>Скачать</span>
            </a>

            <button v-if="contextMenu.message.sender_id === $page.props.auth.user.id" @click="deleteMessage" class="w-full text-left px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 flex items-center space-x-2">
                <span>🗑️</span> <span>Удалить</span>
            </button>
        </div>

        <div class="h-[calc(100vh-64px)] w-full bg-white flex overflow-hidden">
            
            <div :class="['w-full md:w-1/3 border-r border-gray-200 flex flex-col h-full bg-white transition-all duration-300 shrink-0', selectedChatId ? 'hidden md:flex' : 'flex']">
                <div class="p-4 border-b">
                    <input v-model="searchQuery" @input="searchContact" type="text" placeholder="Поиск по имени..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-700 focus:ring-slate-700"/>
                </div>
                
                <div class="flex-1 overflow-y-auto">
                    <div v-if="searchQuery.trim() && isSearched" class="p-4 border-b bg-slate-50">
                        <div v-if="searchResult" @click="startChat(searchResult.id)" class="p-3 bg-white rounded-lg cursor-pointer hover:shadow-md transition border border-slate-200 flex items-center">
                            <div class="w-9 h-9 rounded-xl bg-slate-700 flex items-center justify-center text-white text-sm font-bold overflow-hidden shrink-0">
                                <img v-if="searchResult.avatar_url" :src="searchResult.avatar_url" class="w-full h-full object-cover" />
                                <span v-else>{{ searchResult.name ? searchResult.name[0] : '?' }}</span>
                            </div>
                            <div class="ml-3 text-left">
                                <p class="font-bold text-gray-900">{{ searchResult.name }}</p>
                                <p class="text-xs text-gray-500">@{{ searchResult.tag }}</p>
                            </div>
                        </div>

                        <div v-else class="p-3 text-center text-sm text-gray-500 font-medium">
                            Пользователи не найдены
                        </div>
                    </div>

                    <div v-if="sortedConversations.length > 0">
                        <div v-for="chat in sortedConversations" :key="chat.id" @click="selectChat(chat.id)" :class="['p-4 border-b cursor-pointer transition flex items-center justify-between border-l-4', selectedChatId === chat.id ? 'bg-slate-50 border-l-slate-800' : 'hover:bg-gray-50 border-l-transparent']">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-slate-700 flex items-center justify-center text-white font-bold overflow-hidden shrink-0">
                                    <img v-if="chat.users[0]?.avatar_url" :src="chat.users[0].avatar_url" class="w-full h-full object-cover" />
                                    <span v-else>{{ chat.users[0]?.name ? chat.users[0].name[0] : '?' }}</span>
                                </div>
                                <div class="ml-3 text-left">
                                    <div class="flex items-baseline space-x-1.5">
                                        <p :class="['font-semibold', selectedChatId === chat.id ? 'text-slate-800' : 'text-gray-900']">
                                            {{ chat.users[0]?.name }}
                                        </p>
                                        <span class="text-[10px] text-gray-400 font-normal">@{{ chat.users[0]?.tag }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="getUnreadCount(chat) > 0" class="min-w-5 h-5 bg-slate-800 text-white text-[11px] font-bold rounded-full flex items-center justify-center px-1.5 shadow-sm">{{ getUnreadCount(chat) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div :class="['w-full md:w-2/3 flex flex-col h-full bg-gray-50 transition-all duration-300 relative', selectedChatId ? 'flex' : 'hidden md:flex']">
                <div v-if="activeChat" class="flex flex-col h-full relative">
                    
                    <div class="p-4 bg-white border-b border-gray-200 flex items-center justify-between shadow-sm relative z-10">
                        <div class="flex items-center">
                            <button @click="selectedChatId = null" class="mr-2 md:hidden text-gray-500 p-1 rounded-lg hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-white font-bold overflow-hidden shrink-0">
                                <img v-if="activeChat.users[0]?.avatar_url" :src="activeChat.users[0].avatar_url" class="w-full h-full object-cover" />
                                <span v-else>{{ activeChat.users[0]?.name[0] }}</span>
                            </div>
                            <div class="ml-3 text-left"><p class="font-bold text-gray-900">{{ activeChat.users[0]?.name }}</p></div>
                        </div>

                        <div class="flex items-center space-x-1 relative">
                            <button @click.stop="showHeaderMenu = !showHeaderMenu" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg>
                            </button>
                            
                            <div v-if="showHeaderMenu" class="absolute right-9 top-10 w-56 bg-white/95 backdrop-blur-xl border border-gray-200 shadow-2xl rounded-xl p-1.5 flex flex-col space-y-0.5 text-left text-sm z-30" @click.stop>
                                <button @click="triggerClearChatForMe" class="w-full text-left px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 flex items-center space-x-2">
                                    <span>🧹</span> <span>Очистить историю</span>
                                </button>
                                <button @click="triggerClearOwnForEveryone" class="w-full text-left px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 flex items-center space-x-2">
                                    <span>💥</span> <span>Удалить мои сообщения</span>
                                </button>
                                <div class="h-px bg-gray-100 my-1"></div>
                                <button @click="triggerDeleteChatForMe" class="w-full text-left px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 flex items-center space-x-2">
                                    <span>🗑️</span> <span>Удалить чат</span>
                                </button>
                            </div>

                            <button @click="selectedChatId = null; firstUnreadMessageId = null;" class="hidden md:block text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 no-scrollbar">
                        <div v-if="!activeChat.messages || activeChat.messages.length === 0" class="text-center text-gray-400 text-sm my-4 italic">Чат пуст 🕊️</div>
                        <div v-else class="space-y-4">
                            <template v-for="msg in activeChat.messages" :key="msg.id">
                                
                                <div v-if="msg.id === firstUnreadMessageId" class="flex items-center my-6">
                                    <div class="flex-1 h-px bg-red-200"></div>
                                    <span class="mx-4 text-xs font-bold text-red-500 bg-red-50 px-3 py-1 rounded-full uppercase tracking-wider shadow-sm border border-red-100">Непрочитанные сообщения</span>
                                    <div class="flex-1 h-px bg-red-200"></div>
                                </div>

                                <div :class="['flex', msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start']">
                                    <div 
                                        @contextmenu.prevent="openContextMenu($event, msg)"
                                        :class="[
                                            'max-w-[85%] md:max-w-md text-left transition select-none cursor-pointer duration-100 active:scale-[0.99] relative',
                                            isSingleEmoji(msg.body) 
                                                ? '!bg-transparent !shadow-none !p-0 !text-5xl' 
                                                : (msg.sender_id === $page.props.auth.user.id 
                                                    ? 'bg-slate-800 text-white rounded-2xl rounded-tr-none p-3 shadow-sm text-sm' 
                                                    : 'bg-white text-gray-900 border border-gray-200 rounded-2xl rounded-tl-none p-3 shadow-sm text-sm')
                                        ]"
                                    >
                                        <div v-if="msg.is_loading" class="w-48 mb-2">
                                            <div class="flex justify-between text-[11px] mb-1 font-medium" :class="msg.sender_id === $page.props.auth.user.id ? 'text-slate-300' : 'text-gray-500'">
                                                <span>Отправка файла...</span>
                                                <span>{{ msg.progress }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-indigo-500 h-1.5 transition-all duration-150" :style="{ width: msg.progress + '%' }"></div>
                                            </div>
                                        </div>

                                        <div v-if="msg.file_path && msg.file_type && msg.file_type.startsWith('image/')" class="mb-2 max-w-xs overflow-hidden rounded-xl shadow-sm border border-black/5">
                                            <a :href="'/storage/' + msg.file_path" target="_blank" class="block">
                                                <img :src="'/storage/' + msg.file_path" @load="scrollToBottom" alt="Photo" class="w-full h-auto object-cover max-h-60 hover:opacity-95 transition" />
                                            </a>
                                        </div>

                                        <div v-else-if="msg.file_path && msg.file_type && msg.file_type.startsWith('video/')" class="mb-2 max-w-xs overflow-hidden rounded-xl shadow-sm border border-black/5 bg-black relative group/video">
    
                                            <a :href="'/storage/' + msg.file_path" target="_blank" class="block relative">
                                                <video 
                                                    :src="'/storage/' + msg.file_path" 
                                                    @loadeddata="scrollToBottom"
                                                    autoplay 
                                                    loop 
                                                    muted 
                                                    playsinline
                                                    class="w-full h-auto object-cover max-h-60 hover:opacity-90 transition"
                                                ></video>
                                                <div class="absolute top-2 right-2 bg-black/40 backdrop-blur-sm p-1 rounded-md text-[10px] opacity-80 group-hover:opacity-100 transition select-none">▶️</div>
                                            </a>

                                            <button 
                                                type="button"
                                                @click.stop="(e) => {
                                                    // e.currentTarget — это кнопка. parentElement — это сам контейнер div.
                                                    const container = e.currentTarget.parentElement;
                                                    const video = container ? container.querySelector('video') : null;
                                                    
                                                    if (video) {
                                                        video.muted = !video.muted;
                                                        // Меняем только иконку динамика
                                                        e.currentTarget.innerText = video.muted ? '🔇' : '🔊';
                                                    }
                                                }"
                                                class="absolute bottom-2 left-2 z-10 bg-black/50 backdrop-blur-sm hover:bg-black/70 text-xs p-1.5 rounded-lg transition-all active:scale-95 select-none opacity-90 md:opacity-0 md:group-hover/video:opacity-100 font-sans"
                                                title="Включить/выключить звук"
                                            >
                                                🔇
                                            </button>
                                        </div>

                                        <div v-else-if="msg.file_path" class="mb-2 p-2.5 rounded-xl border flex items-center space-x-3 transition-colors max-w-xs"
                                            :class="msg.sender_id === $page.props.auth.user.id 
                                                ? 'bg-slate-700/50 border-slate-600 text-white' 
                                                : 'bg-gray-50 border-gray-100 text-gray-900'">
                                            <span class="text-2xl select-none shrink-0">📄</span>
                                            <div class="flex-1 min-w-0 text-left">
                                                <p class="font-semibold text-xs truncate" :class="msg.sender_id === $page.props.auth.user.id ? 'text-white' : 'text-slate-900'">
                                                    {{ msg.body }}
                                                </p>
                                                <span class="text-[9px] uppercase tracking-wider font-bold block opacity-60 mt-0.5">
                                                    {{ msg.file_type?.split('/')[1] || 'FILE' }}
                                                </span>
                                            </div>
                                        </div>

                                        <p v-if="msg.body && !(msg.file_path && !msg.file_type?.startsWith('image/')) && msg.body !== msg.file_path" class="break-words whitespace-pre-wrap">{{ msg.body }}</p>
                                        
                                        <div class="flex items-center justify-end space-x-1 mt-1" :class="{'absolute -bottom-5 right-1 bg-gray-50/80 px-1 rounded text-gray-500': isSingleEmoji(msg.body)}">
                                            <span :class="['text-[10px]', isSingleEmoji(msg.body) ? 'text-gray-500' : (msg.sender_id === $page.props.auth.user.id ? 'text-slate-300' : 'text-gray-400')]">
                                                {{ new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                            </span>
                                            <span v-if="msg.sender_id === $page.props.auth.user.id" class="text-xs leading-none">
                                                {{ msg.is_read ? '👀' : '🕊️' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </template>
                        </div>
                    </div>

                    <div class="p-4 bg-white border-t border-gray-200">
                        <div v-if="replyingToMessage" class="mb-3 p-2 bg-gray-50 border-l-4 border-slate-700 rounded-r-xl flex items-center justify-between text-left">
                            <div class="text-xs">
                                <p class="font-bold text-slate-800">Ответ для {{ getMessageSenderName(replyingToMessage) }}:</p>
                                <p class="text-gray-500 line-clamp-1 italic">"{{ replyingToMessage.body || '📎 Файл' }}"</p>
                            </div>
                            <button @click="replyingToMessage = null" class="text-gray-400 hover:text-gray-600 p-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>

                        <div v-if="selectedFile" class="mb-2 p-2 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs animate-fade-in">
                            <div class="flex items-center space-x-2 truncate">
                                <span class="text-base">📎</span>
                                <span class="font-medium text-slate-700 truncate max-w-xs">{{ selectedFile.name }}</span>
                                <span class="text-[10px] text-gray-400">({{ (selectedFile.size / 1024 / 1024).toFixed(2) }} МБ)</span>
                            </div>
                            <button @click="clearSelectedFile" class="text-gray-400 hover:text-red-500 p-1 transition">✕</button>
                        </div>

                        <div class="flex items-center space-x-2 relative">
                            
                            <input type="file" ref="fileInput" style="display: none" @change="onFileSelected" />
                            <button 
                                @click="fileInput.click()" 
                                type="button" 
                                class="p-2 text-gray-400 hover:text-slate-800 rounded-xl hover:bg-gray-100 transition text-xl leading-none"
                                title="Прикрепить файл"
                            >
                                📎
                            </button>

                            <div ref="emojiPickerRef" class="relative flex items-center">
                                <button 
                                    @click.stop="showEmojiPicker = !showEmojiPicker"
                                    type="button"
                                    class="p-2 text-gray-500 hover:text-slate-800 rounded-xl hover:bg-gray-100 transition focus:outline-none text-xl leading-none"
                                    title="Выбрать эмодзи"
                                >
                                    😊
                                </button>

                                <div v-if="showEmojiPicker" class="absolute bottom-12 left-0 w-72 h-80 bg-white/95 backdrop-blur-md border border-gray-200 rounded-2xl shadow-2xl z-50 flex flex-col overflow-hidden" @click.stop>
                                    <div class="overflow-y-auto p-3 space-y-3 custom-scrollbar text-left">
                                        <div v-for="(emojis, category) in emojiList" :key="category">
                                            <h4 class="text-[10px] font-bold text-gray-400 mb-1.5 uppercase tracking-wider">{{ category }}</h4>
                                            <div class="grid grid-cols-7 gap-1">
                                                <button v-for="emoji in emojis" :key="emoji" @click="addEmoji(emoji)" type="button" class="text-xl p-1 hover:bg-gray-100 rounded-lg transition active:scale-90">{{ emoji }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input id="message-field-input" v-model="newMessageText" @keydown.enter="sendMessage" type="text" placeholder="Напишите сообщение..." class="flex-1 rounded-xl border-gray-300 shadow-sm focus:border-slate-700 focus:ring-slate-700" />
                            <button @click="sendMessage" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-xl transition shadow-md">Отправить</button>
                        </div>
                    </div>

                </div>
                <div v-else class="flex-1 flex items-center justify-center text-gray-400 flex-col space-y-2">
                    <span class="text-4xl">🕊️</span>
                    <p class="text-lg font-medium">Выберите чат, чтобы начать общение</p>
                </div>
            </div>
            
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.toast-top-enter-active, .toast-top-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.toast-top-enter-from { transform: translate(-50%, -20px) scale(0.95); opacity: 0; }
.toast-top-leave-to { transform: translate(-50%, -10px) scale(0.98); opacity: 0; }
</style>