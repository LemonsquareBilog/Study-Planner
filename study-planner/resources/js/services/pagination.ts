import type { PaginatedResponse } from '../types/index';

/** Compatibility for preview fixtures and servers returning the earlier array contract. */
export function paginateItems<T>(items: T[], page = 1): PaginatedResponse<T> {
  const current = Math.max(1, Math.trunc(page));
  const offset = (current - 1) * 20;
  const data = items.slice(offset, offset + 20);
  const last = Math.max(1, Math.ceil(items.length / 20));
  return { data, current_page: current, last_page: last, per_page: 20, total: items.length,
    from: data.length ? offset + 1 : 0, to: data.length ? offset + data.length : 0,
    first_page_url: '', last_page_url: '', links: [], path: '',
    next_page_url: current < last ? `?page=${current + 1}` : null,
    prev_page_url: current > 1 ? `?page=${current - 1}` : null };
}

export function normalizePage<T>(response: PaginatedResponse<T> | { data: T[] }, page = 1): PaginatedResponse<T> {
  return 'current_page' in response ? response : paginateItems(response.data, page);
}
