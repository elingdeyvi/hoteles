import ApiService from "@/services/ApiService";
import ApiServiceFile from "@/services/ApiServiceFile";

const baseUrl = "configuracion-empresa";

export const getConfiguracion = async () => {
  const response = await ApiService.get(`${baseUrl}`);
  return response.data;
};

export const getPublicConfiguracion = async () => {
  const response = await ApiService.get(`${baseUrl}/public`);
  return response.data;
};

export const updateConfiguracion = async (params) => {
  const response = await ApiService.put(`${baseUrl}/update`, params);
  return response.data;
};

export const uploadLogo = async (formData) => {
  const response = await ApiServiceFile.post(`${baseUrl}/upload-logo`, formData);
  return response.data;
};

export const uploadFavicon = async (formData) => {
  const response = await ApiServiceFile.post(`${baseUrl}/upload-favicon`, formData);
  return response.data;
};

export const deleteLogo = async () => {
  const response = await ApiService.delete(`${baseUrl}/delete-logo`);
  return response.data;
};

export const deleteFavicon = async () => {
  const response = await ApiService.delete(`${baseUrl}/delete-favicon`);
  return response.data;
};

export const uploadIconoMensaje = async (formData) => {
  const response = await ApiServiceFile.post(`${baseUrl}/upload-icono-mensaje`, formData);
  return response.data;
};

export const deleteIconoMensaje = async () => {
  const response = await ApiService.delete(`${baseUrl}/delete-icono-mensaje`);
  return response.data;
};
