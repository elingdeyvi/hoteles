<template>
  <div class="layout-px-spacing">
    <teleport to="#breadcrumb">
      <ul class="navbar-nav flex-row">
        <li>
          <div class="page-header">
            <nav class="breadcrumb-one">
              <ol class="breadcrumb">
                <li class="breadcrumb-item">
                  <a href="javascript:;">{{ $t("admin.config_empresa.breadcrumb_parent") }}</a>
                </li>
                <li class="breadcrumb-item active">{{ $t("admin.config_empresa.breadcrumb_current") }}</li>
              </ol>
            </nav>
          </div>
        </li>
      </ul>
    </teleport>

    <div class="row layout-top-spacing">
      <div class="col-12">
        <div class="panel hotel-panel p-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
              <h4 class="mb-0">{{ $t("admin.config_empresa.title") }}</h4>
              <p class="text-muted small mb-0 mt-1">{{ $t("admin.config_empresa.subtitle") }}</p>
            </div>
            <div>
              <button type="button" class="btn btn-outline-secondary me-2" :disabled="loading || saving" @click="cargar">
                {{ $t("admin.config_empresa.reload") }}
              </button>
              <button type="button" class="btn btn-primary" :disabled="loading || saving" @click="guardar">
                {{ saving ? $t("admin.config_empresa.saving") : $t("admin.config_empresa.save") }}
              </button>
            </div>
          </div>

          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
          </div>

          <form v-else @submit.prevent="guardar">
            <div class="card mb-4">
              <div class="card-header">{{ $t("admin.config_empresa.section_general") }}</div>
              <div class="card-body row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_nombre_empresa") }} *</label>
                  <input v-model="form.nombre_empresa" class="form-control" maxlength="255" required />
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_nombre_corto") }} *</label>
                  <input v-model="form.nombre_corto" class="form-control" maxlength="50" required />
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_rfc") }}</label>
                  <input v-model="form.rfc" class="form-control" maxlength="13" />
                </div>
                <div class="col-12 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_nombre_largo") }} *</label>
                  <input v-model="form.nombre_largo" class="form-control" maxlength="500" required />
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_telefono") }}</label>
                  <input v-model="form.telefono" class="form-control" maxlength="20" />
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_email") }}</label>
                  <input v-model="form.email" type="email" class="form-control" maxlength="255" />
                </div>
                <div class="col-12 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_direccion") }}</label>
                  <input v-model="form.direccion" class="form-control" maxlength="500" />
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_codigo_postal") }}</label>
                  <input v-model="form.codigo_postal" class="form-control" maxlength="10" />
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_ciudad") }}</label>
                  <input v-model="form.ciudad" class="form-control" maxlength="100" />
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_estado") }}</label>
                  <input v-model="form.estado" class="form-control" maxlength="100" />
                </div>
                <div class="col-md-2 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_pais") }}</label>
                  <input v-model="form.pais" class="form-control" maxlength="100" />
                </div>
                <div class="col-12 mb-0">
                  <label class="form-label">{{ $t("admin.config_empresa.f_sitio_web") }}</label>
                  <input v-model="form.sitio_web" class="form-control" maxlength="255" placeholder="https://" />
                </div>
              </div>
            </div>

            <div class="card mb-4">
              <div class="card-header">{{ $t("admin.config_empresa.section_ticket_header") }}</div>
              <div class="card-body row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_ticket_nombre") }}</label>
                  <input v-model="form.ticket_encabezado_nombre" class="form-control" maxlength="255" />
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_ticket_rfc") }}</label>
                  <input v-model="form.ticket_encabezado_rfc" class="form-control" maxlength="13" />
                </div>
                <div class="col-12 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_ticket_domicilio") }}</label>
                  <input v-model="form.ticket_encabezado_domicilio" class="form-control" />
                </div>
                <div class="col-12 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_ticket_cp_ciudad") }}</label>
                  <input v-model="form.ticket_encabezado_cp_ciudad" class="form-control" maxlength="255" />
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_ticket_telefono") }}</label>
                  <input v-model="form.ticket_encabezado_telefono" class="form-control" maxlength="30" />
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_ticket_linea_folio") }}</label>
                  <input v-model="form.ticket_encabezado_linea_folio" class="form-control" maxlength="580" />
                  <div class="form-text">{{ $t("admin.config_empresa.ticket_linea_folio_help") }}</div>
                </div>
              </div>
            </div>

            <div class="card mb-4">
              <div class="card-header">{{ $t("admin.config_empresa.section_ticket_footer") }}</div>
              <div class="card-body">
                <label class="form-label">{{ $t("admin.config_empresa.f_ticket_footer") }}</label>
                <textarea v-model="form.ticket_footer_text" class="form-control" rows="12"></textarea>
                <div class="form-text">{{ $t("admin.config_empresa.ticket_footer_help") }}</div>
              </div>
            </div>

            <div class="card mb-4">
              <div class="card-header">{{ $t("admin.config_empresa.section_legal_optional") }}</div>
              <div class="card-body row">
                <div class="col-12 mb-3">
                  <label class="form-label">{{ $t("admin.config_empresa.f_terminos") }}</label>
                  <textarea v-model="form.terminos_condiciones" class="form-control" rows="4"></textarea>
                </div>
                <div class="col-12 mb-0">
                  <label class="form-label">{{ $t("admin.config_empresa.f_politica") }}</label>
                  <textarea v-model="form.politica_privacidad" class="form-control" rows="3"></textarea>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? $t("admin.config_empresa.saving") : $t("admin.config_empresa.save") }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import ApiService from "@/services/ApiService";
import { useConfiguracionEmpresaStore } from "@/store/ConfiguracionEmpresaStore";

const { t } = useI18n();
const configuracionStore = useConfiguracionEmpresaStore();

const loading = ref(true);
const saving = ref(false);

/** Campos que acepta PUT configuracion-empresa/update (debe coincidir con el controlador). */
const UPDATE_FIELDS = [
  "nombre_empresa",
  "nombre_corto",
  "nombre_largo",
  "rfc",
  "descripcion",
  "telefono",
  "email",
  "direccion",
  "codigo_postal",
  "ciudad",
  "estado",
  "pais",
  "sitio_web",
  "terminos_condiciones",
  "politica_privacidad",
  "bienvenida",
  "titulo_mensaje",
  "mensaje_descripcion",
  "mensaje_alerta",
  "horarios_lunes_viernes",
  "horarios_sabados",
  "horarios_domingos",
  "ticket_encabezado_nombre",
  "ticket_encabezado_rfc",
  "ticket_encabezado_domicilio",
  "ticket_encabezado_cp_ciudad",
  "ticket_encabezado_telefono",
  "ticket_encabezado_linea_folio",
  "ticket_footer_text",
];

const form = ref({
  nombre_empresa: "",
  nombre_corto: "",
  nombre_largo: "",
  rfc: "",
  descripcion: "",
  telefono: "",
  email: "",
  direccion: "",
  codigo_postal: "",
  ciudad: "",
  estado: "",
  pais: "México",
  sitio_web: "",
  terminos_condiciones: "",
  politica_privacidad: "",
  bienvenida: "",
  titulo_mensaje: "",
  mensaje_descripcion: "",
  mensaje_alerta: "",
  horarios_lunes_viernes: "",
  horarios_sabados: "",
  horarios_domingos: "",
  ticket_encabezado_nombre: "",
  ticket_encabezado_rfc: "",
  ticket_encabezado_domicilio: "",
  ticket_encabezado_cp_ciudad: "",
  ticket_encabezado_telefono: "",
  ticket_encabezado_linea_folio: "",
  ticket_footer_text: "",
});

function toast(msg, icon) {
  const mix = window.Swal?.mixin({ toast: true, position: "top-end", showConfirmButton: false, timer: 3500 });
  mix?.fire({ icon, title: msg });
}

function mapFromApi(data) {
  if (!data || typeof data !== "object") return;
  const next = { ...form.value };
  for (const key of UPDATE_FIELDS) {
    if (Object.prototype.hasOwnProperty.call(data, key)) {
      const v = data[key];
      next[key] = v === null || v === undefined ? "" : v;
    }
  }
  form.value = next;
}

function buildPayload() {
  const o = {};
  for (const key of UPDATE_FIELDS) {
    let v = form.value[key];
    if (key === "sitio_web" && typeof v === "string" && v.trim() === "") {
      v = null;
    }
    o[key] = v;
  }
  return o;
}

async function cargar() {
  loading.value = true;
  try {
    const res = await ApiService.get("configuracion-empresa");
    const row = res.data?.data;
    mapFromApi(row);
  } catch {
    toast(t("admin.config_empresa.load_error"), "error");
  } finally {
    loading.value = false;
  }
}

async function guardar() {
  saving.value = true;
  try {
    const payload = buildPayload();
    await configuracionStore.actualizarConfiguracion(payload);
    mapFromApi(configuracionStore.configuracion);
    toast(t("admin.config_empresa.saved"), "success");
  } catch (e) {
    const err = e.response?.data;
    const msg = err?.message || (err?.errors ? JSON.stringify(err.errors) : t("admin.config_empresa.save_error"));
    toast(typeof msg === "string" ? msg : t("admin.config_empresa.save_error"), "error");
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  cargar();
});
</script>
