<!-- resources/js/Pages/Admin/Operators.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { ref, reactive, computed, watch } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps({
    operators: Array,
})

const flash = computed(() => usePage().props.flash ?? {})

// ─── Modal state ──────────────────────────────────────────────────────────
const modalOpen   = ref(false)
const editTarget  = ref(null)  // null = mode tambah, object = mode edit

const form = reactive({
    name: '', email: '', password: '', station: '',
})
const errors = ref({})

function openAdd() {
    editTarget.value = null
    form.name = ''; form.email = ''; form.password = ''; form.station = ''
    errors.value = {}
    modalOpen.value = true
}

function openEdit(op) {
    editTarget.value = op
    form.name     = op.name
    form.email    = op.email
    form.password = ''          // kosong = tidak ganti password
    form.station  = op.station ?? ''
    errors.value  = {}
    modalOpen.value = true
}

function closeModal() {
    modalOpen.value = false
}

function submitForm() {
    errors.value = {}
    if (editTarget.value) {
        router.patch(route('admin.operators.update', editTarget.value.id), form, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError:   (e) => { errors.value = e },
        })
    } else {
        router.post(route('admin.operators.store'), form, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError:   (e) => { errors.value = e },
        })
    }
}

function confirmDelete(op) {
    Swal.fire({
        title: 'Hapus Operator?',
        html: `Hapus operator <strong class="text-red-500">${op.name}</strong>?<br><span class="text-sm">Semua data rekamannya tetap akan tersimpan.</span>`,
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
            router.delete(route('admin.operators.destroy', op.id), {
                preserveScroll: true,
            })
        }
    })
}

// ─── Helpers ─────────────────────────────────────────────────────────────
function fmtDate(dt) {
    if (!dt) return '—'
    return new Date(dt).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
    })
}

function initials(name) {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

const avatarColors = [
    'bg-blue-600', 'bg-purple-600', 'bg-green-600',
    'bg-orange-600', 'bg-pink-600', 'bg-teal-600',
]
function avatarColor(id) {
    return avatarColors[id % avatarColors.length]
}

// Tutup modal saat klik backdrop
function onBackdropClick(e) {
    if (e.target === e.currentTarget) closeModal()
}
</script>

<template>
    <!-- Flash message -->
    <div v-if="flash.message"
         class="mb-4 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl
                text-green-400 text-sm flex items-center gap-2">
      ✓ {{ flash.message }}
    </div>

    <!-- Header bar -->
    <div class="flex items-center justify-between mb-5">
      <p class="text-gray-600 text-sm">
        Total <span class="text-gray-400 font-medium">{{ operators.length }}</span> operator terdaftar
      </p>
      <button
        id="btn-tambah-operator"
        @click="openAdd"
        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-500
               text-white text-sm rounded-lg font-medium transition-colors"
      >
        + Tambah Operator
      </button>
    </div>

    <!-- Grid kartu operator ──────────────────────────────────────────────── -->
    <div v-if="operators.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="op in operators" :key="op.id"
        class="bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-gray-700
               transition-colors group"
      >
        <!-- Avatar + nama -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div :class="['w-10 h-10 rounded-full flex items-center justify-center',
                          'text-white font-bold text-sm shrink-0', avatarColor(op.id)]">
              {{ initials(op.name) }}
            </div>
            <div>
              <p class="text-white font-semibold text-sm leading-tight">{{ op.name }}</p>
              <p class="text-gray-500 text-xs mt-0.5 truncate max-w-[160px]">{{ op.email }}</p>
            </div>
          </div>
          <!-- Aksi — tampil saat hover -->
          <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="openEdit(op)"
                    class="text-xs text-blue-400 hover:text-blue-300 px-2 py-1
                           bg-blue-900/30 rounded transition-colors">
              Edit
            </button>
            <button @click="confirmDelete(op)"
                    class="text-xs text-red-500 hover:text-red-400 px-2 py-1
                           bg-red-900/30 rounded transition-colors">
              Hapus
            </button>
          </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div class="bg-gray-800/50 rounded-lg p-3 text-center">
            <p class="text-xl font-black text-white">{{ op.recordings_count }}</p>
            <p class="text-gray-600 text-xs mt-0.5">Total Rekaman</p>
          </div>
          <div class="bg-gray-800/50 rounded-lg p-3 text-center">
            <p class="text-xs font-medium text-gray-400 mt-1">
              {{ op.recordings_max_recorded_at ? fmtDate(op.recordings_max_recorded_at) : '—' }}
            </p>
            <p class="text-gray-600 text-xs mt-0.5">Terakhir Aktif</p>
          </div>
        </div>

        <!-- Stasiun -->
        <div class="flex items-center gap-2 text-xs text-gray-500">
          <span>📍</span>
          <span>{{ op.station ?? 'Stasiun belum diatur' }}</span>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else
         class="bg-gray-900 border border-gray-800 border-dashed rounded-xl py-16
                text-center text-gray-600">
      <p class="text-4xl mb-3">👷</p>
      <p class="font-medium">Belum ada operator</p>
      <p class="text-xs mt-1 text-gray-700">Klik tombol "Tambah Operator" untuk memulai</p>
    </div>

    <!-- ─── Modal Tambah / Edit ──────────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="modalOpen"
        class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
        @click="onBackdropClick"
      >
        <div class="bg-gray-900 border border-gray-700 rounded-2xl w-full max-w-md shadow-2xl">

          <!-- Modal header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
            <h2 class="text-white font-bold text-base">
              {{ editTarget ? '✏️ Edit Operator' : '➕ Tambah Operator' }}
            </h2>
            <button @click="closeModal"
                    class="text-gray-500 hover:text-gray-300 text-xl leading-none transition-colors">
              ×
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="submitForm" class="px-6 py-5 space-y-4">

            <!-- Nama -->
            <div>
              <label class="block text-gray-400 text-xs uppercase tracking-wider mb-1.5">
                Nama Lengkap <span class="text-red-500">*</span>
              </label>
              <input
                id="input-nama"
                v-model="form.name"
                type="text"
                placeholder="cth. Budi Santoso"
                class="w-full bg-gray-800 border rounded-lg px-3 py-2.5 text-white text-sm
                       placeholder-gray-600 focus:outline-none transition-colors"
                :class="errors.name ? 'border-red-600' : 'border-gray-700 focus:border-blue-500'"
              />
              <p v-if="errors.name" class="text-red-400 text-xs mt-1">{{ errors.name }}</p>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-gray-400 text-xs uppercase tracking-wider mb-1.5">
                Email <span class="text-red-500">*</span>
              </label>
              <input
                id="input-email"
                v-model="form.email"
                type="email"
                placeholder="budi@gudang.com"
                class="w-full bg-gray-800 border rounded-lg px-3 py-2.5 text-white text-sm
                       placeholder-gray-600 focus:outline-none transition-colors"
                :class="errors.email ? 'border-red-600' : 'border-gray-700 focus:border-blue-500'"
              />
              <p v-if="errors.email" class="text-red-400 text-xs mt-1">{{ errors.email }}</p>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-gray-400 text-xs uppercase tracking-wider mb-1.5">
                Password
                <span v-if="editTarget" class="text-gray-600 normal-case tracking-normal ml-1">
                  (kosongkan jika tidak ingin mengubah)
                </span>
                <span v-else class="text-red-500">*</span>
              </label>
              <input
                id="input-password"
                v-model="form.password"
                type="password"
                placeholder="Minimal 8 karakter"
                class="w-full bg-gray-800 border rounded-lg px-3 py-2.5 text-white text-sm
                       placeholder-gray-600 focus:outline-none transition-colors"
                :class="errors.password ? 'border-red-600' : 'border-gray-700 focus:border-blue-500'"
              />
              <p v-if="errors.password" class="text-red-400 text-xs mt-1">{{ errors.password }}</p>
            </div>

            <!-- Stasiun -->
            <div>
              <label class="block text-gray-400 text-xs uppercase tracking-wider mb-1.5">
                Stasiun / Meja Kerja
              </label>
              <input
                id="input-station"
                v-model="form.station"
                type="text"
                placeholder="cth. Meja 1, Stasiun A"
                class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500
                       rounded-lg px-3 py-2.5 text-white text-sm placeholder-gray-600
                       focus:outline-none transition-colors"
              />
            </div>

            <!-- Action buttons -->
            <div class="flex gap-3 pt-1">
              <button type="button" @click="closeModal"
                      class="flex-1 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300
                             rounded-lg text-sm transition-colors">
                Batal
              </button>
              <button
                id="btn-simpan-operator"
                type="submit"
                class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-semibold
                       rounded-lg text-sm transition-colors"
              >
                {{ editTarget ? 'Simpan Perubahan' : 'Tambah Operator' }}
              </button>
            </div>

          </form>
        </div>
      </div>
    </Teleport>

</template>
