import store from "./store";
import { $themeConfig } from "./theme.config";

/** Alinea códigos BCP-47 (es-MX, es_ES) con los de `countryList` (es, en, …). */
function normalizeLocaleCode(code) {
    if (code == null || code === "") return null;
    let c = String(code).trim().toLowerCase();
    if (c.includes("-")) c = c.split("-")[0];
    if (c.includes("_")) c = c.split("_")[0];
    return c || null;
}

export default {
    init() {
        // Cork / versiones antiguas guardaban "en" por fallback o plantilla; era el idioma por defecto erróneo.
        const enResetKey = "i18n_reset_accidental_en_v1";
        if (localStorage.getItem("i18n_locale") === "en" && !localStorage.getItem(enResetKey)) {
            localStorage.removeItem("i18n_locale");
            localStorage.setItem(enResetKey, "1");
        }

        // set default styles
        let val = localStorage.getItem("dark_mode"); // light, dark, system
        if (!val) {
            val = $themeConfig.theme;
        }
        store.commit("toggleDarkMode", val);

        val = localStorage.getItem("menu_style"); // vertical, collapsible-vertical, horizontal
        if (!val) {
            val = $themeConfig.navigation;
        }
        store.commit("toggleMenuStyle", val);

        val = localStorage.getItem("layout_style"); // full, boxed-layout, large-boxed-layout
        if (!val) {
            val = $themeConfig.layout;
        }
        store.commit("toggleLayoutStyle", val);

        const savedLocale = localStorage.getItem("i18n_locale"); // en, da, de, el, es, …
        if (!savedLocale) {
            const defaultCode = $themeConfig.lang;
            const item = store.state.countryList.find((i) => i.code === defaultCode);
            if (item) {
                this.toggleLanguage(item);
            }
        } else {
            this.toggleLanguage();
        }
    },

    toggleLanguage(item) {
        let lang = null;
        if (item) {
            lang = item;
        } else {
            // localStorage es la fuente de verdad persistida; el Vuex se reinicia en cada carga completa.
            const raw = localStorage.getItem("i18n_locale");
            let code = normalizeLocaleCode(raw);
            if (!code) {
                code = normalizeLocaleCode(store.state.locale);
            }
            if (code && raw !== code) {
                localStorage.setItem("i18n_locale", code);
            }

            const found = code ? store.state.countryList.find((d) => d.code === code) : null;
            if (found) {
                lang = found;
            }
        }

        if (!lang) {
            lang =
                store.state.countryList.find((d) => d.code === $themeConfig.lang) ||
                store.state.countryList.find((d) => d.code === "es");
        }

        store.commit("toggleLocale", lang.code);
        return lang;
    },

    toggleMode(mode) {
        if (!mode) {
            let val = localStorage.getItem("dark_mode"); //light|dark|system
            mode = val;
            if (!val) {
                mode = "light";
            }
        }
        store.commit("toggleDarkMode", mode || "light");
        return mode;
    },
};
