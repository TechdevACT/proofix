<!-- resources/js/Pages/Admin/Settings.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { reactive, computed } from 'vue'

const props = defineProps({
    settings:  Object,
    disk:      Object,
    videoSize: String,
})

const flash = computed(() => usePage().props.flash ?? {})

const form = reactive({ ...props.settings })

function save() {
    router.post(route('admin.settings.update'), form, {
        preserveScroll: true,
    })
}

const diskColor = computed(() => {
    if (props.disk.percent >= 90) return 'bg-red-500'
    if (props.disk.percent >= 70) return 'bg-yellow-500'
    return 'bg-green-500'
})
</script>

<template>
    <!-- Flash -->
    <div v-if="flash.message"
         class="mb-5 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl
                text-green-400 text-sm flex items-center gap-2">
      ✓ {{ flash.message }}
    </div>

    <div class="max-w-3xl space-y-5">

      <!-- Disk Space ──────────────────────────────────────────────────────── -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Kapasitas Penyimpanan Local Storage</p>
            <p class="text-gray-500 dark:text-gray-600 text-xs mt-0.5">Tersisa {{ disk.free_gb }} GB dari total {{ disk.total_gb }} GB</p>
          </div>
          <span class="text-gray-900 dark:text-white font-bold text-sm bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-lg">{{ disk.percent }}% Terpakai</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
          <div :class="['h-full rounded-full transition-all duration-500', diskColor]" :style="{ width: disk.percent + '%' }"></div>
        </div>
        <p class="text-gray-500 dark:text-gray-600 text-[11px] mt-3 flex justify-between">
          <span>Otomatis dikelola oleh sistem (Retensi {{ form.retention_days }} hari)</span>
          <span>Ukuran folder video saat ini: <strong class="text-gray-700 dark:text-gray-400">{{ videoSize }}</strong></span>
        </p>
      </div>

      <!-- Konfigurasi Rekaman ───────────────────────────────────────────── -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-4">🎬 Konfigurasi Rekaman</h2>

        <div class="space-y-5">

          <!-- Kata kunci STOP -->
          <div>
            <label class="block text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider mb-2">
              Kata Kunci Berhenti (STOP Keyword)
            </label>
            <input
              id="input-stop-keyword"
              v-model="form.stop_keyword"
              type="text"
              class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 focus:border-blue-500
                     rounded-lg px-3 py-2.5 text-gray-900 dark:text-white text-sm font-mono
                     placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none transition-colors max-w-xs"
            />
            <p class="text-gray-500 dark:text-gray-600 text-xs mt-1.5">
              Teks yang ketika dipindai akan menghentikan rekaman. Default: STOP
            </p>
          </div>

          <!-- Kualitas video -->
          <div>
            <label class="block text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider mb-2">
              Kualitas Video
            </label>
            <div class="flex gap-3">
              <label v-for="q in ['720p', '480p', '360p']" :key="q"
                     :class="['flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition-colors',
                              form.video_quality === q
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                : 'border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-600']">
                <input type="radio" :value="q" v-model="form.video_quality" class="hidden" />
                <span class="font-medium text-sm">{{ q }}</span>
              </label>
            </div>
            <p class="text-gray-500 dark:text-gray-600 text-xs mt-1.5">
              Kualitas video disimpan murni (tanpa kompresi) pada resolusi yang dipilih. Frame rate stabil di 30 FPS.
            </p>
          </div>

          <!-- Posisi watermark -->
          <div>
            <label class="block text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider mb-2">
              Posisi Watermark
            </label>
            <div class="flex gap-3">
              <label v-for="pos in [{ val: 'bottom', label: 'Bawah' }, { val: 'top', label: 'Atas' }]"
                     :key="pos.val"
                     :class="['flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition-colors',
                              form.watermark_pos === pos.val
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                : 'border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-600']">
                <input type="radio" :value="pos.val" v-model="form.watermark_pos" class="hidden" />
                <span class="text-sm font-medium">{{ pos.label }}</span>
              </label>
            </div>
          </div>

          <!-- Retensi video -->
          <div>
            <label class="block text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider mb-2">
              Retensi Video (hari)
            </label>
            <div class="flex items-center gap-3">
              <input
                id="input-retention"
                v-model.number="form.retention_days"
                type="number"
                min="1"
                max="365"
                class="w-28 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 focus:border-blue-500
                       rounded-lg px-3 py-2.5 text-gray-900 dark:text-white text-sm focus:outline-none transition-colors"
              />
              <span class="text-gray-500 dark:text-gray-500 text-sm">hari</span>
            </div>
            <p class="text-gray-500 dark:text-gray-600 text-xs mt-1.5">
              Video yang lebih lama dari batas ini akan dihapus secara otomatis dari sistem.
              0 = simpan selamanya.
            </p>
          </div>

        </div>
      </div>

      <!-- Akun Default ────────────────────────────────────────────────────── -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
        <h2 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-3">ℹ️ Informasi Sistem</h2>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-500">URL Aplikasi</span>
            <span class="text-gray-800 dark:text-gray-300 font-mono text-xs">{{ $page.props.ziggy?.url ?? window?.location.origin }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-500">Barcode STOP aktif</span>
            <span class="text-gray-900 dark:text-white font-mono font-bold">{{ settings.stop_keyword }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-500">Kualitas video aktif</span>
            <span class="text-gray-900 dark:text-white">{{ settings.video_quality }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-500">Retensi video</span>
            <span class="text-gray-900 dark:text-white">{{ settings.retention_days }} hari</span>
          </div>
        </div>
      </div>

      <!-- Simpan -->
      <div class="flex justify-end">
        <button
          id="btn-simpan-settings"
          @click="save"
          class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-semibold
                 rounded-lg text-sm transition-colors"
        >
          Simpan Pengaturan
        </button>
      </div>

    </div>
</template>
