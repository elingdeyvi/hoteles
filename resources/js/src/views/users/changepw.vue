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
                                <h1>Crea tu nueva contraseña</h1>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-sm-12">
                            <p class="signup-link register">Regresar al <router-link to="/auth/login-boxed" class="forgot-pass-link">Acceso</router-link></p>
                            <form
                                class="mt-0"
                                ref="formComponent"
                                @submit.stop.prevent=""
                                autocomplete="off"
                            >
                                <div class="row">
                                    <div class="col-md-6 col-sm-12 col-6">
                                        <div class="form-group">
                                            <label>Password</label>
                                            <input
                                                v-model="form.password"
                                                type="password"
                                                class="form-control"
                                                placeholder="Contraseña"
                                                autocomplete="off"
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
                                    <div class="col-md-12 mt-4">
                                        <div class="form-group">
                                            <button
                                                type="button"
                                                class="btn btn-black w-100"
                                                v-show="editedIndex !== -1"
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
import { useRoute } from "vue-router";
import * as UserRepository from "@/repositories/UserRepository";
import * as AuthRepository from "@/repositories/AuthRepository";
import axios from "axios";
import router from "@/router";

import "@/assets/sass/scrollspyNav.scss";

// loading
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/css/index.css';

useMeta({ title: 'Cambiar Contraseña' });

const editedIndex = ref(-1);
const formComponent = ref(null);
const errors = ref({});
const form = ref({
    uuid:null,
    password:null,
    password_confirmation:null
});
const is_entrada = ref(false);
const isLoading = ref(true);
const storeState = useStateStore();
const route = useRoute();

const login = ()=>{
    initializeCreate();
    storeState.current_state.layout='auth';
    router.replace({
        name: "login-route",
    });
}

const updatePw = async () => {
    try {
        isLoading.value=true;
        is_entrada.value = true;
        const response = await AuthRepository.savePw(
            form.value
        );
        showMessage("Listo cambiaste tu contraseña");
        login();
        isLoading.value=false;
    } catch (error) {
        if (axios.isAxiosError(error))
            errors.value = error.response.data.errors;
        isLoading.value=false;
    }
};

const initializeCreate = async() => {
    form.value = {
        uuid:null,
        password:null,
        password_confirmation:null
    };
    errors.value = {};
    editedIndex.value = -1;
    isLoading.value=false;
    is_entrada.value = false;
};

const obtener_user = async () => {
    try {
        const response = await AuthRepository.getPwUuid({uuid:route.params.uuid});
        return response;
    } catch (error) {
        console.log(error);
        login();
    }
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
    editedIndex.value = response.id;
    form.value.uuid = response.uuid;
    isLoading.value=false;
});
</script>
