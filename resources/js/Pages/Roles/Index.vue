<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ roles: Array, permissions: Array });
const selected = ref(props.roles[0]?.id || null);
const form = useForm({ permissions: props.roles[0]?.permissions?.map((item) => item.name) || [] });

const pick = (role) => {
    selected.value = role.id;
    form.permissions = role.permissions.map((item) => item.name);
};
const save = () => form.put(route('roles.update', selected.value));
</script>

<template>
    <Head title="Roles" />
    <AuthenticatedLayout>
        <template #header>Roles y permisos</template>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="list-group">
                    <button v-for="role in roles" :key="role.id" class="list-group-item list-group-item-action" :class="{ active: selected === role.id }" @click="pick(role)">{{ role.name }}</button>
                </div>
            </div>
            <form class="col-md-9 card card-body" @submit.prevent="save">
                <div class="row">
                    <div v-for="permission in permissions" :key="permission" class="col-md-6">
                        <div class="form-check">
                            <input :id="permission" v-model="form.permissions" class="form-check-input" type="checkbox" :value="permission" />
                            <label class="form-check-label" :for="permission">{{ permission }}</label>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" :disabled="!selected">Guardar permisos</button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
