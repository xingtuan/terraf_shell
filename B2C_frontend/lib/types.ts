export interface Product {
  id: string
  slug: string
  name: string
  description: string
  categoryId: string
  categoryLabel: string
  image: string
  priceFrom: number
  currency: "KRW"
  priceLabel: string
  availability: string
  features: string[]
  featured: boolean
}

export interface ProductCategory {
  id: string
  label: string
  description: string
}

export interface MaterialSpec {
  id: string
  label: string
  value: string
  detail: string
  icon: "feather" | "shield" | "leaf" | "badge"
}

export interface B2BInquiry {
  name: string
  company: string
  email: string
  phone?: string
  country?: string
  application: string
  volume: string
  timeline?: string
  message: string
  locale: string
  sourcePage: string
}

export interface CommunityIdea {
  id: string
  title: string
  summary: string
  stage: string
  supportType: string
  focus: string
  image: string
  tags: string[]
}

export interface InquirySubmissionResult {
  success: boolean
  id: number
  reference: string
  status: string
}

export interface ApiPaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface CommunityProfile {
  bio?: string | null
  location?: string | null
  website?: string | null
  avatar_url?: string | null
}

export interface CommunityUser {
  id: number
  name: string
  username: string
  email?: string | null
  role?: string | null
  is_banned?: boolean
  avatar_url?: string | null
  profile?: CommunityProfile | null
  followers_count?: number
  following_count?: number
  posts_count?: number
  comments_count?: number
  is_following: boolean
  created_at?: string | null
  updated_at?: string | null
}

export interface CommunityCategory {
  id: number
  name: string
  slug: string
  description?: string | null
  is_active?: boolean
  sort_order?: number
  posts_count?: number
}

export interface CommunityTag {
  id: number
  name: string
  slug: string
  posts_count?: number
}

export interface CommunityPostImage {
  id: number
  url: string
  alt_text?: string | null
  sort_order: number
}

export interface CommunityPost {
  id: number
  user_id: number
  category_id?: number | null
  title: string
  slug: string
  content: string
  excerpt?: string | null
  status: string
  is_pinned: boolean
  is_featured: boolean
  comments_count: number
  likes_count: number
  favorites_count: number
  is_liked: boolean
  is_favorited: boolean
  user?: CommunityUser | null
  category?: CommunityCategory | null
  tags: CommunityTag[]
  images: CommunityPostImage[]
  can_edit: boolean
  can_delete: boolean
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface CommunityComment {
  id: number
  post_id: number
  parent_id?: number | null
  content: string
  status: string
  likes_count: number
  is_liked: boolean
  user?: CommunityUser | null
  replies: CommunityComment[]
  can_edit: boolean
  can_delete: boolean
  created_at?: string | null
  updated_at?: string | null
}

export interface AuthSessionPayload {
  token: string
  token_type: "Bearer"
  user: CommunityUser
}

export interface PostLikePayload {
  post_id: number
  likes_count: number
  is_liked: boolean
}

export interface PostFavoritePayload {
  post_id: number
  favorites_count: number
  is_favorited: boolean
}
