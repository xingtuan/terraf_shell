import type { LocalizedValue } from "@/lib/i18n"

type RawCategoryRecord = {
  id: string
  label: LocalizedValue<string>
  description: LocalizedValue<string>
}

type RawProductRecord = {
  id: string
  slug: string
  categoryId: string
  image: string
  priceFrom: number
  currency: "KRW"
  featured: boolean
  availability: LocalizedValue<string>
  name: LocalizedValue<string>
  description: LocalizedValue<string>
  features: LocalizedValue<string[]>
}

export const productCategoryRecords: RawCategoryRecord[] = [
  {
    id: "tableware",
    label: {
      en: "Tableware",
      ko: "테이블웨어",
      zh: "餐具",
    },
    description: {
      en: "Dining pieces shaped for everyday rituals and premium hospitality.",
      ko: "일상 식탁과 프리미엄 호스피탈리티를 위한 다이닝 오브제.",
      zh: "为日常餐桌与高端餐饮空间打造的用餐器物。",
    },
  },
  {
    id: "home-objects",
    label: {
      en: "Home Objects",
      ko: "홈 오브제",
      zh: "家居物件",
    },
    description: {
      en: "Quiet, tactile pieces that introduce the material story into the home.",
      ko: "소재의 이야기를 집 안으로 가져오는 고요한 촉감의 오브제.",
      zh: "以安静触感将材料故事带入居家的物件。",
    },
  },
  {
    id: "gift-sets",
    label: {
      en: "Gift Sets",
      ko: "기프트 세트",
      zh: "礼盒系列",
    },
    description: {
      en: "Curated sets for boutique retail, concept gifting, and special launches.",
      ko: "부티크 리테일과 브랜드 출시를 위한 큐레이션 세트.",
      zh: "适用于精品零售、品牌发布与概念赠礼的组合。",
    },
  },
]

export const productRecords: RawProductRecord[] = [
  {
    id: "tidal-dinner-plate",
    slug: "tidal-dinner-plate",
    categoryId: "tableware",
    image: "/images/application-tableware.jpg",
    priceFrom: 68000,
    currency: "KRW",
    featured: true,
    availability: {
      en: "Made in small runs",
      ko: "소량 생산",
      zh: "小批量制作",
    },
    name: {
      en: "Tidal Dinner Plate",
      ko: "타이달 디너 플레이트",
      zh: "Tidal 晚餐盘",
    },
    description: {
      en: "A refined dinner plate with a soft mineral edge and quiet shell luminosity.",
      ko: "은은한 패각 광택과 부드러운 미네랄 엣지를 담은 디너 플레이트.",
      zh: "带有柔和矿物边缘与低调贝壳光泽的晚餐盘。",
    },
    features: {
      en: ["Shellfin composite", "Compress-moulded form", "Lighter carry weight"],
      ko: ["쉘핀 복합 소재", "압축 성형", "가벼운 사용감"],
      zh: ["Shellfin 复合材料", "压缩模塑", "更轻的使用重量"],
    },
  },
  {
    id: "harbor-serving-bowl",
    slug: "harbor-serving-bowl",
    categoryId: "tableware",
    image: "/images/application-interior.jpg",
    priceFrom: 92000,
    currency: "KRW",
    featured: true,
    availability: {
      en: "Available for hospitality packs",
      ko: "호스피탈리티 패키지 가능",
      zh: "可用于餐饮项目配套",
    },
    name: {
      en: "Harbor Serving Bowl",
      ko: "하버 서빙 볼",
      zh: "Harbor 分享碗",
    },
    description: {
      en: "A generous serving bowl developed for premium dining rooms and boutique stays.",
      ko: "프리미엄 다이닝과 부티크 숙소를 위해 개발된 넉넉한 서빙 볼.",
      zh: "为高端餐饮与精品酒店开发的大容量分享碗。",
    },
    features: {
      en: ["Durable rim", "Warm matte finish", "Hospitality-ready sizing"],
      ko: ["내구성 있는 림", "따뜻한 매트 마감", "호스피탈리티 규격"],
      zh: ["耐用边缘", "温润哑光表面", "适配餐饮空间尺寸"],
    },
  },
  {
    id: "shore-catchall",
    slug: "shore-catchall",
    categoryId: "home-objects",
    image: "/images/material-texture.jpg",
    priceFrom: 54000,
    currency: "KRW",
    featured: false,
    availability: {
      en: "Ready for online pre-order",
      ko: "온라인 프리오더 가능",
      zh: "可在线预订",
    },
    name: {
      en: "Shore Catchall",
      ko: "쇼어 캐치올",
      zh: "Shore 收纳盘",
    },
    description: {
      en: "A compact tray for jewelry, keys, and quiet daily rituals.",
      ko: "주얼리와 열쇠, 일상 소품을 위한 컴팩트 트레이.",
      zh: "适合首饰、钥匙与日常小物的紧凑托盘。",
    },
    features: {
      en: ["Dense mineral touch", "Home styling accent", "Natural shell speckle"],
      ko: ["밀도감 있는 촉감", "홈 스타일링 포인트", "자연스러운 패각 입자"],
      zh: ["紧实矿物触感", "家居陈列点缀", "自然贝壳纹理颗粒"],
    },
  },
  {
    id: "atelier-gift-set",
    slug: "atelier-gift-set",
    categoryId: "gift-sets",
    image: "/images/application-retail.jpg",
    priceFrom: 148000,
    currency: "KRW",
    featured: false,
    availability: {
      en: "Concept launch edition",
      ko: "컨셉 런칭 에디션",
      zh: "概念发布限量版",
    },
    name: {
      en: "Atelier Gift Set",
      ko: "아틀리에 기프트 세트",
      zh: "Atelier 礼盒套组",
    },
    description: {
      en: "A pairing of signature objects designed for premium gifting and retail displays.",
      ko: "프리미엄 기프트와 리테일 디스플레이를 위한 시그니처 오브제 세트.",
      zh: "适用于高端赠礼与零售陈列的标志性组合。",
    },
    features: {
      en: ["Curated pairings", "Brand-ready packaging", "Limited seasonal release"],
      ko: ["큐레이션 구성", "브랜드 패키징 대응", "시즌 한정 출시"],
      zh: ["精选组合", "支持品牌化包装", "季节限定发售"],
    },
  },
]
