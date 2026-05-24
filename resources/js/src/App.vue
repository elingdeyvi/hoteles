<template>
    <div :class="containerClasses">
        <component v-bind:is="layout"></component>
    </div>
</template>
<script setup>
    import { computed, onMounted } from "vue";

    import "./assets/sass/app.scss";
    import { applyDocumentFavicon } from "./composables/use-branding";

    import { useMeta } from "./composables/use-meta";
    import { useStore } from "vuex";
    import { useI18n } from "vue-i18n";

    const { t } = useI18n();
    useMeta({ title: t("brand.default_page_title") });

    const store = useStore();

    const layout = computed(() => {
        return store.getters.layout;
    });

    const containerClasses = computed(() => {
        const classes = [];
        if (store.state.layout_style) {
            classes.push(store.state.layout_style);
        }
        if (store.state.menu_style) {
            classes.push(store.state.menu_style);
        }
        return classes;
    });

    onMounted(() => {
        applyDocumentFavicon('/favicon.svg');
    });
</script>
<script>
    // layouts
    import appLayout from "./layouts/app-layout.vue";
    import authLayout from "./layouts/auth-layout.vue";

    export default {
        components: {
            app: appLayout,
            auth: authLayout,
        },
    };
</script>
