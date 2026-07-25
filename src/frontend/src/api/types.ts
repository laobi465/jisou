// 错误码枚举
// 与后端 app/common/ErrorCode.php 保持一致，详见 SPEC.md §2.3.3
export const ErrorCode = {
  OK: 0,
  UNAUTHENTICATED: 1001,
  TOKEN_INVALID: 1002,
  PERMISSION_DENIED: 1003,
  PARAM_MISSING: 2001,
  PARAM_FORMAT_INVALID: 2002,
  PARAM_OUT_OF_RANGE: 2003,
  RESOURCE_NOT_FOUND: 3001,
  RESOURCE_INVALID: 3002,
  RESOURCE_FAVORITED: 3003,
  QUERY_TOO_SHORT: 4001,
  SOURCE_NOT_ENABLED: 4002,
  PARSE_FAILED: 4003,
  PARSE_MODULE_OFF: 4004,
  PROVIDER_UNHEALTHY: 5001,
  PROVIDER_TIMEOUT: 5002,
  PROVIDER_RATE_LIMITED: 5003,
  CRAWL_TASK_NOT_FOUND: 6001,
  CRAWL_TASK_PAUSED: 6002,
  CRAWL_FAILED: 6003,
  DB_ERROR: 9001,
  CACHE_ERROR: 9002,
  SEARCH_ENGINE_ERROR: 9003,
  UNKNOWN_ERROR: 9999,
} as const;

export type ErrorCodeValue = (typeof ErrorCode)[keyof typeof ErrorCode];

// 统一响应体
export interface ApiResponse<T = unknown> {
  code: number;
  message: string;
  data: T;
}

// 资源元数据
export interface ResourceItem {
  hash: string;
  title: string;
  source: string;
  source_url: string;
  size_bytes: number | null;
  file_type: string | null;
  origin_url: string | null;
  extracted_at: string;
  status?: 'active' | 'invalid' | 'unchecked';
  last_checked?: string;
  file_count?: number;
}

// 搜索结果
export interface SearchResult {
  total: number;
  took_ms: number;
  sources_used: string[];
  sources_failed: string[];
  page: number;
  size: number;
  items: ResourceItem[];
}
