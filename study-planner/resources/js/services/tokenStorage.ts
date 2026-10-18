import { Capacitor, registerPlugin } from '@capacitor/core';

const nativeStorage = registerPlugin<{
  get(): Promise<{ value: string | null }>;
  set(options: { value: string }): Promise<void>;
  remove(): Promise<void>;
}>('AuthTokenStorage');

// Native tokens never fall back to browser storage when the secure plugin fails.
export const tokenStorage = {
  async get(): Promise<string | null> {
    if (!Capacitor.isNativePlatform()) {
      const stored = localStorage.getItem('token');
      if (stored && import.meta.env.MODE !== 'preview' && !stored.includes('cookie-session')) { localStorage.removeItem('token'); return null; }
      return stored;
    }
    const { value } = await nativeStorage.get();
    const legacy = localStorage.getItem('token');
    if (!value && legacy && !legacy.startsWith('local-token-')) {
      await nativeStorage.set({ value: legacy });
      localStorage.removeItem('token');
      return legacy;
    }
    localStorage.removeItem('token');
    return value;
  },
  async set(value: string) {
    if (Capacitor.isNativePlatform()) {
      await nativeStorage.set({ value });
      localStorage.removeItem('token');
    } else localStorage.setItem('token', value);
  },
  async remove() {
    localStorage.removeItem('token');
    if (Capacitor.isNativePlatform()) await nativeStorage.remove();
  },
};
