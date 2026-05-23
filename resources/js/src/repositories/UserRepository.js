import ApiService from "@/services/ApiService";

const baseUrl = "users";

export const getusers = async () => {
  const response = await ApiService.get(`${baseUrl}/all`);
  return response.data;
};

export const getuser = async () => {
  const response = await ApiService.get(`${baseUrl}`);
  return response.data;
};

export const getuserId = async (user) => {
  const response = await ApiService.get(`${baseUrl}/${user}`);
  return response.data;
};

export const registro = async (params) => {
  const response = await ApiService.post(`/registro`, params);
  return response.data;
};

export const createUser = async (params) => {
  const response = await ApiService.post(`${baseUrl}/create`, params);
  return response.data;
};

export const updateUser = async (params, user) => {
  const response = await ApiService.put(`${baseUrl}/${user}/edit`, params);
  return response.data;
};

export const deleteUser = async (user) => {
  const response = await ApiService.delete(`${baseUrl}/${user}`);
  return response.data;
};

export const passwordUser = async (params, user) => {
  const response = await ApiService.put(`${baseUrl}/${user}/password`, params);
  return response.data;
};

export const updatePerfil = async (params) => {
  const response = await ApiService.put(`${baseUrl}/perfil`, params);
  return response.data;
};

export const setConfigSucursal = async (sucursal) => {
  const response = await ApiService.get(`${baseUrl}/config/setconfig?sucursal_id=${sucursal}`);
  return response.data;
};


export const getConfigSucursal = async () => {
  const response = await ApiService.get(`${baseUrl}/config/sucursal`);
  return response.data;
};

export const getTerapistas = async () => {
  const response = await ApiService.get(`${baseUrl}/get/terapista`);
  return response.data;
};

export const getRevision = async () => {
  const response = await ApiService.get(`${baseUrl}/get/revision`);
  return response.data;
};

export const getClientePublico = async () => {
  const response = await ApiService.get(`${baseUrl}/cliente/publico`);
  return response.data;
};