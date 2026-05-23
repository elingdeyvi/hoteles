import { createI18n } from "vue-i18n";
import messages from "@intlify/vite-plugin-vue-i18n/messages";

export default createI18n({
    legacy: false,
    allowComposition: true,
    locale: "es",
    globalInjection: true,
    fallbackLocale: "es",
    messages,
});
