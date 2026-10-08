import { afterAll, afterEach, beforeAll } from 'vitest';
import { server } from './server';

// jsdom has no ResizeObserver; Radix primitives (Select, Checkbox) measure with it
globalThis.ResizeObserver ??= class {
  observe() {}
  unobserve() {}
  disconnect() {}
};

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
