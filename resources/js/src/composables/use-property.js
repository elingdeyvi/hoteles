import { ref } from 'vue';
import api from '@/services/ApiService';

const STORAGE_KEY = 'hotel_property_id';
const properties = ref([]);
const currentId = ref(localStorage.getItem(STORAGE_KEY) || '');
const loaded = ref(false);

export function useProperty() {
  async function loadProperties() {
    if (loaded.value && properties.value.length) {
      return properties.value;
    }
    try {
      const res = await api.get('/hotel/properties');
      properties.value = res.data?.data || [];
      loaded.value = true;
      if (!currentId.value && properties.value.length) {
        setProperty(properties.value[0].id);
      }
    } catch {
      properties.value = [];
    }
    return properties.value;
  }

  function setProperty(id) {
    currentId.value = String(id);
    localStorage.setItem(STORAGE_KEY, String(id));
    if (api.defaults.headers) {
      api.defaults.headers.common['X-Property-Id'] = String(id);
    }
  }

  function initFromStorage() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored && api.defaults.headers) {
      api.defaults.headers.common['X-Property-Id'] = stored;
      currentId.value = stored;
    }
  }

  const current = () => properties.value.find((p) => String(p.id) === String(currentId.value));

  return {
    properties,
    currentId,
    loadProperties,
    setProperty,
    initFromStorage,
    current,
  };
}
