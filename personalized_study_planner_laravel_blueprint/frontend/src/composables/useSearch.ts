import { ref, computed, watch, onUnmounted, type Ref, type ComputedRef } from 'vue'

export interface SearchOptions<T> {
  data: Ref<T[]>
  searchFields: (keyof T)[]
  debounceMs?: number
  minQueryLength?: number
  fuzzyThreshold?: number
  getSuggestions?: (query: string, data: T[]) => string[]
}

export interface SearchResult<T> {
  query: Ref<string>
  debouncedQuery: Ref<string>
  results: ComputedRef<T[]>
  suggestions: ComputedRef<string[]>
  isSearching: Ref<boolean>
  hasResults: ComputedRef<boolean>
  clear: () => void
}

function levenshteinDistance(a: string, b: string): number {
  const matrix: number[][] = []
  for (let i = 0; i <= b.length; i++) {
    matrix[i] = [i]
  }
  for (let j = 0; j <= a.length; j++) {
    matrix[0][j] = j
  }
  for (let i = 1; i <= b.length; i++) {
    for (let j = 1; j <= a.length; j++) {
      if (b.charAt(i - 1) === a.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1]
      } else {
        matrix[i][j] = Math.min(
          matrix[i - 1][j - 1] + 1,
          matrix[i][j - 1] + 1,
          matrix[i - 1][j] + 1
        )
      }
    }
  }
  return matrix[b.length][a.length]
}

function fuzzyMatch(query: string, text: string, threshold: number = 0.3): boolean {
  if (!query || !text) return false
  const q = query.toLowerCase().trim()
  const t = text.toLowerCase().trim()
  
  if (t.includes(q)) return true
  
  const words = t.split(/\s+/)
  for (const word of words) {
    if (word.length < 3) continue
    const distance = levenshteinDistance(q, word)
    const similarity = 1 - distance / Math.max(q.length, word.length)
    if (similarity >= threshold) return true
  }
  return false
}

function extractKeywords(text: string): string[] {
  return text
    .toLowerCase()
    .split(/[\s\-_.,;:()\[\]{}]+/)
    .filter(w => w.length >= 3)
}

export function useSearch<T extends Record<string, any>>(options: SearchOptions<T>): SearchResult<T> {
  const {
    data,
    searchFields,
    debounceMs = 150,
    minQueryLength = 1,
    fuzzyThreshold = 0.3,
    getSuggestions
  } = options

  const query = ref('')
  const debouncedQuery = ref('')
  const isSearching = ref(false)
  let debounceTimer: ReturnType<typeof setTimeout> | null = null

  const results = computed(() => {
    const q = debouncedQuery.value.trim().toLowerCase()
    if (!q || q.length < minQueryLength) return data.value

    return data.value.filter(item => {
      return searchFields.some(field => {
        const value = item[field]
        if (value == null) return false
        
        const text = String(value)
        if (text.toLowerCase().includes(q)) return true
        
        if (fuzzyThreshold > 0) {
          return fuzzyMatch(q, text, fuzzyThreshold)
        }
        return false
      })
    })
  })

  const suggestions = computed(() => {
    const q = debouncedQuery.value.trim().toLowerCase()
    if (!q || q.length < minQueryLength) return []
    
    if (getSuggestions) {
      return getSuggestions(q, data.value)
    }

    const suggestionSet = new Set<string>()
    const qWords = extractKeywords(q)
    
    for (const item of data.value) {
      for (const field of searchFields) {
        const value = item[field]
        if (value == null) continue
        const text = String(value)
        const words = extractKeywords(text)
        
        for (const word of words) {
          if (word.startsWith(q) || word.includes(q)) {
            suggestionSet.add(word)
          }
          for (const qWord of qWords) {
            const distance = levenshteinDistance(qWord, word)
            const similarity = 1 - distance / Math.max(qWord.length, word.length)
            if (similarity >= fuzzyThreshold && similarity < 1) {
              suggestionSet.add(word)
            }
          }
        }
      }
    }
    
    return Array.from(suggestionSet).slice(0, 8)
  })

  const hasResults = computed(() => results.value.length > 0)

  function debounceSearch(newQuery: string) {
    if (debounceTimer) clearTimeout(debounceTimer)
    isSearching.value = true
    
    debounceTimer = setTimeout(() => {
      debouncedQuery.value = newQuery
      isSearching.value = false
    }, debounceMs)
  }

  watch(query, (newQuery) => {
    debounceSearch(newQuery)
  })

  function clear() {
    query.value = ''
    debouncedQuery.value = ''
    if (debounceTimer) clearTimeout(debounceTimer)
  }

  onUnmounted(() => {
    if (debounceTimer) clearTimeout(debounceTimer)
  })

  return {
    query,
    debouncedQuery,
    results,
    suggestions,
    isSearching,
    hasResults,
    clear
  }
}

export function useServerSearch<T extends Record<string, any>>(
  searchFn: (query: string) => Promise<T[]>,
  options: { debounceMs?: number; minQueryLength?: number } = {}
) {
  const { debounceMs = 300, minQueryLength = 1 } = options
  
  const query = ref('')
  const debouncedQuery = ref('')
  const results = ref<T[]>([])
  const suggestions = ref<string[]>([])
  const isSearching = ref(false)
  const error = ref<Error | null>(null)
  let debounceTimer: ReturnType<typeof setTimeout> | null = null
  let abortController: AbortController | null = null

  async function performSearch(q: string) {
    if (abortController) abortController.abort()
    abortController = new AbortController()
    
    isSearching.value = true
    error.value = null
    
    try {
      const data = await searchFn(q)
      results.value = data
    } catch (err) {
      if (err instanceof Error && err.name !== 'AbortError') {
        error.value = err
        results.value = []
      }
    } finally {
      isSearching.value = false
    }
  }

  function debounceSearch(newQuery: string) {
    if (debounceTimer) clearTimeout(debounceTimer)
    
    debounceTimer = setTimeout(() => {
      debouncedQuery.value = newQuery
      if (newQuery.trim().length >= minQueryLength) {
        performSearch(newQuery.trim())
      } else {
        results.value = []
        suggestions.value = []
      }
    }, debounceMs)
  }

  watch(query, (newQuery) => {
    debounceSearch(newQuery)
  })

  function clear() {
    query.value = ''
    debouncedQuery.value = ''
    results.value = []
    suggestions.value = []
    error.value = null
    if (debounceTimer) clearTimeout(debounceTimer)
    if (abortController) abortController.abort()
  }

  onUnmounted(() => {
    if (debounceTimer) clearTimeout(debounceTimer)
    if (abortController) abortController.abort()
  })

  return {
    query,
    debouncedQuery,
    results: computed(() => results.value),
    suggestions: computed(() => suggestions.value),
    isSearching,
    error: computed(() => error.value),
    hasResults: computed(() => results.value.length > 0),
    clear,
    search: performSearch
  }
}

export function createSuggestionExtractor<T>(
  fields: (keyof T)[],
  maxSuggestions: number = 10
) {
  return (query: string, data: T[]): string[] => {
    const q = query.toLowerCase().trim()
    if (!q) return []
    
    const suggestions = new Set<string>()
    const qWords = extractKeywords(q)
    
    for (const item of data) {
      for (const field of fields) {
        const value = item[field]
        if (value == null) continue
        const text = String(value)
        const words = extractKeywords(text)
        
        for (const word of words) {
          if (word.startsWith(q) || word.includes(q)) {
            suggestions.add(word)
          }
        }
      }
    }
    
    return Array.from(suggestions).slice(0, maxSuggestions)
  }
}