<template>
    <div :class="containerClasses">
        <component v-bind:is="layout"></component>
    </div>
</template>
<script setup>
    import { computed } from "vue";

    import "./assets/sass/app.scss";

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
            // Si layout_style es "full" y menu_style es "vertical", usar "collapsible-vertical"
            if (store.state.layout_style === "full" && store.state.menu_style === "vertical") {
                classes.push("collapsible-vertical");
            } else {
                classes.push(store.state.menu_style);
            }
        }
        return classes;
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
