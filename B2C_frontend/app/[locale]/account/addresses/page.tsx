"use client"

import { use, useEffect, useMemo, useState } from "react"

import { AuthGate } from "@/components/auth/AuthGate"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import {
  createAddress,
  deleteAddress,
  listAddresses,
  setDefaultAddress,
  updateAddress,
  type AddressPayload,
} from "@/lib/api/addresses"
import { getErrorMessage } from "@/lib/api/client"
import { getLocalizedHref, isValidLocale, type Locale } from "@/lib/i18n"
import type { Address } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type AccountAddressesPageProps = {
  params: Promise<{ locale: string }>
}

const emptyAddressForm: AddressPayload = {
  label: "",
  recipient_name: "",
  phone: "",
  address_line1: "",
  address_line2: "",
  city: "",
  state_province: "",
  postal_code: "",
  country: "NZ",
  is_default: false,
}

function AddressesScreen({ locale }: { locale: Locale }) {
  const session = useAuthSession()
  const [addresses, setAddresses] = useState<Address[]>([])
  const [editingAddressId, setEditingAddressId] = useState<number | null>(null)
  const [form, setForm] = useState<AddressPayload>(emptyAddressForm)
  const [error, setError] = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    if (!session.token) {
      return
    }

    void listAddresses(session.token)
      .then(setAddresses)
      .catch((nextError) => setError(getErrorMessage(nextError)))
  }, [session.token])

  const orderedAddresses = useMemo(
    () =>
      [...addresses].sort((leftAddress, rightAddress) =>
        Number(rightAddress.is_default) - Number(leftAddress.is_default),
      ),
    [addresses],
  )

  function resetForm() {
    setEditingAddressId(null)
    setForm(emptyAddressForm)
  }

  async function handleSubmit() {
    if (!session.token) {
      return
    }

    setError(null)
    setMessage(null)

    try {
      const nextAddress = editingAddressId
        ? await updateAddress(editingAddressId, form, session.token)
        : await createAddress(form as AddressPayload, session.token)

      setAddresses((currentAddresses) => {
        if (editingAddressId) {
          return currentAddresses.map((address) =>
            address.id === editingAddressId ? nextAddress : address,
          )
        }

        return [nextAddress, ...currentAddresses]
      })

      setMessage(
        editingAddressId
          ? "Address updated successfully."
          : "Address created successfully.",
      )
      resetForm()
    } catch (nextError) {
      setError(getErrorMessage(nextError))
    }
  }

  async function handleDelete(addressId: number) {
    if (!session.token) {
      return
    }

    if (!window.confirm("Delete this address?")) {
      return
    }

    try {
      await deleteAddress(addressId, session.token)
      setAddresses((currentAddresses) =>
        currentAddresses.filter((address) => address.id !== addressId),
      )
    } catch (nextError) {
      setError(getErrorMessage(nextError))
    }
  }

  async function handleSetDefault(addressId: number) {
    if (!session.token) {
      return
    }

    try {
      const updatedAddress = await setDefaultAddress(addressId, session.token)
      setAddresses((currentAddresses) =>
        currentAddresses.map((address) => ({
          ...address,
          is_default: address.id === updatedAddress.id,
        })),
      )
    } catch (nextError) {
      setError(getErrorMessage(nextError))
    }
  }

  return (
    <div className="mx-auto max-w-6xl px-6 py-16 lg:px-8">
      <div className="grid grid-cols-1 gap-8 xl:grid-cols-[0.95fr_1.05fr]">
        <section className="rounded-[2rem] border border-border/60 bg-card p-8">
          <p className="text-sm uppercase tracking-[0.2em] text-primary">Addresses</p>
          <h1 className="mt-3 font-serif text-4xl text-foreground">
            {editingAddressId ? "Edit Address" : "Add Address"}
          </h1>

          <div className="mt-8 space-y-4">
            <Input
              placeholder="Label"
              value={form.label ?? ""}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  label: event.target.value,
                }))
              }
            />
            <Input
              placeholder="Recipient Name"
              value={form.recipient_name}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  recipient_name: event.target.value,
                }))
              }
            />
            <Input
              placeholder="Phone"
              value={form.phone ?? ""}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  phone: event.target.value,
                }))
              }
            />
            <Input
              placeholder="Address Line 1"
              value={form.address_line1}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  address_line1: event.target.value,
                }))
              }
            />
            <Input
              placeholder="Address Line 2"
              value={form.address_line2 ?? ""}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  address_line2: event.target.value,
                }))
              }
            />
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Input
                placeholder="City"
                value={form.city}
                onChange={(event) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    city: event.target.value,
                  }))
                }
              />
              <Input
                placeholder="State / Province"
                value={form.state_province ?? ""}
                onChange={(event) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    state_province: event.target.value,
                  }))
                }
              />
            </div>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Input
                placeholder="Postal Code"
                value={form.postal_code ?? ""}
                onChange={(event) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    postal_code: event.target.value,
                  }))
                }
              />
              <Input
                placeholder="Country (ISO code)"
                value={form.country}
                onChange={(event) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    country: event.target.value.toUpperCase(),
                  }))
                }
              />
            </div>

            {message ? (
              <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {message}
              </div>
            ) : null}

            {error ? (
              <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {error}
              </div>
            ) : null}

            <div className="flex flex-wrap gap-3">
              <Button type="button" onClick={() => void handleSubmit()}>
                {editingAddressId ? "Update Address" : "Add Address"}
              </Button>
              {editingAddressId ? (
                <Button type="button" variant="outline" onClick={resetForm}>
                  Cancel
                </Button>
              ) : null}
            </div>
          </div>
        </section>

        <section className="rounded-[2rem] border border-border/60 bg-card p-8">
          <p className="text-sm uppercase tracking-[0.2em] text-primary">
            Saved Addresses
          </p>
          <div className="mt-6 space-y-4">
            {orderedAddresses.length === 0 ? (
              <p className="text-sm text-muted-foreground">
                No saved addresses yet.
              </p>
            ) : (
              orderedAddresses.map((address) => (
                <div
                  key={address.id}
                  className="rounded-3xl border border-border/60 p-5"
                >
                  <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                      <div className="flex flex-wrap items-center gap-3">
                        <p className="font-medium text-foreground">
                          {address.label || address.recipient_name}
                        </p>
                        {address.is_default ? (
                          <span className="rounded-full bg-primary/10 px-2 py-1 text-[10px] uppercase tracking-[0.18em] text-primary">
                            Default
                          </span>
                        ) : null}
                      </div>
                      <p className="mt-2 text-sm text-muted-foreground">
                        {address.recipient_name}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        {address.address_line1}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        {address.city}, {address.country}
                      </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                      {!address.is_default ? (
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            void handleSetDefault(address.id)
                          }}
                        >
                          Set Default
                        </Button>
                      ) : null}
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          setEditingAddressId(address.id)
                          setForm({
                            label: address.label ?? "",
                            recipient_name: address.recipient_name,
                            phone: address.phone ?? "",
                            address_line1: address.address_line1,
                            address_line2: address.address_line2 ?? "",
                            city: address.city,
                            state_province: address.state_province ?? "",
                            postal_code: address.postal_code ?? "",
                            country: address.country,
                            is_default: address.is_default,
                          })
                        }}
                      >
                        Edit
                      </Button>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                          void handleDelete(address.id)
                        }}
                      >
                        Delete
                      </Button>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </section>
      </div>
    </div>
  )
}

export default function AccountAddressesPage({
  params,
}: AccountAddressesPageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"
  const addressesHref = getLocalizedHref(locale, "account/addresses")

  return (
    <AuthGate locale={locale} redirectAfterLogin={addressesHref}>
      <AddressesScreen locale={locale} />
    </AuthGate>
  )
}
