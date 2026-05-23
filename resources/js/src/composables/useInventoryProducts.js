import { ref, computed } from "vue";
import * as InventoryProductRepository from "@/repositories/InventoryProductRepository";
import * as InventoryCatalogRepository from "@/repositories/InventoryCatalogRepository";

/**
 * Estado y llamadas API del inventario (productos almacén), sin duplicar lógica en la vista.
 */
export function useInventoryProducts() {
  const loading = ref(false);
  const catalogLoading = ref(false);
  const catalog = ref(null);
  const paginator = ref(null);
  const filters = ref({
    q: "",
    active: "",
    page: 1,
    per_page: 20,
  });

  const rows = computed(() => {
    const p = paginator.value;
    if (!p || !Array.isArray(p.data)) return [];
    return p.data;
  });

  async function loadCatalog() {
    catalogLoading.value = true;
    try {
      const body = await InventoryCatalogRepository.getCatalog();
      catalog.value = body?.data ?? null;
    } catch {
      catalog.value = null;
    } finally {
      catalogLoading.value = false;
    }
  }

  async function fetchProducts() {
    loading.value = true;
    try {
      const params = {
        page: filters.value.page,
        per_page: filters.value.per_page,
      };
      if (filters.value.q?.trim()) params.q = filters.value.q.trim();
      if (filters.value.active === "1" || filters.value.active === "0") {
        params.active = filters.value.active === "1";
      }
      const body = await InventoryProductRepository.getAll(params);
      paginator.value = body?.data ?? null;
    } finally {
      loading.value = false;
    }
  }

  function goToPage(n) {
    const last = paginator.value?.last_page || 1;
    filters.value.page = Math.min(Math.max(1, n), last);
    return fetchProducts();
  }

  function applySearch() {
    filters.value.page = 1;
    return fetchProducts();
  }

  return {
    loading,
    catalogLoading,
    catalog,
    paginator,
    filters,
    rows,
    loadCatalog,
    fetchProducts,
    goToPage,
    applySearch,
  };
}
