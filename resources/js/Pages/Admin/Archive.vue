<!-- resources/js/Pages/Admin/Archive.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
    recordings: Object,  // LengthAwarePaginator
    operators:  Array,
    filters:    Object,
    retentionDays: Number,
})

// Local form state — sync dengan URL filter
const form = ref({
    q:         props.filters?.q         ?? '',
    operator:  props.filters?.operator  ?? '',
    status:    props.filters?.status    ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to:   props.filters?.date_to   ?? '',
})

let searchTimeout = null

// Debounce pencarian resi
watch(() => form.value.q, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilter, 400)
})

function applyFilter() {
    router.get(route('admin.archive'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function clearFilter() {
    form.value = { q: '', operator: '', status: '', date_from: '', date_to: '' }
    applyFilter()
}

function confirmDelete(rec) {
    Swal.fire({
        title: 'Hapus Rekaman?',
        html: `Hapus rekaman resi <strong class="text-red-500">${rec.order_number}</strong>?<br>Tindakan ini tidak bisa dibatalkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#374151',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: '#111827',
        color: '#fff',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.recordings.destroy', rec.id), {
                preserveScroll: true,
            })
        }
    })
}

function fmtDate(dt) {
    if (!dt) return '—'
    return new Date(dt).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

function fmtDuration(sec) {
    if (sec == null || sec === '') return '—'
    const m = Math.floor(sec / 60)
    const s = sec % 60
    return `${m}m ${s}s`
}

const statusBadge = {
    uploaded: 'bg-green-900/50 text-green-400 border-green-800',
    failed:   'bg-red-900/50 text-red-400 border-red-800',
    pending:  'bg-yellow-900/50 text-yellow-400 border-yellow-800',
}

const hasActiveFilter = () =>
    Object.values(form.value).some(v => v !== '')

function isExpired(dt) {
    if (!props.retentionDays || props.retentionDays === 0) return false
    const recordTime = new Date(dt).getTime()
    const now = Date.now()
    const daysOld = (now - recordTime) / (1000 * 60 * 60 * 24)
    return daysOld > props.retentionDays
}
</script>

<template>
    <!-- Search & Filter ──────────────────────────────────────────────────── -->
    <div class="bg-gray-900 rounded-xl border border-gray-800 p-4 mb-5">
      <!-- Search bar utama -->
      <div class="relative mb-3">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-lg">🔍</span>
        <input
          id="search-resi"
          v-model="form.q"
          type="text"
          placeholder="Cari nomor resi…"
          class="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2.5
                 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500
                 text-sm transition-colors"
        />
      </div>

      <!-- Filter row -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <!-- Operator -->
        <select v-model="form.operator" @change="applyFilter"
                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm
                       text-gray-300 focus:outline-none focus:border-blue-500">
          <option value="">Semua Operator</option>
          <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.name }}</option>
        </select>

        <!-- Status -->
        <select v-model="form.status" @change="applyFilter"
                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm
                       text-gray-300 focus:outline-none focus:border-blue-500">
          <option value="">Semua Status</option>
          <option value="uploaded">Uploaded</option>
          <option value="pending">Pending</option>
          <option value="failed">Gagal</option>
        </select>

        <!-- Date from -->
        <input v-model="form.date_from" @change="applyFilter" type="date"
               class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm
                      text-gray-300 focus:outline-none focus:border-blue-500" />

        <!-- Date to -->
        <input v-model="form.date_to" @change="applyFilter" type="date"
               class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm
                      text-gray-300 focus:outline-none focus:border-blue-500" />
      </div>

      <!-- Clear filter -->
      <div v-if="hasActiveFilter()" class="mt-3 flex justify-end">
        <button @click="clearFilter"
                class="text-xs text-gray-500 hover:text-red-400 transition-colors flex items-center gap-1">
          ✕ Hapus semua filter
        </button>
      </div>
    </div>

    <!-- Jumlah hasil -->
    <p class="text-gray-600 text-xs mb-3">
      Menampilkan <span class="text-gray-400 font-medium">{{ recordings.total }}</span> rekaman
    </p>

    <!-- Tabel ────────────────────────────────────────────────────────────── -->
    <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-gray-600 text-xs uppercase tracking-wider
                       border-b border-gray-800 bg-gray-900/80">
              <th class="px-5 py-3 font-medium">Nomor Resi</th>
              <th class="px-5 py-3 font-medium">Operator</th>
              <th class="px-5 py-3 font-medium">Stasiun</th>
              <th class="px-5 py-3 font-medium">Waktu Rekam</th>
              <th class="px-5 py-3 font-medium">Durasi</th>
              <th class="px-5 py-3 font-medium">Status</th>
              <th class="px-5 py-3 font-medium text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-800/40">
            <tr v-for="rec in recordings.data" :key="rec.id"
                :class="[
                  'transition-colors group',
                  isExpired(rec.recorded_at) ? 'bg-red-900/20 hover:bg-red-900/30' : 'hover:bg-gray-800/30'
                ]">
              <td class="px-5 py-3 font-mono font-semibold">
                <div class="flex items-center gap-2">
                  <span :class="isExpired(rec.recorded_at) ? 'text-red-300' : 'text-blue-300'">
                    {{ rec.order_number }}
                  </span>
                  <span v-if="isExpired(rec.recorded_at)" 
                        title="Video ini melewati batas retensi dan perlu dihapus"
                        class="text-[10px] bg-red-500/20 text-red-400 border border-red-500/30 px-1.5 py-0.5 rounded uppercase font-bold tracking-wider animate-pulse flex-shrink-0">
                    Expired
                  </span>
                </div>
              </td>
              <td class="px-5 py-3 text-gray-300">{{ rec.user?.name ?? '—' }}</td>
              <td class="px-5 py-3 text-gray-500 text-xs">{{ rec.station ?? '—' }}</td>
              <td class="px-5 py-3 text-gray-400 text-xs">{{ fmtDate(rec.recorded_at) }}</td>
              <td class="px-5 py-3 text-gray-400 text-xs">{{ fmtDuration(rec.duration_seconds) }}</td>
              <td class="px-5 py-3">
                <span :class="['text-xs px-2 py-0.5 rounded-full border',
                               statusBadge[rec.upload_status] ?? statusBadge.pending]">
                  {{ rec.upload_status }}
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-3">
                  <Link :href="route('admin.recordings.show', rec.id)"
                        class="text-xs text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v6M8 11h6"/>
                    </svg>
                    Detail
                  </Link>
                  <a :href="route('admin.recordings.download', rec.id)"
                  
                    class="text-xs text-green-400 hover:text-green-300 transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Unduh
                  </a>
                  <button @click="confirmDelete(rec)"
                          class="text-xs text-red-500 hover:text-red-400 transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                    </svg>
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!recordings.data.length">
              <td colspan="7" class="px-5 py-12 text-center text-gray-600">
                <p class="text-3xl mb-2">📭</p>
                <p>Tidak ada rekaman ditemukan</p>
                <button v-if="hasActiveFilter()" @click="clearFilter"
                        class="mt-2 text-blue-400 text-xs hover:underline">
                  Hapus filter
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination ─────────────────────────────────────────────────────── -->
      <div v-if="recordings.last_page > 1"
           class="px-5 py-3 border-t border-gray-800 flex items-center justify-between">
        <p class="text-xs text-gray-600">
          Halaman {{ recordings.current_page }} dari {{ recordings.last_page }}
        </p>
        <div class="flex items-center gap-1">
          <Link v-if="recordings.prev_page_url"
                :href="recordings.prev_page_url"
                class="px-3 py-1.5 text-xs bg-gray-800 hover:bg-gray-700 rounded text-gray-300 transition-colors">
            ← Sebelumnya
          </Link>
          <Link v-for="link in recordings.links.slice(1, -1)" :key="link.label"
                :href="link.url ?? '#'"
                :class="['px-3 py-1.5 text-xs rounded transition-colors',
                         link.active
                           ? 'bg-blue-600 text-white'
                           : 'bg-gray-800 hover:bg-gray-700 text-gray-400']">
            {{ link.label }}
          </Link>
          <Link v-if="recordings.next_page_url"
                :href="recordings.next_page_url"
                class="px-3 py-1.5 text-xs bg-gray-800 hover:bg-gray-700 rounded text-gray-300 transition-colors">
            Selanjutnya →
          </Link>
        </div>
      </div>
    </div>

</template>
