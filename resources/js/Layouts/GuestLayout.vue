<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const empresa = computed(() => page.props.empresa ?? {});
const appName = computed(() => empresa.value.nombre || page.props.appName || 'Hotel');
const logoUrl = computed(() => empresa.value.logo_url || '/favicon.ico');
</script>

<template>
    <div class="login-page">
        <div class="login-backdrop" aria-hidden="true" />
        <div class="login-shell login-shell--branded">
            <div class="login-panel login-panel--form">
                <div class="login-form-card">
                    <div class="login-form-brand text-center mb-4">
                        <img :src="logoUrl" :alt="appName" class="login-logo login-logo--hero" />
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
