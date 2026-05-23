import ApiService from "@/services/ApiService";

const baseUrl = "tokens";

export const createTokens = async (params) => {
  try {
    const response = await ApiService.post(`${baseUrl}/create`, params);
    return {
      token: response.data.data,
      success: true,
    };
  } catch (error) {
    return {
      error: error.response.data.errors,
      success: false,
    };
  }
};


/** Revoca el token Bearer actual (POST /tokens/logout, Sanctum). */
export const logoutCurrentToken = async () => {
  await ApiService.post(`${baseUrl}/logout`);
};

export const expireTokens = async (idToken) => {
  try {
    const response = await ApiService.delete(`${baseUrl}/${idToken}`);
    return {
      token: response.data.data,
      success: true,
    };
  } catch (error) {
    return {
      error: error.response.data.errors,
      success: false,
    };
  }
};

export const permissions = async () => {
  try {
    const response = await ApiService.get(`${baseUrl}/permissions`);
    return response.data || { all: false, permissions: [] };
  } catch (error) {
    console.error('Error fetching permissions:', error);
    return { all: false, permissions: [] };
  }
};

export const changePw = async (params) => {
  try {
    const response = await ApiService.post(`${baseUrl}/pw`, params);
    return {
      token: response.data.data,
      success: true,
    };
  } catch (error) {
    return {
      error: error.response.data.errors,
      success: false,
    };
  }
};

export const savePw = async (params) => {
    const response = await ApiService.post(`${baseUrl}/savepw`, params);
    return response.data.data;
};

export const getPwUuid = async (params) => {
  const response = await ApiService.post(`${baseUrl}/getpwuuid`, params);
  return response.data.data;
};