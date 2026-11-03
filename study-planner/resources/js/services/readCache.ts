// Short-lived, memory-only data reuse for navigation. Authentication is never
// cached. A generation prevents old requests from repopulating invalidated data.
export class ReadCache {
  private entries = new Map<string, { value: unknown; expires: number }>();
  private pending = new Map<string, Promise<unknown>>();
  private generation = 0;
  private ttl: number;
  private capacity: number;
  private now: () => number;

  constructor(ttl = 30_000, capacity = 80, now = Date.now) {
    this.ttl = ttl;
    this.capacity = capacity;
    this.now = now;
  }

  clear() {
    this.generation++;
    this.entries.clear();
    this.pending.clear();
  }

  async get<T>(key: string, load: () => Promise<T>): Promise<T> {
    const cached = this.entries.get(key);
    if (cached && cached.expires > this.now()) {
      return structuredClone(cached.value) as T;
    }
    this.entries.delete(key);

    let request = this.pending.get(key) as Promise<T> | undefined;
    if (!request) {
      const generation = this.generation;
      request = Promise.resolve().then(load).then(value => {
        if (generation === this.generation) {
          if (this.entries.size >= this.capacity) {
            this.entries.delete(this.entries.keys().next().value!);
          }
          this.entries.set(key, { value: structuredClone(value), expires: this.now() + this.ttl });
        }
        return value;
      }).finally(() => {
        if (this.pending.get(key) === request) this.pending.delete(key);
      });
      this.pending.set(key, request);
    }

    // A view may edit its data locally; never let that edit mutate the cache
    // or another consumer waiting for the same response.
    return structuredClone(await request);
  }
}

export function readCacheKey(url: string, params: Record<string, unknown> = {}): string {
  const entries = Object.entries(params)
    .filter(([, value]) => value !== undefined && value !== null)
    .sort(([left], [right]) => left.localeCompare(right));
  return JSON.stringify([url, entries]);
}
