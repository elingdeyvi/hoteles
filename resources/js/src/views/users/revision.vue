<template>
    <div class="layout-px-spacing">
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                <div class="panel p-3">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-sm-12">
                            <div class="text-center titulo-dorado">
                                <h1 class="pull-left">Nuevos usuarios de clientes</h1>
                                <button
                                    class="btn btn-black pull-right m-1"
                                    @click="refrescar"
                                >
                                    Refrescar
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
                                <div class="col-md-12 col-sm-12 col-12" v-show="is_password">
                                    <h6>Cliente: {{ form.name }}</h6>
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
                                            <li v-for="item in errors.name">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-12 col-6" v-show="editedIndex === -1 || is_password">
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
                                            <li v-for="item in errors.password">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-12 col-6" v-show="editedIndex === -1 || is_password">
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
                                            <li v-for="item in errors.password_confirmation">
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
                                            <li v-for="item in errors.email">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-12 col-6">
                                    <div class="form-group">
                                        <label>Autorización</label>
                                        <multiselect
                                            v-model="form.autorizado"
                                            mode="tags"
                                            :close-on-select="true"
                                            :searchable="true"
                                            :options="optionsAutorizado"
                                            :class="[
                                                is_entrada
                                                    ? !errors.autorizado
                                                        ? 'is-valid'
                                                        : 'is-invalid'
                                                    : '',
                                            ]"
                                            selected-label=""
                                            select-label=""
                                            deselect-label=""
                                            placeholder="Seleccione..."
                                            :showNoOptions="false"
                                            :showNoResults="false"
                                        >
                                        </multiselect>
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="item in errors.autorizado">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mb-4">
                                        <label class="">Mas información de contacto:</label>
                                        <textarea
                                            v-model="form.nota"
                                            class="form-control"
                                            placeholder="Agregar telefono, celular, dirección, ect."
                                            rows="3"
                                        ></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 col-12" v-show="is_cliente && !is_password">
                                    <div class="form-group mb-4">
                                        <button
                                            type="button"
                                            class="btn btn-success"
                                            @click="abrirClientes"
                                        >
                                            Elegir Cliente
                                        </button>
                                        <input
                                        type="hidden"
                                        :class="[
                                            is_entrada
                                                ? !errors.cliente_id
                                                    ? 'is-valid'
                                                    : 'is-invalid'
                                                : '',
                                        ]"
                                        v-model="form.cliente_id">
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="item in errors.cliente_id">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mr-4" v-show="is_cliente && !is_password">
                                    <h5>Cliente: {{ nombre_cliente }}</h5>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <div class="form-group">
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
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal cliente -->
        <div
            id="modal_index_cliente"
            class="modal fade"
            aria-labelledby="exampleModalLabel"
            aria-hidden="true"
            style="z-index: 1100 !important"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content mailbox-popup">
                    <div class="modal-header">
                        <h5 class="modal-title">Buscar cliente</h5>
                        <button
                            type="button"
                            data-dismiss="modal"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            class="btn-close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="w-100">
                            <input
                                v-model="search.search"
                                class="form-control w-100 product-search"
                                placeholder="Buscar..."
                                @keyup.enter="getClienteAll"
                            />
                        </div>
                        <div class="custom-table">
                            <v-client-table
                                :data="obtener_clientes"
                                :columns="columns_cliente"
                                :options="table_option_cliente"
                                ref="table_cliente"
                            >
                                <template #id="props">
                                    <button class="btn btn-black" @click="asignarCliente(props.row.id,props.row.razonSocial)">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-log-out"
                                        >
                                            <path
                                                d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                            ></path>
                                            <polyline
                                                points="16 17 21 12 16 7"
                                            ></polyline>
                                            <line
                                                x1="21"
                                                y1="12"
                                                x2="9"
                                                y2="12"
                                            ></line>
                                        </svg>
                                        &nbsp{{ props.row.id }}
                                    </button>
                                </template>
                            </v-client-table>
                        </div>
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
import { onMounted, ref } from "vue";
import "@/assets/sass/elements/tooltip.scss";
import { useMeta } from "@/composables/use-meta";
import { useAuthStore } from "@/store/AuthStore";
import * as UserRepository from "@/repositories/UserRepository";
import * as RoleRepository from "@/repositories/RoleRepository";
import * as ClienteRepository from "@/repositories/ClienteRepository";
import { useStateStore } from "@/store/StateStore";
import axios from "axios";
import { useRoute } from "vue-router";
import Multiselect from "@suadelabs/vue3-multiselect";
import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";

import "@/assets/sass/scrollspyNav.scss";

// loading
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/css/index.css';

useMeta({ title: 'Revisión' });

const store = useAuthStore();
const storeState = useStateStore();
const obtener_users = ref([]);
const route = useRoute();

//formulario crear y eliminar
let registerModal = ref(false);
let deleteModal = ref(false);
let modal_index_cliente = ref(null);
const is_entrada = ref(false);
const is_cliente = ref(false);
const is_password = ref(false);
const editedIndex = ref(-1);
const formComponent = ref(null);
const errors = ref({});
const form = ref({
    name:null,
    email:null,
    password:null,
    password_confirmation:null,
    role:"Cliente",
    cliente_id:null,
    autorizado:'no',
    nota:'',
});
const nombre_cliente = ref("Sin cliente");

const isLoading = ref(true);

//select2
const optionsRoles = ref([]);
const optionsAutorizado =  ref(['si','no']);

//table 2
const columns1 = ref([
    "id",
    'name',
    'email',
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
        actions: (h, row, index) => {
            return "";
        },
    },
    perPage: 20,
    perPageValues: [20, 50, 100],
    skin: "table table-hover",
    columnsClasses: { actions: "actions text-center" },
    sortable: ["id", "name",  "email",  "role"],
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
    },
    resizableColumns: true,
});

//table clientes
const search = ref({
    search: "",
});
const columns_cliente = ref(["id", "razonSocial", "email"]);
const table_option_cliente = ref({
    headings: {
        id: (h, row, index) => {
            return "Folio";
        },
        razonSocial: (h, row, index) => {
            return "Cliente";
        },
        count_exp: (h, row, index) => {
            return "Entrar";
        },
    },
    perPage: 20,
    perPageValues: [20, 50, 100],
    skin: "table table-hover",
    columnsClasses: { count_exp: "count_exp text-center" },
    sortable: ["id", "razonSocial", "email"],
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
    filterable: false,
});

//clientes index
const obtener_clientes = ref([]);

const abrirClientes = () => {
    modal_index_cliente.show();
};

const getClienteAll = async () => {
    try {
        isLoading.value = true;
        const response = await ClienteRepository.getAllSearch(search.value);
        obtener_clientes.value = response.data;
        isLoading.value = false;
    } catch (error) {
        console.log(error);
    }
};

// eventos de asignar cliente
const asignarCliente = (cliente_id,razonSocial) => {
    nombre_cliente.value=razonSocial;
    form.value.cliente_id=cliente_id;
    is_entrada.value = true;
    is_cliente.value = true;
    modal_index_cliente.hide();
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
        role:"Cliente",
        cliente_id:null,
        autorizado:'no',
        nota:null,
    };
    errors.value = {};
    editedIndex.value = -1;
    nombre_cliente.value = "Sin cliente";
    is_cliente.value=false;
    is_password.value=false;
};

const getUserAll = async () => {
    try {
        const response = await UserRepository.getRevision();
        return response.data;
    } catch (error) {
        console.log(error);
    }
};

const getRoleAll = async () => {
    try {
        const response = await RoleRepository.getRoles();
        optionsRoles.value= response.data;
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

const updateUser = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;
        const response = await UserRepository.updateUser(
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
        form.value = {
            name: response.data.name,
            email: response.data.email,
            role: response.data.roles['0'].name,
            cliente_id: response.data.cliente_id,
            password:null,
            password_confirmation:null,
            autorizado: response.data.autorizado,
        };
        if(!entra_pw){
            is_password.value=false;
        }
        is_cliente.value=false;
        nombre_cliente.value='Sin cliente';
        if(response.data.roles['0'].name == 'Cliente'){
            is_cliente.value=true;
            if(response.data.cliente!==null){
                nombre_cliente.value=response.data.cliente.razonSocial;
            }
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
    obtener_users.value = resp;
    table_option1.value = {
        headings: {
            id: (h, row, index) => {
                return "Folio";
            },
            name: (h, row, index) => {
                return "Nombre";
            },
            actions: (h, row, index) => {
                return "";
            },
        },
        perPage: 100,
        perPageValues: [20, 50, 100],
        skin: "table table-hover",
        columnsClasses: { actions: "actions text-center" },
        sortable: ["id", "name",  "email",  "role"],
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
    const response = await obtener_user();
    store.current_user.id = response.id;
    store.current_user.name = response.name;
    store.current_user.email = response.email;
    registerModal = new window.bootstrap.Modal(
        document.getElementById("registerModal")
    );
    deleteModal = new window.bootstrap.Modal(
        document.getElementById("deleteModal")
    );
    modal_index_cliente = new window.bootstrap.Modal(
        document.getElementById("modal_index_cliente")
    );
    getRoleAll();
    initialize();
});
</script>
