import axios from "axios";
const API_BASE_URI = import.meta.env.VITE_API_URL || '/api';
const instance = axios.create({
  baseURL: API_BASE_URI,
});
instance.defaults.headers.post["Content-Type"] = "multipart/form-data";
instance.defaults.headers.put["Content-Type"] = "multipart/form-data";
instance.interceptors.request.use(
  (config) => {
    const token = window.localStorage.getItem("token");
    if (config.headers) config.headers["Authorization"] = `Bearer ${token}`;
    return config;
  },
  (error) => Promise.reject(error)
);
export default instance;
