<!-- resources/js/Pages/Admin/Show.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
    recording:   Object,
    videoExists: Boolean,
    fileSize:    Number,   // bytes
})

const showDeleteModal = ref(false)

const actualDuration = ref(props.recording.duration_seconds)

function onVideoLoaded(e) {
    if (!props.recording.duration_seconds && e.target.duration && e.target.duration !== Infinity) {
        actualDuration.value = Math.round(e.target.duration)
    }
}

function fmtDate(dt) {
    if (!dt) return '—'
    return new Date(dt).toLocaleString('id-ID', {
        weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    })
}

function fmtDuration(sec) {
    if (sec == null || sec === '') return '—'
    const h = Math.floor(sec / 3600)
    const m = Math.floor((sec % 3600) / 60)
    const s = sec % 60
    return h > 0
        ? `${h}j ${m}m ${s}d`
        : `${m}m ${s}d`
}

function fmtSize(bytes) {
    if (!bytes) return '—'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function doDelete() {
    const isDark = document.documentElement.classList.contains('dark');
    Swal.fire({
        title: 'Hapus Rekaman?',
        html: `Rekaman resi <strong class="text-blue-400 font-mono">${props.recording.order_number}</strong><br><br><span class="text-sm text-red-400">File video akan ikut terhapus dan tidak bisa dipulihkan.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b91c1c',
        cancelButtonColor: isDark ? '#1f2937' : '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        background: isDark ? '#111827' : '#ffffff',
        color: isDark ? '#ffffff' : '#111827',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.recordings.destroy', props.recording.id))
        }
    })
}

const statusBadge = {
    uploaded: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/50 dark:text-green-400 dark:border-green-800',
    failed:   'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/50 dark:text-red-400 dark:border-red-800',
    pending:  'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-900/50 dark:text-yellow-400 dark:border-yellow-800',
}

const streamUrl = route('admin.recordings.stream', props.recording.id)
const downloadUrl = route('admin.recordings.download', props.recording.id)
</script>

<template>
    <div class="max-w-5xl mx-auto mb-4 flex items-center gap-3">
      <Link :href="route('admin.archive')"
            class="text-gray-500 dark:text-gray-500 hover:text-gray-800 dark:text-gray-300 transition-colors text-sm">
        ← Kembali ke Arsip
      </Link>
      <span class="text-gray-400 dark:text-gray-700">/</span>
      <span class="text-gray-900 dark:text-gray-200 font-bold font-mono text-sm">{{ recording.order_number }}</span>
    </div>

    <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-5">

      <!-- Video Player ──────────────────────────────────────────────────── -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold">▶ Video Rekaman</h2>
            <a v-if="videoExists"
               :href="downloadUrl"
               class="flex items-center gap-1.5 text-xs bg-green-700 hover:bg-green-600
                      text-white px-3 py-1.5 rounded-lg transition-colors">
              ↓ Unduh Video
            </a>
          </div>

          <!-- Video ada -->
          <div v-if="videoExists" class="bg-black">
            <video
              id="video-player"
              :src="streamUrl"
              controls
              class="w-full"
              style="max-height: 420px;"
              preload="metadata"
              @loadedmetadata="onVideoLoaded"
            >
              Browser Anda tidak mendukung HTML5 video.
            </video>
          </div>

          <!-- Video tidak ada -->
          <div v-else class="flex flex-col items-center justify-center py-16 text-gray-500 dark:text-gray-600">
            <p class="text-4xl mb-3">📹</p>
            <p class="font-medium">File video tidak ditemukan</p>
            <p class="text-xs mt-1 text-gray-400 dark:text-gray-700">
              File mungkin sudah dihapus atau gagal diupload
            </p>
          </div>
        </div>
      </div>

      <!-- Info & Aksi ───────────────────────────────────────────────────── -->
      <div class="space-y-4">

        <!-- Info rekaman -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
          <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-4">Informasi Rekaman</h2>
          <dl class="space-y-3 text-sm">
            <div>
              <dt class="text-gray-500 dark:text-gray-600 text-xs uppercase tracking-wider">Nomor Resi</dt>
              <dd class="text-blue-300 font-mono font-bold mt-0.5">{{ recording.order_number }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 dark:text-gray-600 text-xs uppercase tracking-wider">Status Upload</dt>
              <dd class="mt-0.5">
                <span :class="['text-xs px-2 py-0.5 rounded-full border',
                               statusBadge[recording.upload_status] ?? statusBadge.pending]">
                  {{ recording.upload_status }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-gray-500 dark:text-gray-600 text-xs uppercase tracking-wider">Operator</dt>
              <dd class="text-gray-800 dark:text-gray-300 mt-0.5">{{ recording.user?.name ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 dark:text-gray-600 text-xs uppercase tracking-wider">Stasiun</dt>
              <dd class="text-gray-600 dark:text-gray-400 mt-0.5">{{ recording.station ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 dark:text-gray-600 text-xs uppercase tracking-wider">Waktu Rekam</dt>
              <dd class="text-gray-600 dark:text-gray-400 mt-0.5 text-xs leading-relaxed">{{ fmtDate(recording.recorded_at) }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 dark:text-gray-600 text-xs uppercase tracking-wider">Durasi</dt>
              <dd class="text-gray-800 dark:text-gray-300 mt-0.5">{{ fmtDuration(actualDuration) }}</dd>
            </div>
            <div v-if="fileSize">
              <dt class="text-gray-500 dark:text-gray-600 text-xs uppercase tracking-wider">Ukuran File</dt>
              <dd class="text-gray-600 dark:text-gray-400 mt-0.5">{{ fmtSize(fileSize) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Aksi -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
          <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-4">Aksi</h2>
          <div class="space-y-2">
            <a v-if="videoExists" :href="downloadUrl"
               class="flex items-center justify-center gap-2 w-full py-2.5 bg-green-700
                      hover:bg-green-600 text-white text-sm rounded-lg transition-colors font-medium">
              ↓ Download Video
            </a>
            <button
              @click="doDelete"
              class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-white dark:bg-transparent border border-red-200 dark:border-transparent
                     hover:bg-red-50 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 text-sm rounded-lg transition-colors">
              🗑️ Hapus Rekaman
            </button>
          </div>
        </div>

      </div>
    </div>



</template>
