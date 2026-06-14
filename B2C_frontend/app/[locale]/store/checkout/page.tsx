"use client"

import { use, useEffect, useMemo, useState } from "react"
import Image from "next/image"
import Link from "next/link"
import { useRouter } from "next/navigation"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { listAddresses } from "@/lib/api/addresses"
import { ApiError, getErrorMessage } from "@/lib/api/client"
import { createOrder } from "@/lib/api/orders"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getPublicSettings, type PublicSettings } from "@/lib/api/public-settings"
import {
  getAddressDetails,
  getShippingOptionTotals,
  getShippingOptions,
  searchAddresses,
} from "@/lib/api/shipping"
import { clearCartSessionKey } from "@/lib/cart/session"
import { getLocalizedHref, getMessages, isValidLocale, type Locale } from "@/lib/i18n"
import {
  getLocalizedShippingMethodDescription,
  getLocalizedShippingMethodLabel,
} from "@/lib/store/order-display"
import type { Address, AddressSearchResult, NzAddress, ShippingOptionTotals, ShippingQuote } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"

type CheckoutPageProps = {
  params: Promise<{ locale: string }>
}

type CheckoutFormState = {
  guest_email: string
  shipping_name: string
  shipping_phone: string
  shipping_address_line1: string
  shipping_address_line2: string
  shipping_city: string
  shipping_state_province: string
  shipping_postal_code: string
  shipping_country: "NZ"
  shipping_is_rural: boolean | null
  customer_note: string
}

const defaultFormState: CheckoutFormState = {
  guest_email: "",
  shipping_name: "",
  shipping_phone: "",
  shipping_address_line1: "",
  shipping_address_line2: "",
  shipping_city: "",
  shipping_state_province: "",
  shipping_postal_code: "",
  shipping_country: "NZ",
  shipping_is_rural: null,
  customer_note: "",
}

const addressFieldKeys = new Set<keyof CheckoutFormState>([
  "shipping_address_line1",
  "shipping_address_line2",
  "shipping_city",
  "shipping_state_province",
  "shipping_postal_code",
  "shipping_is_rural",
])

function formatEta(min?: number | null, max?: number | null) {
  if (!min && !max) return null
  if (min && max) return min === max ? `${min}` : `${min}-${max}`
  return `${min ?? max}`
}

function CheckoutScreen({ locale }: { locale: Locale }) {
  const router = useRouter()
  const session = useAuthSession()
  const messages = getMessages(locale)
  const t = messages.checkout
  const { cart, loading, loadCart } = useCart()
  const [addresses, setAddresses] = useState<Address[]>([])
  const [form, setForm] = useState<CheckoutFormState>(defaultFormState)
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null)
  const [addressQuery, setAddressQuery] = useState("")
  const [addressResults, setAddressResults] = useState<AddressSearchResult[]>([])
  const [addressLookupUnavailable, setAddressLookupUnavailable] = useState(false)
  const [addressLookupError, setAddressLookupError] = useState<string | null>(null)
  const [addressLookupLoading, setAddressLookupLoading] = useState(false)
  const [shippingQuote, setShippingQuote] = useState<ShippingQuote | null>(null)
  const [selectedShippingCode, setSelectedShippingCode] = useState("")
  const [selectedOptionTotals, setSelectedOptionTotals] = useState<ShippingOptionTotals | null>(null)
  const [shippingLoading, setShippingLoading] = useState(false)
  const [totalsLoading, setTotalsLoading] = useState(false)
  const [shippingError, setShippingError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [runtimeSettings, setRuntimeSettings] = useState<PublicSettings | null>(null)

  useEffect(() => {
    // Wait until auth has finished hydrating before loading the cart. Firing
    // while session.token is still transitioning null -> token would issue a
    // guest getCart() whose (empty) response can land after the authenticated
    // one and clobber the real cart, leaving checkout stuck on "cart is empty".
    if (!session.isReady) return

    void loadCart()
    // loadCart intentionally reads the latest auth token from context.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [session.isReady, session.token])

  useEffect(() => {
    let cancelled = false

    void getPublicSettings()
      .then((settings) => {
        if (!cancelled) {
          setRuntimeSettings(settings)
        }
      })
      .catch(() => {
        if (!cancelled) {
          setRuntimeSettings(null)
        }
      })

    return () => {
      cancelled = true
    }
  }, [])

  useEffect(() => {
    if (!session.token) {
      setAddresses([])
      return
    }

    void listAddresses(session.token).then(setAddresses).catch(() => setAddresses([]))
  }, [session.token])

  useEffect(() => {
    if (!session.user?.email) {
      return
    }

    setForm((currentValue) => ({
      ...currentValue,
      guest_email: currentValue.guest_email || session.user?.email || "",
      shipping_name: currentValue.shipping_name || session.user?.name || "",
    }))
  }, [session.user?.email, session.user?.name])

  useEffect(() => {
    if (loading || !cart || cart.items.length > 0) return

    const timer = window.setTimeout(() => {
      router.push(getLocalizedHref(locale, "store"))
    }, 4000)

    return () => window.clearTimeout(timer)
  }, [loading, cart, locale, router])

  useEffect(() => {
    const query = addressQuery.trim()

    if (query.length < 3) {
      setAddressResults([])
      setAddressLookupError(null)
      return
    }

    let cancelled = false
    const timeout = window.setTimeout(() => {
      setAddressLookupLoading(true)
      setAddressLookupError(null)

      void searchAddresses(query)
        .then((response) => {
          if (cancelled) return
          setAddressResults(response.items)
          setAddressLookupUnavailable(response.unavailable)
        })
        .catch((error) => {
          if (cancelled) return
          setAddressResults([])
          setAddressLookupUnavailable(true)
          setAddressLookupError(getErrorMessage(error))
        })
        .finally(() => {
          if (!cancelled) setAddressLookupLoading(false)
        })
    }, 300)

    return () => {
      cancelled = true
      window.clearTimeout(timeout)
    }
  }, [addressQuery])

  const quoteAddress = useMemo<NzAddress | null>(() => {
    if (!form.shipping_city.trim() || !form.shipping_postal_code.trim()) {
      return null
    }

    return {
      line1: form.shipping_address_line1.trim(),
      line2: form.shipping_address_line2.trim() || null,
      city: form.shipping_city.trim(),
      region: form.shipping_state_province.trim() || null,
      postcode: form.shipping_postal_code.trim(),
      country: "NZ",
      is_rural: form.shipping_is_rural,
    }
  }, [
    form.shipping_address_line1,
    form.shipping_address_line2,
    form.shipping_city,
    form.shipping_is_rural,
    form.shipping_postal_code,
    form.shipping_state_province,
  ])

  useEffect(() => {
    if (!quoteAddress || !cart || cart.items.length === 0) {
      setShippingQuote(null)
      setSelectedShippingCode("")
      setSelectedOptionTotals(null)
      setTotalsLoading(false)
      setShippingError(null)
      return
    }

    let cancelled = false
    setShippingLoading(true)
    setSelectedOptionTotals(null)
    setShippingError(null)

    void getShippingOptions(quoteAddress)
      .then((quote) => {
        if (cancelled) return
        setShippingQuote(quote)
        if (quote.options.length === 0) {
          setShippingError(t.selectedShippingMethodUnavailable)
        }
        setSelectedShippingCode((currentCode) => {
          const currentOption = quote.options.find((option) => option.code === currentCode)
          const defaultOption =
            quote.options.find((option) => option.is_default) ?? quote.options[0]

          return currentOption?.code ?? defaultOption?.code ?? ""
        })
      })
      .catch((error) => {
        if (cancelled) return
        setShippingQuote(null)
        setSelectedShippingCode("")
        setShippingError(getErrorMessage(error) || t.unableToCalculateShipping)
      })
      .finally(() => {
        if (!cancelled) setShippingLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [cart, quoteAddress, t.selectedShippingMethodUnavailable, t.unableToCalculateShipping])

  // Fetch authoritative tax/totals from the backend whenever the selected shipping code changes.
  // This ensures the displayed amounts always match what the backend will actually charge.
  useEffect(() => {
    if (!selectedShippingCode || !quoteAddress) {
      setSelectedOptionTotals(null)
      setTotalsLoading(false)
      return
    }

    let cancelled = false
    setTotalsLoading(true)
    setSelectedOptionTotals(null)
    setShippingError(null)

    void getShippingOptionTotals(quoteAddress, selectedShippingCode)
      .then((result) => {
        if (!cancelled) setSelectedOptionTotals(result)
      })
      .catch((error) => {
        if (!cancelled) {
          setSelectedOptionTotals(null)
          setShippingError(getErrorMessage(error) || t.shippingTotalsUnavailable)
        }
      })
      .finally(() => {
        if (!cancelled) setTotalsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [selectedShippingCode, quoteAddress, t.shippingTotalsUnavailable])

  const topAddresses = useMemo(() => addresses.slice(0, 3), [addresses])
  const selectedShippingOption = shippingQuote?.options.find(
    (option) => option.code === selectedShippingCode,
  )
  const subtotal = Number(selectedOptionTotals?.totals.subtotal ?? cart?.subtotal_usd ?? 0)
  const shipping = selectedOptionTotals ? Number(selectedOptionTotals.totals.shipping) : null
  const tax = selectedOptionTotals ? Number(selectedOptionTotals.totals.tax) : null
  const total = selectedOptionTotals ? Number(selectedOptionTotals.totals.total) : null
  const taxLabel =
    selectedOptionTotals?.tax.label ??
    shippingQuote?.tax.label ??
    (selectedOptionTotals?.tax.included === false ? t.gstAdded : t.gstIncluded)
  const storeDisabled = runtimeSettings?.store_enabled === false
  const guestCheckoutDisabled =
    !session.token && runtimeSettings?.guest_checkout_enabled === false
  const currency =
    selectedOptionTotals?.totals.currency ??
    selectedShippingOption?.currency ??
    shippingQuote?.totals.currency ??
    cart?.currency ??
    "NZD"

  function applyAddress(address: Address) {
    setSelectedAddressId(address.id)
    setFieldErrors({})
    setAddressQuery(
      [address.address_line1, address.address_line2, address.city, address.postal_code]
        .filter(Boolean)
        .join(", "),
    )
    setForm((currentValue) => ({
      ...currentValue,
      shipping_name: address.recipient_name,
      shipping_phone: address.phone ?? "",
      shipping_address_line1: address.address_line1,
      shipping_address_line2: address.address_line2 ?? "",
      shipping_city: address.city,
      shipping_state_province: address.state_province ?? "",
      shipping_postal_code: address.postal_code ?? "",
      shipping_country: "NZ",
      shipping_is_rural: null,
    }))
  }

  async function applyLookupResult(result: AddressSearchResult) {
    setAddressQuery(result.label)
    setAddressLookupError(null)
    setAddressResults([])

    try {
      const response = await getAddressDetails(result.id)

      if (response.unavailable) {
        setAddressLookupUnavailable(true)
      }

      if (!response.address) {
        return
      }

      setSelectedAddressId(null)
      setForm((currentValue) => ({
        ...currentValue,
        shipping_address_line1: response.address?.line1 ?? "",
        shipping_address_line2: response.address?.line2 ?? response.address?.suburb ?? "",
        shipping_city: response.address?.city ?? "",
        shipping_state_province: response.address?.region ?? "",
        shipping_postal_code: response.address?.postcode ?? "",
        shipping_country: "NZ",
        shipping_is_rural: response.address?.is_rural ?? null,
      }))
    } catch (error) {
      setAddressLookupUnavailable(true)
      setAddressLookupError(getErrorMessage(error))
    }
  }

  function updateField<Key extends keyof CheckoutFormState>(
    key: Key,
    value: CheckoutFormState[Key],
  ) {
    if (addressFieldKeys.has(key)) {
      setSelectedAddressId(null)
    }

    setFieldErrors((currentValue) => {
      const nextErrors = { ...currentValue }
      delete nextErrors[key]
      return nextErrors
    })
    setForm((currentValue) => ({
      ...currentValue,
      [key]: value,
    }))
  }

  async function handleSubmit() {
    if (storeDisabled) {
      setSubmitError(t.storeDisabledDescription)
      return
    }

    if (guestCheckoutDisabled) {
      setSubmitError(t.guestCheckoutDisabled)
      return
    }

    if (!selectedShippingCode) {
      setSubmitError(t.shippingMethodRequired)
      return
    }

    if (!selectedOptionTotals) {
      setSubmitError(t.shippingTotalsUnavailable)
      return
    }

    setIsSubmitting(true)
    setSubmitError(null)
    setFieldErrors({})

    try {
      const payload = selectedAddressId
        ? {
            address_id: selectedAddressId,
            guest_email: form.guest_email,
            shipping_method_code: selectedShippingCode,
            customer_note: form.customer_note,
          }
        : {
            guest_email: form.guest_email,
            shipping_method_code: selectedShippingCode,
            shipping_name: form.shipping_name,
            shipping_phone: form.shipping_phone,
            shipping_address_line1: form.shipping_address_line1,
            shipping_address_line2: form.shipping_address_line2,
            shipping_city: form.shipping_city,
            shipping_state_province: form.shipping_state_province,
            shipping_postal_code: form.shipping_postal_code,
            shipping_country: "NZ",
            shipping_is_rural: form.shipping_is_rural,
            customer_note: form.customer_note,
          }

      const order = await createOrder(payload, session.token)

      if (!session.token || order.is_guest) {
        clearCartSessionKey()
      }

      await loadCart()

      if (session.token && !order.is_guest) {
        router.push(
          `${getLocalizedHref(locale, `account/orders/${order.order_number}`)}?submitted=1`,
        )
        return
      }

      router.push(
        `${getLocalizedHref(locale, `store/orders/${order.order_number}`)}?token=${encodeURIComponent(order.guest_order_token ?? "")}`,
      )
    } catch (error) {
      if (error instanceof ApiError) {
        setFieldErrors(error.errors ?? {})
      }

      setSubmitError(getErrorMessage(error))
    } finally {
      setIsSubmitting(false)
    }
  }

  // Show the loading state until auth is ready and the cart has finished
  // loading. This prevents a stale/empty cart from flashing the "empty" screen
  // while the authenticated cart is still being fetched.
  if (!session.isReady || (loading && (!cart || cart.items.length === 0))) {
    return (
      <div className="mx-auto max-w-4xl px-6 py-20 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-center text-sm text-muted-foreground">
          {t.loadingCart}
        </div>
      </div>
    )
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="mx-auto max-w-4xl px-6 py-20 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-center">
          <h1 className="font-serif text-4xl text-foreground">{t.emptyTitle}</h1>
          <p className="mt-4 text-muted-foreground">{t.emptyDescription}</p>
          <Button asChild className="mt-6">
            <Link href={getLocalizedHref(locale, "store")}>{t.browseCollection}</Link>
          </Button>
        </div>
      </div>
    )
  }

  if (storeDisabled) {
    return (
      <div className="mx-auto max-w-4xl px-6 py-20 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-center">
          <h1 className="font-serif text-4xl text-foreground">{t.storeDisabledTitle}</h1>
          <p className="mt-4 text-muted-foreground">{t.storeDisabledDescription}</p>
          <Button asChild className="mt-6">
            <Link href={getLocalizedHref(locale, "")}>{t.backToHome}</Link>
          </Button>
        </div>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-8">
      <div className="mb-10">
        <Link
          href={getLocalizedHref(locale, "store/cart")}
          className="text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
          {t.backToCart}
        </Link>
        <p className="mt-4 text-sm uppercase tracking-[0.2em] text-primary">
          {t.eyebrow}
        </p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">{t.title}</h1>
        <p className="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
          {t.description}
        </p>
        <p className="mt-3 text-sm font-medium text-foreground">{t.nzOnlyNotice}</p>
      </div>

      <div className="grid grid-cols-1 gap-8 xl:grid-cols-[1.15fr_0.85fr]">
        <section className="space-y-8 rounded-[2rem] border border-border/60 bg-card p-8">
          <div>
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {t.contactDetails}
            </p>
            <div className="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
              <label className="space-y-2">
                <span className="text-sm text-foreground">{t.email}</span>
                <Input
                  type="email"
                  value={form.guest_email}
                  onChange={(event) => updateField("guest_email", event.target.value)}
                />
                <span className="block text-xs text-muted-foreground">
                  {t.emailHelper}
                </span>
                {fieldErrors.guest_email ? (
                  <span className="text-sm text-red-600">
                    {fieldErrors.guest_email[0]}
                  </span>
                ) : null}
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{t.fullName}</span>
                <Input
                  value={form.shipping_name}
                  onChange={(event) => updateField("shipping_name", event.target.value)}
                />
                {fieldErrors.shipping_name ? (
                  <span className="text-sm text-red-600">
                    {fieldErrors.shipping_name[0]}
                  </span>
                ) : null}
              </label>
              <label className="space-y-2 md:col-span-2">
                <span className="text-sm text-foreground">{t.phone}</span>
                <Input
                  value={form.shipping_phone}
                  onChange={(event) => updateField("shipping_phone", event.target.value)}
                />
                {fieldErrors.shipping_phone ? (
                  <span className="text-sm text-red-600">
                    {fieldErrors.shipping_phone[0]}
                  </span>
                ) : null}
              </label>
            </div>
          </div>

          {topAddresses.length > 0 ? (
            <div>
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {t.savedAddresses}
              </p>
              <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                {topAddresses.map((address) => (
                  <button
                    key={address.id}
                    type="button"
                    className={`rounded-3xl border p-4 text-left transition-colors ${
                      selectedAddressId === address.id
                        ? "border-foreground bg-muted"
                        : "border-border/60 bg-background hover:border-foreground/40"
                    }`}
                    onClick={() => applyAddress(address)}
                  >
                    <div className="flex items-center justify-between gap-3">
                      <p className="font-medium text-foreground">
                        {address.label || address.recipient_name}
                      </p>
                      {address.is_default ? (
                        <span className="rounded-full bg-primary/10 px-2 py-1 text-[10px] uppercase tracking-[0.18em] text-primary">
                          {t.defaultBadge}
                        </span>
                      ) : null}
                    </div>
                    <p className="mt-3 text-sm text-muted-foreground">
                      {address.recipient_name}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {address.city}, {address.country}
                    </p>
                    <p className="mt-4 text-sm text-foreground">{t.useThisAddress}</p>
                  </button>
                ))}
              </div>
            </div>
          ) : null}

          <div>
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {t.deliveryAddress}
            </p>
            <label className="mt-5 block space-y-2">
              <span className="text-sm text-foreground">{t.addressSearch}</span>
              <Input
                value={addressQuery}
                placeholder={t.addressSearchPlaceholder}
                onChange={(event) => setAddressQuery(event.target.value)}
              />
            </label>

            {addressLookupLoading ? (
              <p className="mt-2 text-sm text-muted-foreground">{t.addressLookupLoading}</p>
            ) : null}
            {addressLookupUnavailable ? (
              <p className="mt-2 text-sm text-amber-700">
                {t.addressLookupUnavailable}
              </p>
            ) : null}
            {addressLookupError ? (
              <p className="mt-2 text-sm text-red-600">{addressLookupError}</p>
            ) : null}
            {addressResults.length > 0 ? (
              <div className="mt-3 overflow-hidden rounded-2xl border border-border/60 bg-background">
                {addressResults.map((result) => (
                  <button
                    key={result.id}
                    type="button"
                    className="block w-full border-b border-border/60 px-4 py-3 text-left text-sm transition-colors last:border-b-0 hover:bg-muted"
                    onClick={() => {
                      void applyLookupResult(result)
                    }}
                  >
                    <span className="font-medium text-foreground">{result.label}</span>
                    {result.is_rural ? (
                      <span className="ml-2 text-muted-foreground">{t.ruralAddress}</span>
                    ) : null}
                  </button>
                ))}
              </div>
            ) : null}

            <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
              <label className="space-y-2 md:col-span-2">
                <span className="text-sm text-foreground">{t.addressLine1}</span>
                <Input
                  value={form.shipping_address_line1}
                  onChange={(event) =>
                    updateField("shipping_address_line1", event.target.value)
                  }
                />
                {fieldErrors.shipping_address_line1 ? (
                  <span className="text-sm text-red-600">
                    {fieldErrors.shipping_address_line1[0]}
                  </span>
                ) : null}
              </label>
              <label className="space-y-2 md:col-span-2">
                <span className="text-sm text-foreground">{t.addressLine2}</span>
                <Input
                  value={form.shipping_address_line2}
                  onChange={(event) =>
                    updateField("shipping_address_line2", event.target.value)
                  }
                />
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{t.city}</span>
                <Input
                  value={form.shipping_city}
                  onChange={(event) => updateField("shipping_city", event.target.value)}
                />
                {fieldErrors.shipping_city ? (
                  <span className="text-sm text-red-600">
                    {fieldErrors.shipping_city[0]}
                  </span>
                ) : null}
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{t.stateProvince}</span>
                <Input
                  value={form.shipping_state_province}
                  onChange={(event) =>
                    updateField("shipping_state_province", event.target.value)
                  }
                />
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{t.postalCode}</span>
                <Input
                  value={form.shipping_postal_code}
                  onChange={(event) =>
                    updateField("shipping_postal_code", event.target.value)
                  }
                />
                {fieldErrors.shipping_postal_code ? (
                  <span className="text-sm text-red-600">
                    {fieldErrors.shipping_postal_code[0]}
                  </span>
                ) : null}
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{t.country}</span>
                <div className="flex h-10 items-center rounded-md border border-input bg-muted px-3 text-sm text-foreground">
                  {t.countryLockedValue}
                </div>
                {fieldErrors.shipping_country ? (
                  <span className="text-sm text-red-600">
                    {fieldErrors.shipping_country[0]}
                  </span>
                ) : null}
              </label>
              <label className="flex items-center gap-3 rounded-2xl border border-border/60 bg-background px-4 py-3 md:col-span-2">
                <input
                  type="checkbox"
                  checked={form.shipping_is_rural === true}
                  onChange={(event) =>
                    updateField(
                      "shipping_is_rural",
                      event.target.checked ? true : null,
                    )
                  }
                />
                <span className="text-sm text-foreground">{t.ruralDelivery}</span>
              </label>
            </div>
          </div>

          <div>
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {t.shippingMethods}
            </p>
            {!quoteAddress ? (
              <p className="mt-3 text-sm text-muted-foreground">
                {t.shippingNeedsPostcode}
              </p>
            ) : null}
            {shippingLoading ? (
              <p className="mt-3 text-sm text-muted-foreground">{t.shippingLoading}</p>
            ) : null}
            {shippingError ? (
              <div className="mt-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {shippingError}
              </div>
            ) : null}
            {shippingQuote?.options.length ? (
              <div className="mt-4 grid gap-3">
                {shippingQuote.options.map((option) => {
                  const eta = formatEta(option.eta_min_days, option.eta_max_days)
                  const label = getLocalizedShippingMethodLabel(
                    option,
                    messages.shippingMethods,
                  )
                  const description = getLocalizedShippingMethodDescription(
                    option,
                    messages.shippingMethods,
                  )

                  return (
                    <button
                      key={option.code}
                      type="button"
                      className={`rounded-3xl border p-4 text-left transition-colors ${
                        selectedShippingCode === option.code
                          ? "border-foreground bg-muted"
                          : "border-border/60 bg-background hover:border-foreground/40"
                      }`}
                      onClick={() => setSelectedShippingCode(option.code)}
                    >
                      <div className="flex items-start justify-between gap-4">
                        <div>
                          <p className="font-medium text-foreground">{label}</p>
                          {description ? (
                            <p className="mt-1 text-sm text-muted-foreground">
                              {description}
                            </p>
                          ) : null}
                          {eta ? (
                            <p className="mt-2 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                              {t.eta.replace("{days}", eta)}
                            </p>
                          ) : null}
                        </div>
                        <p className="text-sm font-medium text-foreground">
                          {Number(option.amount) === 0
                            ? t.shippingFree
                            : formatCurrencyAmount(option.amount, locale, option.currency)}
                        </p>
                      </div>
                    </button>
                  )
                })}
              </div>
            ) : null}
          </div>

          <label className="block space-y-2">
            <span className="text-sm text-foreground">{t.orderNote}</span>
            <Textarea
              value={form.customer_note}
              onChange={(event) => updateField("customer_note", event.target.value)}
              rows={5}
            />
          </label>

          {submitError ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {submitError}
            </div>
          ) : null}
        </section>

        <aside className="rounded-[2rem] border border-border/60 bg-card p-8">
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {t.orderSummary}
          </p>

          <div className="mt-6 space-y-4">
            {cart.items.map((item) => (
              <div
                key={`${item.product_id}-${item.product_variant_id ?? "default"}`}
                className="flex gap-4"
              >
                <div className="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-muted">
                  <Image
                    src={item.product?.primary_image_url || item.product?.image_url || "/placeholder.jpg"}
                    alt={item.product?.name || t.productFallback}
                    fill
                    className="object-cover"
                  />
                </div>
                <div className="min-w-0 flex-1">
                  <p className="line-clamp-2 text-sm font-medium text-foreground">
                    {item.product?.name || t.productFallback}
                  </p>
                  {item.variant_title || item.variant_sku ? (
                    <p className="mt-1 text-xs text-muted-foreground">
                      {[item.variant_title, item.variant_sku ? `${t.productCodeLabel} ${item.variant_sku}` : null]
                        .filter(Boolean)
                        .join(" | ")}
                    </p>
                  ) : null}
                  <p className="mt-1 text-sm text-muted-foreground">
                    {t.qty} {item.quantity} x{" "}
                    {formatCurrencyAmount(
                      item.unit_price_usd,
                      locale,
                      item.currency ?? item.product?.currency ?? currency,
                    )}
                  </p>
                </div>
              </div>
            ))}
          </div>

          <div className="mt-8 space-y-3 border-t border-border/60 pt-6 text-sm">
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">{t.subtotal}</span>
              <span className="text-foreground">
                {formatCurrencyAmount(subtotal, locale, currency)}
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">{t.shipping}</span>
              <span className="text-foreground">
                {shipping !== null
                  ? shipping === 0
                    ? t.shippingFree
                    : formatCurrencyAmount(shipping, locale, currency)
                  : t.shippingCalculatedAtCheckout}
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">
                {taxLabel ?? t.gstIncluded}
              </span>
              <span className="text-foreground">
                {tax !== null
                  ? formatCurrencyAmount(tax, locale, currency)
                  : <span className="text-xs text-muted-foreground">{t.shippingCalculatedAtCheckout}</span>
                }
              </span>
            </div>
            <div className="flex items-center justify-between pt-2 text-base font-medium">
              <span className="text-foreground">{t.total}</span>
              <span className="text-foreground">
                {total !== null
                  ? formatCurrencyAmount(total, locale, currency)
                  : <span className="text-xs text-muted-foreground">{t.shippingCalculatedAtCheckout}</span>
                }
              </span>
            </div>
          </div>

          <div className="mt-6 rounded-3xl bg-background p-5 text-sm leading-relaxed text-muted-foreground">
            <p>{guestCheckoutDisabled ? t.guestCheckoutDisabled : t.noAccountRequired}</p>
            <p className="mt-2">{t.confirmationNote}</p>
          </div>

          <Button
            type="button"
            className="mt-8 w-full"
            disabled={
              isSubmitting ||
              shippingLoading ||
              totalsLoading ||
              !selectedShippingCode ||
              !selectedOptionTotals ||
              guestCheckoutDisabled
            }
            onClick={() => {
              void handleSubmit()
            }}
          >
            {isSubmitting ? t.placingOrder : t.placeOrder}
          </Button>
        </aside>
      </div>
    </div>
  )
}

export default function CheckoutPage({ params }: CheckoutPageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"

  return <CheckoutScreen locale={locale} />
}
