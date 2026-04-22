"use client"

import { use, useEffect, useMemo, useState } from "react"
import Image from "next/image"
import Link from "next/link"
import { useRouter } from "next/navigation"

import { AuthGate } from "@/components/auth/AuthGate"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { listAddresses } from "@/lib/api/addresses"
import { formatCurrencyAmount } from "@/lib/api/products"
import { createOrder } from "@/lib/api/orders"
import { ApiError, getErrorMessage } from "@/lib/api/client"
import { getLocalizedHref, isValidLocale, type Locale } from "@/lib/i18n"
import type { Address } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"

type CheckoutPageProps = {
  params: Promise<{ locale: string }>
}

type CheckoutFormState = {
  shipping_name: string
  shipping_phone: string
  shipping_address_line1: string
  shipping_address_line2: string
  shipping_city: string
  shipping_state_province: string
  shipping_postal_code: string
  shipping_country: string
  customer_note: string
}

const defaultFormState: CheckoutFormState = {
  shipping_name: "",
  shipping_phone: "",
  shipping_address_line1: "",
  shipping_address_line2: "",
  shipping_city: "",
  shipping_state_province: "",
  shipping_postal_code: "",
  shipping_country: "NZ",
  customer_note: "",
}

const countryOptions = [
  { label: "South Korea", value: "KR" },
  { label: "New Zealand", value: "NZ" },
  { label: "China", value: "CN" },
  { label: "Australia", value: "AU" },
  { label: "United States", value: "US" },
  { label: "United Kingdom", value: "GB" },
  { label: "Other", value: "OT" },
]

function CheckoutScreen({ locale }: { locale: Locale }) {
  const router = useRouter()
  const session = useAuthSession()
  const { cart, loadCart } = useCart()
  const [addresses, setAddresses] = useState<Address[]>([])
  const [form, setForm] = useState<CheckoutFormState>(defaultFormState)
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    if (!session.token) {
      return
    }

    void loadCart()
    void listAddresses(session.token).then(setAddresses).catch(() => setAddresses([]))
  }, [session.token])

  const subtotal = Number(cart?.subtotal_usd ?? 0)
  const shipping = Number(cart?.estimated_shipping_usd ?? 0)
  const tax = Number(cart?.estimated_tax_usd ?? 0)
  const total = Number(cart?.estimated_total_usd ?? subtotal + shipping + tax)
  const topAddresses = useMemo(() => addresses.slice(0, 3), [addresses])

  function applyAddress(address: Address) {
    setSelectedAddressId(address.id)
    setFieldErrors({})
    setForm((currentValue) => ({
      ...currentValue,
      shipping_name: address.recipient_name,
      shipping_phone: address.phone ?? "",
      shipping_address_line1: address.address_line1,
      shipping_address_line2: address.address_line2 ?? "",
      shipping_city: address.city,
      shipping_state_province: address.state_province ?? "",
      shipping_postal_code: address.postal_code ?? "",
      shipping_country: address.country,
    }))
  }

  function updateField<Key extends keyof CheckoutFormState>(
    key: Key,
    value: CheckoutFormState[Key],
  ) {
    setSelectedAddressId(null)
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
    if (!session.token) {
      return
    }

    setIsSubmitting(true)
    setSubmitError(null)
    setFieldErrors({})

    try {
      const order = await createOrder(
        selectedAddressId
          ? {
              address_id: selectedAddressId,
              customer_note: form.customer_note,
            }
          : form,
        session.token,
      )

      await loadCart()
      router.push(getLocalizedHref(locale, `store/orders/${order.order_number}`))
    } catch (error) {
      if (error instanceof ApiError) {
        setFieldErrors(error.errors ?? {})
      }

      setSubmitError(getErrorMessage(error))
    } finally {
      setIsSubmitting(false)
    }
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="mx-auto max-w-4xl px-6 py-20 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-center">
          <h1 className="font-serif text-4xl text-foreground">Your cart is empty</h1>
          <p className="mt-4 text-muted-foreground">
            Add a Shellfin product before continuing to checkout.
          </p>
          <Button asChild className="mt-6">
            <Link href={getLocalizedHref(locale, "store")}>Browse Collection</Link>
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
          Back to cart
        </Link>
        <p className="mt-4 text-sm uppercase tracking-[0.2em] text-primary">
          Checkout
        </p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">
          Shipping and Order Review
        </h1>
        <p className="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
          Orders run through the shared Shellfin account flow. Shipping is
          structured now, while tax remains a placeholder until the full commerce
          calculator is introduced.
        </p>
      </div>

      <div className="grid grid-cols-1 gap-8 xl:grid-cols-[1.15fr_0.85fr]">
        <section className="space-y-6 rounded-[2rem] border border-border/60 bg-card p-8">
          {topAddresses.length > 0 ? (
            <div>
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                Saved addresses
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
                          Default
                        </span>
                      ) : null}
                    </div>
                    <p className="mt-3 text-sm text-muted-foreground">
                      {address.recipient_name}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {address.city}, {address.country}
                    </p>
                    <p className="mt-4 text-sm text-foreground">Use this address</p>
                  </button>
                ))}
              </div>
            </div>
          ) : null}

          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            <label className="space-y-2">
              <span className="text-sm text-foreground">Full Name</span>
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
            <label className="space-y-2">
              <span className="text-sm text-foreground">Phone</span>
              <Input
                value={form.shipping_phone}
                onChange={(event) => updateField("shipping_phone", event.target.value)}
              />
            </label>
          </div>

          <label className="space-y-2">
            <span className="text-sm text-foreground">Address Line 1</span>
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

          <label className="space-y-2">
            <span className="text-sm text-foreground">Address Line 2</span>
            <Input
              value={form.shipping_address_line2}
              onChange={(event) =>
                updateField("shipping_address_line2", event.target.value)
              }
            />
          </label>

          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            <label className="space-y-2">
              <span className="text-sm text-foreground">City</span>
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
              <span className="text-sm text-foreground">State/Province</span>
              <Input
                value={form.shipping_state_province}
                onChange={(event) =>
                  updateField("shipping_state_province", event.target.value)
                }
              />
            </label>
          </div>

          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            <label className="space-y-2">
              <span className="text-sm text-foreground">Postal Code</span>
              <Input
                value={form.shipping_postal_code}
                onChange={(event) =>
                  updateField("shipping_postal_code", event.target.value)
                }
              />
            </label>
            <label className="space-y-2">
              <span className="text-sm text-foreground">Country</span>
              <select
                value={form.shipping_country}
                onChange={(event) => updateField("shipping_country", event.target.value)}
                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
              >
                {countryOptions.map((country) => (
                  <option key={country.value} value={country.value}>
                    {country.label}
                  </option>
                ))}
              </select>
              {fieldErrors.shipping_country ? (
                <span className="text-sm text-red-600">
                  {fieldErrors.shipping_country[0]}
                </span>
              ) : null}
            </label>
          </div>

          <label className="space-y-2">
            <span className="text-sm text-foreground">Order Note</span>
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
            Order summary
          </p>

          <div className="mt-6 space-y-4">
            {cart.items.map((item) => (
              <div key={item.product_id} className="flex gap-4">
                <div className="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-muted">
                  <Image
                    src={item.product?.primary_image_url || item.product?.image_url || "/placeholder.jpg"}
                    alt={item.product?.name || "Product"}
                    fill
                    className="object-cover"
                  />
                </div>
                <div className="min-w-0 flex-1">
                  <p className="line-clamp-2 text-sm font-medium text-foreground">
                    {item.product?.name || "Shellfin product"}
                  </p>
                  <p className="mt-1 text-sm text-muted-foreground">
                    Qty {item.quantity} ·{" "}
                    {formatCurrencyAmount(
                      item.unit_price_usd,
                      locale,
                      item.product?.currency ?? "USD",
                    )}
                  </p>
                </div>
              </div>
            ))}
          </div>

          <div className="mt-8 space-y-3 border-t border-border/60 pt-6 text-sm">
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Subtotal</span>
              <span className="text-foreground">
                {formatCurrencyAmount(subtotal, locale)}
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Shipping</span>
              <span className="text-foreground">
                {shipping === 0 ? "Free" : formatCurrencyAmount(shipping, locale)}
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Tax</span>
              <span className="text-foreground">
                {formatCurrencyAmount(tax, locale)}
              </span>
            </div>
            <div className="flex items-center justify-between pt-2 text-base font-medium">
              <span className="text-foreground">Total</span>
              <span className="text-foreground">
                {formatCurrencyAmount(total, locale)}
              </span>
            </div>
          </div>

          <p className="mt-6 text-sm leading-relaxed text-muted-foreground">
            Orders are manually confirmed. You will receive a confirmation email
            once your order is reviewed.
          </p>

          <Button
            type="button"
            className="mt-8 w-full"
            disabled={isSubmitting}
            onClick={() => {
              void handleSubmit()
            }}
          >
            {isSubmitting ? "Placing Order..." : "Place Order"}
          </Button>
        </aside>
      </div>
    </div>
  )
}

export default function CheckoutPage({ params }: CheckoutPageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"
  const checkoutHref = getLocalizedHref(locale, "store/checkout")

  return (
    <AuthGate locale={locale} redirectAfterLogin={checkoutHref}>
      <CheckoutScreen locale={locale} />
    </AuthGate>
  )
}
