"use client"

import { useEffect, useState, useTransition } from "react"
import { useSearchParams } from "next/navigation"

import { ApiError, getErrorMessage } from "@/lib/api/client"
import {
  mapLeadValidationErrors,
  submitLeadForm,
} from "@/lib/api/leads"
import type { Locale, SiteMessages } from "@/lib/i18n"
import type { LeadFormType, LeadFormValues, LeadInterestType } from "@/lib/types"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"
import type {
  B2BFormContent,
  LeadCustomField,
  LeadFormFieldKey,
} from "@/lib/page-content"

type B2BInquiryFormSectionProps = {
  locale: Locale
  content: B2BFormContent
  common: SiteMessages["common"]
  id?: string
  sourcePage?: string
  defaultLeadType?: LeadFormType
}

type SubmissionState = {
  reference: string
  status: string
  id: number
} | null

type FieldErrors = Partial<Record<LeadFormFieldKey, string>>
type CustomFieldErrors = Record<string, string>

const leadFieldMaxLengths: Partial<Record<LeadFormFieldKey, number>> = {
  name: 100,
  companyName: 150,
  organizationType: 80,
  email: 255,
  phone: 40,
  country: 120,
  region: 120,
  companyWebsite: 2048,
  jobTitle: 120,
  application: 150,
  volume: 120,
  timeline: 120,
  message: 5000,
  collaborationGoal: 1000,
  projectStage: 120,
  materialInterest: 150,
  quantityEstimate: 120,
  shippingCountry: 120,
  shippingRegion: 120,
  shippingAddress: 500,
  intendedUse: 1000,
}

const leadFormFieldKeys = [
  "name",
  "companyName",
  "organizationType",
  "email",
  "phone",
  "country",
  "region",
  "companyWebsite",
  "jobTitle",
  "application",
  "volume",
  "timeline",
  "message",
  "collaborationGoal",
  "projectStage",
  "materialInterest",
  "quantityEstimate",
  "shippingCountry",
  "shippingRegion",
  "shippingAddress",
  "intendedUse",
] as const satisfies readonly LeadFormFieldKey[]

const textareaFields = new Set<LeadFormFieldKey>([
  "message",
  "collaborationGoal",
  "shippingAddress",
  "intendedUse",
])

const wideFields = new Set<LeadFormFieldKey>([
  "application",
  "message",
  "collaborationGoal",
  "projectStage",
  "materialInterest",
  "shippingAddress",
  "intendedUse",
])

const leadTypeDefinitions: Array<{
  id: LeadFormType
  interestType: LeadInterestType
}> = [
  {
    id: "sample_request",
    interestType: "sample_request",
  },
  {
    id: "inquiry",
    interestType: "pellet_supply",
  },
  {
    id: "product_development_collaboration",
    interestType: "product_development",
  },
  {
    id: "bulk_order",
    interestType: "bulk_order",
  },
  {
    id: "partnership_inquiry",
    interestType: "partnership",
  },
  {
    id: "other",
    interestType: "other",
  },
]

const leadTypeInterestFallback: Record<LeadFormType, LeadInterestType> = {
  business_contact: "other",
  partnership_inquiry: "partnership",
  sample_request: "sample_request",
  university_collaboration: "partnership",
  product_development_collaboration: "product_development",
  inquiry: "pellet_supply",
  bulk_order: "bulk_order",
  other: "other",
}

function isLeadFormType(value: string | null): value is LeadFormType {
  return Boolean(value && value in leadTypeInterestFallback)
}

function getInterestTypeForLeadType(type: LeadFormType): LeadInterestType {
  return (
    leadTypeDefinitions.find((option) => option.id === type)?.interestType ??
    leadTypeInterestFallback[type]
  )
}

function getLeadTypeForInterestType(interestType: LeadInterestType): LeadFormType {
  return (
    leadTypeDefinitions.find((option) => option.interestType === interestType)?.id ??
    "inquiry"
  )
}

function createInitialValues(
  locale: Locale,
  sourcePage: string,
  type: LeadFormType,
): LeadFormValues {
  return {
    type,
    interestType: getInterestTypeForLeadType(type),
    name: "",
    companyName: "",
    organizationType: "",
    email: "",
    phone: "",
    country: "",
    region: "",
    companyWebsite: "",
    jobTitle: "",
    inquiryType: "",
    application: "",
    volume: "",
    timeline: "",
    message: "",
    collaborationGoal: "",
    projectStage: "",
    materialInterest: "",
    quantityEstimate: "",
    shippingCountry: "",
    shippingRegion: "",
    shippingAddress: "",
    intendedUse: "",
    locale,
    sourcePage,
  }
}

function normalizeSearchValue(value: string | null) {
  const normalized = value?.trim()

  return normalized ? normalized : null
}

function formatValidationMessage(
  template: string,
  replacements: Record<string, string | number>,
) {
  return template.replace(/\{(\w+)\}/g, (match, key) =>
    replacements[key] === undefined ? match : String(replacements[key]),
  )
}

function getLeadFieldLabel(content: B2BFormContent, field: LeadFormFieldKey) {
  return content.fieldSettings[field]?.label ?? content.validation.defaultField
}

function getLeadFieldValue(values: LeadFormValues, field: LeadFormFieldKey) {
  return values[field]
}

function isCollaborationLeadType(type: LeadFormType) {
  return (
    type === "partnership_inquiry" ||
    type === "university_collaboration" ||
    type === "product_development_collaboration"
  )
}

function isFieldApplicable(type: LeadFormType, field: LeadFormFieldKey) {
  if (
    [
      "organizationType",
      "jobTitle",
      "companyWebsite",
    ].includes(field)
  ) {
    return type === "business_contact" || isCollaborationLeadType(type)
  }

  if (
    [
      "materialInterest",
      "quantityEstimate",
      "shippingCountry",
      "shippingRegion",
      "shippingAddress",
      "intendedUse",
    ].includes(field)
  ) {
    return type === "sample_request"
  }

  if (["collaborationGoal", "projectStage"].includes(field)) {
    return isCollaborationLeadType(type)
  }

  return true
}

function isBackendRequiredField(type: LeadFormType, field: LeadFormFieldKey) {
  if (["name", "companyName", "email", "message"].includes(field)) {
    return true
  }

  if (field === "organizationType" || field === "collaborationGoal") {
    return isCollaborationLeadType(type)
  }

  if (field === "materialInterest" || field === "intendedUse") {
    return type === "sample_request"
  }

  return false
}

function activeLeadFields(content: B2BFormContent, type: LeadFormType) {
  return leadFormFieldKeys
    .map((field) => content.fieldSettings[field])
    .filter((field) => field.visible || isBackendRequiredField(type, field.key))
    .filter((field) => isFieldApplicable(type, field.key))
    .sort((a, b) => a.sortOrder - b.sortOrder)
}

function isFieldRequired(
  content: B2BFormContent,
  type: LeadFormType,
  field: LeadFormFieldKey,
) {
  return (
    isBackendRequiredField(type, field) ||
    content.fieldSettings[field]?.required === true
  )
}

function requiredMessageForField(
  content: B2BFormContent,
  field: LeadFormFieldKey,
) {
  const validation = content.validation
  const messages: Partial<Record<LeadFormFieldKey, string>> = {
    name: validation.nameRequired,
    companyName: validation.companyRequired,
    email: validation.emailRequired,
    message: validation.messageRequired,
    application: validation.applicationRequired,
    organizationType: validation.organizationTypeRequired,
    collaborationGoal: validation.collaborationGoalRequired,
    materialInterest: validation.materialInterestRequired,
    intendedUse: validation.intendedUseRequired,
  }

  return (
    messages[field] ??
    formatValidationMessage(validation.required, {
      field: getLeadFieldLabel(content, field),
    })
  )
}

function validateMaxLength(
  errors: FieldErrors,
  field: LeadFormFieldKey,
  value: string,
  content: B2BFormContent,
) {
  const maxLength = leadFieldMaxLengths[field]

  if (!maxLength || value.trim().length <= maxLength) {
    return
  }

  errors[field] = formatValidationMessage(content.validation.max, {
    field: getLeadFieldLabel(content, field),
    max: maxLength,
  })
}

function validateLeadForm(
  values: LeadFormValues,
  content: B2BFormContent,
): { fields: FieldErrors; customFields: CustomFieldErrors } {
  const errors: FieldErrors = {}
  const customFieldErrors: CustomFieldErrors = {}
  const validation = content.validation
  const visibleFields = activeLeadFields(content, values.type)

  for (const field of visibleFields) {
    const value = getLeadFieldValue(values, field.key)

    if (isFieldRequired(content, values.type, field.key) && !value.trim()) {
      errors[field.key] = requiredMessageForField(content, field.key)
    }

    validateMaxLength(errors, field.key, value, content)
  }

  if (visibleFields.some((field) => field.key === "email")) {
    if (!values.email.trim()) {
      errors.email = validation.emailRequired
    } else if (!/\S+@\S+\.\S+/.test(values.email)) {
      errors.email = validation.emailInvalid
    }
  }

  if (
    visibleFields.some((field) => field.key === "companyWebsite") &&
    values.companyWebsite.trim()
  ) {
    if (!errors.companyWebsite) {
      try {
        new URL(values.companyWebsite)
      } catch {
        errors.companyWebsite = validation.urlInvalid
      }
    }
  }

  const customValues = getCustomFieldValues(values)

  for (const customField of content.customFields) {
    const value = customValues[customField.key]

    if (customField.required && isBlankCustomValue(value)) {
      customFieldErrors[customField.key] = formatValidationMessage(
        validation.required,
        {
          field: customField.label,
        },
      )
    }
  }

  return { fields: errors, customFields: customFieldErrors }
}

function getCustomFieldValues(values: LeadFormValues) {
  const customFields = values.metadata?.custom_fields

  return customFields &&
    typeof customFields === "object" &&
    !Array.isArray(customFields)
    ? customFields
    : {}
}

function isBlankCustomValue(value: unknown) {
  if (value === null || value === undefined) {
    return true
  }

  if (typeof value === "string") {
    return value.trim() === ""
  }

  if (Array.isArray(value)) {
    return value.length === 0
  }

  return false
}

function getCustomFieldValue(
  values: LeadFormValues,
  customField: LeadCustomField,
) {
  const value = getCustomFieldValues(values)[customField.key]

  if (customField.type === "checkbox") {
    if (customField.options.length > 0) {
      return Array.isArray(value) ? value.filter((item) => typeof item === "string") : []
    }

    return value === true
  }

  return typeof value === "string" ? value : ""
}

function customErrorFromApiKey(key: string) {
  const match = key.match(/^metadata\.custom_fields\.([^.]+)$/)

  return match?.[1] ?? null
}

export function B2BInquiryFormSection({
  locale,
  content,
  common,
  id = "inquiry",
  sourcePage = "b2b",
  defaultLeadType = "inquiry",
}: B2BInquiryFormSectionProps) {
  const searchParams = useSearchParams()
  const [isPending, startTransition] = useTransition()
  const [submission, setSubmission] = useState<SubmissionState>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})
  const [customFieldErrors, setCustomFieldErrors] = useState<CustomFieldErrors>({})
  const [values, setValues] = useState<LeadFormValues>(() =>
    createInitialValues(locale, sourcePage, defaultLeadType),
  )

  useEffect(() => {
    const leadTypeFromUrl = searchParams.get("leadType")
    const nextType = isLeadFormType(leadTypeFromUrl)
      ? leadTypeFromUrl
      : defaultLeadType
    const nextInterestType = getInterestTypeForLeadType(nextType)
    const productSlug = normalizeSearchValue(searchParams.get("product"))
    const productName = normalizeSearchValue(searchParams.get("productName"))
    const productCategory = normalizeSearchValue(searchParams.get("category"))

    setValues((currentValues) => {
      const {
        product_slug: _productSlug,
        product_name: _productName,
        product_category: _productCategory,
        ...existingMetadata
      } = currentValues.metadata ?? {}
      const nextMetadata = {
        ...existingMetadata,
        ...(productSlug ? { product_slug: productSlug } : {}),
        ...(productName ? { product_name: productName } : {}),
        ...(productCategory ? { product_category: productCategory } : {}),
      }

      return {
        ...currentValues,
        type: nextType,
        interestType: nextInterestType,
        metadata: Object.keys(nextMetadata).length > 0 ? nextMetadata : undefined,
        application:
          currentValues.application.trim() !== ""
            ? currentValues.application
            : productName || currentValues.application,
        materialInterest:
          currentValues.materialInterest.trim() !== ""
            ? currentValues.materialInterest
            : nextType === "sample_request"
              ? productName || ""
              : currentValues.materialInterest,
      }
    })
  }, [defaultLeadType, searchParams])

  const fields = content.fields
  const placeholders = content.placeholders
  const cmsLeadTypeOptions = content.interestOptionList
    ?.flatMap((option) => {
      if (
        !isLeadFormType(option.id) ||
        !(option.interestType in content.interestOptions)
      ) {
        return []
      }

      return [
        {
          id: option.id,
          interestType: option.interestType as LeadInterestType,
          label: option.label,
          description: option.description,
        },
      ]
    })
  const localizedLeadTypeOptions = cmsLeadTypeOptions?.length
    ? cmsLeadTypeOptions
    : leadTypeDefinitions.map((option) => ({
        ...option,
        ...(content.interestOptions[option.interestType] ?? {}),
      }))
  const panelCopy = content.panelCopy[values.interestType] ?? []
  const visibleFields = activeLeadFields(content, values.type)
  const visibleCustomFields = [...content.customFields].sort(
    (a, b) => a.sortOrder - b.sortOrder,
  )
  const productContext =
    typeof values.metadata?.product_name === "string"
      ? values.metadata.product_name
      : null

  function updateField(field: LeadFormFieldKey, value: string) {
    setValues((currentValues) => ({
      ...currentValues,
      [field]: value,
    }))
    setFieldErrors((currentErrors) => ({
      ...currentErrors,
      [field]: undefined,
    }))
  }

  function updateCustomField(key: string, value: string | string[] | boolean) {
    setValues((currentValues) => {
      const metadata = currentValues.metadata ?? {}
      const currentCustomFields =
        metadata.custom_fields &&
        typeof metadata.custom_fields === "object" &&
        !Array.isArray(metadata.custom_fields)
          ? metadata.custom_fields
          : {}

      return {
        ...currentValues,
        metadata: {
          ...metadata,
          custom_fields: {
            ...currentCustomFields,
            [key]: value,
          },
        },
      }
    })
    setCustomFieldErrors((currentErrors) => {
      const { [key]: _removed, ...remainingErrors } = currentErrors

      return remainingErrors
    })
  }

  function renderCoreField(field: LeadFormFieldKey) {
    const setting = content.fieldSettings[field]
    const error = fieldErrors[field]
    const required = isFieldRequired(content, values.type, field)
    const inputId = `${id}-${field}`
    const value = getLeadFieldValue(values, field)
    const label = setting.label
    const helper = setting.helper || content.helpers[field]
    const className = `space-y-2 ${wideFields.has(field) ? "sm:col-span-2" : ""}`

    return (
      <label key={field} className={className} htmlFor={inputId}>
        <span className="text-sm text-foreground">
          {label}
          {required ? <span aria-hidden="true"> *</span> : null}
        </span>
        {textareaFields.has(field) ? (
          <Textarea
            id={inputId}
            value={value}
            onChange={(event) => updateField(field, event.target.value)}
            placeholder={setting.placeholder}
            className={field === "message" ? "min-h-36" : "min-h-24"}
            aria-invalid={error ? true : undefined}
          />
        ) : (
          <Input
            id={inputId}
            value={value}
            onChange={(event) => updateField(field, event.target.value)}
            type={field === "email" ? "email" : field === "companyWebsite" ? "url" : "text"}
            placeholder={setting.placeholder}
            aria-invalid={error ? true : undefined}
          />
        )}
        {helper ? <p className="text-sm text-muted-foreground">{helper}</p> : null}
        {error ? <p className="text-sm text-destructive">{error}</p> : null}
      </label>
    )
  }

  function renderCustomField(customField: LeadCustomField) {
    const value = getCustomFieldValue(values, customField)
    const error = customFieldErrors[customField.key]
    const inputId = `${id}-custom-${customField.key}`

    if (customField.type === "textarea") {
      return (
        <label
          key={customField.key}
          className="space-y-2 sm:col-span-2"
          htmlFor={inputId}
        >
          <span className="text-sm text-foreground">
            {customField.label}
            {customField.required ? <span aria-hidden="true"> *</span> : null}
          </span>
          <Textarea
            id={inputId}
            value={typeof value === "string" ? value : ""}
            onChange={(event) =>
              updateCustomField(customField.key, event.target.value)
            }
            placeholder={customField.placeholder}
            className="min-h-24"
            aria-invalid={error ? true : undefined}
          />
          {customField.helper ? (
            <p className="text-sm text-muted-foreground">{customField.helper}</p>
          ) : null}
          {error ? <p className="text-sm text-destructive">{error}</p> : null}
        </label>
      )
    }

    if (customField.type === "select") {
      return (
        <label key={customField.key} className="space-y-2" htmlFor={inputId}>
          <span className="text-sm text-foreground">
            {customField.label}
            {customField.required ? <span aria-hidden="true"> *</span> : null}
          </span>
          <select
            id={inputId}
            value={typeof value === "string" ? value : ""}
            onChange={(event) =>
              updateCustomField(customField.key, event.target.value)
            }
            className="h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground outline-none transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            aria-invalid={error ? true : undefined}
          >
            <option value="">{customField.placeholder}</option>
            {customField.options.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          {customField.helper ? (
            <p className="text-sm text-muted-foreground">{customField.helper}</p>
          ) : null}
          {error ? <p className="text-sm text-destructive">{error}</p> : null}
        </label>
      )
    }

    if (customField.type === "checkbox") {
      const selectedValues = Array.isArray(value) ? value : []

      return (
        <fieldset key={customField.key} className="space-y-3 sm:col-span-2">
          <legend className="text-sm text-foreground">
            {customField.label}
            {customField.required ? <span aria-hidden="true"> *</span> : null}
          </legend>
          {customField.options.length > 0 ? (
            <div className="grid gap-3 sm:grid-cols-2">
              {customField.options.map((option) => {
                const checked = selectedValues.includes(option.value)

                return (
                  <label
                    key={option.value}
                    className="flex items-start gap-3 rounded-2xl border border-border/60 px-4 py-3 text-sm text-foreground"
                  >
                    <input
                      type="checkbox"
                      checked={checked}
                      onChange={(event) => {
                        const nextValue = event.target.checked
                          ? [...selectedValues, option.value]
                          : selectedValues.filter((item) => item !== option.value)

                        updateCustomField(customField.key, nextValue)
                      }}
                      className="mt-1"
                    />
                    <span>{option.label}</span>
                  </label>
                )
              })}
            </div>
          ) : (
            <label className="flex items-start gap-3 rounded-2xl border border-border/60 px-4 py-3 text-sm text-foreground">
              <input
                type="checkbox"
                checked={value === true}
                onChange={(event) =>
                  updateCustomField(customField.key, event.target.checked)
                }
                className="mt-1"
              />
              <span>{customField.placeholder || customField.label}</span>
            </label>
          )}
          {customField.helper ? (
            <p className="text-sm text-muted-foreground">{customField.helper}</p>
          ) : null}
          {error ? <p className="text-sm text-destructive">{error}</p> : null}
        </fieldset>
      )
    }

    return (
      <label key={customField.key} className="space-y-2" htmlFor={inputId}>
        <span className="text-sm text-foreground">
          {customField.label}
          {customField.required ? <span aria-hidden="true"> *</span> : null}
        </span>
        <Input
          id={inputId}
          value={typeof value === "string" ? value : ""}
          onChange={(event) =>
            updateCustomField(customField.key, event.target.value)
          }
          placeholder={customField.placeholder}
          aria-invalid={error ? true : undefined}
        />
        {customField.helper ? (
          <p className="text-sm text-muted-foreground">{customField.helper}</p>
        ) : null}
        {error ? <p className="text-sm text-destructive">{error}</p> : null}
      </label>
    )
  }

  return (
    <section id={id} className="bg-card py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-14 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="mb-5 font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
          <p className="text-lg leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        <div className="mb-8 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
          {localizedLeadTypeOptions.map((option) => {
            const isActive =
              option.id === values.type ||
              (option.interestType === values.interestType &&
                values.type === "business_contact")

            return (
              <button
                key={option.id}
                type="button"
                className={`rounded-2xl border px-5 py-4 text-left transition-colors ${
                  isActive
                    ? "border-primary/40 bg-primary/10"
                    : "border-border/60 bg-background"
                }`}
                onClick={() => {
                  setSubmission(null)
                  setErrorMessage(null)
                  setFieldErrors({})
                  setCustomFieldErrors({})
                  setValues((currentValues) => ({
                    ...currentValues,
                    type: option.id,
                    interestType: option.interestType,
                  }))
                }}
              >
                <p className="text-sm uppercase tracking-[0.16em] text-primary">
                  {option.label}
                </p>
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                  {option.description}
                </p>
              </button>
            )
          })}
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-[0.9fr_1.1fr]">
          <div className="rounded-3xl border border-border/60 bg-background p-8">
            {content.leftPanelEyebrow ? (
              <p className="mb-6 text-sm uppercase tracking-[0.18em] text-primary">
                {content.leftPanelEyebrow}
              </p>
            ) : null}
            <div className="space-y-4 text-muted-foreground">
              {panelCopy.map((line) => (
                <p key={line}>{line}</p>
              ))}
            </div>
            {productContext ? (
              <div className="mt-6 rounded-2xl border border-border/60 bg-card px-4 py-3 text-sm text-foreground">
                {content.productContextLabel}: {productContext}
              </div>
            ) : null}
            <p className="mt-8 text-sm text-muted-foreground">
              {content.disclaimer}
            </p>
          </div>

          <form
            className="rounded-3xl border border-border/60 bg-background p-8"
            onSubmit={(event) => {
              event.preventDefault()
              setSubmission(null)
              setErrorMessage(null)

              const nextErrors = validateLeadForm(values, content)
              setFieldErrors(nextErrors.fields)
              setCustomFieldErrors(nextErrors.customFields)

              if (
                Object.keys(nextErrors.fields).length > 0 ||
                Object.keys(nextErrors.customFields).length > 0
              ) {
                return
              }

              startTransition(() => {
                void submitLeadForm(values)
                  .then((result) => {
                    setSubmission({
                      reference: result.reference,
                      status: result.status,
                      id: result.id,
                    })
                    setFieldErrors({})
                    setCustomFieldErrors({})
                    setValues((currentValues) => ({
                      ...createInitialValues(locale, sourcePage, currentValues.type),
                    }))
                  })
                  .catch((error) => {
                    if (error instanceof ApiError) {
                      const mapped = mapLeadValidationErrors(error.errors)
                      const nextCustomFieldErrors: CustomFieldErrors = {}

                      for (const [key, messages] of Object.entries(
                        error.errors ?? {},
                      )) {
                        const customFieldKey = customErrorFromApiKey(key)

                        if (customFieldKey && messages[0]) {
                          nextCustomFieldErrors[customFieldKey] = messages[0]
                        }
                      }

                      if (Object.keys(mapped).length > 0) {
                        setFieldErrors(mapped as FieldErrors)
                        setCustomFieldErrors(nextCustomFieldErrors)
                        setErrorMessage(common.errors.validation)
                        return
                      }

                      if (Object.keys(nextCustomFieldErrors).length > 0) {
                        setCustomFieldErrors(nextCustomFieldErrors)
                        setErrorMessage(common.errors.validation)
                        return
                      }
                    }
                    setErrorMessage(getErrorMessage(error))
                  })
              })
            }}
          >
            <div className="mb-6">
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {content.groups.contact}
              </p>
            </div>
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              {visibleFields.map((field) => renderCoreField(field.key))}
              {visibleCustomFields.map((customField) =>
                renderCustomField(customField),
              )}
            </div>

            {false ? (
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.name}</span>
                <Input
                  value={values.name}
                  onChange={(event) => updateField("name", event.target.value)}
                  placeholder={placeholders.name}
                />
                {fieldErrors.name ? (
                  <p className="text-sm text-destructive">{fieldErrors.name}</p>
                ) : null}
              </label>

              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.company}</span>
                <Input
                  value={values.companyName}
                  onChange={(event) =>
                    updateField("companyName", event.target.value)
                  }
                  placeholder={placeholders.company}
                />
                {fieldErrors.companyName ? (
                  <p className="text-sm text-destructive">
                    {fieldErrors.companyName}
                  </p>
                ) : null}
              </label>

              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.email}</span>
                <Input
                  value={values.email}
                  onChange={(event) => updateField("email", event.target.value)}
                  type="email"
                  placeholder={placeholders.email}
                />
                {fieldErrors.email ? (
                  <p className="text-sm text-destructive">{fieldErrors.email}</p>
                ) : null}
              </label>

              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.phone}</span>
                <Input
                  value={values.phone}
                  onChange={(event) => updateField("phone", event.target.value)}
                  placeholder={placeholders.phone}
                />
                {fieldErrors.phone ? (
                  <p className="text-sm text-destructive">{fieldErrors.phone}</p>
                ) : null}
              </label>

              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.country}</span>
                <Input
                  value={values.country}
                  onChange={(event) => updateField("country", event.target.value)}
                  placeholder={placeholders.country}
                />
                {fieldErrors.country ? (
                  <p className="text-sm text-destructive">{fieldErrors.country}</p>
                ) : null}
              </label>

              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.region}</span>
                <Input
                  value={values.region}
                  onChange={(event) => updateField("region", event.target.value)}
                  placeholder={placeholders.region}
                />
                {fieldErrors.region ? (
                  <p className="text-sm text-destructive">{fieldErrors.region}</p>
                ) : null}
              </label>

              <div className="pt-4 sm:col-span-2">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {content.groups.project}
                </p>
              </div>

              <label className="space-y-2 sm:col-span-2">
                <span className="text-sm text-foreground">{fields.application}</span>
                <Input
                  value={values.application}
                  onChange={(event) =>
                    updateField("application", event.target.value)
                  }
                  placeholder={placeholders.application}
                />
                {fieldErrors.application ? (
                  <p className="text-sm text-destructive">
                    {fieldErrors.application}
                  </p>
                ) : null}
              </label>

              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.volume}</span>
                <Input
                  value={values.volume}
                  onChange={(event) => updateField("volume", event.target.value)}
                  placeholder={placeholders.volume}
                />
                {fieldErrors.volume ? (
                  <p className="text-sm text-destructive">{fieldErrors.volume}</p>
                ) : null}
              </label>

              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.timeline}</span>
                <Input
                  value={values.timeline}
                  onChange={(event) => updateField("timeline", event.target.value)}
                  placeholder={placeholders.timeline}
                />
                {fieldErrors.timeline ? (
                  <p className="text-sm text-destructive">{fieldErrors.timeline}</p>
                ) : null}
              </label>

              {values.type === "business_contact" ? (
                <>
                  <label className="space-y-2">
                    <span className="text-sm text-foreground">
                      {fields.organizationType}
                    </span>
                    <Input
                      value={values.organizationType}
                      onChange={(event) =>
                        updateField("organizationType", event.target.value)
                      }
                      placeholder={placeholders.organizationType}
                    />
                    {fieldErrors.organizationType ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.organizationType}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2">
                    <span className="text-sm text-foreground">{fields.jobTitle}</span>
                    <Input
                      value={values.jobTitle}
                      onChange={(event) => updateField("jobTitle", event.target.value)}
                      placeholder={placeholders.jobTitle}
                    />
                    {fieldErrors.jobTitle ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.jobTitle}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2 sm:col-span-2">
                    <span className="text-sm text-foreground">
                      {fields.companyWebsite}
                    </span>
                    <Input
                      value={values.companyWebsite}
                      onChange={(event) =>
                        updateField("companyWebsite", event.target.value)
                      }
                      placeholder={placeholders.companyWebsite}
                    />
                    {fieldErrors.companyWebsite ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.companyWebsite}
                      </p>
                    ) : null}
                  </label>
                </>
              ) : null}

              {values.type === "sample_request" ? (
                <>
                  <label className="space-y-2 sm:col-span-2">
                    <span className="text-sm text-foreground">
                      {fields.materialInterest}
                    </span>
                    <Input
                      value={values.materialInterest}
                      onChange={(event) =>
                        updateField("materialInterest", event.target.value)
                      }
                      placeholder={placeholders.materialInterest}
                    />
                    {fieldErrors.materialInterest ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.materialInterest}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2">
                    <span className="text-sm text-foreground">
                      {fields.quantityEstimate}
                    </span>
                    <Input
                      value={values.quantityEstimate}
                      onChange={(event) =>
                        updateField("quantityEstimate", event.target.value)
                      }
                      placeholder={placeholders.quantityEstimate}
                    />
                    {fieldErrors.quantityEstimate ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.quantityEstimate}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2">
                    <span className="text-sm text-foreground">
                      {fields.shippingCountry}
                    </span>
                    <Input
                      value={values.shippingCountry}
                      onChange={(event) =>
                        updateField("shippingCountry", event.target.value)
                      }
                      placeholder={placeholders.shippingCountry}
                    />
                    {fieldErrors.shippingCountry ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.shippingCountry}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2">
                    <span className="text-sm text-foreground">
                      {fields.shippingRegion}
                    </span>
                    <Input
                      value={values.shippingRegion}
                      onChange={(event) =>
                        updateField("shippingRegion", event.target.value)
                      }
                      placeholder={placeholders.shippingRegion}
                    />
                    {fieldErrors.shippingRegion ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.shippingRegion}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2 sm:col-span-2">
                    <span className="text-sm text-foreground">
                      {fields.shippingAddress}
                    </span>
                    <Textarea
                      value={values.shippingAddress}
                      onChange={(event) =>
                        updateField("shippingAddress", event.target.value)
                      }
                      className="min-h-24"
                      placeholder={placeholders.shippingAddress}
                    />
                    {fieldErrors.shippingAddress ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.shippingAddress}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2 sm:col-span-2">
                    <span className="text-sm text-foreground">{fields.intendedUse}</span>
                    <Textarea
                      value={values.intendedUse}
                      onChange={(event) =>
                        updateField("intendedUse", event.target.value)
                      }
                      className="min-h-24"
                      placeholder={placeholders.intendedUse}
                    />
                    {fieldErrors.intendedUse ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.intendedUse}
                      </p>
                    ) : null}
                  </label>
                </>
              ) : null}

              {(values.type === "partnership_inquiry" ||
                values.type === "university_collaboration" ||
                values.type === "product_development_collaboration") ? (
                <>
                  <label className="space-y-2">
                    <span className="text-sm text-foreground">
                      {fields.organizationType}
                    </span>
                    <Input
                      value={values.organizationType}
                      onChange={(event) =>
                        updateField("organizationType", event.target.value)
                      }
                      placeholder={placeholders.organizationType}
                    />
                    {fieldErrors.organizationType ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.organizationType}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2">
                    <span className="text-sm text-foreground">{fields.timeline}</span>
                    <Input
                      value={values.timeline}
                      onChange={(event) =>
                        updateField("timeline", event.target.value)
                      }
                      placeholder={placeholders.timeline}
                    />
                  </label>

                  <label className="space-y-2 sm:col-span-2">
                    <span className="text-sm text-foreground">
                      {fields.collaborationGoal}
                    </span>
                    <Textarea
                      value={values.collaborationGoal}
                      onChange={(event) =>
                        updateField("collaborationGoal", event.target.value)
                      }
                      className="min-h-24"
                      placeholder={placeholders.collaborationGoal}
                    />
                    {fieldErrors.collaborationGoal ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.collaborationGoal}
                      </p>
                    ) : null}
                  </label>

                  <label className="space-y-2 sm:col-span-2">
                    <span className="text-sm text-foreground">
                      {fields.projectStage}
                    </span>
                    <Input
                      value={values.projectStage}
                      onChange={(event) =>
                        updateField("projectStage", event.target.value)
                      }
                      placeholder={placeholders.projectStage}
                    />
                    {fieldErrors.projectStage ? (
                      <p className="text-sm text-destructive">
                        {fieldErrors.projectStage}
                      </p>
                    ) : null}
                  </label>
                </>
              ) : null}

              <label className="space-y-2 sm:col-span-2">
                <span className="text-sm text-foreground">{fields.message}</span>
                <Textarea
                  value={values.message}
                  onChange={(event) => updateField("message", event.target.value)}
                  placeholder={placeholders.message}
                  className="min-h-36"
                />
                {fieldErrors.message ? (
                  <p className="text-sm text-destructive">{fieldErrors.message}</p>
                ) : null}
              </label>
            </div>
            ) : null}

            <div className="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <Button type="submit" size="lg" disabled={isPending}>
                {isPending ? common.loading.submitting : content.submit}
              </Button>
              {errorMessage ? (
                <div className="rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
                  <p>{errorMessage}</p>
                </div>
              ) : null}
              {submission ? (
                <div className="rounded-2xl bg-primary/8 px-4 py-3 text-sm text-foreground">
                  <p>
                    {(content.successMessage ?? common.success.inquirySubmitted).replace(
                      "{reference}",
                      submission.reference,
                    )}
                  </p>
                </div>
              ) : null}
            </div>
          </form>
        </div>
      </div>
    </section>
  )
}
