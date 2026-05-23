<template>
    <div class="form auth-boxed">
        <div class="form-container outer">
            <div class="form-form">
                <div class="form-form-wrap">
                    <div class="form-container">
                        <div class="form-content" id="login-content-pos">
                            <div class="row">
                                <div class="col-md-6">
                                    <h1 class="">Iniciar sesión</h1>
        
                                    <form novalidate class="text-start" ref="formComponent" @submit.stop.prevent="login" autocomplete="off">
                                        <div class="form">
                                            <div
                                                id="username-field"
                                                class="field-wrapper input form-group"
                                            >
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
                                                    class="feather feather-user"
                                                >
                                                    <path
                                                        d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                                                    ></path>
                                                    <circle
                                                        cx="12"
                                                        cy="7"
                                                        r="4"
                                                    ></circle>
                                                </svg>
                                                <input
                                                    type="text"
                                                    class="form-control-black"
                                                    placeholder="Correo"
                                                    v-model="form.email"
                                                    :class="[is_login ? (!errors.email ? 'is-valid' : 'is-invalid') : '']"
                                                />
                                                <div class="valid-feedback"></div>
                                                <div class="invalid-feedback">
                                                    <li v-for="(item, index) in errors.email" :key="index">
                                                        {{ item }}
                                                    </li>
                                                </div>
                                            </div>
        
                                            <div
                                                id="password-field"
                                                class="field-wrapper input mb-2 form-group"
                                            >
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
                                                    class="feather feather-lock"
                                                >
                                                    <rect
                                                        x="3"
                                                        y="11"
                                                        width="18"
                                                        height="11"
                                                        rx="2"
                                                        ry="2"
                                                    ></rect>
                                                    <path
                                                        d="M7 11V7a5 5 0 0 1 10 0v4"
                                                    ></path>
                                                </svg>
                                                <input
                                                    v-model="form.password"
                                                    :type="passwordField"
                                                    class="form-control-black"
                                                    placeholder="Contraseña"
                                                    :class="[is_login ? (!errors.password ? 'is-valid' : 'is-invalid') : '']"
                                                />
                                                <div class="valid-feedback"></div>
                                                <div class="invalid-feedback" >
                                                    <li v-for="(item, index) in errors.password" :key="index">
                                                        {{ item }}
                                                    </li>
                                                </div>
                                                <svg
                                                    @click="set_pwd_type"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="24"
                                                    height="24"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    id="toggle-password"
                                                    class="feather feather-eye"
                                                >
                                                    <path
                                                        d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                                    ></path>
                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="3"
                                                    ></circle>
                                                </svg>
                                            </div>
                                            <div
                                                class="d-sm-flex justify-content-between"
                                            >
                                                <div class="field-wrapper">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger-rounded"
                                                    >
                                                        Entrar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                </div>
                            </div>
                        </div>
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
                            Recuperar contraseña
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
                        <h6>Solo ingresa tu correo electrónico. Es normal que la olvidemos, por suerte puedes cambiarla.</h6>
                        <form
                            class="mt-0"
                            ref="formComponent2"
                            @submit.stop.prevent=""
                            autocomplete="off"
                        >
                            <div class="row">
                                <div class="col-md-12 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input
                                            type="text"
                                            class="form-control mb-2"
                                            placeholder="Correo"
                                            v-model="form2.email"
                                            :class="[is_entrada ? (!errors2.email ? 'is-valid' : 'is-invalid') : '']"
                                        />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors2.email" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <div class="form-group">
                                        <button
                                            type="button"
                                            class="btn btn-black w-100"
                                            @click="enviar_acceso"
                                        >
                                            Enviar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Aviso de recuperacion -->
        <div class="modal fade" id="avisoModalPricipal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="z-index: 1100 !important">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel">¡Revisa tu correo electrónico!</h4>
                        <button type="button" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Enviamos un enlace para que cambies tu contraseña o actives tu cuenta.</h6>
                        <h6>Recuerda revisar la bandeja de correo no deseado y de promociones.</h6>
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
import { ref, onMounted, computed, watch } from "vue";
import "@/assets/sass/authentication/auth-boxed.scss";
import * as AuthRepository from "@/repositories/AuthRepository";
import store from '../../store';
// import { useConfiguracionEmpresaStore } from "@/store/ConfiguracionEmpresaStore";
import router from "@/router";

// loading
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/css/index.css';
const passwordField = ref("password");
const set_pwd_type = () => {
    passwordField.value= passwordField.value==="password"?"text":"password";
};

//modales
let avisoModalPricipal = ref(null);
let registerModal = ref(false);

const formComponent = ref(null);
const is_login = ref(false);
const errors = ref({});
const form = ref({
    email: null,
    password: null,
});
const isLoading = ref(true);

const formComponent2 = ref(null);
const is_entrada = ref(false);
const errors2 = ref({});
const form2 = ref({
    email: null
});

// const configuracionEmpresaStore = useConfiguracionEmpresaStore();
const defaultLogo = require('@/assets/images/logo.png');

// Logo dinámico
const logoUrl = ref(defaultLogo);

// Computed para obtener el logo del store
// const getLogoUrl = computed(() => {
//     return configuracionEmpresaStore.getLogoUrl || defaultLogo;
// });

// Manejar error de carga del logo
const handleLogoError = () => {
    logoUrl.value = defaultLogo;
};

const login = async () => {
    is_login.value = true;
    try {
        const response = await AuthRepository.createTokens(form.value);
        if (!response.success) return (errors.value = response.error);
        errors.value ={
            email: null,
            password: null,
        }
        localStorage.setItem("token", response.token);
        store.commit('setLayout', 'app');
        // router.replace({
        //     name: "home-route-principal",
        // });
        window.location.href = window.location.origin + window.location.pathname + '?nocache=' + Date.now();
    } catch (error) {
        console.log(error);
    }
};

const recuperarpw = () => {
    is_entrada.value = true;
    errors2.value = {};
    form2.value.email=null;
    registerModal.show();
}

const enviar_acceso = async () => {
    try {
        is_login.value = true;
        isLoading.value=true;
        const response = await AuthRepository.changePw(form2.value);
        if (!response.success) return (errors2.value = response.error);
        registerModal.hide();
        avisoModalPricipal.show();
        isLoading.value=false;
    } catch (error) {
        console.log(error);
    }
};

onMounted(async ()=>{
    registerModal = new window.bootstrap.Modal(
        document.getElementById("registerModal")
    );
    avisoModalPricipal = new window.bootstrap.Modal(
        document.getElementById("avisoModalPricipal")
    );

    // Cargar configuración pública de la empresa para obtener el logo
    // try {
    //     await configuracionEmpresaStore.cargarConfiguracionPublica();
    //     // Actualizar el logo si se cargó correctamente
    //     if (configuracionEmpresaStore.getLogoUrl) {
    //         logoUrl.value = configuracionEmpresaStore.getLogoUrl;
    //     }
    // } catch (error) {
    //     console.error('Error cargando configuración pública de empresa:', error);
    //     // Mantener logo por defecto
    // }

    isLoading.value=false;
});
</script>

<style scoped>
.login-logo {
    max-width: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    margin: 0 auto 20px auto;
    display: block;
}
</style>
