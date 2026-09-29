import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Make Pusher available globally for laravel-echo
window.Pusher = Pusher

let echoInstance: Echo | null = null

export function initEcho(): Echo | null {
  if (echoInstance) return echoInstance
  
  const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY
  const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1'
  const pusherHost = import.meta.env.VITE_PUSHER_HOST
  const pusherPort = import.meta.env.VITE_PUSHER_PORT
  const pusherScheme = import.meta.env.VITE_PUSHER_SCHEME || 'https'
  
  if (!pusherKey) {
    console.warn('Pusher key not configured. Real-time features disabled.')
    return null
  }
  
  const options: Record<string, unknown> = {
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    forceTLS: pusherScheme === 'https',
    authEndpoint: '/api/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('access_token')}`,
      },
    },
  }
  
  if (pusherHost) {
    options.host = pusherHost
    options.port = pusherPort || (pusherScheme === 'https' ? 443 : 80)
    options.scheme = pusherScheme
  }
  
  echoInstance = new Echo(options)
  
  // Handle connection events
  echoInstance.connector.pusher.connection.bind('connected', () => {
    console.log('[Echo] Connected to Pusher')
  })
  
  echoInstance.connector.pusher.connection.bind('disconnected', () => {
    console.log('[Echo] Disconnected from Pusher')
  })
  
  echoInstance.connector.pusher.connection.bind('error', (err: Error) => {
    console.error('[Echo] Connection error:', err)
  })
  
  return echoInstance
}

export function getEcho(): Echo | null {
  return echoInstance
}

export function disconnectEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
}

// Channel helpers
export function joinUserChannel(userId: number) {
  if (!echoInstance) return null
  return echoInstance.private(`user.${userId}`)
}

export function joinAdminChannel() {
  if (!echoInstance) return null
  return echoInstance.private('admin.system')
}

export function joinStudySessionChannel(sessionId: number) {
  if (!echoInstance) return null
  return echoInstance.private(`study_session.${sessionId}`)
}

export function leaveChannel(channelName: string) {
  if (!echoInstance) return
  echoInstance.leave(channelName)
}

export function leaveUserChannel(userId: number) {
  leaveChannel(`user.${userId}`)
}

export function leaveAdminChannel() {
  leaveChannel('admin.system')
}

export function leaveStudySessionChannel(sessionId: number) {
  leaveChannel(`study_session.${sessionId}`)
}