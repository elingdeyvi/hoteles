import ApiService from "@/services/ApiService";

const baseUrl = "roles";

export const getRoles = async () => {
  const response = await ApiService.get(`${baseUrl}/all`);
  return response.data;
};

export const getPermissions = async () => {
  const response = await ApiService.get(`${baseUrl}/permissions`);
  return response.data;
};

export const updateRolePermissions = async (roleId, permissions) => {
  const response = await ApiService.put(`${baseUrl}/${roleId}/permissions`, {
    permissions: permissions
  });
  return response.data;
};
