<script setup>
import { Chart, registerables } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(...registerables);

const props = defineProps({
    type: { type: String, default: 'bar' },
    labels: { type: Array, default: () => [] },
    datasets: { type: Array, default: () => [] },
});

const canvas = ref(null);
let chart;

const draw = () => {
    if (!canvas.value) return;
    chart?.destroy();
    chart = new Chart(canvas.value, {
        type: props.type,
        data: { labels: props.labels, datasets: props.datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        },
    });
};

onMounted(draw);
watch(() => [props.labels, props.datasets, props.type], draw, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div style="height: 280px">
        <canvas ref="canvas"></canvas>
    </div>
</template>
