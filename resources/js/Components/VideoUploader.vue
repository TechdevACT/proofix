<template>
  <div class="max-w-xl mx-auto p-6 bg-white rounded-lg shadow-md border border-gray-200">
    <h2 class="text-2xl font-semibold mb-4 text-gray-800">Video Compression & Upload</h2>
    
    <div class="mb-4">
      <label class="block mb-2 text-sm font-medium text-gray-900" for="video_file">Upload Video</label>
      <input 
        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" 
        id="video_file" 
        type="file" 
        accept="video/*"
        @change="handleFileChange"
        :disabled="isProcessing"
      >
    </div>

    <!-- Progress Status -->
    <div v-if="isProcessing" class="mb-4">
      <p class="text-sm text-gray-600 mb-1">{{ statusMessage }}</p>
      <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: progress + '%' }"></div>
      </div>
      <p class="text-xs text-gray-500 mt-1">{{ progress.toFixed(1) }}%</p>
    </div>

    <div v-if="error" class="p-3 mb-4 text-sm text-red-800 rounded-lg bg-red-50">
      {{ error }}
    </div>

    <div v-if="successUrl" class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-50">
      Upload complete! <br>
      Video path: {{ successUrl }}
    </div>

    <button 
      @click="processAndUpload" 
      :disabled="!selectedFile || isProcessing"
      class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center disabled:opacity-50"
    >
      {{ isProcessing ? 'Processing...' : 'Compress & Upload' }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { FFmpeg } from '@ffmpeg/ffmpeg';
import { fetchFile } from '@ffmpeg/util';
import axios from 'axios';

const selectedFile = ref(null);
const isProcessing = ref(false);
const progress = ref(0);
const statusMessage = ref('');
const error = ref('');
const successUrl = ref('');

let ffmpeg = null;

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file && file.type.startsWith('video/')) {
    selectedFile.value = file;
    error.value = '';
    successUrl.value = '';
    progress.value = 0;
  } else {
    selectedFile.value = null;
    error.value = 'Please select a valid video file.';
  }
};

const loadFFmpeg = async () => {
  if (ffmpeg) return ffmpeg;
  
  ffmpeg = new FFmpeg();
  
  ffmpeg.on('progress', ({ progress: ffmpegProgress }) => {
    progress.value = ffmpegProgress * 100;
  });

  ffmpeg.on('log', ({ message }) => {
    console.log('[FFmpeg]', message);
  });

  statusMessage.value = 'Loading compression engine...';
  // Load ffmpeg.wasm-core
  await ffmpeg.load({
    coreURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/umd/ffmpeg-core.js',
    wasmURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/umd/ffmpeg-core.wasm',
  });
  
  return ffmpeg;
};

const compressVideo = async (file) => {
  const ffmpegInstance = await loadFFmpeg();
  statusMessage.value = 'Compressing video... This may take a moment depending on your device.';
  progress.value = 0;

  const inputName = 'input_video.mp4';
  const outputName = 'output_video.mp4';

  // Write file to FFmpeg's virtual file system
  await ffmpegInstance.writeFile(inputName, await fetchFile(file));

  // Run compression command
  // Settings used: libx264 codec, crf 28 (good balance of quality/size), preset veryfast
  // Scaled to 720p maximum to save size, adjusting aspect ratio automatically
  await ffmpegInstance.exec([
    '-i', inputName,
    '-vcodec', 'libx264',
    '-crf', '35',               // Dinaikkan ke 35 untuk ukuran yang jauh lebih kecil
    '-preset', 'veryfast',
    '-r', '15',                 // 15 FPS
    '-vf', 'scale=-2:480',      // 480p
    '-acodec', 'aac',           // Kompresi format audio
    '-b:a', '48k',              // Bitrate audio sangat rendah (hemat banyak size)
    '-movflags', '+faststart',  // SANGAT PENTING: Memperbaiki durasi, bar, dan kecepatan player di web!
    outputName
  ]);

  // Read the compressed file
  const data = await ffmpegInstance.readFile(outputName);
  
  // Clean up memory
  await ffmpegInstance.deleteFile(inputName);
  await ffmpegInstance.deleteFile(outputName);

  return new Blob([data.buffer], { type: 'video/mp4' });
};

const uploadInChunks = async (blob, originalFileName) => {
  statusMessage.value = 'Uploading...';
  progress.value = 0;

  const chunkSize = 2 * 1024 * 1024; // 2MB chunks for cPanel safety
  const totalChunks = Math.ceil(blob.size / chunkSize);
  const uploadId = Date.now().toString() + Math.random().toString(36).substring(7);

  for (let i = 0; i < totalChunks; i++) {
    const start = i * chunkSize;
    const end = Math.min(start + chunkSize, blob.size);
    const chunk = blob.slice(start, end);

    const formData = new FormData();
    formData.append('chunk', chunk);
    formData.append('chunkIndex', i);
    formData.append('totalChunks', totalChunks);
    formData.append('fileName', originalFileName);
    formData.append('uploadId', uploadId);

    // Using Axios to upload the chunk
    try {
        const response = await axios.post('/upload-video-chunk', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        // Update overall upload progress
        progress.value = ((i + 1) / totalChunks) * 100;

        if (response.data.file_path) {
            return response.data.file_path; // Final chunk completed
        }
    } catch (err) {
        throw new Error('Upload failed at chunk ' + (i + 1) + '. ' + err.message);
    }
  }
};

const processAndUpload = async () => {
  if (!selectedFile.value) return;
  
  isProcessing.value = true;
  error.value = '';
  
  try {
    // 1. Compress
    const compressedBlob = await compressVideo(selectedFile.value);
    
    // 2. Upload
    const finalPath = await uploadInChunks(compressedBlob, selectedFile.value.name);
    
    successUrl.value = finalPath;
    statusMessage.value = 'Done!';
    progress.value = 100;
  } catch (err) {
    console.error(err);
    error.value = err.message || 'An error occurred during processing.';
  } finally {
    isProcessing.value = false;
  }
};
</script>
