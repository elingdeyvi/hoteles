import axios from "axios";
const API_BASE_URI = import.meta.env.VITE_API_URL || '/api';
const instance = axios.create({
  baseURL: API_BASE_URI,
  timeout: 12000,
});
instance.defaults.headers.post["Content-Type"] = "application/json";
instance.interceptors.request.use(
  (config) => {
    const token = window.localStorage.getItem("token");
    if (config.headers) config.headers["Authorization"] = `Bearer ${token}`;
    return config;
  },
  (error) => Promise.reject(error)
);
export default instance;
