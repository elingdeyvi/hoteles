<template>
    <div class="layout-px-spacing">
        <div class="row layout-top-spacing">
            <div class="col-xl-6 col-lg-6 col-sm-6 mx-auto layout-spacing">
                <div class="panel p-3">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-sm-12">
                            <div class="text-center titulo-dorado">
                                <img
                                    :src="defaultLogo"
                                    class="navbar-logo"
                                    alt="logo"
                                />
                                <h1>Registro de cliente</h1>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-sm-12">
                            <p class="signup-link register">¿Ya tienes una cuenta? <router-link to="/auth/login-boxed" class="forgot-pass-link">Acceso</router-link></p>
                            <form
                                class="mt-0"
                                ref="formComponent"
                                @submit.stop.prevent=""
                                autocomplete="off"
                            >
                                <div class="row">
                                    <div class="col-md-4 col-sm-12 col-4">
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
                                    <div class="col-md-4 col-sm-12 col-4">
                                        <div class="form-group">
                                            <label>Ap. Paterno</label>
                                            <input
                                                type="text"
                                                class="form-control mb-2"
                                                placeholder="Ap. Paterno"
                                                v-model="form.paternal_surname"
                                                :class="[is_entrada ? (!errors.paternal_surname ? 'is-valid' : 'is-invalid') : '']"
                                            />
                                            <div class="valid-feedback"></div>
                                            <div class="invalid-feedback">
                                                <li v-for="item in errors.paternal_surname">
                                                    {{ item }}
                                                </li>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12 col-4">
                                        <div class="form-group">
                                            <label>Ap. Materno</label>
                                            <input
                                                type="text"
                                                class="form-control mb-2"
                                                placeholder="Ap. Materno"
                                                v-model="form.maternal_surname"
                                                :class="[is_entrada ? (!errors.maternal_surname ? 'is-valid' : 'is-invalid') : '']"
                                            />
                                            <div class="valid-feedback"></div>
                                            <div class="invalid-feedback">
                                                <li v-for="item in errors.maternal_surname">
                                                    {{ item }}
                                                </li>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-12">
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
                                    <div class="col-md-6 col-sm-12 col-6">
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
                                    <div class="col-md-12">
                                        <div class="form-group mb-4">
                                            <label class="">Mas información para contactarte:</label>
                                            <textarea
                                                v-model="form.nota"
                                                class="form-control"
                                                placeholder="Agregar telefono, celular, dirección, ect."
                                                rows="3"
                                            ></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-4">
                                        <div class="form-group">
                                            <button
                                            type="button"
                                            class="btn btn-black w-100"
                                            @click="createUser"
                                            >
                                                Registrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="avisoModalPricipal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="z-index: 1100 !important">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Su cuenta aun no esta activada, le llegara un aviso por correo cuando este disponible para usar</h5>
                        <button type="button" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-dismiss="modal" data-bs-dismiss="modal"><i class="flaticon-cancel-12"></i> Cerrar</button>
                        <button type="button" class="btn btn-black" @click="login()">Regresar al inicio de sesion</button>
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
<script setup>
import { onMounted, ref } from "vue";
const defaultLogo = require("@/assets/images/logo.png");
import "@/assets/sass/elements/tooltip.scss";
import { useMeta } from "@/composables/use-meta";
import { useStateStore } from "@/store/StateStore";
import * as UserRepository from "@/repositories/UserRepository";
import axios from "axios";
import router from "@/router";

import "@/assets/sass/scrollspyNav.scss";

// loading
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/css/index.css';

useMeta({ title: 'Registro' });

//modales
let avisoModalPricipal = ref(null);

const editedIndex = ref(-1);
const formComponent = ref(null);
const errors = ref({});
const form = ref({
    name:null,
    paternal_surname:null,
    maternal_surname:null,
    email:null,
    password:null,
    password_confirmation:null,
    nota: null
});
const is_entrada = ref(false);
const isLoading = ref(true);
const storeState = useStateStore();

const createUser = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;
        const response = await UserRepository.registro(
            form.value
        );
        showMessage("Registrado correctamente");
        initializeCreate();
        avisoModalPricipal.show();
    } catch (error) {
        if (axios.isAxiosError(error))
            errors.value = error.response.data.errors;
        isLoading.value=false;
    }
};

const login = ()=>{
    avisoModalPricipal.hide();
    storeState.current_state.layout='auth';
    router.replace({
        name: "login-route",
    });
}

const initializeCreate = async() => {
    form.value = {
        name:null,
        paternal_surname:null,
        maternal_surname:null,
        email:null,
        password:null,
        password_confirmation:null,
        nota:null
    };
    errors.value = {};
    editedIndex.value = -1;
    isLoading.value=false;
    is_entrada.value = false;
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
    initializeCreate();
    avisoModalPricipal = new window.bootstrap.Modal(
        document.getElementById("avisoModalPricipal")
    );
});
</script>
