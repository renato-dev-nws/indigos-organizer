<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import OverlayPanel from 'primevue/overlaypanel';

const page = usePage();

// Reactive unread count shared from Inertia props (updated on navigation)
const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);

const panel = ref(null);
const notifications = ref([]);
const loading = ref(false);
const localUnread = ref(page.props.unreadNotificationsCount ?? 0);

watch(unreadCount, (count) => {
    localUnread.value = count;
});

let pollInterval = null;

// ─── Icon per notification type ──────────────────────────────────────────────
const typeIcon = {
    task_assigned: 'ph:clipboard-text-bold',
    task_due_soon: 'ph:clock-countdown-bold',
    task_reminder: 'ph:bell-ringing-bold',
    idea_on_board: 'ph:lightbulb-bold',
    idea_voted: 'ph:thumbs-up-bold',
};

const typeLabel = {
    task_assigned: 'Tarefa atribuída',
    task_due_soon: 'Tarefa vence em breve',
    task_reminder: 'Lembrete de tarefa',
    idea_on_board: 'Ideia no quadro',
    idea_voted: 'Voto na ideia',
};

function iconFor(type) {
    return typeIcon[type] ?? 'ph:bell-bold';
}

// ─── Fetch notifications from API ────────────────────────────────────────────
async function fetchNotifications() {
    loading.value = true;
    try {
        const res = await fetch('/notifications', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        notifications.value = data.notifications ?? [];
        localUnread.value = data.unread_count ?? 0;
    } finally {
        loading.value = false;
    }
}

// ─── Open panel ──────────────────────────────────────────────────────────────
async function openPanel(event) {
    panel.value.toggle(event);
    await fetchNotifications();
}

// ─── Mark one as read ────────────────────────────────────────────────────────
async function markRead(id) {
    const n = notifications.value.find((n) => n.id === id);
    if (!n || n.read_at) return;

    n.read_at = new Date().toISOString();
    localUnread.value = Math.max(0, localUnread.value - 1);

    await fetch(`/notifications/${id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), Accept: 'application/json' },
        credentials: 'same-origin',
    });
}

// ─── Mark all as read ────────────────────────────────────────────────────────
async function markAllRead() {
    notifications.value.forEach((n) => {
        if (!n.read_at) n.read_at = new Date().toISOString();
    });
    localUnread.value = 0;

    await fetch('/notifications/read-all', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), Accept: 'application/json' },
        credentials: 'same-origin',
    });
}

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const now = new Date();
    const diff = (now - d) / 1000;
    if (diff < 60) return 'agora';
    if (diff < 3600) return `${Math.floor(diff / 60)}min atrás`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

// ─── Poll every 60s to keep badge fresh ──────────────────────────────────────
onMounted(() => {
    pollInterval = setInterval(async () => {
        const res = await fetch('/notifications', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        localUnread.value = data.unread_count ?? 0;
    }, 60_000);
});

onUnmounted(() => {
    clearInterval(pollInterval);
});
</script>

<template>
    <div class="relative">
        <!-- Bell button -->
        <button
            type="button"
            class="relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-indigo-400"
            aria-label="Notificações"
            title="Notificações"
            @click="openPanel"
        >
            <Icon icon="ph:bell-bold" class="h-[18px] w-[18px]" />
            <span
                v-if="localUnread > 0"
                class="absolute right-0.5 top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-indigo-500 px-1 text-[10px] font-bold leading-none text-white"
            >
                {{ localUnread > 99 ? '99+' : localUnread }}
            </span>
        </button>

        <!-- Overlay panel -->
        <OverlayPanel ref="panel" class="w-80 max-w-[calc(100vw-1rem)]">
            <div class="flex items-center justify-between border-b border-slate-200 px-3 py-2 dark:border-slate-700">
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Notificações</span>
                <button
                    v-if="localUnread > 0"
                    type="button"
                    class="text-xs text-indigo-500 hover:underline dark:text-indigo-400"
                    @click="markAllRead"
                >
                    Marcar todas como lidas
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-6">
                <Icon icon="ph:spinner-gap-bold" class="h-5 w-5 animate-spin text-indigo-400" />
            </div>

            <!-- Empty state -->
            <div
                v-else-if="notifications.length === 0"
                class="flex flex-col items-center gap-2 py-8 text-sm text-slate-400 dark:text-slate-500"
            >
                <Icon icon="ph:bell-slash" class="h-8 w-8" />
                <span>Nenhuma notificação</span>
            </div>

            <!-- List -->
            <ul v-else class="max-h-[420px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                <li
                    v-for="n in notifications"
                    :key="n.id"
                    class="flex cursor-pointer gap-3 px-3 py-2.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                    :class="{ 'bg-indigo-50/60 dark:bg-indigo-950/20': !n.read_at }"
                    @click="markRead(n.id)"
                >
                    <!-- Icon -->
                    <div
                        class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                        :class="n.read_at ? 'bg-slate-100 dark:bg-slate-800' : 'bg-indigo-100 dark:bg-indigo-900/40'"
                    >
                        <Icon
                            :icon="iconFor(n.type)"
                            class="h-3.5 w-3.5"
                            :class="n.read_at ? 'text-slate-400' : 'text-indigo-500 dark:text-indigo-400'"
                        />
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs font-medium"
                            :class="n.read_at ? 'text-slate-500 dark:text-slate-400' : 'text-slate-700 dark:text-slate-200'"
                        >
                            {{ typeLabel[n.type] ?? n.type }}
                        </p>
                        <p class="mt-0.5 line-clamp-2 text-xs text-slate-400 dark:text-slate-500">
                            {{ n.message }}
                        </p>
                        <p class="mt-1 text-[10px] text-slate-300 dark:text-slate-600">
                            {{ formatTime(n.created_at) }}
                        </p>
                    </div>

                    <!-- Unread dot -->
                    <div v-if="!n.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-indigo-500" />
                </li>
            </ul>
        </OverlayPanel>
    </div>
</template>
