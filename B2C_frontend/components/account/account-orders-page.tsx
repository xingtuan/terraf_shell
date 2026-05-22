"use client"

import {
  AccountOrderActions,
  AccountOrderList,
} from "@/components/account/account-order-list"
import {
  AccountPageHeader,
  AccountPanel,
} from "@/components/account/account-ui"
import { getAccountCopy } from "@/lib/account-copy"
import type { Locale } from "@/lib/i18n"

type AccountOrdersPageProps = {
  locale: Locale
}

export function AccountOrdersPage({ locale }: AccountOrdersPageProps) {
  const copy = getAccountCopy(locale)

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.orders.eyebrow}
        title={copy.orders.title}
        description={copy.orders.description}
        actions={<AccountOrderActions locale={locale} />}
      />

      <AccountOrderList locale={locale} />
    </AccountPanel>
  )
}
