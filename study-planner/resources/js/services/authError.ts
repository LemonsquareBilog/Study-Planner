export function authErrorMessage(error: any): string {
  if (/secure session storage/i.test(error.message || '')) return 'Unable to access secure session storage. Restart the application and try again.';
  if (error.code === 'ECONNABORTED' || error.code === 'ETIMEDOUT') return 'The server took too long to respond. Please try again.';
  if (!error.response) return 'Unable to reach the server. Check your internet connection and try again.';
  if (error.response.status === 429) return 'Too many sign-in attempts. Please wait a minute and try again.';
  if (error.response.status >= 500) return 'The server is temporarily unavailable. Please try again later.';
  if (error.response.status === 401) return 'Your session has expired. Please sign in again.';
  if (error.response.status === 404) return 'The authentication service is unavailable. Please contact your administrator.';
  return error.response.data?.message || 'Sign-in failed. Please try again.';
}
