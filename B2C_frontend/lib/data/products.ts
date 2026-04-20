import type { LocalizedValue } from "@/lib/i18n"

type ProductModel = "1.5 Lite" | "1.6 Heritage"
type ProductFinish = "Glossy" | "Matte"
type ProductColor = "Ocean Bone" | "Forged Ash"
type ProductTechnique = "Original Pure" | "Precision Inlay" | "Driftwood Blend"
type ProductCategory =
  | "Tableware"
  | "Planters"
  | "Wellness & Interior"
  | "Architectural"

type RawCategoryRecord = {
  id: string
  label: LocalizedValue<string>
  description: LocalizedValue<string>
}

type RawProductRecord = {
  id: string
  slug: string
  name: LocalizedValue<string>
  description: LocalizedValue<string>
  model: ProductModel
  finish: ProductFinish
  color: ProductColor
  technique: ProductTechnique
  category: ProductCategory
  price: number
  currency: "USD"
  inStock: true
  image: string
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
      en: "Plates, bowls, and service pieces shaped for premium dining.",
      ko: "프리미엄 다이닝을 위한 플레이트, 볼, 서빙 피스입니다.",
      zh: "面向高端用餐场景的盘、碗与服务器皿。",
    },
  },
  {
    id: "planters",
    label: {
      en: "Planters",
      ko: "플랜터",
      zh: "花器",
    },
    description: {
      en: "Lightweight planters with shell-mineral tactility for indoor and outdoor use.",
      ko: "실내외 공간에 어울리는 경량 쉘 미네랄 플랜터입니다.",
      zh: "兼具轻量与贝壳矿物质感的室内外花器。",
    },
  },
  {
    id: "wellness-interior",
    label: {
      en: "Wellness & Interior",
      ko: "웰니스 & 인테리어",
      zh: "康养与家居",
    },
    description: {
      en: "Trays and interior objects designed for calm rituals and wellness settings.",
      ko: "차분한 리추얼과 웰니스 공간을 위한 트레이와 인테리어 오브제입니다.",
      zh: "用于康养场景与静心日常的托盘及家居物件。",
    },
  },
  {
    id: "architectural",
    label: {
      en: "Architectural",
      ko: "건축",
      zh: "建筑",
    },
    description: {
      en: "Compression-moulded panels and tiles for breathable, moisture-aware surfaces.",
      ko: "통기성과 차습 특성을 고려한 압축성형 패널 및 타일입니다.",
      zh: "兼顾可呼吸性与阻湿表现的压缩成型板材与砖面。",
    },
  },
]

export const productRecords: RawProductRecord[] = [
  {
    id: "dinner-plate-lite-ocean-bone",
    slug: "dinner-plate-lite-ocean-bone",
    name: {
      en: "Dinner Plate - 1.5 Lite / Ocean Bone",
      ko: "디너 플레이트 - 1.5 Lite / Ocean Bone",
      zh: "晚餐盘 - 1.5 Lite / Ocean Bone",
    },
    description: {
      en: "A glossy lightweight dinner plate built for premium daily service.",
      ko: "프리미엄 일상 식탁을 위한 글로시 경량 디너 플레이트입니다.",
      zh: "适合高端日常餐桌的亮面轻量晚餐盘。",
    },
    model: "1.5 Lite",
    finish: "Glossy",
    color: "Ocean Bone",
    technique: "Original Pure",
    category: "Tableware",
    price: 45,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Dinner+Plate",
  },
  {
    id: "side-plate-lite-forged-ash",
    slug: "side-plate-lite-forged-ash",
    name: {
      en: "Side Plate - 1.5 Lite / Forged Ash",
      ko: "사이드 플레이트 - 1.5 Lite / Forged Ash",
      zh: "边盘 - 1.5 Lite / Forged Ash",
    },
    description: {
      en: "A slim glossy side plate with a forged grey mineral tone.",
      ko: "단조 회색 미네랄 톤을 가진 슬림한 글로시 사이드 플레이트입니다.",
      zh: "带有锻灰矿物色调的纤薄亮面边盘。",
    },
    model: "1.5 Lite",
    finish: "Glossy",
    color: "Forged Ash",
    technique: "Precision Inlay",
    category: "Tableware",
    price: 48,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Side+Plate",
  },
  {
    id: "coupe-bowl-heritage-ocean-bone",
    slug: "coupe-bowl-heritage-ocean-bone",
    name: {
      en: "Coupe Bowl - 1.6 Heritage / Ocean Bone",
      ko: "쿠프 볼 - 1.6 Heritage / Ocean Bone",
      zh: "浅口碗 - 1.6 Heritage / Ocean Bone",
    },
    description: {
      en: "A matte coupe bowl with a calm mineral texture for plated courses.",
      ko: "플레이팅 코스에 어울리는 차분한 미네랄 텍스처의 매트 쿠프 볼입니다.",
      zh: "适合精致摆盘的哑光矿物浅口碗。",
    },
    model: "1.6 Heritage",
    finish: "Matte",
    color: "Ocean Bone",
    technique: "Original Pure",
    category: "Tableware",
    price: 62,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Coupe+Bowl",
  },
  {
    id: "serving-bowl-heritage-forged-ash",
    slug: "serving-bowl-heritage-forged-ash",
    name: {
      en: "Serving Bowl - 1.6 Heritage / Forged Ash",
      ko: "서빙 볼 - 1.6 Heritage / Forged Ash",
      zh: "分享碗 - 1.6 Heritage / Forged Ash",
    },
    description: {
      en: "A matte hospitality-scale serving bowl with higher impact resistance.",
      ko: "높은 내충격성을 갖춘 호스피탈리티 스케일의 매트 서빙 볼입니다.",
      zh: "适合餐饮服务场景、具备高抗冲击性的哑光分享碗。",
    },
    model: "1.6 Heritage",
    finish: "Matte",
    color: "Forged Ash",
    technique: "Driftwood Blend",
    category: "Tableware",
    price: 78,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Serving+Bowl",
  },
  {
    id: "countertop-planter-heritage-ocean-bone",
    slug: "countertop-planter-heritage-ocean-bone",
    name: {
      en: "Countertop Planter - 1.6 Heritage / Ocean Bone",
      ko: "카운터탑 플랜터 - 1.6 Heritage / Ocean Bone",
      zh: "台面花器 - 1.6 Heritage / Ocean Bone",
    },
    description: {
      en: "A matte planter that balances breathable OTR performance with a stable shell body.",
      ko: "통기성과 안정적인 셸 바디를 함께 고려한 매트 플랜터입니다.",
      zh: "兼顾透气表现与稳定壳体结构的哑光花器。",
    },
    model: "1.6 Heritage",
    finish: "Matte",
    color: "Ocean Bone",
    technique: "Original Pure",
    category: "Planters",
    price: 85,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Planter",
  },
  {
    id: "wellness-tray-lite-ocean-bone",
    slug: "wellness-tray-lite-ocean-bone",
    name: {
      en: "Wellness Tray - 1.5 Lite / Ocean Bone",
      ko: "웰니스 트레이 - 1.5 Lite / Ocean Bone",
      zh: "康养托盘 - 1.5 Lite / Ocean Bone",
    },
    description: {
      en: "A glossy tray for tea rituals, spa amenities, and bathroom styling.",
      ko: "티 리추얼, 스파 어메니티, 욕실 스타일링에 맞는 글로시 트레이입니다.",
      zh: "适合茶席、SPA 配件与浴室陈列的亮面托盘。",
    },
    model: "1.5 Lite",
    finish: "Glossy",
    color: "Ocean Bone",
    technique: "Precision Inlay",
    category: "Wellness & Interior",
    price: 68,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Wellness+Tray",
  },
  {
    id: "aroma-object-heritage-forged-ash",
    slug: "aroma-object-heritage-forged-ash",
    name: {
      en: "Aroma Object - 1.6 Heritage / Forged Ash",
      ko: "아로마 오브제 - 1.6 Heritage / Forged Ash",
      zh: "香薰摆件 - 1.6 Heritage / Forged Ash",
    },
    description: {
      en: "A matte interior object that highlights Shellfin's non-slip mineral touch.",
      ko: "Shellfin 특유의 논슬립 미네랄 촉감을 강조한 매트 인테리어 오브제입니다.",
      zh: "突出 Shellfin 防滑矿物触感的哑光家居摆件。",
    },
    model: "1.6 Heritage",
    finish: "Matte",
    color: "Forged Ash",
    technique: "Driftwood Blend",
    category: "Wellness & Interior",
    price: 92,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Aroma+Object",
  },
  {
    id: "panel-tile-heritage-forged-ash",
    slug: "panel-tile-heritage-forged-ash",
    name: {
      en: "Panel Tile - 1.6 Heritage / Forged Ash",
      ko: "패널 타일 - 1.6 Heritage / Forged Ash",
      zh: "板材砖 - 1.6 Heritage / Forged Ash",
    },
    description: {
      en: "A compression-moulded architectural tile developed for breathable, moisture-aware surfaces.",
      ko: "통기성과 차습 특성을 고려한 압축성형 건축용 타일입니다.",
      zh: "面向可呼吸、阻湿表面的压缩成型建筑砖面。",
    },
    model: "1.6 Heritage",
    finish: "Matte",
    color: "Forged Ash",
    technique: "Precision Inlay",
    category: "Architectural",
    price: 120,
    currency: "USD",
    inStock: true,
    image: "https://placehold.co/600x400?text=Shellfin+Panel+Tile",
  },
]
