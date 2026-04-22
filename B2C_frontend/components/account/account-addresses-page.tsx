"use client"

import { useEffect, useMemo, useState } from "react"

import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
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
import { getAccountCopy } from "@/lib/account-copy"
import { getMessages, type Locale } from "@/lib/i18n"
import type { Address } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import {
  AccountEmptyState,
  AccountPageHeader,
  AccountPanel,
  AccountStatCard,
} from "@/components/account/account-ui"
import {
  formatAddressSummary,
  getDefaultAddress,
} from "@/components/account/account-utils"

type AccountAddressesPageProps = {
  locale: Locale
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

function mergeAddress(currentAddresses: Address[], nextAddress: Address) {
  const remainingAddresses = currentAddresses
    .filter((address) => address.id !== nextAddress.id)
    .map((address) =>
      nextAddress.is_default ? { ...address, is_default: false } : address,
    )

  return [nextAddress, ...remainingAddresses]
}

export function AccountAddressesPage({ locale }: AccountAddressesPageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const messages = getMessages(locale).addressPage
  const [addresses, setAddresses] = useState<Address[]>([])
  const [editingAddressId, setEditingAddressId] = useState<number | null>(null)
  const [form, setForm] = useState<AddressPayload>(emptyAddressForm)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    if (!session.token) {
      return
    }

    setLoading(true)
    setError(null)

    void listAddresses(session.token)
      .then((nextAddresses) => {
        setAddresses(nextAddresses)
      })
      .catch((loadError) => {
        setError(getErrorMessage(loadError))
      })
      .finally(() => {
        setLoading(false)
      })
  }, [session.token])

  const orderedAddresses = useMemo(
    () =>
      [...addresses].sort(
        (leftAddress, rightAddress) =>
          Number(rightAddress.is_default) - Number(leftAddress.is_default),
      ),
    [addresses],
  )
  const defaultAddress = useMemo(() => getDefaultAddress(addresses), [addresses])

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
        : await createAddress(form, session.token)

      setAddresses((currentAddresses) => mergeAddress(currentAddresses, nextAddress))
      resetForm()
      setMessage(editingAddressId ? messages.updatedSuccess : messages.createdSuccess)
    } catch (submitError) {
      setError(getErrorMessage(submitError))
    }
  }

  async function handleDelete(addressId: number) {
    if (!session.token) {
      return
    }

    if (!window.confirm(messages.deleteConfirm)) {
      return
    }

    try {
      await deleteAddress(addressId, session.token)
      setAddresses((currentAddresses) =>
        currentAddresses.filter((address) => address.id !== addressId),
      )
    } catch (deleteError) {
      setError(getErrorMessage(deleteError))
    }
  }

  async function handleSetDefault(addressId: number) {
    if (!session.token) {
      return
    }

    try {
      const nextAddress = await setDefaultAddress(addressId, session.token)
      setAddresses((currentAddresses) => mergeAddress(currentAddresses, nextAddress))
    } catch (defaultError) {
      setError(getErrorMessage(defaultError))
    }
  }

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.addresses.eyebrow}
        title={copy.addresses.title}
        description={copy.addresses.description}
        actions={
          <>
            <Button type="button" variant="outline" onClick={resetForm}>
              {copy.addresses.resetForm}
            </Button>
            <Button type="button" onClick={resetForm}>
              {copy.addresses.startNew}
            </Button>
          </>
        }
      />

      {error ? (
        <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      {message ? (
        <div className="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          {message}
        </div>
      ) : null}

      <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <AccountStatCard
          label={copy.addresses.totalSaved}
          value={addresses.length}
          detail={messages.savedAddresses}
        />
        <AccountStatCard
          label={copy.addresses.defaultStatus}
          value={defaultAddress ? copy.addresses.defaultReady : copy.addresses.noDefault}
          detail={
            defaultAddress
              ? defaultAddress.label || defaultAddress.recipient_name
              : copy.addresses.description
          }
        />
        <AccountStatCard
          label={messages.savedAddresses}
          value={editingAddressId ? messages.editTitle : messages.addTitle}
          detail={copy.addresses.makeDefault}
        />
      </div>

      {loading ? (
        <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
          {copy.addresses.loading}
        </div>
      ) : null}

      <div className="mt-8 grid gap-8 xl:grid-cols-[0.95fr_1.05fr]">
        <AccountPanel className="bg-background/70 p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {copy.addresses.eyebrow}
          </p>
          <h2 className="mt-3 font-serif text-3xl text-foreground">
            {editingAddressId ? messages.editTitle : messages.addTitle}
          </h2>

          <div className="mt-8 space-y-4">
            <Input
              placeholder={messages.labelPlaceholder}
              value={form.label ?? ""}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  label: event.target.value,
                }))
              }
            />
            <Input
              placeholder={messages.recipientNamePlaceholder}
              value={form.recipient_name}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  recipient_name: event.target.value,
                }))
              }
            />
            <Input
              placeholder={messages.phonePlaceholder}
              value={form.phone ?? ""}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  phone: event.target.value,
                }))
              }
            />
            <Input
              placeholder={messages.addressLine1Placeholder}
              value={form.address_line1}
              onChange={(event) =>
                setForm((currentValue) => ({
                  ...currentValue,
                  address_line1: event.target.value,
                }))
              }
            />
            <Input
              placeholder={messages.addressLine2Placeholder}
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
                placeholder={messages.cityPlaceholder}
                value={form.city}
                onChange={(event) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    city: event.target.value,
                  }))
                }
              />
              <Input
                placeholder={messages.stateProvincePlaceholder}
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
                placeholder={messages.postalCodePlaceholder}
                value={form.postal_code ?? ""}
                onChange={(event) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    postal_code: event.target.value,
                  }))
                }
              />
              <Input
                placeholder={messages.countryPlaceholder}
                value={form.country}
                onChange={(event) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    country: event.target.value.toUpperCase(),
                  }))
                }
              />
            </div>
            <label className="flex items-start gap-3 rounded-[1.25rem] border border-border/60 bg-card px-4 py-4 text-sm text-foreground">
              <Checkbox
                checked={Boolean(form.is_default)}
                onCheckedChange={(checked) =>
                  setForm((currentValue) => ({
                    ...currentValue,
                    is_default: Boolean(checked),
                  }))
                }
              />
              <span>{copy.addresses.makeDefault}</span>
            </label>

            <div className="flex flex-wrap gap-3">
              <Button type="button" onClick={() => void handleSubmit()}>
                {editingAddressId ? messages.updateAddress : messages.addAddress}
              </Button>
              {editingAddressId ? (
                <Button type="button" variant="outline" onClick={resetForm}>
                  {messages.cancel}
                </Button>
              ) : null}
            </div>
          </div>
        </AccountPanel>

        <AccountPanel className="bg-background/70 p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {messages.savedAddresses}
          </p>
          <h2 className="mt-3 font-serif text-3xl text-foreground">
            {messages.savedAddresses}
          </h2>

          <div className="mt-6 space-y-4">
            {!loading && orderedAddresses.length === 0 ? (
              <AccountEmptyState
                title={messages.savedAddresses}
                description={messages.noAddresses}
              />
            ) : (
              orderedAddresses.map((address) => (
                <div
                  key={address.id}
                  className="rounded-[1.5rem] border border-border/60 bg-card p-5"
                >
                  <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                      <div className="flex flex-wrap items-center gap-3">
                        <p className="font-medium text-foreground">
                          {address.label || address.recipient_name}
                        </p>
                        {address.is_default ? (
                          <span className="rounded-full bg-primary/10 px-2 py-1 text-[10px] uppercase tracking-[0.18em] text-primary">
                            {messages.defaultBadge}
                          </span>
                        ) : null}
                      </div>
                      <p className="mt-2 text-sm text-muted-foreground">
                        {address.recipient_name}
                      </p>
                      <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                        {formatAddressSummary(address)}
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
                          {messages.setDefault}
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
                        {messages.edit}
                      </Button>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                          void handleDelete(address.id)
                        }}
                      >
                        {messages.delete}
                      </Button>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </AccountPanel>
      </div>
    </AccountPanel>
  )
}
