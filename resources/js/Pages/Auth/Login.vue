<!-- resources/js/Pages/Auth/Login.vue -->
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ThemeToggle from '@/Components/ThemeToggle.vue'

defineProps({
    canResetPassword: Boolean,
    status: String,
})

const form = useForm({
    email:    '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Masuk — Proofix" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center p-4 relative">
        <!-- Theme Toggle -->
        <div class="absolute top-4 right-4">
            <ThemeToggle />
        </div>
        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="text-center mb-8">
                <p class="text-blue-400 font-black text-4xl tracking-widest mb-1">PROOFIX</p>
                <p class="text-gray-600 text-sm">Sistem Dokumentasi Pengemasan</p>
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-8 shadow-xl dark:shadow-2xl">

                <!-- Status message -->
                <div v-if="status"
                     class="mb-5 px-4 py-3 bg-green-100 dark:bg-green-900/50 border border-green-200 dark:border-green-700
                            rounded-lg text-green-700 dark:text-green-400 text-sm text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Email -->
                    <div>
                        <label for="email"
                               class="block text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider mb-2">
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@gudang.com"
                            :class="['w-full bg-gray-50 dark:bg-gray-800 border rounded-xl px-4 py-3 text-gray-900 dark:text-white text-sm',
                                     'placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none transition-colors',
                                     form.errors.email
                                       ? 'border-red-600'
                                       : 'border-gray-300 dark:border-gray-700 focus:border-blue-500']"
                        />
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1.5">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password"
                               class="block text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider mb-2">
                            Password
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            :class="['w-full bg-gray-50 dark:bg-gray-800 border rounded-xl px-4 py-3 text-gray-900 dark:text-white text-sm',
                                     'placeholder-gray-400 dark:placeholder-gray-600 focus:outline-none transition-colors',
                                     form.errors.password
                                       ? 'border-red-600'
                                       : 'border-gray-300 dark:border-gray-700 focus:border-blue-500']"
                        />
                        <p v-if="form.errors.password" class="text-red-400 text-xs mt-1.5">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 rounded bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600
                                       text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-50 dark:focus:ring-offset-gray-900"
                            />
                            <span class="text-gray-600 dark:text-gray-500 text-sm">Ingat saya</span>
                        </label>
                        <Link v-if="canResetPassword"
                              :href="route('password.request')"
                              class="text-gray-600 dark:text-gray-500 text-sm hover:text-blue-500 dark:hover:text-blue-400 transition-colors">
                            Lupa password?
                        </Link>
                    </div>

                    <!-- Submit -->
                    <button
                        id="btn-login"
                        type="submit"
                        :disabled="form.processing"
                        :class="['w-full py-3 rounded-xl font-semibold text-sm transition-all',
                                 form.processing
                                   ? 'bg-blue-800 text-blue-400 cursor-not-allowed'
                                   : 'bg-blue-600 hover:bg-blue-500 text-white']"
                    >
                        <span v-if="form.processing">Masuk…</span>
                        <span v-else>Masuk</span>
                    </button>

                </form>
            </div>

            <!-- Footer hint
            <p class="text-center text-gray-700 text-xs mt-6">
                Admin masuk → diarahkan ke Dashboard &nbsp;|&nbsp;
                Operator masuk → diarahkan ke halaman Packing
            </p> -->
        </div>
    </div>
</template>
