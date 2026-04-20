"use client"

import { use, useEffect, useState } from "react"
import Link from "next/link"

import { AuthGate } from "@/components/auth/AuthGate"
import { Button } from "@/components/ui/button"
import { deleteAddress, listAddresses } from "@/lib/api/addresses"
import { getOrders } from "@/lib/api/orders"
import { getUserProfile } from "@/lib/api/users"
import { getErrorMessage } from "@/lib/api/client"
import { getLocalizedHref, isValidLocale, type Locale } from "@/lib/i18n"
import type { Address, CommunityUser, StoreOrder } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type AccountPageProps = {
  params: Promise<{ locale: string }>
}

function statusClasses(status: StoreOrder["status"]) {
  switch (status) {
    case "confirmed":
      return "bg-sky-100 text-sky-700"
    case "processing":
      return "bg-amber-100 text-amber-700"
    case "shipped":
      return "bg-violet-100 text-violet-700"
    case "delivered":
      return "bg-emerald-100 text-emerald-700"
    case "cancelled":
      return "bg-red-100 text-red-700"
    default:
      return "bg-muted text-foreground"
  }
}

function AccountScreen({ locale }: { locale: Locale }) {
  const session = useAuthSession()
  const [profile, setProfile] = useState<CommunityUser | null>(session.user)
  const [recentOrders, setRecentOrders] = useState<StoreOrder[]>([])
  const [addresses, setAddresses] = useState<Address[]>([])
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!session.token || !session.user?.username) {
      return
    }

    void Promise.all([
      getUserProfile(session.user.username, session.token),
      getOrders(session.token, 1, 3),
      listAddresses(session.token),
    ])
      .then(([nextProfile, ordersResponse, nextAddresses]) => {
        setProfile(nextProfile ?? session.user)
        setRecentOrders(ordersResponse.items)
        setAddresses(nextAddresses)
      })
      .catch((nextError) => {
        setError(getErrorMessage(nextError))
      })
  }, [session.token, session.user?.username])

  async function handleDeleteAddress(addressId: number) {
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

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-8">
      <div className="mb-10">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">Account</p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">
          Hello, {session.user?.name}
        </h1>
      </div>

      {error ? (
        <div className="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div className="rounded-3xl border border-border/60 bg-card p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-muted-foreground">
            Orders
          </p>
          <p className="mt-4 text-3xl font-medium text-foreground">
            {recentOrders.length}
          </p>
        </div>
        <div className="rounded-3xl border border-border/60 bg-card p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-muted-foreground">
            Community Posts
          </p>
          <p className="mt-4 text-3xl font-medium text-foreground">
            {profile?.posts_count ?? 0}
          </p>
        </div>
        <div className="rounded-3xl border border-border/60 bg-card p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-muted-foreground">
            Following
          </p>
          <p className="mt-4 text-3xl font-medium text-foreground">
            {profile?.following_count ?? 0}
          </p>
        </div>
      </div>

      <div className="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-[1.1fr_0.9fr]">
        <section className="rounded-[2rem] border border-border/60 bg-card p-8">
          <div className="flex items-end justify-between gap-4">
            <div>
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                Recent Orders
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                Latest activity
              </h2>
            </div>
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "store/orders")}>
                View all orders →
              </Link>
            </Button>
          </div>

          <div className="mt-6 space-y-4">
            {recentOrders.length === 0 ? (
              <p className="text-sm text-muted-foreground">
                No orders yet. Browse the collection to get started.
              </p>
            ) : (
              recentOrders.map((order) => (
                <div
                  key={order.order_number}
                  className="rounded-3xl border border-border/60 p-5"
                >
                  <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                      <p className="font-medium text-foreground">
                        {order.order_number}
                      </p>
                      <p className="mt-1 text-sm text-muted-foreground">
                        {order.created_at
                          ? new Date(order.created_at).toLocaleDateString()
                          : "Pending date"}
                      </p>
                    </div>
                    <span
                      className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${statusClasses(order.status)}`}
                    >
                      {order.status}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        </section>

        <div className="space-y-8">
          <section className="rounded-[2rem] border border-border/60 bg-card p-8">
            <div className="flex items-end justify-between gap-4">
              <div>
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  Account Settings
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  Profile
                </h2>
              </div>
              <Button asChild variant="outline">
                <Link href={getLocalizedHref(locale, "account/profile")}>
                  Edit Profile
                </Link>
              </Button>
            </div>

            <div className="mt-6 space-y-3 text-sm text-muted-foreground">
              <p>
                <span className="text-foreground">Display name:</span>{" "}
                {session.user?.name}
              </p>
              <p>
                <span className="text-foreground">Email:</span> {session.user?.email}
              </p>
            </div>
          </section>

          <section className="rounded-[2rem] border border-border/60 bg-card p-8">
            <div className="flex items-end justify-between gap-4">
              <div>
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  Saved Addresses
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  Shipping Book
                </h2>
              </div>
              <Button asChild variant="outline">
                <Link href={getLocalizedHref(locale, "account/addresses")}>
                  Add new address
                </Link>
              </Button>
            </div>

            <div className="mt-6 space-y-4">
              {addresses.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No saved addresses yet.
                </p>
              ) : (
                addresses.map((address) => (
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
                          {address.city}, {address.country}
                        </p>
                      </div>
                      <div className="flex gap-3">
                        <Button asChild variant="outline" size="sm">
                          <Link href={getLocalizedHref(locale, "account/addresses")}>
                            Edit
                          </Link>
                        </Button>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            void handleDeleteAddress(address.id)
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
    </div>
  )
}

export default function AccountPage({ params }: AccountPageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"
  const accountHref = getLocalizedHref(locale, "account")

  return (
    <AuthGate locale={locale} redirectAfterLogin={accountHref}>
      <AccountScreen locale={locale} />
    </AuthGate>
  )
}
