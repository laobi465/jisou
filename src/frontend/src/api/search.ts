import http from './http';
import type { ApiResponse, SearchResult } from './types';

export interface SearchParams {
  q: string;
  sources?: string[];
  page?: number;
  size?: number;
  time_range?: string;
  min_size?: number;
  max_size?: number;
  status?: 'active' | 'invalid' | 'unchecked';
}

// 聚合搜索
export async function search(params: SearchParams): Promise<SearchResult> {
  const query: Record<string, string | number> = { q: params.q };
  if (params.sources && params.sources.length > 0) {
    query.sources = params.sources.join(',');
  }
  if (params.page) query.page = params.page;
  if (params.size) query.size = params.size;
  if (params.time_range) query.time_range = params.time_range;
  if (params.min_size !== undefined) query.min_size = params.min_size;
  if (params.max_size !== undefined) query.max_size = params.max_size;
  if (params.status) query.status = params.status;

  const { data } = await http.get<ApiResponse<SearchResult>>('/search', { params: query });
  return data.data;
}
