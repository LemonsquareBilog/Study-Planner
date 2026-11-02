export type HttpErrorCategory = 'cancelled' | 'timeout' | 'network' | 'authentication' | 'authorization' | 'validation' | 'not-found' | 'server-unavailable' | 'unknown';

export function classifyHttpError(error: any): HttpErrorCategory {
  if (!error) return 'unknown';
  if (error.code === 'ERR_CANCELED' || error.name === 'AbortError' || error.__CANCEL__) return 'cancelled';
  if (error.code === 'ECONNABORTED' || error.code === 'ETIMEDOUT') return 'timeout';
  const status = error.response?.status;
  if (status === 401) return 'authentication';
  if (status === 403) return 'authorization';
  if (status === 404) return 'not-found';
  if (status === 422) return 'validation';
  if (status >= 500) return 'server-unavailable';
  if (!error.response && (error.request || error.code === 'ERR_NETWORK')) return 'network';
  return 'unknown';
}

export function isBackendUnavailable(error: unknown): boolean {
  return ['network', 'timeout', 'server-unavailable'].includes(classifyHttpError(error));
}
