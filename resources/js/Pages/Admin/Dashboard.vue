<!-- resources/js/Pages/Admin/Dashboard.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    stats:         Object,
    operatorStats: Array,
    recent:        Array,
    disk:          Object,
})

function fmtDuration(sec) {
    if (!sec) return '—'
    const m = Math.floor(sec / 60)
    const s = sec % 60
    return `${m}m ${s}s`
}

function fmtDate(dt) {
    if (!dt) return '—'
    return new Date(dt).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

const statusBadge = {
    uploaded: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/50 dark:text-green-400 dark:border-green-800',
    failed:   'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/50 dark:text-red-400 dark:border-red-800',
    pending:  'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-900/50 dark:text-yellow-400 dark:border-yellow-800',
}
</script>

<template>


    <!-- Stat cards ─────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800">
        <p class="text-gray-500 text-xs uppercase tracking-wider">Hari Ini</p>
        <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ stats.today }}</p>
        <p class="text-gray-600 text-xs mt-1">resi direkam</p>
      </div>
      <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800">
        <p class="text-gray-500 text-xs uppercase tracking-wider">Minggu Ini</p>
        <p class="text-3xl font-black text-blue-400 mt-1">{{ stats.week }}</p>
        <p class="text-gray-600 text-xs mt-1">resi direkam</p>
      </div>
      <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800">
        <p class="text-gray-500 text-xs uppercase tracking-wider">Bulan Ini</p>
        <p class="text-3xl font-black text-purple-400 mt-1">{{ stats.month }}</p>
        <p class="text-gray-600 text-xs mt-1">resi direkam</p>
      </div>
      <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800">
        <p class="text-gray-500 text-xs uppercase tracking-wider">Total Keseluruhan</p>
        <p class="text-3xl font-black text-green-400 mt-1">{{ stats.all_time }}</p>
        <p class="text-gray-600 text-xs mt-1">rekaman tersimpan</p>
      </div>
    </div>

    <!-- Upload status + Operator stats ────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

      <!-- Upload status -->
      <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800">
        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-4">Status Upload</h2>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="flex items-center gap-2 text-sm text-green-400">
              <span class="w-2 h-2 bg-green-400 rounded-full inline-block"></span>
              Berhasil
            </span>
            <span class="font-bold text-gray-900 dark:text-white">{{ stats.uploaded }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="flex items-center gap-2 text-sm text-yellow-400">
              <span class="w-2 h-2 bg-yellow-400 rounded-full inline-block"></span>
              Pending
            </span>
            <span class="font-bold text-gray-900 dark:text-white">{{ stats.pending }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="flex items-center gap-2 text-sm text-red-400">
              <span class="w-2 h-2 bg-red-400 rounded-full inline-block"></span>
              Gagal
            </span>
            <span class="font-bold text-gray-900 dark:text-white">{{ stats.failed }}</span>
          </div>
        </div>
      </div>

      <!-- Operator stats -->
      <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800">
        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-4">Performa Operator</h2>
        <div v-if="operatorStats.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200 dark:border-gray-800">
                <th class="pb-2 font-medium">Operator</th>
                <th class="pb-2 font-medium text-center">Hari Ini</th>
                <th class="pb-2 font-medium text-center">Total</th>
                <th class="pb-2 font-medium">Stasiun</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800/50">
              <tr v-for="op in operatorStats" :key="op.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                <td class="py-2.5 text-gray-900 dark:text-white font-medium">{{ op.name }}</td>
                <td class="py-2.5 text-center">
                  <span :class="op.today_count > 0 ? 'text-blue-400 font-bold' : 'text-gray-600'">
                    {{ op.today_count }}
                  </span>
                </td>
                <td class="py-2.5 text-center text-gray-800 dark:text-gray-300">{{ op.total_count }}</td>
                <td class="py-2.5 text-gray-500 text-xs">{{ op.station ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-gray-600 text-sm text-center py-6">Belum ada operator terdaftar</p>
      </div>
    </div>

    <!-- Rekaman terbaru ─────────────────────────────────────────────────── -->
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold">Rekaman Terbaru</h2>
        <Link :href="route('admin.archive')"
              class="text-blue-400 text-xs hover:text-blue-300 transition-colors">
          Lihat semua →
        </Link>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200 dark:border-gray-800/50">
              <th class="px-5 py-3 font-medium">Nomor Resi</th>
              <th class="px-5 py-3 font-medium">Operator</th>
              <th class="px-5 py-3 font-medium">Waktu</th>
              <th class="px-5 py-3 font-medium">Durasi</th>
              <th class="px-5 py-3 font-medium">Status</th>
              <th class="px-5 py-3 font-medium"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-800/40">
            <tr v-for="rec in recent" :key="rec.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
              <td class="px-5 py-3 font-mono text-blue-300 font-medium">{{ rec.order_number }}</td>
              <td class="px-5 py-3 text-gray-800 dark:text-gray-300">{{ rec.user?.name ?? '—' }}</td>
              <td class="px-5 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ fmtDate(rec.recorded_at) }}</td>
              <td class="px-5 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ fmtDuration(rec.duration_seconds) }}</td>
              <td class="px-5 py-3">
                <span :class="['text-xs px-2 py-0.5 rounded-full border', statusBadge[rec.upload_status] ?? statusBadge.pending]">
                  {{ rec.upload_status }}
                </span>
              </td>
              <td class="px-5 py-3">
                <Link :href="route('admin.recordings.show', rec.id)"
                      class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                  Detail →
                </Link>
              </td>
            </tr>
            <tr v-if="!recent.length">
              <td colspan="6" class="px-5 py-8 text-center text-gray-600">Belum ada rekaman</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

</template>
