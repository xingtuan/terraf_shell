import { requestApi } from "@/lib/api/client"
import type {
  BaseLeadPayload,
  BusinessContactLeadPayload,
  CollaborationLeadPayload,
  InquiryLeadPayload,
  LeadFormValues,
  LeadSubmissionResult,
  LeadType,
  PartnershipInquiryLeadPayload,
  SampleRequestLeadPayload,
} from "@/lib/types"

type LeadApiResponse = {
  id: number
  reference: string
  status: string
  lead_type?: LeadType | null
  inquiry_type?: string | null
}

export type LeadFormField = keyof LeadFormValues

const leadFieldMap = {
  name: "name",
  email: "email",
  phone: "phone",
  country: "country",
  region: "region",
  message: "message",
  company_name: "companyName",
  organization_type: "organizationType",
  company_website: "companyWebsite",
  job_title: "jobTitle",
  interest_type: "interestType",
  application_type: "application",
  expected_use_case: "message",
  estimated_quantity: "volume",
  inquiry_type: "application",
  collaboration_type: "type",
  collaboration_goal: "collaborationGoal",
  project_stage: "projectStage",
  timeline: "timeline",
  material_interest: "materialInterest",
  quantity_estimate: "quantityEstimate",
  shipping_country: "shippingCountry",
  shipping_region: "shippingRegion",
  shipping_address: "shippingAddress",
  intended_use: "intendedUse",
} satisfies Partial<Record<string, LeadFormField>>

function normalizeLeadResult(data: LeadApiResponse): LeadSubmissionResult {
  return {
    success: true,
    id: data.id,
    reference: data.reference,
    status: data.status,
    lead_type: data.lead_type,
    inquiry_type: data.inquiry_type,
  }
}

function buildSourcePage(payload: { sourcePage: string; locale: string }) {
  return `${payload.sourcePage}:${payload.locale}`
}

function buildMetadata(payload: {
  locale: string
  metadata?: BaseLeadPayload["metadata"]
}) {
  return {
    locale: payload.locale,
    ...(payload.metadata ?? {}),
  }
}

function normalizeRequiredText(value: string) {
  return value.trim()
}

function normalizeOptionalText(value?: string | null) {
  const normalized = value?.trim()

  return normalized ? normalized : null
}

function buildBaseLeadBody(payload: BaseLeadPayload) {
  return {
    name: normalizeRequiredText(payload.name),
    company_name: normalizeRequiredText(payload.companyName),
    organization_type: normalizeOptionalText(payload.organizationType),
    email: normalizeRequiredText(payload.email),
    phone: normalizeOptionalText(payload.phone),
    country: normalizeOptionalText(payload.country),
    region: normalizeOptionalText(payload.region),
    company_website: normalizeOptionalText(payload.companyWebsite),
    job_title: normalizeOptionalText(payload.jobTitle),
    interest_type: normalizeOptionalText(payload.interestType),
    application_type: normalizeOptionalText(payload.applicationType),
    expected_use_case: normalizeOptionalText(payload.expectedUseCase),
    estimated_quantity: normalizeOptionalText(payload.estimatedQuantity),
    timeline: normalizeOptionalText(payload.timeline),
    inquiry_type: normalizeOptionalText(payload.applicationType),
    message: normalizeRequiredText(payload.message),
    source_page: buildSourcePage(payload),
    metadata: buildMetadata(payload),
  }
}

export async function submitInquiryLead(payload: InquiryLeadPayload) {
  const response = await requestApi<LeadApiResponse>("/inquiries", {
    method: "POST",
    body: {
      ...buildBaseLeadBody(payload),
      inquiry_type: payload.inquiryType,
    },
  })

  return normalizeLeadResult(response.data)
}

export async function submitBusinessContactLead(
  payload: BusinessContactLeadPayload,
) {
  const response = await requestApi<LeadApiResponse>("/business-contacts", {
    method: "POST",
    body: buildBaseLeadBody(payload),
  })

  return normalizeLeadResult(response.data)
}

export async function submitPartnershipInquiryLead(
  payload: PartnershipInquiryLeadPayload,
) {
  const response = await requestApi<LeadApiResponse>("/partnership-inquiries", {
    method: "POST",
    body: {
      ...buildBaseLeadBody(payload),
      organization_type: payload.organizationType,
      collaboration_type: payload.collaborationType,
      collaboration_goal: payload.collaborationGoal,
      project_stage: payload.projectStage || null,
      timeline: payload.timeline || null,
    },
  })

  return normalizeLeadResult(response.data)
}

export async function submitSampleRequestLead(payload: SampleRequestLeadPayload) {
  const response = await requestApi<LeadApiResponse>("/sample-requests", {
    method: "POST",
    body: {
      ...buildBaseLeadBody(payload),
      material_interest: payload.materialInterest,
      quantity_estimate: payload.quantityEstimate || null,
      shipping_country: payload.shippingCountry || null,
      shipping_region: payload.shippingRegion || null,
      shipping_address: payload.shippingAddress || null,
      intended_use: payload.intendedUse,
    },
  })

  return normalizeLeadResult(response.data)
}

export async function submitUniversityCollaborationLead(
  payload: CollaborationLeadPayload,
) {
  const response = await requestApi<LeadApiResponse>(
    "/university-collaborations",
    {
      method: "POST",
      body: {
        ...buildBaseLeadBody(payload),
        organization_type: payload.organizationType,
        collaboration_goal: payload.collaborationGoal,
        project_stage: payload.projectStage || null,
        timeline: payload.timeline || null,
      },
    },
  )

  return normalizeLeadResult(response.data)
}

export async function submitProductDevelopmentCollaborationLead(
  payload: CollaborationLeadPayload,
) {
  const response = await requestApi<LeadApiResponse>(
    "/product-development-collaborations",
    {
      method: "POST",
      body: {
        ...buildBaseLeadBody(payload),
        organization_type: payload.organizationType,
        collaboration_goal: payload.collaborationGoal,
        project_stage: payload.projectStage || null,
        timeline: payload.timeline || null,
      },
    },
  )

  return normalizeLeadResult(response.data)
}

function buildInquiryMessage(values: LeadFormValues) {
  return [
    values.message.trim(),
    values.volume.trim() ? `Estimated volume: ${values.volume.trim()}` : null,
    values.timeline.trim() ? `Target timeline: ${values.timeline.trim()}` : null,
  ]
    .filter(Boolean)
    .join("\n\n")
}

export async function submitLeadForm(values: LeadFormValues) {
  switch (values.type) {
    case "business_contact":
      return submitBusinessContactLead({
        ...values,
        companyName: values.companyName,
        interestType: values.interestType,
        applicationType: values.application,
        expectedUseCase: values.message,
        estimatedQuantity: values.volume,
        timeline: values.timeline,
        message: values.message.trim(),
      })
    case "partnership_inquiry":
      return submitPartnershipInquiryLead({
        ...values,
        companyName: values.companyName,
        organizationType: values.organizationType,
        interestType: values.interestType,
        applicationType: values.application,
        expectedUseCase: values.collaborationGoal.trim() || values.message,
        estimatedQuantity: values.volume,
        collaborationType: "partnership_inquiry",
        collaborationGoal: values.collaborationGoal.trim(),
        projectStage: values.projectStage.trim() || null,
        timeline: values.timeline.trim() || null,
        message: values.message.trim(),
      })
    case "sample_request":
      return submitSampleRequestLead({
        ...values,
        companyName: values.companyName,
        interestType: values.interestType,
        applicationType: values.application,
        expectedUseCase: values.intendedUse.trim() || values.message,
        estimatedQuantity:
          values.quantityEstimate.trim() || values.volume.trim() || null,
        timeline: values.timeline.trim() || null,
        materialInterest: values.materialInterest.trim(),
        quantityEstimate: values.quantityEstimate.trim() || null,
        shippingCountry: values.shippingCountry.trim() || null,
        shippingRegion: values.shippingRegion.trim() || null,
        shippingAddress: values.shippingAddress.trim() || null,
        intendedUse: values.intendedUse.trim(),
        message: values.message.trim(),
      })
    case "university_collaboration":
      return submitUniversityCollaborationLead({
        ...values,
        companyName: values.companyName,
        organizationType: values.organizationType,
        interestType: values.interestType,
        applicationType: values.application,
        expectedUseCase: values.collaborationGoal.trim() || values.message,
        estimatedQuantity: values.volume,
        collaborationGoal: values.collaborationGoal.trim(),
        projectStage: values.projectStage.trim() || null,
        timeline: values.timeline.trim() || null,
        message: values.message.trim(),
      })
    case "product_development_collaboration":
      return submitProductDevelopmentCollaborationLead({
        ...values,
        companyName: values.companyName,
        organizationType: values.organizationType,
        interestType: values.interestType,
        applicationType: values.application,
        expectedUseCase: values.collaborationGoal.trim() || values.message,
        estimatedQuantity: values.volume,
        collaborationGoal: values.collaborationGoal.trim(),
        projectStage: values.projectStage.trim() || null,
        timeline: values.timeline.trim() || null,
        message: values.message.trim(),
      })
    case "bulk_order":
    case "other":
    case "inquiry":
    default:
      return submitInquiryLead({
        ...values,
        companyName: values.companyName,
        interestType: values.interestType,
        applicationType: values.application,
        expectedUseCase: values.message,
        estimatedQuantity: values.volume,
        timeline: values.timeline,
        inquiryType:
          values.application.trim() || values.inquiryType.trim() || "General Inquiry",
        message: buildInquiryMessage(values),
        metadata: {
          application: values.application.trim() || null,
          volume: values.volume.trim() || null,
          timeline: values.timeline.trim() || null,
          ...(values.metadata ?? {}),
        },
      })
  }
}

export function mapLeadValidationErrors(
  errors?: Record<string, string[]>,
): Partial<Record<LeadFormField, string>> {
  if (!errors) {
    return {}
  }

  const nextErrors: Partial<Record<LeadFormField, string>> = {}

  for (const [field, messages] of Object.entries(errors)) {
    const mappedField = leadFieldMap[field as keyof typeof leadFieldMap] ?? null

    if (mappedField && messages[0]) {
      nextErrors[mappedField] = messages[0]
    }
  }

  return nextErrors
}
