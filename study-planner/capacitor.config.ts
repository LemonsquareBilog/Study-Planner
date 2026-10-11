import type { CapacitorConfig } from '@capacitor/cli';
import { loadEnv } from 'vite';

const mobileEnv = loadEnv('mobile', process.cwd(), 'VITE_');
const usesHttpApi = mobileEnv.VITE_API_BASE_URL?.startsWith('http://') ?? false;

const config: CapacitorConfig = {
  appId: 'com.studyplanner.app',
  appName: 'Study Planner',
  webDir: 'www',
  server: {
    androidScheme: usesHttpApi ? 'http' : 'https',
    cleartext: usesHttpApi,
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#4f46e5',
      showSpinner: true,
      spinnerColor: '#ffffff',
    },
    StatusBar: {
      style: 'dark',
      backgroundColor: '#4f46e5',
    },
    Keyboard: {
      resize: 'body',
      style: 'dark',
      resizeOnFullScreen: true,
    },
    Haptics: {
      enabled: true,
    },
    LocalNotifications: {
      smallIcon: 'ic_stat_notification',
      iconColor: '#B33F62',
      sound: 'default',
    },
  },
};

export default config;
