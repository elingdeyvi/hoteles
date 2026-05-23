<template>
    <div class="form full-form auth-cover">
        <div class="form-container">
            <div class="form-form">
                <div class="form-form-wrap">
                    <div class="form-container">
                        <div class="form-content">
                            <h1 class="">
                                Iniciar sesión
                            </h1>
                            <form novalidate class="text-start" ref="formComponent" @submit.stop.prevent="login">
                                <div class="form">
                                    <div id="username-field" class="field-wrapper input">
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
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input v-model="form.email" type="text" :class="[is_login ? (!errors.email ? 'is-valid' : 'is-invalid') : '']" placeholder="Usuario" />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback">
                                            <li v-for="(item, index) in errors.email" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>

                                    <div id="password-field" class="field-wrapper input mb-2">
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
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                        <input v-model="form.password" :type="passwordField" :class="[is_login ? (!errors.password ? 'is-valid' : 'is-invalid') : '']" placeholder="Contraseña" />
                                        <div class="valid-feedback"></div>
                                        <div class="invalid-feedback" >
                                            <li v-for="(item, index) in errors.password" :key="index">
                                                {{ item }}
                                            </li>
                                        </div>
                                    </div>
                                    <div class="d-sm-flex justify-content-between">
                                        <div class="field-wrapper toggle-pass d-flex align-items-center">
                                            <p class="d-inline-block">Mostrar contraseña</p>
                                            <label class="switch s-primary mx-2">
                                                <input @click="set_pwd_type" type="checkbox" class="custom-control-input" checked="" />
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                        <div class="field-wrapper">
                                            <button type="submit" class="btn btn-primary">Iniciar sesión</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <p class="terms-conditions">
                                Sistema de gestión hotelera —
                                <router-link to="/reservar">Reservar en línea</router-link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-image">
                <div class="l-image"></div>
            </div>
        </div>
        <loading
        v-model:active="isLoading"
        :can-cancel="false"
        :is-full-page="true"/>
    </div>
</template>

<script setup>
    import "../../assets/sass/authentication/auth.scss";
    import { ref, onMounted, computed, watch } from "vue";
    import * as AuthRepository from "@/repositories/AuthRepository";
    import { useMeta } from "../../composables/use-meta";
    import Loading from 'vue-loading-overlay';
    import 'vue-loading-overlay/dist/css/index.css';
    import store from '../../store';
    useMeta({ title: "Iniciar sesión" });

    const passwordField = ref("password");
    const form = ref({
        email: null,
        password: null,
    });
    const formComponent = ref(null);
    const is_login = ref(false);
    const errors = ref({});
    const isLoading = ref(true);

    const set_pwd_type = () => {
        passwordField.value= passwordField.value==="password"?"text":"password";
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
            // store.commit('setLayout', 'app');
            window.location.href = window.location.origin + window.location.pathname + '?nocache=' + Date.now();
        } catch (error) {
            console.log(error);
        }
    };

    onMounted(async ()=>{
        isLoading.value=false;
        console.log(import.meta.env.VITE_API_URL,'import.meta.env.VITE_API_URL');
    });
</script>
