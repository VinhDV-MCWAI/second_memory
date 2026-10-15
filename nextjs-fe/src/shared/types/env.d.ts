// Environment variables type definitions
declare global {
  namespace NodeJS {
    interface ProcessEnv {
      NEXT_PUBLIC_API_URL: string;
      NEXT_PUBLIC_APP_URL: string;
      /** Reverb app key (public); live notifications are off without it. */
      NEXT_PUBLIC_REVERB_APP_KEY?: string;
    }
  }
}

export {};
