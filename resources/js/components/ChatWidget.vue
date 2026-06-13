<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from 'vue';
import { MessageSquare, Send, X, Bot, Sparkles, Loader2 } from '@lucide/vue';
import { usePage } from '@inertiajs/vue3';
import MascotFox from '@/components/MascotFox.vue';

interface Message {
    role: 'user' | 'model';
    text: string;
}

const page = usePage();
const userName = ref('Kak');

const userLevel = ref(1);
const mascotName = ref('Finku Fox');
const equippedAccessories = ref<string[]>([]);

const syncMascotDetails = () => {
    const authUser = (page.props as any).auth?.user || (page.props as any).user;
    if (authUser) {
        userLevel.value = authUser.level || 1;
        mascotName.value = authUser.mascot_name || 'Finku Fox';
        equippedAccessories.value = authUser.equipped_accessories || [];
    }
};

onMounted(() => {
    syncMascotDetails();
    const authUser = (page.props as any).auth?.user || (page.props as any).user;
    if (authUser?.name) {
        // Grab first name
        userName.value = authUser.name.split(' ')[0];
    }

    // Update welcome message dynamically with the mascot name
    messages.value[0].text = `Halo! Aku ${mascotName.value}, maskot pendamping dan asisten keuangan pribadi cerdasmu. Ada yang ingin kamu tanyakan mengenai kondisi anggaran, progres target tabungan, atau cara naik level XP finansialmu?`;
});

watch(() => (page.props as any).auth?.user, () => {
    syncMascotDetails();
}, { deep: true });

const isOpen = ref(false);
const isTyping = ref(false);
const inputMessage = ref('');
const isOfflineMode = ref(false);
const showOfflineOption = ref(false);
const messages = ref<Message[]>([
    {
        role: 'model',
        text: 'Halo! Aku Finku-AI, asisten keuangan pribadi cerdasmu. Ada yang ingin kamu tanyakan mengenai kondisi anggaran, progres target tabungan, atau cara naik level XP finansialmu?'
    }
]);

const chatContainer = ref<HTMLDivElement | null>(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

watch(() => messages.value.length, scrollToBottom);
watch(isOpen, (newVal) => {
    if (newVal) {
        scrollToBottom();
    }
});

const getCookie = (name: string): string => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop()?.split(';').shift() || '');
    return '';
};

const sendMessage = async (text: string) => {
    if (!text.trim() || isTyping.value) return;

    messages.value.push({ role: 'user', text });
    inputMessage.value = '';
    isTyping.value = true;
    showOfflineOption.value = false;

    try {
        const xsrfToken = getCookie('XSRF-TOKEN');

        const historyPayload = messages.value.slice(0, -1).map(m => ({
            role: m.role,
            text: m.text
        }));

        const response = await fetch('/ai/chat', {
            method: 'POST',
            body: JSON.stringify({
                message: text,
                history: historyPayload,
                force_offline: isOfflineMode.value
            }),
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrfToken,
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (response.ok && result.success) {
            messages.value.push({
                role: 'model',
                text: result.text
            });
        } else if (result.error === 'ai_inactive') {
            messages.value.push({
                role: 'model',
                text: 'Mohon maaf, layanan AI saat ini sedang tidak aktif (kuota token habis).'
            });
            showOfflineOption.value = true;
        } else {
            messages.value.push({
                role: 'model',
                text: 'Mohon maaf, layanan AI sedang tidak aktif.'
            });
            showOfflineOption.value = true;
        }
    } catch (e) {
        console.error(e);
        messages.value.push({
            role: 'model',
            text: 'Mohon maaf, AI sedang tidak aktif karena gangguan koneksi.'
        });
        showOfflineOption.value = true;
    } finally {
        isTyping.value = false;
    }
};

const switchToOfflineMode = () => {
    isOfflineMode.value = true;
    showOfflineOption.value = false;
    messages.value.push({
        role: 'model',
        text: '🟢 **Asisten Offline Aktif!** Halo Kak, sekarang Finku-AI berjalan dalam mode lokal. Kakak tetap bisa bertanya seputar:\n- *Kondisi keuangan & pembagian budget*\n- *Status over-budget / boros*\n- *Tips hemat dan menabung*\n- *Misi quest gamifikasi & level XP*'
    });
};

const sendQuickPrompt = (promptText: string) => {
    sendMessage(promptText);
};

const formatMessageText = (text: string) => {
    if (!text) return '';
    // Escape HTML first to prevent XSS
    let escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    // Replace bold formatting: **text** -> <strong>text</strong>
    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Replace italic formatting: *text* -> <em>text</em>
    escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
    escaped = escaped.replace(/_(.*?)_/g, '<em>$1</em>');

    // Bullet points: - or * or • at start of line -> custom bullet list styling
    const lines = escaped.split('\n');
    const processedLines = lines.map(line => {
        const trimmed = line.trim();
        if (trimmed.startsWith('- ') || trimmed.startsWith('• ') || trimmed.startsWith('* ')) {
            const content = trimmed.substring(2);
            return `<div class="pl-4 py-0.5 flex items-start gap-2"><span class="text-emerald-500 font-bold">•</span><span>${content}</span></div>`;
        }
        return line;
    });

    return processedLines.join('\n');
};
</script>

<template>
    <div class="fixed bottom-20 lg:bottom-6 right-6 z-50 font-sans">
        <!-- Chat Bubble Button (Mascot) -->
        <button
            v-if="!isOpen"
            @click="isOpen = true"
            class="flex items-center justify-center w-16 h-16 rounded-full text-white shadow-xl hover:shadow-emerald-200/50 hover:scale-105 transition-all duration-300 relative group cursor-pointer"
        >
            <MascotFox :level="userLevel" :equipped="equippedAccessories" :size="52" :hideBadge="true" class="mt-1" />
            <!-- Glowing status indicator -->
            <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
        </button>

        <!-- Chat Panel Window -->
        <div
            v-else
            class="w-[360px] sm:w-[400px] h-[550px] bg-white border border-slate-100 rounded-2xl shadow-2xl flex flex-col overflow-hidden transition-all duration-300 transform scale-100 origin-bottom-right"
        >
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-500 p-4 text-white flex items-center justify-between shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full flex items-center justify-center relative overflow-hidden">
                        <MascotFox :level="userLevel" :equipped="equippedAccessories" :size="38" :hideBadge="true" />
                    </div>
                    <div>
                        <h3 class="font-bold text-sm flex items-center gap-1.5">
                            {{ mascotName }} <Sparkles class="w-3.5 h-3.5 text-amber-300" />
                        </h3>
                        <p class="text-[10px] text-emerald-100 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full" :class="isOfflineMode ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400'"></span>
                            {{ isOfflineMode ? 'Mode Offline' : 'Asisten Keuangan Cerdas' }}
                        </p>
                    </div>
                </div>
                <button
                    @click="isOpen = false"
                    class="p-1 rounded-full hover:bg-white/10 transition-colors text-white/80 hover:text-white cursor-pointer"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Chat Messages Box -->
            <div
                ref="chatContainer"
                class="flex-1 p-4 overflow-y-auto bg-slate-50/50 space-y-3 scroll-smooth"
            >
                <div
                    v-for="(msg, idx) in messages"
                    :key="idx"
                    class="flex flex-col"
                >
                    <!-- Message Bubble -->
                    <div
                        :class="[
                            'max-w-[85%] rounded-2xl p-3 text-sm shadow-sm leading-relaxed',
                            msg.role === 'user'
                                ? 'bg-emerald-600 text-white self-end rounded-br-none'
                                : 'bg-white text-slate-700 border border-slate-100 self-start rounded-bl-none'
                        ]"
                    >
                        <p class="whitespace-pre-wrap" v-html="formatMessageText(msg.text)"></p>
                    </div>
                </div>

                <!-- AI Typing Indicator -->
                <div v-if="isTyping" class="flex items-center gap-2 text-slate-400 self-start max-w-[85%] bg-white border border-slate-100 rounded-2xl rounded-bl-none p-3 shadow-sm">
                    <Loader2 class="w-4 h-4 animate-spin text-emerald-500" />
                    <span class="text-xs font-medium text-slate-500 animate-pulse">Finku-AI sedang meramu jawaban...</span>
                </div>
            </div>

            <!-- Quick Suggestions (Chips) -->
            <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 overflow-x-auto flex gap-2 no-scrollbar">
                <button
                    @click="sendQuickPrompt('Bagaimana kondisi keuanganku bulan ini?')"
                    class="text-xs font-semibold px-3 py-1.5 bg-white border border-slate-200 rounded-full text-slate-600 hover:border-emerald-500 hover:text-emerald-600 transition-colors shrink-0 shadow-sm cursor-pointer"
                >
                    📊 Cek Keuangan
                </button>
                <button
                    @click="sendQuickPrompt('Beri aku tips hemat dan menabung minggu ini')"
                    class="text-xs font-semibold px-3 py-1.5 bg-white border border-slate-200 rounded-full text-slate-600 hover:border-emerald-500 hover:text-emerald-600 transition-colors shrink-0 shadow-sm cursor-pointer"
                >
                    💡 Tips Hemat
                </button>
                <button
                    @click="sendQuickPrompt('Bagaimana cara cepat naik level XP finansial?')"
                    class="text-xs font-semibold px-3 py-1.5 bg-white border border-slate-200 rounded-full text-slate-600 hover:border-emerald-500 hover:text-emerald-600 transition-colors shrink-0 shadow-sm cursor-pointer"
                >
                    🎮 Naik Level
                </button>
            </div>

            <!-- Offline Mode Option Warning -->
            <div v-if="showOfflineOption" class="px-4 py-3 bg-amber-50 border-t border-amber-100 flex flex-col gap-2">
                <p class="text-xs text-amber-800 leading-normal">
                    ⚠️ <strong>Layanan AI sedang tidak aktif.</strong> Silakan beralih ke Asisten Offline FinKu untuk tetap menanyakan analisis budget, target tabungan, dan gamifikasi.
                </p>
                <button
                    @click="switchToOfflineMode"
                    class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors cursor-pointer"
                >
                    Aktifkan Asisten Offline
                </button>
            </div>

            <!-- Chat Input Bar -->
            <div class="p-3 bg-white border-t border-slate-100 flex items-center gap-2">
                <input
                    type="text"
                    v-model="inputMessage"
                    @keyup.enter="sendMessage(inputMessage)"
                    placeholder="Tanyakan keuanganmu ke Finku-AI..."
                    class="flex-1 bg-slate-50 text-slate-700 text-sm px-4 py-2.5 rounded-xl border border-slate-200/80 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
                    :disabled="isTyping"
                />
                <button
                    @click="sendMessage(inputMessage)"
                    class="p-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 transition-colors disabled:opacity-50 cursor-pointer shadow"
                    :disabled="!inputMessage.trim() || isTyping"
                >
                    <Send class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
