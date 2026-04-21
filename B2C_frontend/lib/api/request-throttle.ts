/**
 * Request throttle/rate limiting utility
 * Prevents too-frequent API requests to the same endpoint
 */

export class ThrottleError extends Error {
  waitTime: number

  constructor(message: string, waitTime: number) {
    super(message)
    this.name = "ThrottleError"
    this.waitTime = waitTime
  }
}

type ThrottleConfig = {
  minInterval: number // Minimum milliseconds between requests
  maxAttempts?: number // Maximum number of requests in time window
  timeWindow?: number // Time window in milliseconds
}

const DEFAULT_CONFIG: ThrottleConfig = {
  minInterval: 1000, // 1 second minimum between requests
}

// Map to track last request time for each endpoint
const lastRequestTime = new Map<string, number>()
// Map to track request counts in time window
const requestCounts = new Map<string, { count: number; resetTime: number }>()

// Default throttle configurations for specific endpoints
const endpointConfigs: Record<string, ThrottleConfig> = {
  "/posts": {
    minInterval: 500, // 500ms between requests
    maxAttempts: 10,
    timeWindow: 60000, // 10 requests per minute
  },
  "/posts/search": {
    minInterval: 500,
    maxAttempts: 10,
    timeWindow: 60000,
  },
  "/notifications": {
    minInterval: 1000,
    maxAttempts: 20,
    timeWindow: 60000,
  },
  "/posts/*/comments": {
    minInterval: 500,
    maxAttempts: 15,
    timeWindow: 60000,
  },
  "/api/posts": {
    minInterval: 500,
    maxAttempts: 10,
    timeWindow: 60000,
  },
}

export function getEndpointConfig(endpoint: string): ThrottleConfig {
  // Check for exact match first
  if (endpointConfigs[endpoint]) {
    return endpointConfigs[endpoint]
  }

  // Check for pattern matches (e.g., /posts/123/comments -> /posts/*/comments)
  for (const [pattern, config] of Object.entries(endpointConfigs)) {
    if (pattern.includes("*")) {
      const regexPattern = pattern
        .replace(/\//g, "\\/")
        .replace(/\*/g, "[^\\/]+")
      if (new RegExp(`^${regexPattern}$`).test(endpoint)) {
        return config
      }
    }
  }

  return DEFAULT_CONFIG
}

export function checkThrottle(
  endpoint: string,
  config?: ThrottleConfig,
): { allowed: boolean; waitTime: number } {
  const throttleConfig = config ?? getEndpointConfig(endpoint)
  const now = Date.now()

  // Check minimum interval
  const lastTime = lastRequestTime.get(endpoint) ?? 0
  const timeSinceLastRequest = now - lastTime

  if (timeSinceLastRequest < throttleConfig.minInterval) {
    const waitTime = throttleConfig.minInterval - timeSinceLastRequest
    return { allowed: false, waitTime }
  }

  // Check max attempts in time window
  if (
    throttleConfig.maxAttempts !== undefined &&
    throttleConfig.timeWindow !== undefined
  ) {
    const tracker = requestCounts.get(endpoint)
    const shouldReset =
      !tracker || now >= tracker.resetTime

    if (shouldReset) {
      requestCounts.set(endpoint, {
        count: 1,
        resetTime: now + throttleConfig.timeWindow,
      })
    } else {
      if (tracker.count >= throttleConfig.maxAttempts) {
        const waitTime = tracker.resetTime - now
        return { allowed: false, waitTime }
      }
      tracker.count += 1
    }
  }

  lastRequestTime.set(endpoint, now)
  return { allowed: true, waitTime: 0 }
}

export async function throttleRequest<T>(
  endpoint: string,
  requestFn: () => Promise<T>,
  config?: ThrottleConfig,
): Promise<T> {
  const { allowed, waitTime } = checkThrottle(endpoint, config)

  if (!allowed) {
    throw new ThrottleError(
      `Request throttled. Please wait ${Math.ceil(waitTime / 1000)} second(s).`,
      waitTime,
    )
  }

  return requestFn()
}

export function resetThrottle(endpoint?: string): void {
  if (endpoint) {
    lastRequestTime.delete(endpoint)
    requestCounts.delete(endpoint)
  } else {
    lastRequestTime.clear()
    requestCounts.clear()
  }
}
