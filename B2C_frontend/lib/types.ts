export type EntityId = number | string

export type JsonPrimitive = string | number | boolean | null
export type JsonValue = JsonPrimitive | JsonObject | JsonValue[]
export type JsonObject = {
  [key: string]: JsonValue
}

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

export interface ApiPaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface NotificationPaginationMeta extends ApiPaginationMeta {
  unread_count?: number
}

export interface PaginatedResult<T, TMeta = ApiPaginationMeta> {
  items: T[]
  meta: TMeta
}

export type MaterialSpecIcon = "feather" | "shield" | "leaf" | "badge"

export interface MaterialSpec {
  id: EntityId
  material_id?: number
  key?: string | null
  label: string
  value: string
  unit?: string | null
  detail?: string | null
  icon: MaterialSpecIcon
  status?: string | null
  sort_order?: number
  media_url?: string | null
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface MaterialStorySection {
  id: EntityId
  material_id?: number
  title: string
  subtitle?: string | null
  content: string
  highlight?: string | null
  status?: string | null
  sort_order?: number
  media_url?: string | null
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface MaterialApplication {
  id: EntityId
  material_id?: number
  title: string
  subtitle?: string | null
  description: string
  audience?: string | null
  cta_label?: string | null
  cta_url?: string | null
  status?: string | null
  sort_order?: number
  media_url?: string | null
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface MaterialSummary {
  id: number
  title: string
  slug: string
  headline?: string | null
  summary?: string | null
  story_overview?: string | null
  science_overview?: string | null
  status?: string | null
  is_featured: boolean
  sort_order?: number
  media_url?: string | null
  specs_count?: number
  story_sections_count?: number
  applications_count?: number
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface MaterialDetail extends MaterialSummary {
  specs: MaterialSpec[]
  story_sections: MaterialStorySection[]
  applications: MaterialApplication[]
}

export interface ArticleSummary {
  id: number
  title: string
  slug: string
  excerpt?: string | null
  content?: string | null
  category?: string | null
  status?: string | null
  sort_order?: number
  media_url?: string | null
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface ArticleDetail extends ArticleSummary {
  content: string
}

export type HomeSectionPayload = JsonObject & {
  variant?: string
  theme?: string
  material_slug?: string
  limit?: number
}

export interface HomeSection {
  id: number
  key: string
  title?: string | null
  subtitle?: string | null
  content?: string | null
  cta_label?: string | null
  cta_url?: string | null
  payload?: HomeSectionPayload | null
  status?: string | null
  sort_order?: number
  media_url?: string | null
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface HomepageContent {
  home_sections: HomeSection[]
  materials: MaterialSummary[]
  articles: ArticleSummary[]
}

export type LeadType =
  | "business_contact"
  | "partnership_inquiry"
  | "sample_request"
  | "university_collaboration"
  | "product_development_collaboration"

export type LeadFormType = LeadType | "inquiry"

export interface LeadContext {
  locale: string
  sourcePage: string
  metadata?: JsonObject
}

export interface BaseLeadPayload extends LeadContext {
  name: string
  companyName: string
  organizationType?: string | null
  email: string
  phone?: string | null
  country?: string | null
  region?: string | null
  companyWebsite?: string | null
  jobTitle?: string | null
  message: string
}

export interface InquiryLeadPayload extends BaseLeadPayload {
  inquiryType: string
}

export interface BusinessContactLeadPayload extends BaseLeadPayload {}

export interface PartnershipInquiryLeadPayload extends BaseLeadPayload {
  organizationType: string
  collaborationType: LeadType
  collaborationGoal: string
  projectStage?: string | null
  timeline?: string | null
}

export interface SampleRequestLeadPayload extends BaseLeadPayload {
  materialInterest: string
  quantityEstimate?: string | null
  shippingCountry?: string | null
  shippingRegion?: string | null
  shippingAddress?: string | null
  intendedUse: string
}

export interface CollaborationLeadPayload extends BaseLeadPayload {
  organizationType: string
  collaborationGoal: string
  projectStage?: string | null
  timeline?: string | null
}

export interface LeadFormValues extends LeadContext {
  type: LeadFormType
  name: string
  companyName: string
  organizationType: string
  email: string
  phone: string
  country: string
  region: string
  companyWebsite: string
  jobTitle: string
  inquiryType: string
  application: string
  volume: string
  timeline: string
  message: string
  collaborationGoal: string
  projectStage: string
  materialInterest: string
  quantityEstimate: string
  shippingCountry: string
  shippingRegion: string
  shippingAddress: string
  intendedUse: string
}

export interface LeadSubmissionResult {
  success: boolean
  id: number
  reference: string
  status: string
  lead_type?: LeadType | null
  inquiry_type?: string | null
}

export type B2BInquiry = InquiryLeadPayload
export type InquirySubmissionResult = LeadSubmissionResult

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

export interface CommunityProfile {
  bio?: string | null
  school_or_company?: string | null
  region?: string | null
  location?: string | null
  portfolio_url?: string | null
  website?: string | null
  open_to_collab?: boolean
  avatar_url?: string | null
}

export interface CommunityUser {
  id: number
  name: string
  username: string
  email?: string | null
  role?: string | null
  account_status?: string | null
  is_banned?: boolean
  is_restricted?: boolean
  email_verified?: boolean
  email_verified_at?: string | null
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
  preview_url?: string | null
  thumbnail_url?: string | null
  alt_text?: string | null
  kind?: string | null
  sort_order: number
}

export interface CommunityMedia {
  id: number
  source_type?: string | null
  media_type?: string | null
  kind?: string | null
  title?: string | null
  alt_text?: string | null
  original_name?: string | null
  file_name?: string | null
  extension?: string | null
  mime_type?: string | null
  size_bytes?: number | null
  url?: string | null
  preview_url?: string | null
  thumbnail_url?: string | null
  external_url?: string | null
  is_image?: boolean
  is_document?: boolean
  is_external?: boolean
  sort_order?: number
  metadata?: JsonObject | null
  created_at?: string | null
  updated_at?: string | null
}

export interface FundingCampaign {
  id: number
  status?: string | null
  support_enabled: boolean
  support_button_text?: string | null
  external_crowdfunding_url?: string | null
  target_amount?: number | null
  pledged_amount?: number | null
  backer_count?: number | null
  reward_description?: string | null
  campaign_start_at?: string | null
  campaign_end_at?: string | null
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
  engagement_score?: number
  trending_score?: number
  views_count?: number
  support_enabled?: boolean
  support_button_text?: string | null
  external_crowdfunding_url?: string | null
  campaign_status?: string | null
  target_amount?: number | null
  pledged_amount?: number | null
  backer_count?: number | null
  reward_description?: string | null
  campaign_start_at?: string | null
  campaign_end_at?: string | null
  funding_campaign?: FundingCampaign | null
  comments_count: number
  likes_count: number
  favorites_count: number
  is_liked: boolean
  is_favorited: boolean
  user?: CommunityUser | null
  category?: CommunityCategory | null
  tags: CommunityTag[]
  images: CommunityPostImage[]
  media?: CommunityMedia[]
  can_edit: boolean
  can_delete: boolean
  featured_at?: string | null
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface CommunityCommentPostSummary {
  id: number
  title: string
  slug: string
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
  post?: CommunityCommentPostSummary | null
  replies: CommunityComment[]
  can_edit: boolean
  can_delete: boolean
  created_at?: string | null
  updated_at?: string | null
}

export interface NotificationPostTarget {
  id: number
  title: string
  slug: string
  status?: string | null
}

export interface NotificationCommentTarget {
  id: number
  post_id: number
  content: string
  status?: string | null
}

export interface NotificationUserTarget {
  id: number
  name: string
  username: string
}

export type NotificationTargetSummary =
  | NotificationPostTarget
  | NotificationCommentTarget
  | NotificationUserTarget

export interface UserNotification {
  id: number
  type: string
  title?: string | null
  body?: string | null
  action_url?: string | null
  target_type?: string | null
  target_id?: number | null
  target?: NotificationTargetSummary | null
  actor?: CommunityUser | null
  data?: JsonObject
  is_read: boolean
  read_at?: string | null
  created_at?: string | null
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

export interface CommentLikePayload {
  comment_id: number
  likes_count: number
  is_liked: boolean
}

export interface PostFavoritePayload {
  post_id: number
  favorites_count: number
  is_favorited: boolean
}

export interface FollowStatePayload {
  user_id: number
  is_following: boolean
}

export interface ReportRecord {
  id: number
  reporter_id: number
  target_type: string
  target_id: number
  target?: NotificationPostTarget | NotificationCommentTarget | null
  reason: string
  description?: string | null
  status: string
  moderator_note?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface SearchResultShape {
  query: string
  posts: CommunityPost[]
  meta: ApiPaginationMeta
}
