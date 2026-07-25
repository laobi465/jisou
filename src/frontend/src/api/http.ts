import axios, { AxiosError, type AxiosInstance } from 'axios';
import { ElMessage } from 'element-plus';
import { ErrorCode, type ApiResponse } from './types';

// Axios 实例
// baseURL 从环境变量读取，禁止硬编码
const baseURL = import.meta.env.VITE_API_BASE_URL;
if (!baseURL) {
  throw new Error('待接入：缺失 VITE_API_BASE_URL 环境变量');
}

const http: AxiosInstance = axios.create({
  baseURL: baseURL.replace(/\/$/, '') + '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// 请求拦截器：附加 token
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('jisou_access_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// 响应拦截器：统一错误处理
http.interceptors.response.use(
  (response) => {
    const body = response.data as ApiResponse;
    if (body.code === ErrorCode.OK) {
      return response;
    }
    // 业务错误
    ElMessage.error(body.message || '请求失败');
    if (body.code === ErrorCode.UNAUTHENTICATED || body.code === ErrorCode.TOKEN_INVALID) {
      // 待接入：M3 实现 token 刷新与登录跳转
      localStorage.removeItem('jisou_access_token');
    }
    return Promise.reject(new Error(body.message));
  },
  (error: AxiosError<ApiResponse>) => {
    const status = error.response?.status;
    const body = error.response?.data;
    if (status === 403) {
      ElMessage.error(body?.message || '权限不足');
    } else if (status && status >= 500) {
      ElMessage.error('服务异常，请稍后重试');
    } else if (error.code === 'ECONNABORTED') {
      ElMessage.error('请求超时');
    } else {
      ElMessage.error(body?.message || '网络异常');
    }
    return Promise.reject(error);
  }
);

export default http;
