<template>
    <!-- BEGIN FOOTER -->
    <div class="footer-wrapper">
        <div class="footer-section f-section-1">
            <p class="">© {{ currentYear }} Todos los derechos reservados. Es un producto de Techmedia Solutiones tecnologicas.</p>
        </div>
        <div class="footer-section f-section-2">
            
        </div>
    </div>
    <!-- END FOOTER -->
</template>

<script setup>
import { computed } from 'vue';

const currentYear = computed(() => {
    return new Date().getFullYear();
});
</script>
