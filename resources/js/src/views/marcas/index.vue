<template>
    <div class="layout-px-spacing">
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                <div class="panel p-3">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-sm-12">
                            <div class="text-center titulo-dorado">
                                <h3 class="pull-left">Marcas de Productos</h3>
                                <button
                                    class="btn btn-black pull-right m-1"
                                    @click="refrescar"
                                >
                                    Refrescar
                                </button>
                                <button
                                    class="btn btn-black pull-right m-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#registerModal"
                                    @click="initializeCreate"
                                >
                                    Nuevo
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="custom-table">
                        <v-client-table
                            :data="marcas"
                            :columns="columns"
                            :options="table_option"
                        >
                            <template #logo="item">
                                <img 
                                    v-if="item.row.logo" 
                                    :src="getLogoUrl(item.row.logo)" 
                                    alt="Logo" 
                                    style="width: 50px; height: 50px; object-fit: contain;"
                                />
                                <span v-else class="text-muted">Sin logo</span>
                            </template>
                            <template #activo="item">
                                <span :class="item.row.activo ? 'badge bg-success' : 'badge bg-danger'">
                                    {{ item.row.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </template>
                            <template #actions="item">
                                <a
                                    href="javascript:void(0);"
                                    title="Editar"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    @click="getMarca(item.row.id)"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-edit-2"
                                    >
                                        <path
                                            d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"
                                        ></path>
                                    </svg>
                                </a>
                                <a
                                    href="javascript:void(0);"
                                    title="Eliminar"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    @click="deleteItem(item.row.id)"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-trash"
                                    >
                                        <polyline
                                            points="3 6 5 6 21 6"
                                        ></polyline>
                                        <path
                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                        ></path>
                                    </svg>
                                </a>
                            </template>
                        </v-client-table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Register Modal -->
        <div
            class="modal fade"
            id="registerModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="registerModalLabel"
            aria-hidden="true"
            v-show="true"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div
                        class="modal-header titulo-dorado"
                        id="registerModalLabel"
                    >
                        <h4 class="modal-title">
                            {{ editedIndex === -1 ? "Nueva " : "Editar " }}Marca
                        </h4>
                        <button
                            type="button"
                            data-dismiss="modal"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            class="btn-close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <form
                            class="mt-0"
                            ref="formComponent"
                            @submit.stop.prevent=""
                            enctype="multipart/form-data"
                        >
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Código *</label>
                                        <input
                                            type="text"
                                            class="form-control mb-2"
                                            placeholder="Código de la marca"
                                            v-model="form.codigo"
                                            :class="[is_entrada ? (!errors.codigo ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.codigo" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nombre *</label>
                                        <input
                                            type="text"
                                            class="form-control mb-2"
                                            placeholder="Nombre de la marca"
                                            v-model="form.nombre"
                                            :class="[is_entrada ? (!errors.nombre ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.nombre" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Estado</label>
                                        <select
                                            class="form-control mb-2"
                                            v-model="form.activo"
                                            :class="[is_entrada ? (!errors.activo ? 'is-valid' : 'is-invalid') : '']"
                                        >
                                            <option :value="true">Activo</option>
                                            <option :value="false">Inactivo</option>
                                        </select>
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.activo" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Logo</label>
                                        <input
                                            type="file"
                                            class="form-control mb-2"
                                            accept="image/*"
                                            @change="handleLogoChange"
                                            :class="[is_entrada ? (!errors.logo ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.logo" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                        <small class="form-text text-muted">
                                            Formatos permitidos: JPEG, PNG, JPG, GIF. Tamaño máximo: 2MB
                                        </small>
                                        <!-- Vista previa del logo -->
                                        <div v-if="logoPreview" class="mt-2">
                                            <img :src="logoPreview" alt="Vista previa" style="max-width: 200px; max-height: 200px; object-fit: contain;" />
                                        </div>
                                        <!-- Logo actual si está editando -->
                                        <div v-else-if="form.logo && editedIndex !== -1" class="mt-2">
                                            <img :src="getLogoUrl(form.logo)" alt="Logo actual" style="max-width: 200px; max-height: 200px; object-fit: contain;" />
                                            <p class="text-muted mt-1">Logo actual</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Descripción</label>
                                        <textarea
                                            class="form-control mb-2"
                                            placeholder="Descripción de la marca"
                                            v-model="form.descripcion"
                                            rows="3"
                                            :class="[is_entrada ? (!errors.descripcion ? 'is-valid' : 'is-invalid') : '']"
                                        ></textarea>
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.descripcion" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button
                                            type="button"
                                            class="btn btn-success w-100"
                                            v-show="editedIndex === -1"
                                            @click="createMarca"
                                        >
                                            Guardar
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-default w-100"
                                            v-show="editedIndex !== -1"
                                            @click="updateMarca"
                                        >
                                            Actualizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Eliminar Modal -->
        <div
            class="modal fade"
            id="deleteModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="exampleModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            ¿Estás seguro de eliminar esta marca?
                        </h5>
                        <button
                            type="button"
                            data-dismiss="modal"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            class="btn-close"
                        ></button>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn"
                            data-dismiss="modal"
                            data-bs-dismiss="modal"
                        >
                            <i class="flaticon-cancel-12"></i> Cancelar
                        </button>
                        <button
                            type="button"
                            class="btn btn-black"
                            @click="deleteMarca()"
                        >
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <loading
            v-model:active="isLoading"
            :can-cancel="false"
            :is-full-page="true"
        />
    </div>
</template>

<style>
.table3 .actions svg {
    padding: 2px;
}
</style>

<script setup>
import { onMounted, ref, computed } from "vue";
import "@/assets/sass/elements/tooltip.scss";
import { useMeta } from "@/composables/use-meta";
import { useAuthStore } from "@/store/AuthStore";
import * as UserRepository from "@/repositories/UserRepository";
import * as MarcaRepository from "@/repositories/MarcaRepository";
import { useStateStore } from "@/store/StateStore";
import axios from "axios";

import "@/assets/sass/scrollspyNav.scss";

// loading
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/css/index.css';

useMeta({ title: 'Marcas de Productos' });

// const store = useAuthStore();
const marcas = ref([]);

//formulario crear y eliminar
let registerModal = ref(false);
let deleteModal = ref(false);
const is_entrada = ref(false);
const editedIndex = ref(-1);
const formComponent = ref(null);
const errors = ref({});
const form = ref({
    codigo: '',
    nombre: '',
    descripcion: '',
    logo: null,
    activo: true,
});
const logoFile = ref(null);
const logoPreview = ref(null);

const isLoading = ref(true);

//table
const columns = ref([
    "id",
    "codigo",
    "nombre",
    "logo",
    "descripcion",
    "activo",
    "actions",
]);
const table_option = ref({
    headings: {
        id: () => {
            return "ID";
        },
        codigo: () => {
            return "Código";
        },
        nombre: () => {
            return "Nombre";
        },
        logo: () => {
            return "Logo";
        },
        descripcion: () => {
            return "Descripción";
        },
        activo: () => {
            return "Estado";
        },
        actions: () => {
            return "";
        },
    },
    perPage: 20,
    perPageValues: [20, 50, 100],
    skin: "table table-hover",
    columnsClasses: { actions: "actions text-center" },
    sortable: ["id", "codigo", "nombre"],
    sortIcon: {
        base: "sort-icon-none",
        up: "sort-icon-asc",
        down: "sort-icon-desc",
    },
    pagination: { nav: "scroll", chunk: 5 },
    texts: {
        count: "Mostrando {from} a {to} de {count}",
        filter: "",
        filterPlaceholder: "Buscar...",
        limit: "Resultados:",
        noResults:"No hay registros"
    },
    resizableColumns: true,
});

const getLogoUrl = (logo) => {
    if (!logo) return null;
    return `/storage/${logo}`;
};

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoFile.value = file;
        
        // Crear vista previa
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const createMarca = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;

        const formData = new FormData();
        formData.append('codigo', form.value.codigo);
        formData.append('nombre', form.value.nombre);
        formData.append('descripcion', form.value.descripcion || '');
        formData.append('activo', form.value.activo ? 1 : 0);
        
        if (logoFile.value) {
            formData.append('logo', logoFile.value);
        }

        const response = await MarcaRepository.createMarca(formData);
        showMessage("Guardado correctamente");
        initializeCreate();
        close();
        initialize();
    } catch (error) {
        if (axios.isAxiosError(error))
            errors.value = error.response.data.errors;
        isLoading.value=false;
    }
};

const updateMarca = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;

        const formData = new FormData();
        formData.append('codigo', form.value.codigo);
        formData.append('nombre', form.value.nombre);
        formData.append('descripcion', form.value.descripcion || '');
        formData.append('activo', form.value.activo ? 1 : 0);
        formData.append('_method', 'PUT');
        
        if (logoFile.value) {
            formData.append('logo', logoFile.value);
        }

        const response = await MarcaRepository.updateMarca(editedIndex.value, formData);
        showMessage("Actualizado correctamente");
        close();
        initializeCreate();
        initialize();
    } catch (error) {
        if (axios.isAxiosError(error))
            errors.value = error.response.data.errors;
        isLoading.value=false;
    }
};

const getMarca = async (id) => {
    try {
        const response = await MarcaRepository.getMarcaById(id);

        form.value = {
            codigo: response.data.codigo,
            nombre: response.data.nombre,
            descripcion: response.data.descripcion,
            logo: response.data.logo,
            activo: response.data.activo,
        };

        logoFile.value = null;
        logoPreview.value = null;

        errors.value = {};
        editedIndex.value = id;
        registerModal.show();
    } catch (error) {
        console.log(error);
    }
};

const close = () => {
    registerModal.hide();
    editedIndex.value = -1;
};

const deleteItem = (id) => {
    editedIndex.value = id;
    deleteModal.show();
};

const deleteMarca = async () => {
    try {
        isLoading.value=true;
        const response = await MarcaRepository.deleteMarca(editedIndex.value);
        showMessage("Eliminado correctamente");
        closeDelete();
        initialize();
    } catch (error) {
        if (axios.isAxiosError(error))
            errors.value = error.response.data.errors;
        isLoading.value=false;
        console.log(error);
    }
};

const closeDelete = () => {
    deleteModal.hide();
    initializeCreate();
};

const initializeCreate = () => {
    form.value = {
        codigo: '',
        nombre: '',
        descripcion: '',
        logo: null,
        activo: true,
    };
    logoFile.value = null;
    logoPreview.value = null;
    errors.value = {};
    editedIndex.value = -1;
};

const getMarcaAll = async () => {
    try {
        const response = await MarcaRepository.getAll();
        return response.data;
    } catch (error) {
        console.log(error);
    }
};

const obtener_user = async () => {
    try {
        const response = await UserRepository.getuser();
        return response;
    } catch (error) {
        console.log(error);
    }
};

const initialize = async () => {
    isLoading.value=true;
    const resp = await getMarcaAll();
    marcas.value = resp;
    is_entrada.value = false;
    isLoading.value=false;
};

const refrescar = () => {
    initialize();
    showMessage("Completado correctamente");
};

const showMessage = (msg = "", type = "success") => {
    const toast = window.Swal.mixin({
        toast: true,
        position: "top",
        showConfirmButton: false,
        timer: 3000,
    });
    toast.fire({
        icon: type,
        title: msg,
        padding: "10px 20px",
    });
};

onMounted(async () => {
    const response = await obtener_user();
    // store.current_user.id = response.id;
    // store.current_user.name = response.name;
    // store.current_user.email = response.email;
    registerModal = new window.bootstrap.Modal(
        document.getElementById("registerModal")
    );
    deleteModal = new window.bootstrap.Modal(
        document.getElementById("deleteModal")
    );
    initialize();
});
</script>

