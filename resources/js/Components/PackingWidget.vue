<!-- resources/js/Pages/Packing/Index.vue -->
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({ operator: Object })
const emit = defineEmits(['close'])

// ─── Refs ─────────────────────────────────────────────────────────────────
const videoEl    = ref(null)   // <video> element untuk preview
const canvasEl   = ref(null)   // <canvas> tersembunyi untuk watermark
const inputEl    = ref(null)   // input tersembunyi untuk scanner
const scannedInput = ref('')   // Khusus untuk input form (selalu dikosongkan)
const currentOrder = ref('')   // Resi yang SEDANG direkam
const recording  = ref(false)
const cameraOk   = ref(false)
const cameraErr  = ref('')
const scannerReady = ref(false) // Status scanner
const uploadStatus = ref('')   // 'uploading' | 'ok' | 'error' | ''
const scanHistory  = ref([])   // [{order, time}] - 5 resi terakhir
const todayCount   = ref(0)    // counter resi hari ini
const timerDisplay = ref('00:00:00')  // durasi rekaman berjalan
const duplicateAlert = ref(null) // alert duplikat { order, time, operator }
const strictAlert = ref(null)    // alert strict mode ketika scan resi lain

// ─── Audio Context untuk Efek Suara ───────────────────────────────────────
const audioCtx = new (window.AudioContext || window.webkitAudioContext)()

function playErrorBeep() {
    if (audioCtx.state === 'suspended') audioCtx.resume()
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.type = 'square'
    osc.frequency.setValueAtTime(200, audioCtx.currentTime)
    osc.frequency.setValueAtTime(150, audioCtx.currentTime + 0.1)
    gain.gain.setValueAtTime(0.1, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3)
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.start()
    osc.stop(audioCtx.currentTime + 0.3)
}

function playWarningBeep() {
    if (audioCtx.state === 'suspended') audioCtx.resume()
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.type = 'sine'
    osc.frequency.setValueAtTime(600, audioCtx.currentTime)
    osc.frequency.setValueAtTime(800, audioCtx.currentTime + 0.2)
    gain.gain.setValueAtTime(0.1, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5)
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.start()
    osc.stop(audioCtx.currentTime + 0.5)
}
// ─── Internal state ───────────────────────────────────────────────────────
let mediaRecorder  = null
let chunks         = []
let canvasStream   = null
let animFrameId    = null
let timerInterval  = null
let refocusInterval = null
let recordStartTime = null

// ─── Lifecycle ────────────────────────────────────────────────────────────
onMounted(async () => {
    await initCamera()
    startRefocusWatcher()
})

onUnmounted(() => {
    clearInterval(refocusInterval)
    clearInterval(timerInterval)
    cancelAnimationFrame(animFrameId)
    mediaRecorder?.stop()
    // matikan stream kamera
    videoEl.value?.srcObject?.getTracks().forEach(t => t.stop())
})

// ─── Kamera ───────────────────────────────────────────────────────────────
async function initCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false,
        })
        videoEl.value.srcObject = stream
        await videoEl.value.play()

        // Canvas stream untuk watermark — MediaRecorder merekam dari sini
        const canvas = canvasEl.value
        canvas.width  = 1280
        canvas.height = 720
        canvasStream = canvas.captureStream(30)

        cameraOk.value = true
        startCanvasLoop()
        focusInput()
    } catch (err) {
        cameraErr.value = err.name === 'NotAllowedError'
            ? 'Izin kamera ditolak. Klik ikon kunci di address bar dan izinkan akses kamera.'
            : `Kamera tidak bisa dibuka: ${err.message}`
    }
}

// ─── Canvas Watermark Loop ────────────────────────────────────────────────
function startCanvasLoop() {
    const canvas = canvasEl.value
    const ctx    = canvas.getContext('2d')
    const W = canvas.width
    const H = canvas.height

    function draw() {
        // 1. Gambar frame dari webcam
        if (videoEl.value?.readyState >= 2) {
            ctx.drawImage(videoEl.value, 0, 0, W, H)
        } else {
            ctx.fillStyle = '#000'
            ctx.fillRect(0, 0, W, H)
        }

        // 2. Watermark overlay hanya saat merekam atau standby (selalu tampil sebagai preview)
        const now    = new Date()
        const tanggal = now.toLocaleDateString('id-ID', { day:'2-digit', month:'2-digit', year:'numeric' }).replace(/\//g, '/')
        const jam     = now.toTimeString().slice(0, 8)
        const namaOp  = props.operator?.name ?? 'Operator'
        const resi    = currentOrder.value || 'MENUNGGU SCAN...'

        const barH = 100
        ctx.fillStyle = 'rgba(0,0,0,0.6)'
        ctx.fillRect(0, H - barH, W, barH)

        ctx.font      = 'bold 18px monospace'
        ctx.fillStyle = recording.value ? '#ff4d4d' : '#aaaaaa'
        ctx.fillText(recording.value ? '● RECORDING' : '○ STANDBY', 16, H - barH + 24)

        ctx.font      = 'bold 22px monospace'
        ctx.fillStyle = '#ffffff'
        ctx.fillText(`RESI: ${resi}`, 16, H - barH + 52)

        ctx.font      = '16px monospace'
        ctx.fillStyle = '#cccccc'
        ctx.fillText(`${tanggal}  ${jam}`, 16, H - barH + 78)

        ctx.textAlign = 'right'
        ctx.fillStyle = '#cccccc'
        ctx.fillText(`Operator: ${namaOp}`, W - 16, H - barH + 78)
        ctx.textAlign = 'left'

        animFrameId = requestAnimationFrame(draw)
    }

    draw()
}

// ─── Scanner Input ────────────────────────────────────────────────────────
function onScan(e) {
    if (e.key !== 'Enter') return
    const val = scannedInput.value.trim()
    if (!val) return

    if (val.toUpperCase() === 'STOP') {
        handleStop()
    } else if (recording.value) {
        // Jika barcode yang discan SAMA dengan yang sedang direkam
        if (val === currentOrder.value) {
            // Cegah accidental double-scan (abaikan jika discan dalam 2 detik pertama)
            if (Date.now() - recordStartTime < 2000) {
                scannedInput.value = ''
                return
            }
            handleStop()
        } else {
            // STRICT MODE: Tolak resi berbeda jika sedang merekam
            showStrictAlert(val)
        }
    } else {
        handleFirstScan(val)
    }
    // Kosongkan input SETELAH di-handle
    scannedInput.value = ''
}

function showStrictAlert(wrongOrder) {
    strictAlert.value = wrongOrder
    playErrorBeep()
    // Auto dismiss after 4 seconds
    setTimeout(() => {
        if (strictAlert.value === wrongOrder) {
            strictAlert.value = null
        }
    }, 4000)
}

function handleFirstScan(order) {
    checkDuplicate(order)
    startRecording(order)
    addToHistory(order)
    todayCount.value++
}



async function checkDuplicate(order) {
    try {
        const res = await axios.get(`/recordings/check/${order}`)
        if (res.data.exists) {
            duplicateAlert.value = {
                order: order,
                time: res.data.recorded_at,
                operator: res.data.operator
            }
            playWarningBeep()
            // Auto dismiss after 8 seconds
            setTimeout(() => {
                if (duplicateAlert.value?.order === order) {
                    duplicateAlert.value = null
                }
            }, 8000)
        } else {
            duplicateAlert.value = null
        }
    } catch (e) {
        // ignore errors
    }
}

function handleStop() {
    if (!recording.value) return

    // Tangkap semua nilai SEBELUM direset
    const prevChunks   = [...chunks]
    const prevOrder    = currentOrder.value
    const prevDuration = recordStartTime ? Math.round((Date.now() - recordStartTime) / 1000) : 0

    mediaRecorder.onstop = () => {
        uploadVideo(prevOrder, prevChunks, prevDuration)
    }
    mediaRecorder.stop()
    chunks = []

    recording.value = false
    currentOrder.value = ''
    stopTimer()
}

// ─── Recording ───────────────────────────────────────────────────────────
function startRecording(order) {
    chunks = []
    mediaRecorder = new MediaRecorder(canvasStream, {
        mimeType: 'video/webm;codecs=vp9',
    })
    mediaRecorder.ondataavailable = e => {
        if (e.data.size > 0) chunks.push(e.data)
    }
    mediaRecorder.start(1000) // ambil chunk tiap 1 detik

    recording.value  = true
    currentOrder.value = order
    startTimer()
    recordStartTime  = Date.now()
}

// ─── Upload ───────────────────────────────────────────────────────────────
async function uploadVideo(order, videoChunks, duration) {
    if (!videoChunks.length) return
    uploadStatus.value = 'uploading'

    try {
        const blob = new Blob(videoChunks, { type: 'video/webm' })
        const form = new FormData()
        form.append('video',        blob, `${order}.webm`)
        form.append('order_number', order)
        form.append('recorded_at',  new Date().toISOString())
        form.append('duration',     duration)
        // CSRF dari meta tag yang disediakan Laravel
        await axios.post('/recordings', form, {
            headers: { 'X-XSRF-TOKEN': getCsrfToken() },
        })
        uploadStatus.value = 'ok'
    } catch {
        uploadStatus.value = 'error'
    }

    // Reset indikator setelah 3 detik
    setTimeout(() => { uploadStatus.value = '' }, 3000)
}

function getCsrfToken() {
    return decodeURIComponent(
        document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? ''
    )
}

// ─── Timer Durasi ─────────────────────────────────────────────────────────
function startTimer() {
    stopTimer()
    timerInterval = setInterval(() => {
        if (!recordStartTime) return
        const s   = Math.floor((Date.now() - recordStartTime) / 1000)
        const hh  = String(Math.floor(s / 3600)).padStart(2, '0')
        const mm  = String(Math.floor((s % 3600) / 60)).padStart(2, '0')
        const ss  = String(s % 60).padStart(2, '0')
        timerDisplay.value = `${hh}:${mm}:${ss}`
    }, 1000)
}

function stopTimer() {
    clearInterval(timerInterval)
    timerDisplay.value = '00:00:00'
    recordStartTime    = null
}

// ─── Auto-refocus ─────────────────────────────────────────────────────────
function focusInput() {
    inputEl.value?.focus()
}

function startRefocusWatcher() {
    // Kembalikan fokus ke input setiap kali dokumen diklik
    document.addEventListener('click', focusInput)
    // Fallback: cek fokus setiap 500ms
    refocusInterval = setInterval(() => {
        if (document.activeElement !== inputEl.value) {
            focusInput()
        }
        scannerReady.value = document.activeElement === inputEl.value
    }, 500)
}

// ─── History ──────────────────────────────────────────────────────────────
function addToHistory(order) {
    const now = new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' })
    scanHistory.value.unshift({ order, time: now })
    if (scanHistory.value.length > 5) scanHistory.value.pop()
}
</script>

<template>
  <!-- Backdrop -->
  <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-[100] p-4 backdrop-blur-sm"
       @click="focusInput">

    <!-- Duplicate Warning Toast (Modern & Keren) -->
    <transition
      enter-active-class="transform ease-out duration-500 transition"
      enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-8 scale-95"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0 scale-100"
      leave-active-class="transition ease-in duration-300"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="duplicateAlert" class="absolute top-20 right-6 sm:right-10 z-[200] w-full max-w-sm overflow-hidden rounded-2xl bg-gray-900/90 backdrop-blur-xl border border-red-500/50 shadow-[0_0_40px_-10px_rgba(239,68,68,0.5)]">
        <!-- Accent Line -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-600 to-orange-400"></div>
        <div class="p-4 flex items-start gap-4 relative">
          <!-- Glow effect behind icon -->
          <div class="absolute -left-4 -top-4 w-16 h-16 bg-red-500/20 rounded-full blur-xl"></div>
          
          <!-- Icon -->
          <div class="relative flex-shrink-0 bg-red-500/10 p-2.5 rounded-xl border border-red-500/20">
            <svg class="h-6 w-6 text-red-500 animate-[pulse_2s_ease-in-out_infinite]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>

          <!-- Content -->
          <div class="flex-1 pt-0.5">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-red-400 tracking-wide uppercase">RESI SUDAH ADA!</h3>
              <button @click.stop="duplicateAlert = null" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700/50 rounded-lg p-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <p class="mt-1 text-sm text-gray-300">
              Resi <strong class="text-white bg-red-500/20 px-1.5 py-0.5 rounded">{{ duplicateAlert.order }}</strong> sudah pernah direkam sebelumnya.
            </p>
            <div class="mt-3 bg-black/40 rounded-xl p-2.5 text-xs text-gray-400 border border-white/5 flex flex-col gap-1.5 shadow-inner">
              <div class="flex justify-between items-center">
                <span>Waktu Perekaman:</span>
                <span class="text-white font-medium">{{ duplicateAlert.time }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span>Operator:</span>
                <span class="text-white font-medium">{{ duplicateAlert.operator }}</span>
              </div>
            </div>
            <p class="mt-3 text-[11px] text-red-300/70 italic flex items-center gap-1.5 border-l-2 border-red-500/50 pl-2">
              <span>⚠️ Perekaman saat ini <strong class="text-red-400">TETAP BERJALAN</strong>. Scan kata <strong class="text-white">STOP</strong> jika ingin membatalkan.</span>
            </p>
          </div>
        </div>
      </div>
    </transition>

    <!-- Strict Mode Warning Toast -->
    <transition
      enter-active-class="transform ease-out duration-500 transition"
      enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-8 scale-95"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0 scale-100"
      leave-active-class="transition ease-in duration-300"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="strictAlert" class="absolute top-[280px] right-6 sm:right-10 z-[200] w-full max-w-sm overflow-hidden rounded-2xl bg-gray-900/90 backdrop-blur-xl border border-orange-500/50 shadow-[0_0_40px_-10px_rgba(249,115,22,0.5)]">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-orange-600 to-yellow-400"></div>
        <div class="p-4 flex items-start gap-4 relative">
          <div class="absolute -left-4 -top-4 w-16 h-16 bg-orange-500/20 rounded-full blur-xl"></div>
          
          <div class="relative flex-shrink-0 bg-orange-500/10 p-2.5 rounded-xl border border-orange-500/20 text-orange-400 text-xl font-black">
            🔒
          </div>

          <div class="flex-1 pt-0.5">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-orange-400 tracking-wide uppercase">SCAN DITOLAK!</h3>
              <button @click.stop="strictAlert = null" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700/50 rounded-lg p-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <p class="mt-1 text-sm text-gray-300 leading-relaxed">
              Anda tidak sengaja menscan resi <strong class="text-white bg-orange-500/20 px-1.5 py-0.5 rounded font-mono">{{ strictAlert }}</strong>.
            </p>
            <p class="mt-3 text-[11px] text-orange-300/70 italic flex items-center gap-1.5 border-l-2 border-orange-500/50 pl-2">
              <span>Selesaikan paket saat ini dengan scan <strong class="text-white font-mono">{{ currentOrder }}</strong> atau <strong class="text-white font-mono">STOP</strong> terlebih dahulu.</span>
            </p>
          </div>
        </div>
      </div>
    </transition>
    
    <!-- Modal Container -->
    <div class="bg-gray-900 rounded-2xl shadow-2xl border border-gray-700 flex flex-col select-none overflow-hidden w-full max-w-5xl"
         @click.stop="focusInput">
      
      <!-- Header -->
      <header class="h-14 bg-gray-950 border-b border-gray-800 flex items-center justify-between px-5">
        <div class="flex items-center gap-3">
          <span class="text-blue-400 font-bold text-lg tracking-wider">📦 PROOFIX PACKING</span>
          <span v-if="recording" class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></span>
        </div>

        <!-- Status Hardware -->
        <div class="flex items-center gap-6 bg-gray-900 px-4 py-1.5 rounded-full border border-gray-700 shadow-inner">
          <div class="flex items-center gap-2 transition-colors duration-300" :class="cameraOk ? 'text-green-400' : 'text-red-400'">
             <span class="relative flex h-2.5 w-2.5">
                <span v-if="cameraOk" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="cameraOk ? 'bg-green-500' : 'bg-red-500'"></span>
             </span>
             <span class="text-xs font-bold tracking-wider uppercase">Kamera</span>
          </div>
          <div class="w-px h-4 bg-gray-700"></div>
          <div class="flex items-center gap-2 transition-colors duration-300 cursor-help" :class="scannerReady ? 'text-green-400' : 'text-yellow-500'" title="Status kesiapan aplikasi menerima input ketikan dari scanner">
             <span class="relative flex h-2.5 w-2.5">
                <span v-if="scannerReady" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="scannerReady ? 'bg-green-500' : 'bg-yellow-500'"></span>
             </span>
             <span class="text-xs font-bold tracking-wider uppercase">Sistem Scan</span>
          </div>
        </div>

        <button @click="emit('close')" class="text-gray-400 hover:text-red-400 transition-colors p-2 bg-gray-800 hover:bg-gray-700 rounded-lg flex items-center gap-2">
          <span class="text-sm font-semibold">Tutup</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </header>

      <!-- Main Layout -->
      <div class="flex flex-col lg:flex-row">
        
        <!-- Canvas + Video area (Left/Top) -->
        <div class="w-full lg:w-2/3 relative bg-black border-b lg:border-b-0 lg:border-r border-gray-800 flex items-center justify-center" style="min-height: 300px;">
           <!-- Error kamera -->
          <div v-if="cameraErr" class="absolute inset-0 flex flex-col items-center justify-center p-6 bg-red-900/40 text-center z-10">
            <p class="text-4xl mb-3">📷</p>
            <p class="text-red-300 font-semibold text-lg">Kamera Tidak Tersedia</p>
            <p class="text-red-400 text-sm mt-2 max-w-md">{{ cameraErr }}</p>
          </div>

          <div v-if="!cameraErr" :class="[
            'transition-colors duration-300 w-full relative flex flex-col',
            recording ? 'border-4 border-red-500 shadow-[inset_0_0_50px_rgba(239,68,68,0.3)]' : 'border-4 border-transparent'
          ]">
            <!-- Canvas yang ditampilkan (output watermark) -->
            <canvas ref="canvasEl" class="w-full block" style="aspect-ratio:16/9"/>
            <!-- Video asli: tersembunyi -->
            <video ref="videoEl" autoplay muted playsinline class="hidden"/>

            <!-- Badge RECORDING -->
            <div v-if="recording"
                 class="absolute top-4 left-4 bg-red-600/90 text-white text-sm font-bold px-4 py-1.5 rounded-full shadow-lg animate-pulse tracking-wider">
              ● REC {{ timerDisplay }}
            </div>
          </div>
        </div>

        <!-- Info Area (Right/Bottom) -->
        <div class="w-full lg:w-1/3 bg-gray-900 flex flex-col">
          <div class="p-6 flex-1 flex flex-col">
            
            <div class="bg-gray-800/50 rounded-xl p-5 border border-gray-700/50 mb-5 text-center">
              <p class="text-gray-400 text-xs uppercase tracking-wider mb-2">Status Perekaman</p>
              <p v-if="recording" class="text-red-400 font-mono font-bold text-3xl mb-1 animate-pulse">RECORDING</p>
              <p v-else class="text-gray-500 font-medium text-3xl mb-1">Standby</p>
              
              <div class="mt-5 border-t border-gray-700/50 pt-4">
                <p class="text-gray-500 text-xs uppercase tracking-wider mb-2">Nomor Resi Aktif</p>
                <p class="text-white font-mono text-2xl truncate px-2" :class="recording ? 'font-bold text-blue-300' : ''">
                  {{ recording ? currentOrder : (scannedInput || '—') }}
                </p>
              </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 mb-5">
              <div class="bg-gray-800/50 rounded-xl p-4 text-center border border-gray-700/50">
                <p class="text-blue-400 font-bold text-2xl">{{ todayCount }}</p>
                <p class="text-gray-500 text-[10px] uppercase mt-1">Pack Hari Ini</p>
              </div>
              <div class="bg-gray-800/50 rounded-xl p-4 text-center border border-gray-700/50 flex flex-col items-center justify-center">
                <p v-if="uploadStatus === 'uploading'" class="text-yellow-400 text-2xl animate-pulse">⏳</p>
                <p v-else-if="uploadStatus === 'ok'" class="text-green-400 text-2xl">✓</p>
                <p v-else-if="uploadStatus === 'error'" class="text-red-400 text-2xl">✗</p>
                <p v-else class="text-gray-500 text-2xl">—</p>
                <p class="text-gray-500 text-[10px] uppercase mt-1">Status Simpan</p>
              </div>
            </div>

            <!-- Panduan -->
            <div class="mt-auto text-sm text-gray-400 space-y-3 bg-gray-950 p-5 rounded-xl border border-gray-800 shadow-inner">
              <p class="flex items-center gap-3">
                <span class="text-xl">📦</span> 
                <span><strong class="text-gray-300">Scan resi</strong> untuk mulai/stop merekam paket ini.</span>
              </p>
              <p class="flex items-center gap-3">
                <span class="text-xl">🔒</span> 
                <span><strong class="text-gray-300">Strict Mode Aktif:</strong> Segala scan resi asing akan ditolak hingga resi saat ini selesai.</span>
              </p>
              <p class="flex items-center gap-3">
                <span class="text-xl">🛑</span> 
                <span><strong class="text-gray-300">Scan kata STOP</strong> untuk matikan kamera.</span>
              </p>
            </div>

          </div>
        </div>

      </div>

      <!-- Input tersembunyi -->
      <input
        ref="inputEl"
        id="barcode-input"
        v-model="scannedInput"
        @keydown="onScan"
        @focus="scannerReady = true"
        @blur="scannerReady = false"
        class="opacity-0 absolute pointer-events-none w-0 h-0"
        autocomplete="off"
        aria-hidden="true"
      />
    </div>
  </div>
</template>