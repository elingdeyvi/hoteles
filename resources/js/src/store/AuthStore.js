import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const current_user =ref({
    id:'',
    name:'',
    email:'',
    cliente_id:'',
    razon_social:'',
  });

  return { current_user }
})
