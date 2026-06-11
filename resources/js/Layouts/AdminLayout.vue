<!-- resources/js/Layouts/AdminLayout.vue -->
<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import PackingWidget from '@/Components/PackingWidget.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'

const page = usePage()
const user = page.props.auth.user

const isPackingOpen = ref(false)

const pageTitle = computed(() => {
    const name = route().current()
    if (name === 'dashboard') return '📊 Dashboard'
    if (name === 'admin.archive') return '🗂️ Arsip Rekaman'
    if (name === 'admin.operators.index') return '👷 Manajemen Operator'
    if (name === 'admin.settings.index') return '⚙️ Pengaturan Sistem'
    if (name === 'admin.recordings.show') return '▶ Detail Rekaman'
    return 'Admin Panel'
})

const navItems = [
    { label: 'Dashboard',  routeName: 'dashboard',              icon: '📊' },
    { label: 'Arsip Resi', routeName: 'admin.archive',          icon: '🗂️' },
]

if (user.role === 'admin') {
    navItems.push(
        { label: 'Operator',   routeName: 'admin.operators.index',  icon: '👷' },
        { label: 'Pengaturan', routeName: 'admin.settings.index',   icon: '⚙️' }
    )
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-white flex transition-colors duration-200">

    <!-- Sidebar -->
    <aside class="w-56 shrink-0 bg-white border-r border-gray-200 dark:bg-gray-900 dark:border-gray-800 flex flex-col transition-colors duration-200">
      <!-- Logo -->
      <div class="px-5 py-5 border-b border-gray-200 dark:border-gray-800">
        <span class="text-blue-600 dark:text-blue-400 font-black text-xl tracking-widest">PROOFIX</span>
        <p class="text-gray-500 dark:text-gray-500 text-xs mt-0.5">{{ user.role === 'admin' ? 'Admin Panel' : 'Operator Panel' }}</p>
      </div>

      <!-- Nav -->
      <nav class="flex-1 p-3 space-y-1">
        <Link
          v-for="item in navItems"
          :key="item.routeName"
          :href="route(item.routeName)"
          :class="['flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                       route().current(item.routeName) 
              ? 'bg-blue-600 text-white shadow-sm'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white'
          ]"
        >
          <span>{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </Link>
      </nav>

      <!-- User info + logout -->
      <div class="p-3 border-t border-gray-200 dark:border-gray-800">
        <div class="px-3 py-2 text-xs text-gray-500 dark:text-gray-500 mb-1">
          <p class="font-medium text-gray-800 dark:text-gray-300 truncate">{{ user.name }}</p>
          <p class="text-gray-500 dark:text-gray-600 truncate">{{ user.email }}</p>
        </div>
        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="w-full text-left flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-500
                 hover:text-red-600 hover:bg-gray-100 dark:hover:text-red-400 dark:hover:bg-gray-800 rounded-lg transition-colors"
        >
          <span>🚪</span> Keluar
        </Link>
      </div>
    </aside>

    <!-- Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="h-14 bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-800 flex items-center justify-between px-6 shrink-0 transition-colors duration-200">
        <h1 class="text-gray-800 dark:text-gray-300 font-semibold text-sm">{{ pageTitle }}</h1>
        
        <div class="flex items-center gap-4">
          <ThemeToggle />
          
          <button
            @click="isPackingOpen = !isPackingOpen"
            class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg transition-colors shadow-sm"
          >
            {{ isPackingOpen ? 'Tutup Packing' : '📦 Mulai Packing' }}
          </button>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-6 overflow-auto bg-gray-50 dark:bg-gray-950 transition-colors duration-200">
        <slot />
      </main>
    </div>

    <!-- Floating Packing Widget -->
    <PackingWidget v-if="isPackingOpen" @close="isPackingOpen = false" :operator="user" />

  </div>
</template>
