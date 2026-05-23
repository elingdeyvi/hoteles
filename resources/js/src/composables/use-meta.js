import { useHead } from '@vueuse/head';
import { unref, computed } from 'vue';
import i18n from '../i18n';

const metaSuffix = () => i18n.global.t('brand.meta_suffix');

export const usePageTitle = (pageTitle) =>
    useHead(
        computed(() => ({
            title: `${unref(pageTitle)} | ${metaSuffix()}`,
        }))
    );

export const useMeta = (data) => {
    return useHead({ ...data, title: `${data.title} | ${metaSuffix()}` });
};
