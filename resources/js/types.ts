export interface AuthUser {
  id: number | string
  name: string
  email: string
  email_verified_at?: string
  avatar_url?: string
  two_factor_confirmed_at?: string
}

export interface AuthPanel {
  id: string
  path: string
  name: string
}

export type Auth2FAMethod = 'totp' | 'email' | 'sms'

export interface AuthSocialProvider {
  name: string
  label: string
  icon?: string
  color?: string
}
