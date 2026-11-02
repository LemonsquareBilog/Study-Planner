export interface StoredSession {
  version: 1;
  token: string;
  refreshToken: string | null;
}

function validToken(value: unknown): value is string {
  return typeof value === 'string' && value.length > 0 && !/\s/.test(value);
}

/** Accept the previous unversioned format and plain tokens during migration. */
export function decodeSession(stored: string | null): StoredSession | null {
  if (!stored) return null;
  try {
    if (!stored.trimStart().startsWith('{')) {
      return validToken(stored) && !/^[\[\"]/.test(stored)
        ? { version: 1, token: stored, refreshToken: null } : null;
    }
    const value = JSON.parse(stored);
    if ((value.version !== undefined && value.version !== 1) || !validToken(value.token)
      || (value.refreshToken !== undefined && value.refreshToken !== null && !validToken(value.refreshToken))) return null;
    return { version: 1, token: value.token, refreshToken: value.refreshToken ?? null };
  } catch {
    return null;
  }
}
