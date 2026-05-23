<template>
    <div class="layout-px-spacing">
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                <div class="panel p-3">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-sm-12">
                            <div class="text-center titulo-dorado">
                                <h3 class="pull-left">Usuarios</h3>
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
                            :data="obtener_users"
                            :columns="columns1"
                            :options="table_option1"
                        >
                            <template #email="item">
                                <p>{{ item.row.email }}</p>
                            </template>
                            <template #actions="item">
                                <a
                                    href="javascript:void(0);"
                                    title="cambio de contraseña"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    @click="changePw(item.row.id)"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-key" data-v-5522efca=""><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                                </a>
                                <a
                                    href="javascript:void(0);"
                                    title="Editar"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    @click="getUserId(item.row.id)"
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
                                    v-if="item.row.id !== 36"
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
                        <h4 class="modal-title" v-show="!is_password">
                            {{ editedIndex === -1 ? "Nuevo " : "Editar" }}
                        </h4>
                        <h4 class="modal-title" v-show="is_password">
                            Cambio de contraseña
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
                        >
                            <div class="row">
                                <div class="col-md-6 col-sm-12 col-6"  v-show="!is_password && !hasValidRole">
                                    <div class="form-group">
                                        <label>Rol</label>
                                        <multiselect
                                            v-model="form.role"
                                            mode="single"
                                            :close-on-select="true"
                                            :searchable="true"
                                            :options="optionsRoles"
                                            valueProp="name"
                                            label="name"
                                            trackBy="name"
                                            @update:model-value="changeRole"
                                            :class="[
                                                is_entrada
                                                    ? !errors.role
                                                        ? 'is-valid'
                                                        : 'is-invalid'
                                                    : '',
                                            ]"
                                            selected-label=""
                                            select-label=""
                                            deselect-label=""
                                            :showNoOptions="false"
                                            :showNoResults="false"
                                            placeholder="Seleccionar opción"
                                        >
                                        </multiselect>
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.role" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 col-12"  v-show="!is_password && hasValidRole">
                                    <div class="form-group">
                                        <label>Rol</label>
                                        <h4>{{ roleDisplayName }}</h4><br>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 col-12" v-show="is_password">
                                    <h6>{{ form.name }}</h6>
                                </div>
                                <div class="col-md-12 col-sm-12 col-12" v-show="!is_password">
                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input
                                            type="text"
                                            class="form-control mb-2"
                                            placeholder="Nombre"
                                            v-model="form.name"
                                            :class="[is_entrada ? (!errors.name ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.name" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-12 col-6" v-show="(editedIndex === -1 || is_password)">
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input
                                            v-model="form.password"
                                            type="password"
                                            class="form-control"
                                            placeholder="Contraseña"
                                            :class="[is_entrada ? (!errors.password ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.password" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-12 col-6" v-show="(editedIndex === -1 || is_password)">
                                    <div class="form-group">
                                        <label>Confirmar password</label>
                                        <input
                                            v-model="form.password_confirmation"
                                            type="password"
                                            class="form-control"
                                            placeholder="Contraseña"
                                            :class="[is_entrada ? (!errors.password_confirmation ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.password_confirmation" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-12 col-6"  v-show="!is_password">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input
                                            type="text"
                                            class="form-control mb-2"
                                            placeholder="Email"
                                            v-model="form.email"
                                            :class="[is_entrada ? (!errors.email ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.email" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 col-12" v-show="!is_password && showPropertyField">
                                    <div class="form-group">
                                        <label>Hoteles asignados</label>
                                        <multiselect
                                            v-model="form.property_ids"
                                            mode="tags"
                                            :close-on-select="false"
                                            :searchable="true"
                                            :options="propertyOptions"
                                            valueProp="id"
                                            label="name"
                                            trackBy="id"
                                            placeholder="Seleccionar hotel(es)"
                                        />
                                        <small class="text-muted">El usuario solo verá estos hoteles en el selector.</small>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 col-12" v-show="!is_password && showAreaField">
                                    <div class="form-group">
                                        <label>Área asignada</label>
                                        <select
                                            class="form-control mb-2"
                                            v-model.number="form.area_id"
                                        >
                                            <option :value="null">—</option>
                                            <option v-for="a in areaOptions" :key="a.id" :value="a.id">{{ a.name }}</option>
                                        </select>
                                        <small class="text-muted d-block">Obligatoria para rol Operador (escaneo 1=inicio / 2=fin en esa estación).</small>
                                        <div v-if="errors.area_id" class="invalid-feedback d-block">
                                            <span v-for="(item, index) in errors.area_id" :key="index">{{ item }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <div class="form-group">
                                        <button
                                        type="button"
                                        class="btn btn-black w-100"
                                        v-show="editedIndex === -1"
                                        @click="createUser"
                                        >
                                            Guardar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-black w-100"
                                            v-show="editedIndex !== -1 && !is_password"
                                            @click="updateUser"
                                        >
                                            Actualizar
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-black w-100"
                                            v-show="editedIndex !== -1 && is_password"
                                            @click="updatePw"
                                        >
                                            Actualizar Contraseña
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
                            ¿Estás seguro de eliminar esta horario?
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
                            @click="deleteUser()"
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
        :is-full-page="true"/>
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
// import { useAuthStore } from "@/store/AuthStore";
import * as UserRepository from "@/repositories/UserRepository";
import * as RoleRepository from "@/repositories/RoleRepository";
import HotelRepository from "@/repositories/HotelRepository";
// import { useStateStore } from "@/store/StateStore";
import axios from "axios";
// import { useRoute } from "vue-router";
import Multiselect from "@suadelabs/vue3-multiselect";
import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";

import "@/assets/sass/scrollspyNav.scss";

// loading
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/css/index.css';

useMeta({ title: 'Usuarios' });

// const store = useAuthStore();
// const storeState = useStateStore();
const obtener_users = ref([]);
// const route = useRoute();

//formulario crear y eliminar
let registerModal = ref(false);
let deleteModal = ref(false);
const is_entrada = ref(false);
const is_password = ref(false);
const editedIndex = ref(-1);
const formComponent = ref(null);
const errors = ref({});
const form = ref({
    name:null,
    email:null,
    password:null,
    password_confirmation:null,
    role:null,
    area_id: null,
    property_ids: [],
});

const propertyOptions = ref([]);
const areaOptions = ref([]);

const isLoading = ref(true);

//select2
const optionsRoles = ref([]);

// Computed para verificar si hay un rol válido seleccionado
const hasValidRole = computed(() => {
    if (!form.value.role) return false;
    if (typeof form.value.role === 'string' && form.value.role.trim() !== '') return true;
    if (typeof form.value.role === 'object' && form.value.role.name) return true;
    return false;
});

// Computed para obtener el nombre del rol a mostrar
const roleDisplayName = computed(() => {
    if (!form.value.role) return '';
    if (typeof form.value.role === 'object' && form.value.role.name) {
        return form.value.role.name;
    }
    if (typeof form.value.role === 'string') {
        return form.value.role;
    }
    return '';
});

/** Campo de áreas operativas no aplica en el módulo hotel. */
const showAreaField = computed(() => false);

const showPropertyField = computed(() => {
    const role = roleDisplayName.value;
    return role && role !== "Administrador";
});

const changeRole = (selectedRole)=>{
    // Si se selecciona un objeto, extraer solo el nombre para el backend
    if (selectedRole && typeof selectedRole === 'object' && selectedRole.name) {
        form.value.role = selectedRole.name;
    } else if (selectedRole && typeof selectedRole === 'string') {
        form.value.role = selectedRole;
    } else {
        form.value.role = null;
    }
};

//table 2
const columns1 = ref([
    "id",
    'name',
    'email',
    'role',
    'area_label',
    "actions",
]);

const table_option1 = ref({
    headings: {
        id: (h, row, index) => {
            return "Folio";
        },
        name: (h, row, index) => {
            return "Nombre";
        },
        area_label: (h, row, index) => {
            return "Área";
        },
        actions: (h, row, index) => {
            return "";
        },
    },
    perPage: 20,
    perPageValues: [20, 50, 100],
    skin: "table table-hover",
    columnsClasses: { actions: "actions text-center" },
        sortable: ["id", "name",  "email",  "role", "area_label"],
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


const createUser = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;
        // Asegurar que role sea solo el nombre (string) para el backend
        let roleValue = null;
        if (form.value.role) {
            if (typeof form.value.role === 'object' && form.value.role.name) {
                roleValue = form.value.role.name;
            } else if (typeof form.value.role === 'string') {
                roleValue = form.value.role;
            }
        }
        const formData = {
            ...form.value,
            role: roleValue,
            area_id: form.value.area_id != null && form.value.area_id !== "" ? form.value.area_id : null,
            property_ids: Array.isArray(form.value.property_ids) ? form.value.property_ids : [],
        };
        const response = await UserRepository.createUser(formData);
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

const close = () => {
    registerModal.hide();
    editedIndex.value = -1;
};

const deleteItem = (id) => {
    editedIndex.value = id;
    deleteModal.show();
};

const deleteUser = async () => {
    try {
        isLoading.value=true;
        const response = await UserRepository.deleteUser(
            editedIndex.value
        );
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

const initializeCreate = async() => {
    form.value = {
        name:null,
        email:null,
        password:null,
        password_confirmation:null,
        role:null,
        area_id: null,
        property_ids: [],
    };
    errors.value = {};
    editedIndex.value = -1;
    is_password.value=false;
};

const getUserAll = async () => {
    try {
        const response = await UserRepository.getusers();
        return response.data;
    } catch (error) {
        console.log(error);
    }
};

const getRoleAll = async () => {
    try {
        const response = await RoleRepository.getRoles();
        // getRoles ya retorna response.data, así que response es directamente el objeto con data
        const rolesData = response.data || response;
        // Filtrar valores null o undefined y asegurar que todos tengan la propiedad name
        optionsRoles.value = Array.isArray(rolesData) 
            ? rolesData.filter(role => role && role.name) 
            : [];
    } catch (error) {
        console.log(error);
        optionsRoles.value = [];
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

const updateUser = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;
        // Asegurar que role sea solo el nombre (string) para el backend
        let roleValue = null;
        if (form.value.role) {
            if (typeof form.value.role === 'object' && form.value.role.name) {
                roleValue = form.value.role.name;
            } else if (typeof form.value.role === 'string') {
                roleValue = form.value.role;
            }
        }
        const formData = {
            ...form.value,
            role: roleValue,
            area_id: form.value.area_id != null && form.value.area_id !== "" ? form.value.area_id : null,
            property_ids: Array.isArray(form.value.property_ids) ? form.value.property_ids : [],
        };
        const response = await UserRepository.updateUser(
            formData,
            editedIndex.value
        );
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

const updatePw = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;
        const response = await UserRepository.passwordUser(
            form.value,
            editedIndex.value
        );
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

const getUserId = async (id,entra_pw=false) => {
    try {
        const response = await UserRepository.getuserId(id);
        // Manejar la estructura de roles que viene del controlador
        // roles puede ser un array o una colección de Laravel
        let roleName = null;
        let roleObject = null;
        
        if (response.data.roles && Array.isArray(response.data.roles) && response.data.roles.length > 0) {
            // Si es un array, tomar el primer elemento
            roleName = response.data.roles[0]?.name || null;
        } else if (response.data.roles && typeof response.data.roles === 'object') {
            // Si es un objeto (colección de Laravel), intentar acceder al primer elemento
            const rolesArray = Object.values(response.data.roles);
            if (rolesArray.length > 0 && rolesArray[0]?.name) {
                roleName = rolesArray[0].name;
            }
        }
        
        // Buscar el objeto del rol completo para que el multiselect lo reconozca
        if (roleName && optionsRoles.value.length > 0) {
            roleObject = optionsRoles.value.find(r => r && r.name === roleName) || roleName;
        } else if (roleName) {
            roleObject = roleName;
        }
        
        form.value = {
            name: response.data.name || null,
            email: response.data.email || null,
            role: roleObject,
            password: null,
            password_confirmation: null,
            area_id: response.data.area_id != null ? response.data.area_id : null,
            property_ids: (response.data.properties || []).map((p) => p.id),
        };
        if(!entra_pw){
            is_password.value=false;
        }
        errors.value = {};
        editedIndex.value = id;
        registerModal.show();
    } catch (error) {
        console.log(error);
    }
};

const initialize = async () => {
    isLoading.value=true;
    const resp = await getUserAll();
    obtener_users.value = Array.isArray(resp)
        ? resp.map((u) => ({ ...u, area_label: u.area?.name || "—" }))
        : resp;
    table_option1.value = {
        headings: {
            id: (h, row, index) => {
                return "Folio";
            },
            name: (h, row, index) => {
                return "Nombre";
            },
            area_label: (h, row, index) => {
                return "Área";
            },
            actions: (h, row, index) => {
                return "";
            },
        },
        perPage: 100,
        perPageValues: [20, 50, 100],
        skin: "table table-hover",
        columnsClasses: { actions: "actions text-center" },
        sortable: ["id", "name",  "email",  "role", "area_label"],
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
    };
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

const changePw = (id)=>{
    is_password.value=true;
    getUserId(id,true);
}

onMounted(async () => {
    // const response = await obtener_user();
    // store.current_user.id = response.id;
    // store.current_user.name = response.name;
    // store.current_user.email = response.email;
    registerModal = new window.bootstrap.Modal(
        document.getElementById("registerModal")
    );
    deleteModal = new window.bootstrap.Modal(
        document.getElementById("deleteModal")
    );
    getRoleAll();
    try {
        const res = await HotelRepository.propertiesManage();
        propertyOptions.value = res.data || [];
    } catch (_) {
        propertyOptions.value = [];
    }
    initialize();
});
</script>
