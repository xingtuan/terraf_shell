import type { Locale } from "@/lib/i18n"

export type AccountCopy = {
  shell: {
    eyebrow: string
    title: string
    description: string
    privateWorkspace: string
    signedInAs: string
    publicProfile: string
  }
  nav: {
    overview: string
    orders: string
    addresses: string
    profile: string
    community: string
    store: string
    settings: string
  }
  publicProfile: {
    manageAccount: string
    detailsTitle: string
    detailsDescription: string
    noDetails: string
    completeProfile: string
  }
  overview: {
    eyebrow: string
    title: string
    description: string
    loading: string
    ordersLabel: string
    addressesLabel: string
    postsLabel: string
    savedPostsLabel: string
    cartLabel: string
    notificationsLabel: string
    accountHealthTitle: string
    accountHealthDescription: string
    readyMessage: string
    addBioAction: string
    addDefaultAddressAction: string
    placeFirstOrderAction: string
    quickActionsTitle: string
    recentOrdersTitle: string
    recentOrdersEmpty: string
    defaultAddressTitle: string
    defaultAddressEmpty: string
    latestNotificationsTitle: string
    latestNotificationsEmpty: string
    viewOrders: string
    manageAddresses: string
    editProfile: string
    manageCommunity: string
    continueShopping: string
    resumeCart: string
    browseCommunity: string
    notificationFallback: string
  }
  orders: {
    eyebrow: string
    title: string
    description: string
    totalOrders: string
    activeOrders: string
    pendingOrders: string
    loadingDetail: string
    orderNumberLabel: string
    copyNumber: string
    copied: string
    itemsTitle: string
    shippingTitle: string
    totalTitle: string
    nextStepsTitle: string
    nextStepsDescription: string
    noteTitle: string
    backToOrders: string
    continueShopping: string
  }
  addresses: {
    eyebrow: string
    title: string
    description: string
    loading: string
    totalSaved: string
    defaultStatus: string
    defaultReady: string
    noDefault: string
    startNew: string
    makeDefault: string
    resetForm: string
  }
  profile: {
    eyebrow: string
    title: string
    description: string
    loading: string
    basicTitle: string
    basicDescription: string
    professionalTitle: string
    professionalDescription: string
    identityTitle: string
    identityDescription: string
    locationLabel: string
    locationPlaceholder: string
    regionLabel: string
    regionPlaceholder: string
    organizationLabel: string
    organizationPlaceholder: string
    websiteLabel: string
    websitePlaceholder: string
    portfolioLabel: string
    portfolioPlaceholder: string
    collaborationLabel: string
    collaborationHint: string
    emailLabel: string
    emailPlaceholder: string
    roleLabel: string
    statusLabel: string
    memberSinceLabel: string
    emailVerificationLabel: string
    emailVerifiedLabel: string
    emailNotVerifiedLabel: string
    avatarHint: string
    publicPreviewTitle: string
    publicPreviewDescription: string
    viewPublicProfile: string
    save: string
    saving: string
    success: string
    emailInvalid: string
    urlInvalid: string
  }
  community: {
    eyebrow: string
    title: string
    description: string
    loading: string
    createPost: string
    viewPublicProfile: string
    postsTitle: string
    commentsTitle: string
    favoritesTitle: string
    followersTitle: string
    followingTitle: string
    noFollowers: string
    noFollowing: string
    networkDescription: string
  }
  store: {
    eyebrow: string
    title: string
    description: string
    loading: string
    cartTitle: string
    cartEmpty: string
    cartSubtotal: string
    checkoutReadyTitle: string
    checkoutReadyDescription: string
    checkoutNeedsAddress: string
    latestOrderTitle: string
    latestOrderEmpty: string
    browseStore: string
    openCart: string
    viewCart: string
    goToCheckout: string
    savedItemsTitle: string
    savedItemsDescription: string
  }
  settings: {
    eyebrow: string
    title: string
    description: string
    detailsTitle: string
    notificationsTitle: string
    notificationsDescription: string
    unreadCount: string
    noUnread: string
    securityTitle: string
    securityDescription: string
    sessionTitle: string
    sessionDescription: string
    manageProfile: string
    viewOrders: string
    signOut: string
    signingOut: string
    defaultRole: string
    activeStatus: string
  }
}

const en: AccountCopy = {
  shell: {
    eyebrow: "Account Center",
    title: "Your private Shellfin workspace",
    description:
      "Manage profile identity, shopping activity, addresses, community work, and account controls from one structured place.",
    privateWorkspace: "Private account workspace",
    signedInAs: "Signed in as {email}",
    publicProfile: "Public profile",
  },
  nav: {
    overview: "Overview",
    orders: "Orders",
    addresses: "Addresses",
    profile: "Profile",
    community: "Community",
    store: "Store",
    settings: "Settings",
  },
  publicProfile: {
    manageAccount: "Account center",
    detailsTitle: "Profile details",
    detailsDescription:
      "Public identity details that help other people understand who you are.",
    noDetails: "No additional public profile details have been shared yet.",
    completeProfile: "Complete profile settings",
  },
  overview: {
    eyebrow: "Overview",
    title: "Account overview",
    description:
      "A balanced snapshot of shopping, identity, and community activity.",
    loading: "Loading your account overview...",
    ordersLabel: "Orders",
    addressesLabel: "Addresses",
    postsLabel: "Posts and comments",
    savedPostsLabel: "Saved community posts",
    cartLabel: "Cart items",
    notificationsLabel: "Unread notifications",
    accountHealthTitle: "Account health",
    accountHealthDescription:
      "Complete a few basics so checkout, profile, and support flows stay ready.",
    readyMessage: "Your account is set up well for the current Shellfin flows.",
    addBioAction: "Add a public bio",
    addDefaultAddressAction: "Add a default address",
    placeFirstOrderAction: "Place your first order",
    quickActionsTitle: "Quick actions",
    recentOrdersTitle: "Recent orders",
    recentOrdersEmpty: "No orders yet. Browse the collection to get started.",
    defaultAddressTitle: "Default address",
    defaultAddressEmpty: "No default shipping address is set yet.",
    latestNotificationsTitle: "Latest notifications",
    latestNotificationsEmpty: "No recent notifications yet.",
    viewOrders: "View orders",
    manageAddresses: "Manage addresses",
    editProfile: "Edit profile",
    manageCommunity: "Manage community",
    continueShopping: "Continue shopping",
    resumeCart: "Resume cart",
    browseCommunity: "Open community",
    notificationFallback: "Community update",
  },
  orders: {
    eyebrow: "Orders",
    title: "Order history",
    description:
      "Track order status, review details, and handle pending cancellations from your account center.",
    totalOrders: "Total orders",
    activeOrders: "Active orders",
    pendingOrders: "Pending cancellations",
    loadingDetail: "Loading order details...",
    orderNumberLabel: "Order number",
    copyNumber: "Copy number",
    copied: "Copied",
    itemsTitle: "Items",
    shippingTitle: "Shipping address",
    totalTitle: "Order total",
    nextStepsTitle: "What happens next?",
    nextStepsDescription:
      "Our team reviews each order manually and updates the status as fulfilment moves forward.",
    noteTitle: "Customer note",
    backToOrders: "Back to orders",
    continueShopping: "Continue shopping",
  },
  addresses: {
    eyebrow: "Addresses",
    title: "Address book",
    description:
      "Keep delivery details organized and make checkout faster with a default shipping address.",
    loading: "Loading your saved addresses...",
    totalSaved: "Saved addresses",
    defaultStatus: "Default status",
    defaultReady: "Default address ready",
    noDefault: "No default address",
    startNew: "New address",
    makeDefault: "Use as default shipping address",
    resetForm: "Reset form",
  },
  profile: {
    eyebrow: "Profile",
    title: "Profile settings",
    description:
      "Manage the identity details shown across your account and public profile.",
    loading: "Loading profile settings...",
    basicTitle: "Basic profile",
    basicDescription: "Public-facing identity, avatar, and bio.",
    professionalTitle: "Personal and professional info",
    professionalDescription:
      "Additional context for location, work, and collaboration.",
    identityTitle: "Account identity",
    identityDescription:
      "Shared account details connected to your Shellfin sign-in.",
    locationLabel: "Location",
    locationPlaceholder: "City or country",
    regionLabel: "Region",
    regionPlaceholder: "Region or state",
    organizationLabel: "School or company",
    organizationPlaceholder: "School, company, or studio",
    websiteLabel: "Website",
    websitePlaceholder: "https://your-site.com",
    portfolioLabel: "Portfolio URL",
    portfolioPlaceholder: "https://portfolio.com",
    collaborationLabel: "Open to collaborate",
    collaborationHint:
      "This public signal helps other people understand whether outreach is welcome.",
    emailLabel: "Email",
    emailPlaceholder: "you@example.com",
    roleLabel: "Role",
    statusLabel: "Account status",
    memberSinceLabel: "Member since",
    emailVerificationLabel: "Email verification",
    emailVerifiedLabel: "Email verified",
    emailNotVerifiedLabel: "Email not verified",
    avatarHint: "Shown across public profile, community, and account surfaces.",
    publicPreviewTitle: "Public preview",
    publicPreviewDescription:
      "This is the identity summary people see first on your public profile.",
    viewPublicProfile: "View public profile",
    save: "Save changes",
    saving: "Saving changes...",
    success: "Profile updated.",
    emailInvalid: "Enter a valid email address.",
    urlInvalid: "Enter a valid URL.",
  },
  community: {
    eyebrow: "Community",
    title: "Community management",
    description:
      "Manage posts, comments, saved ideas, and your network without burying the rest of the account.",
    loading: "Loading community activity...",
    createPost: "Create post",
    viewPublicProfile: "View public profile",
    postsTitle: "My posts",
    commentsTitle: "Recent comments",
    favoritesTitle: "Saved posts",
    followersTitle: "Followers",
    followingTitle: "Following",
    noFollowers: "No followers yet.",
    noFollowing: "You are not following anyone yet.",
    networkDescription: "Your public community network lives here as a private management view.",
  },
  store: {
    eyebrow: "Store",
    title: "Store activity",
    description:
      "Keep shopping progress, fulfilment readiness, and order follow-up close to the rest of your account.",
    loading: "Loading store activity...",
    cartTitle: "Active cart",
    cartEmpty: "Your cart is empty right now.",
    cartSubtotal: "Subtotal",
    checkoutReadyTitle: "Checkout readiness",
    checkoutReadyDescription:
      "A default address is available, so checkout can move faster.",
    checkoutNeedsAddress:
      "Add a default address to make checkout and support flows smoother.",
    latestOrderTitle: "Latest order",
    latestOrderEmpty: "No recent store activity yet.",
    browseStore: "Browse store",
    openCart: "Open cart",
    viewCart: "View cart",
    goToCheckout: "Go to checkout",
    savedItemsTitle: "Saved items",
    savedItemsDescription:
      "Store-side wishlists are not available in the current API yet. This section is structured so they can be added without redesigning the account.",
  },
  settings: {
    eyebrow: "Settings",
    title: "General settings",
    description:
      "Review account identity, notification state, and session controls.",
    detailsTitle: "Account details",
    notificationsTitle: "Notifications",
    notificationsDescription:
      "Notification preferences are not configurable yet, but current unread activity is surfaced here.",
    unreadCount: "{count} unread notifications",
    noUnread: "No unread notifications.",
    securityTitle: "Security and privacy",
    securityDescription:
      "Password management and advanced privacy controls are not available in the current API yet.",
    sessionTitle: "Session",
    sessionDescription:
      "Use sign out to end the current frontend session for this shared Shellfin account.",
    manageProfile: "Manage profile",
    viewOrders: "View orders",
    signOut: "Sign out",
    signingOut: "Signing out...",
    defaultRole: "Member",
    activeStatus: "Active",
  },
}

const copies: Record<Locale, AccountCopy> = {
  en,
  ko: en,
  zh: en,
}

export function getAccountCopy(locale: Locale) {
  return copies[locale]
}
