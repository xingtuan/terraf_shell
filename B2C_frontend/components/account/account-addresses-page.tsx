"use client"

import { useEffect, useState } from "react"

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import {
  createAddress,
  deleteAddress,
  listAddresses,
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
import { formatAddressSummary } from "@/components/account/account-utils"

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
}

function isFormEqual(a: AddressPayload, b: AddressPayload) {
  return JSON.stringify(a) === JSON.stringify(b)
}

function mergeAddress(currentAddresses: Address[], nextAddress: Address) {
  const remainingAddresses = currentAddresses
    .filter((address) => address.id !== nextAddress.id)
    .map((address) =>
      nextAddress.is_default ? { ...address, is_default: false } : address,
    )
  return [nextAddress, ...remainingAddresses]
}

function addressToForm(address: Address): AddressPayload {
  return {
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
  }
}

function addressPayloadWithoutDefault(payload: AddressPayload) {
  const nextPayload = { ...payload }
  delete nextPayload.is_default
  return nextPayload
}

export function AccountAddressesPage({ locale }: AccountAddressesPageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const siteMessages = getMessages(locale)
  const messages = siteMessages.addressPage

  const [addresses, setAddresses] = useState<Address[]>([])
  const [editingAddressId, setEditingAddressId] = useState<number | null>(null)
  const [isAddingNew, setIsAddingNew] = useState(false)
  const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null)
  const [form, setForm] = useState<AddressPayload>(emptyAddressForm)
  const [originalForm, setOriginalForm] = useState<AddressPayload>(emptyAddressForm)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [showDiscardDialog, setShowDiscardDialog] = useState(false)
  const [pendingAction, setPendingAction] = useState<(() => void) | null>(null)

  const formVisible = isAddingNew || editingAddressId !== null
  const isDirty = !isFormEqual(form, originalForm)

  useEffect(() => {
    if (!session.token) return
    setLoading(true)
    setError(null)
    void listAddresses(session.token)
      .then((nextAddresses) => setAddresses(nextAddresses))
      .catch((loadError) => setError(getErrorMessage(loadError)))
      .finally(() => setLoading(false))
  }, [session.token])

  function runOrConfirm(action: () => void) {
    if (formVisible && isDirty) {
      setPendingAction(() => action)
      setShowDiscardDialog(true)
    } else {
      action()
    }
  }

  function closeForm() {
    setEditingAddressId(null)
    setIsAddingNew(false)
    setForm(emptyAddressForm)
    setOriginalForm(emptyAddressForm)
  }

  function startAddNew() {
    runOrConfirm(() => {
      setEditingAddressId(null)
      setIsAddingNew(true)
      setForm({ ...emptyAddressForm })
      setOriginalForm({ ...emptyAddressForm })
    })
  }

  function startEdit(address: Address) {
    const addressForm = addressToForm(address)
    runOrConfirm(() => {
      setIsAddingNew(false)
      setEditingAddressId(address.id)
      setForm(addressForm)
      setOriginalForm(addressForm)
    })
  }

  function handleCancelEdit() {
    if (isDirty) {
      setPendingAction(() => closeForm)
      setShowDiscardDialog(true)
    } else {
      closeForm()
    }
  }

  function handleResetEdit() {
    setForm({ ...originalForm })
  }

  function confirmDiscard() {
    if (pendingAction) {
      pendingAction()
      setPendingAction(null)
    }
    setShowDiscardDialog(false)
  }

  async function handleSubmit() {
    if (!session.token) return
    setError(null)
    setMessage(null)

    try {
      const payload = addressPayloadWithoutDefault(form)
      const nextAddress = editingAddressId
        ? await updateAddress(editingAddressId, payload, session.token)
        : await createAddress(payload, session.token)
      setAddresses((current) => mergeAddress(current, nextAddress))
      closeForm()
      setMessage(editingAddressId ? messages.updatedSuccess : messages.createdSuccess)
    } catch (submitError) {
      setError(getErrorMessage(submitError))
    }
  }

  async function handleDelete(addressId: number) {
    if (!session.token) return
    try {
      await deleteAddress(addressId, session.token)
      setAddresses((current) => current.filter((a) => a.id !== addressId))
      setMessage(siteMessages.common.success.addressDeleted)
    } catch (deleteError) {
      setError(getErrorMessage(deleteError))
    } finally {
      setPendingDeleteId(null)
    }
  }

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.addresses.eyebrow}
        title={copy.addresses.title}
        description={copy.addresses.description}
        actions={
          <Button type="button" onClick={startAddNew}>
            {copy.addresses.startNew}
          </Button>
        }
      />

      {error ? (
        <div className="mt-6 rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {error}
        </div>
      ) : null}

      {message ? (
        <div className="mt-6 rounded-2xl bg-primary/8 px-4 py-3 text-sm text-foreground">
          {message}
        </div>
      ) : null}

      <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <AccountStatCard
          label={copy.addresses.totalSaved}
          value={addresses.length}
        />
      </div>

      {loading ? (
        <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
          {copy.addresses.loading}
        </div>
      ) : null}

      {formVisible ? (
        <AccountPanel className="mt-8 bg-background/70 p-6">
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
                setForm((f) => ({ ...f, label: event.target.value }))
              }
            />
            <Input
              placeholder={messages.recipientNamePlaceholder}
              value={form.recipient_name}
              onChange={(event) =>
                setForm((f) => ({ ...f, recipient_name: event.target.value }))
              }
            />
            <Input
              placeholder={messages.phonePlaceholder}
              value={form.phone ?? ""}
              onChange={(event) =>
                setForm((f) => ({ ...f, phone: event.target.value }))
              }
            />
            <Input
              placeholder={messages.addressLine1Placeholder}
              value={form.address_line1}
              onChange={(event) =>
                setForm((f) => ({ ...f, address_line1: event.target.value }))
              }
            />
            <Input
              placeholder={messages.addressLine2Placeholder}
              value={form.address_line2 ?? ""}
              onChange={(event) =>
                setForm((f) => ({ ...f, address_line2: event.target.value }))
              }
            />
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Input
                placeholder={messages.cityPlaceholder}
                value={form.city}
                onChange={(event) =>
                  setForm((f) => ({ ...f, city: event.target.value }))
                }
              />
              <Input
                placeholder={messages.stateProvincePlaceholder}
                value={form.state_province ?? ""}
                onChange={(event) =>
                  setForm((f) => ({ ...f, state_province: event.target.value }))
                }
              />
            </div>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Input
                placeholder={messages.postalCodePlaceholder}
                value={form.postal_code ?? ""}
                onChange={(event) =>
                  setForm((f) => ({ ...f, postal_code: event.target.value }))
                }
              />
              <Input
                placeholder={messages.countryPlaceholder}
                value={form.country}
                onChange={(event) =>
                  setForm((f) => ({
                    ...f,
                    country: event.target.value.toUpperCase(),
                  }))
                }
              />
            </div>

            <div className="flex flex-wrap gap-3">
              <Button type="button" onClick={() => void handleSubmit()}>
                {editingAddressId ? messages.updateAddress : messages.addAddress}
              </Button>
              {editingAddressId ? (
                <Button
                  type="button"
                  variant="outline"
                  onClick={handleResetEdit}
                  disabled={!isDirty}
                >
                  {messages.resetEdit}
                </Button>
              ) : null}
              <Button type="button" variant="ghost" onClick={handleCancelEdit}>
                {messages.cancel}
              </Button>
            </div>
          </div>
        </AccountPanel>
      ) : null}

      <AccountPanel className="mt-8 bg-background/70 p-6">
        <div className="space-y-4">
          {!loading && addresses.length === 0 ? (
            <AccountEmptyState
              title={messages.noAddresses}
              description={copy.addresses.description}
            />
          ) : (
            addresses.map((address) => (
              <div
                key={address.id}
                className={`rounded-[1.5rem] border bg-card p-5 transition-colors ${
                  editingAddressId === address.id
                    ? "border-primary/40 bg-primary/5"
                    : "border-border/60"
                }`}
              >
                <div className="flex flex-wrap items-center justify-between gap-4">
                  <div>
                    <p className="font-medium text-foreground">
                      {address.label || address.recipient_name}
                    </p>
                    <p className="mt-2 text-sm text-muted-foreground">
                      {address.recipient_name}
                    </p>
                    <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                      {formatAddressSummary(address)}
                    </p>
                  </div>
                  <div className="flex flex-wrap gap-3">
                    <Button
                      type="button"
                      variant={editingAddressId === address.id ? "default" : "outline"}
                      size="sm"
                      onClick={() => startEdit(address)}
                    >
                      {messages.edit}
                    </Button>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => setPendingDeleteId(address.id)}
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

      <AlertDialog
        open={pendingDeleteId !== null}
        onOpenChange={(open) => {
          if (!open) setPendingDeleteId(null)
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {siteMessages.common.confirm.deleteAddress.title}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {siteMessages.common.confirm.deleteAddress.description}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>
              {siteMessages.common.confirm.deleteAddress.cancel}
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (pendingDeleteId !== null) void handleDelete(pendingDeleteId)
              }}
            >
              {siteMessages.common.confirm.deleteAddress.confirm}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog
        open={showDiscardDialog}
        onOpenChange={(open) => {
          if (!open) {
            setShowDiscardDialog(false)
            setPendingAction(null)
          }
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {siteMessages.common.confirm.discardChanges.title}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {siteMessages.common.confirm.discardChanges.description}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>
              {siteMessages.common.confirm.discardChanges.cancel}
            </AlertDialogCancel>
            <AlertDialogAction onClick={confirmDiscard}>
              {siteMessages.common.confirm.discardChanges.confirm}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AccountPanel>
  )
}
