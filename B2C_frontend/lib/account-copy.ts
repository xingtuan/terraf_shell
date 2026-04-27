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

const ko: AccountCopy = {
  shell: {
    eyebrow: "계정 센터",
    title: "나만의 Shellfin 프라이빗 공간",
    description: "프로필 정보, 쇼핑 내역, 주소, 커뮤니티 활동, 계정 설정을 한 곳에서 관리하세요.",
    privateWorkspace: "프라이빗 계정 공간",
    signedInAs: "{email}로 로그인됨",
    publicProfile: "공개 프로필",
  },
  nav: {
    overview: "개요",
    orders: "주문",
    addresses: "주소",
    profile: "프로필",
    community: "커뮤니티",
    store: "스토어",
    settings: "설정",
  },
  publicProfile: {
    manageAccount: "계정 센터",
    detailsTitle: "프로필 정보",
    detailsDescription: "공개 프로필에 표시되는 정보입니다.",
    noDetails: "아직 공개 프로필 정보가 없습니다.",
    completeProfile: "프로필 설정 완료",
  },
  overview: {
    eyebrow: "개요",
    title: "계정 개요",
    description: "쇼핑, 정보, 커뮤니티 활동의 전체 현황을 한눈에 확인하세요.",
    loading: "계정 개요를 불러오는 중...",
    ordersLabel: "주문",
    addressesLabel: "주소",
    postsLabel: "게시물과 댓글",
    savedPostsLabel: "저장한 커뮤니티 게시물",
    cartLabel: "장바구니 상품",
    notificationsLabel: "읽지 않은 알림",
    accountHealthTitle: "계정 상태",
    accountHealthDescription: "결제, 프로필, 고객 지원이 원활하게 이루어지도록 기본 정보를 완성해 주세요.",
    readyMessage: "현재 Shellfin 흐름에 맞게 계정이 잘 설정되어 있습니다.",
    addBioAction: "공개 소개 추가",
    addDefaultAddressAction: "기본 주소 추가",
    placeFirstOrderAction: "첫 주문 하기",
    quickActionsTitle: "빠른 실행",
    recentOrdersTitle: "최근 주문",
    recentOrdersEmpty: "아직 주문이 없습니다. 컬렉션을 둘러보세요.",
    defaultAddressTitle: "기본 주소",
    defaultAddressEmpty: "아직 기본 배송 주소가 없습니다.",
    latestNotificationsTitle: "최신 알림",
    latestNotificationsEmpty: "아직 알림이 없습니다.",
    viewOrders: "주문 보기",
    manageAddresses: "주소 관리",
    editProfile: "프로필 수정",
    manageCommunity: "커뮤니티 관리",
    continueShopping: "쇼핑 계속하기",
    resumeCart: "장바구니 이어서",
    browseCommunity: "커뮤니티 열기",
    notificationFallback: "커뮤니티 업데이트",
  },
  orders: {
    eyebrow: "주문",
    title: "주문 내역",
    description: "계정 센터에서 주문 상태를 추적하고 세부 정보를 확인하세요.",
    totalOrders: "전체 주문",
    activeOrders: "진행 중인 주문",
    pendingOrders: "취소 대기 중",
    loadingDetail: "주문 상세 정보를 불러오는 중...",
    orderNumberLabel: "주문 번호",
    copyNumber: "번호 복사",
    copied: "복사됨",
    itemsTitle: "상품",
    shippingTitle: "배송 주소",
    totalTitle: "주문 합계",
    nextStepsTitle: "다음 단계",
    nextStepsDescription: "팀이 각 주문을 직접 검토하고 처리가 진행됨에 따라 상태를 업데이트합니다.",
    noteTitle: "고객 메모",
    backToOrders: "주문 목록으로",
    continueShopping: "쇼핑 계속하기",
  },
  addresses: {
    eyebrow: "주소",
    title: "주소록",
    description: "배송 정보를 정리하고 기본 주소를 설정해 결제를 빠르게 진행하세요.",
    loading: "저장된 주소를 불러오는 중...",
    totalSaved: "저장된 주소",
    defaultStatus: "기본 상태",
    defaultReady: "기본 주소 준비 완료",
    noDefault: "기본 주소 없음",
    startNew: "새 주소",
    makeDefault: "기본 배송 주소로 설정",
    resetForm: "양식 초기화",
  },
  profile: {
    eyebrow: "프로필",
    title: "프로필 설정",
    description: "계정과 공개 프로필에 표시되는 정보를 관리하세요.",
    loading: "프로필 설정을 불러오는 중...",
    basicTitle: "기본 프로필",
    basicDescription: "공개 정보, 아바타, 소개.",
    professionalTitle: "개인 및 직업 정보",
    professionalDescription: "위치, 업무, 협업에 관한 추가 정보.",
    identityTitle: "계정 정보",
    identityDescription: "Shellfin 계정에 연결된 공유 정보.",
    locationLabel: "위치",
    locationPlaceholder: "도시 또는 국가",
    regionLabel: "지역",
    regionPlaceholder: "지역 또는 주",
    organizationLabel: "학교 또는 회사",
    organizationPlaceholder: "학교, 회사, 또는 스튜디오",
    websiteLabel: "웹사이트",
    websitePlaceholder: "https://your-site.com",
    portfolioLabel: "포트폴리오 URL",
    portfolioPlaceholder: "https://portfolio.com",
    collaborationLabel: "협업 의향 있음",
    collaborationHint: "이 공개 정보는 협업 문의를 환영하는지 알리는 데 도움이 됩니다.",
    emailLabel: "이메일",
    emailPlaceholder: "you@example.com",
    roleLabel: "역할",
    statusLabel: "계정 상태",
    memberSinceLabel: "가입일",
    emailVerificationLabel: "이메일 인증",
    emailVerifiedLabel: "이메일 인증 완료",
    emailNotVerifiedLabel: "이메일 미인증",
    avatarHint: "공개 프로필, 커뮤니티, 계정 화면에 표시됩니다.",
    publicPreviewTitle: "공개 미리보기",
    publicPreviewDescription: "공개 프로필에서 처음 보이는 정보입니다.",
    viewPublicProfile: "공개 프로필 보기",
    save: "변경사항 저장",
    saving: "저장 중...",
    success: "프로필이 업데이트되었습니다.",
    emailInvalid: "올바른 이메일 주소를 입력하세요.",
    urlInvalid: "올바른 URL을 입력하세요.",
  },
  community: {
    eyebrow: "커뮤니티",
    title: "커뮤니티 관리",
    description: "게시물, 댓글, 저장된 아이디어, 네트워크를 계정에서 바로 관리하세요.",
    loading: "커뮤니티 활동을 불러오는 중...",
    createPost: "게시물 작성",
    viewPublicProfile: "공개 프로필 보기",
    postsTitle: "내 게시물",
    commentsTitle: "최근 댓글",
    favoritesTitle: "저장한 게시물",
    followersTitle: "팔로워",
    followingTitle: "팔로잉",
    noFollowers: "아직 팔로워가 없습니다.",
    noFollowing: "아직 팔로우하는 사람이 없습니다.",
    networkDescription: "커뮤니티 네트워크를 프라이빗 관리 뷰에서 확인하세요.",
  },
  store: {
    eyebrow: "스토어",
    title: "스토어 활동",
    description: "쇼핑 진행 상황, 결제 준비 상태, 주문 후속 조치를 계정과 함께 관리하세요.",
    loading: "스토어 활동을 불러오는 중...",
    cartTitle: "현재 장바구니",
    cartEmpty: "장바구니가 비어 있습니다.",
    cartSubtotal: "소계",
    checkoutReadyTitle: "결제 준비 완료",
    checkoutReadyDescription: "기본 주소가 등록되어 있어 빠른 결제가 가능합니다.",
    checkoutNeedsAddress: "기본 주소를 추가하면 결제와 지원 흐름이 더 원활해집니다.",
    latestOrderTitle: "최근 주문",
    latestOrderEmpty: "아직 스토어 활동이 없습니다.",
    browseStore: "스토어 둘러보기",
    openCart: "장바구니 열기",
    viewCart: "장바구니 보기",
    goToCheckout: "결제로 이동",
    savedItemsTitle: "저장한 상품",
    savedItemsDescription: "위시리스트 기능은 현재 API에서 아직 지원되지 않습니다. 추후 계정을 재설계하지 않고도 추가할 수 있도록 구조가 준비되어 있습니다.",
  },
  settings: {
    eyebrow: "설정",
    title: "일반 설정",
    description: "계정 정보, 알림 상태, 세션 관리를 확인하세요.",
    detailsTitle: "계정 정보",
    notificationsTitle: "알림",
    notificationsDescription: "알림 설정은 아직 변경할 수 없지만 현재 읽지 않은 활동을 여기서 확인할 수 있습니다.",
    unreadCount: "{count}개 읽지 않은 알림",
    noUnread: "읽지 않은 알림이 없습니다.",
    securityTitle: "보안 및 개인정보",
    securityDescription: "비밀번호 관리와 고급 개인정보 설정은 현재 API에서 아직 지원되지 않습니다.",
    sessionTitle: "세션",
    sessionDescription: "로그아웃하면 공유 Shellfin 계정의 현재 프론트엔드 세션이 종료됩니다.",
    manageProfile: "프로필 관리",
    viewOrders: "주문 보기",
    signOut: "로그아웃",
    signingOut: "로그아웃 중...",
    defaultRole: "회원",
    activeStatus: "활성",
  },
}

const zh: AccountCopy = {
  shell: {
    eyebrow: "账户中心",
    title: "你的专属 Shellfin 私人工作台",
    description: "在一个结构清晰的界面中管理个人身份、购物记录、收货地址、社区内容与账户设置。",
    privateWorkspace: "私人账户工作台",
    signedInAs: "当前登录：{email}",
    publicProfile: "公开主页",
  },
  nav: {
    overview: "概览",
    orders: "订单",
    addresses: "地址",
    profile: "资料",
    community: "社区",
    store: "商店",
    settings: "设置",
  },
  publicProfile: {
    manageAccount: "账户中心",
    detailsTitle: "资料详情",
    detailsDescription: "帮助他人了解你的公开身份信息。",
    noDetails: "尚未公开任何个人资料详情。",
    completeProfile: "完善资料设置",
  },
  overview: {
    eyebrow: "概览",
    title: "账户概览",
    description: "全面了解购物、身份与社区活动的快照。",
    loading: "正在加载账户概览...",
    ordersLabel: "订单",
    addressesLabel: "地址",
    postsLabel: "帖子与评论",
    savedPostsLabel: "已收藏的社区帖子",
    cartLabel: "购物车商品",
    notificationsLabel: "未读通知",
    accountHealthTitle: "账户健康",
    accountHealthDescription: "完成基础设置，确保结账、个人资料和支持流程顺畅进行。",
    readyMessage: "你的账户已为当前 Shellfin 流程做好准备。",
    addBioAction: "添加公开简介",
    addDefaultAddressAction: "添加默认地址",
    placeFirstOrderAction: "下第一单",
    quickActionsTitle: "快捷操作",
    recentOrdersTitle: "最近订单",
    recentOrdersEmpty: "还没有订单。浏览系列来开始购物。",
    defaultAddressTitle: "默认地址",
    defaultAddressEmpty: "尚未设置默认配送地址。",
    latestNotificationsTitle: "最新通知",
    latestNotificationsEmpty: "暂无最新通知。",
    viewOrders: "查看订单",
    manageAddresses: "管理地址",
    editProfile: "编辑资料",
    manageCommunity: "管理社区",
    continueShopping: "继续购物",
    resumeCart: "继续购物车",
    browseCommunity: "打开社区",
    notificationFallback: "社区动态",
  },
  orders: {
    eyebrow: "订单",
    title: "订单历史",
    description: "在账户中心跟踪订单状态、查看详情并处理待取消的订单。",
    totalOrders: "全部订单",
    activeOrders: "进行中的订单",
    pendingOrders: "待取消的订单",
    loadingDetail: "正在加载订单详情...",
    orderNumberLabel: "订单号",
    copyNumber: "复制订单号",
    copied: "已复制",
    itemsTitle: "商品",
    shippingTitle: "配送地址",
    totalTitle: "订单总计",
    nextStepsTitle: "下一步是什么？",
    nextStepsDescription: "团队会人工审核每笔订单，并随着履单进展更新状态。",
    noteTitle: "客户备注",
    backToOrders: "返回订单",
    continueShopping: "继续购物",
  },
  addresses: {
    eyebrow: "地址",
    title: "地址簿",
    description: "整理配送信息，设置默认配送地址以加快结账流程。",
    loading: "正在加载已保存地址...",
    totalSaved: "已保存地址",
    defaultStatus: "默认状态",
    defaultReady: "默认地址已就绪",
    noDefault: "暂无默认地址",
    startNew: "新增地址",
    makeDefault: "设为默认配送地址",
    resetForm: "重置表单",
  },
  profile: {
    eyebrow: "资料",
    title: "资料设置",
    description: "管理账户和公开主页中展示的身份信息。",
    loading: "正在加载资料设置...",
    basicTitle: "基础资料",
    basicDescription: "公开展示的身份信息、头像和简介。",
    professionalTitle: "个人与职业信息",
    professionalDescription: "位置、工作和协作意向的补充信息。",
    identityTitle: "账户身份",
    identityDescription: "与你的 Shellfin 账号关联的共享信息。",
    locationLabel: "位置",
    locationPlaceholder: "城市或国家",
    regionLabel: "地区",
    regionPlaceholder: "地区或省份",
    organizationLabel: "学校或公司",
    organizationPlaceholder: "学校、公司或工作室",
    websiteLabel: "网站",
    websitePlaceholder: "https://your-site.com",
    portfolioLabel: "作品集链接",
    portfolioPlaceholder: "https://portfolio.com",
    collaborationLabel: "开放合作",
    collaborationHint: "这个公开信号有助于让他人了解你是否欢迎合作联系。",
    emailLabel: "邮箱",
    emailPlaceholder: "you@example.com",
    roleLabel: "角色",
    statusLabel: "账户状态",
    memberSinceLabel: "注册时间",
    emailVerificationLabel: "邮箱验证",
    emailVerifiedLabel: "邮箱已验证",
    emailNotVerifiedLabel: "邮箱未验证",
    avatarHint: "显示在公开主页、社区和账户各界面中。",
    publicPreviewTitle: "公开预览",
    publicPreviewDescription: "这是他人在你的公开主页上首先看到的身份摘要。",
    viewPublicProfile: "查看公开主页",
    save: "保存更改",
    saving: "正在保存...",
    success: "资料已更新。",
    emailInvalid: "请输入有效的邮箱地址。",
    urlInvalid: "请输入有效的 URL。",
  },
  community: {
    eyebrow: "社区",
    title: "社区管理",
    description: "在不影响其他账户功能的情况下，管理帖子、评论、已保存的创意和你的关注网络。",
    loading: "正在加载社区活动...",
    createPost: "发布帖子",
    viewPublicProfile: "查看公开主页",
    postsTitle: "我的帖子",
    commentsTitle: "最近评论",
    favoritesTitle: "已收藏帖子",
    followersTitle: "粉丝",
    followingTitle: "关注中",
    noFollowers: "还没有粉丝。",
    noFollowing: "还没有关注任何人。",
    networkDescription: "你的公开社区网络作为私人管理视图显示在此处。",
  },
  store: {
    eyebrow: "商店",
    title: "商店活动",
    description: "将购物进度、履单准备情况与订单跟进纳入账户整体管理。",
    loading: "正在加载商店活动...",
    cartTitle: "当前购物车",
    cartEmpty: "你的购物车目前为空。",
    cartSubtotal: "小计",
    checkoutReadyTitle: "结账准备完成",
    checkoutReadyDescription: "已有默认地址，可以更快速地完成结账。",
    checkoutNeedsAddress: "添加默认地址，让结账和支持流程更顺畅。",
    latestOrderTitle: "最新订单",
    latestOrderEmpty: "暂无最近的商店活动。",
    browseStore: "浏览商店",
    openCart: "打开购物车",
    viewCart: "查看购物车",
    goToCheckout: "前往结账",
    savedItemsTitle: "已保存商品",
    savedItemsDescription: "当前 API 暂不支持商店端的愿望清单功能。此区域已预留结构，后续可在无需重新设计账户的情况下直接添加。",
  },
  settings: {
    eyebrow: "设置",
    title: "通用设置",
    description: "查看账户信息、通知状态与会话控制。",
    detailsTitle: "账户详情",
    notificationsTitle: "通知",
    notificationsDescription: "通知偏好暂不支持自定义，但当前未读活动会显示在此处。",
    unreadCount: "{count} 条未读通知",
    noUnread: "没有未读通知。",
    securityTitle: "安全与隐私",
    securityDescription: "密码管理和高级隐私设置在当前 API 中暂不支持。",
    sessionTitle: "会话",
    sessionDescription: "使用退出登录结束此共享 Shellfin 账户的当前前端会话。",
    manageProfile: "管理资料",
    viewOrders: "查看订单",
    signOut: "退出登录",
    signingOut: "正在退出...",
    defaultRole: "成员",
    activeStatus: "活跃",
  },
}

const copies: Record<Locale, AccountCopy> = {
  en,
  ko,
  zh,
}

export function getAccountCopy(locale: Locale) {
  return copies[locale]
}
