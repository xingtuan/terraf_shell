import type { LocalizedValue } from "@/lib/i18n"
import type { MaterialSpec } from "@/lib/types"

type RawMaterialSpecRecord = {
  id: string
  icon: MaterialSpec["icon"]
  label: LocalizedValue<string>
  value: LocalizedValue<string>
  detail: LocalizedValue<string>
}

type RawMaterialProcessStep = {
  id: string
  title: LocalizedValue<string>
  body: LocalizedValue<string>
}

type RawMaterialCertificationRecord = {
  id: string
  name: LocalizedValue<string>
  detail: LocalizedValue<string>
}

type RawMaterialContentRecord = {
  id: string
  title: LocalizedValue<string>
  subtitle: LocalizedValue<string>
  origin: {
    title: LocalizedValue<string>
    body: LocalizedValue<string>
  }
  propertiesTitle: LocalizedValue<string>
  processTitle: LocalizedValue<string>
  process: RawMaterialProcessStep[]
  certifications: {
    title: LocalizedValue<string>
    items: RawMaterialCertificationRecord[]
  }
}

export const materialSpecRecords: RawMaterialSpecRecord[] = [
  {
    id: "weight",
    icon: "feather",
    label: {
      en: "Weight",
      ko: "무게",
      zh: "重量",
    },
    value: {
      en: "35% lighter than ceramics",
      ko: "기존 도자기 대비 35% 경량",
      zh: "比传统陶瓷轻 35%",
    },
    detail: {
      en: "Specific gravity 1.5-1.6 versus roughly 2.4 for traditional ceramic bodies.",
      ko: "비중 1.5-1.6으로 일반 세라믹 약 2.4 대비 훨씬 가볍게 설계되었습니다.",
      zh: "比重为 1.5-1.6，而传统陶瓷约为 2.4，拿取负担更低。",
    },
  },
  {
    id: "strength",
    icon: "shield",
    label: {
      en: "Strength",
      ko: "강도",
      zh: "强度",
    },
    value: {
      en: "High impact resistance",
      ko: "높은 내충격성",
      zh: "高抗冲击性",
    },
    detail: {
      en: "Compression-moulded shell pellets create an unbreakable body suited to repeated service use.",
      ko: "압축성형된 셸 펠릿 구조가 반복 사용에도 버티는 높은 내충격성과 비파손 특성을 제공합니다.",
      zh: "压缩成型后的贝壳颗粒结构具备高抗冲击性，适合高频重复使用。",
    },
  },
  {
    id: "absorption",
    icon: "badge",
    label: {
      en: "Water Absorption",
      ko: "흡수율",
      zh: "吸水率",
    },
    value: {
      en: "0.00% absorption",
      ko: "흡수율 0.00%",
      zh: "吸水率 0.00%",
    },
    detail: {
      en: "A zero-absorption body helps prevent odour retention and reduces conditions for bacterial growth.",
      ko: "수분을 흡수하지 않아 냄새 배임을 줄이고 세균 번식 환경을 최소화합니다.",
      zh: "本体不吸水，可降低异味残留并减少细菌滋生条件。",
    },
  },
  {
    id: "antibacterial",
    icon: "leaf",
    label: {
      en: "Antibacterial",
      ko: "항균성",
      zh: "抗菌性",
    },
    value: {
      en: "Natural weak-alkaline antibacterial",
      ko: "천연 약알칼리 항균",
      zh: "天然弱碱性抗菌",
    },
    detail: {
      en: "Shell minerals provide antibacterial performance without relying on artificial surface coatings.",
      ko: "굴 껍데기 유래 미네랄이 인공 코팅 없이도 자연스러운 항균 포지션을 제공합니다.",
      zh: "牡蛎壳矿物本身具备天然弱碱性抗菌特性，无需额外人工涂层。",
    },
  },
  {
    id: "grip",
    icon: "shield",
    label: {
      en: "Grip Texture",
      ko: "그립감",
      zh: "握持触感",
    },
    value: {
      en: "Mineral non-slip texture",
      ko: "미네랄 논슬립 텍스처",
      zh: "矿物防滑纹理",
    },
    detail: {
      en: "The mineral surface keeps a confident grip even when the object is wet.",
      ko: "젖은 상태에서도 미끄러지지 않는 미네랄 그립 텍스처를 유지합니다.",
      zh: "即使在潮湿状态下也能保持稳定不打滑的矿物握持感。",
    },
  },
  {
    id: "otr",
    icon: "badge",
    label: {
      en: "OTR",
      ko: "산소투과도",
      zh: "氧气透过率",
    },
    value: {
      en: "500 cc/m²·day",
      ko: "500 cc/m²·day",
      zh: "500 cc/m²·day",
    },
    detail: {
      en: "Breathable yet moisture-blocking performance supports packaging, wellness, and architectural use cases.",
      ko: "통기성과 차습성을 함께 제공해 패키징, 웰니스, 건축 적용까지 확장 가능한 데이터입니다.",
      zh: "兼具可呼吸性与阻湿表现，可延展到包装、康养与建筑类应用。",
    },
  },
]

export const oxpMaterialRecord: RawMaterialContentRecord = {
  id: "oxp-oyster-shell-material",
  title: {
    en: "OXP Oyster Shell Material",
    ko: "OXP 굴패각 소재",
    zh: "OXP 牡蛎壳材料",
  },
  subtitle: {
    en: "Premium compression-moulded material made from recycled oyster shell pellets.",
    ko: "재생 굴패각 펠릿으로 압축성형한 프리미엄 소재입니다.",
    zh: "由再生牡蛎壳颗粒压缩成型的高端材料。",
  },
  origin: {
    title: {
      en: "Origin Story",
      ko: "원료 스토리",
      zh: "材料起源",
    },
    body: {
      en: "OXP starts with discarded oyster shell waste from coastal food and aquaculture streams. The shells are cleaned, purified, milled into mineral-rich pellets, and remade into premium products through compression moulding.",
      ko: "OXP은 해안 양식과 식음 산업에서 버려지는 굴패각 폐기물에서 시작합니다. 수거된 패각은 세척과 정제 과정을 거쳐 미네랄이 풍부한 펠릿으로 만들어지고, 다시 압축성형을 통해 프리미엄 제품으로 재탄생합니다.",
      zh: "OXP 始于沿海养殖与餐饮体系中被丢弃的牡蛎壳废弃物。回收后的贝壳经过清洗、净化与粉体化，制成富含矿物的颗粒，再通过压缩成型转化为高端成品。",
    },
  },
  propertiesTitle: {
    en: "Key Properties",
    ko: "핵심 물성",
    zh: "核心性能",
  },
  processTitle: {
    en: "Manufacturing Process",
    ko: "제조 공정",
    zh: "制造流程",
  },
  process: [
    {
      id: "collection",
      title: {
        en: "Collection",
        ko: "수거",
        zh: "回收收集",
      },
      body: {
        en: "Recovered oyster shell waste is collected from coastal processing streams and sorted for material recovery.",
        ko: "해안 가공 공정에서 발생한 굴패각 폐기물을 수거하고 소재화 가능한 원료만 선별합니다.",
        zh: "从沿海加工链回收牡蛎壳废弃物，并筛选出适合材料化的原料。",
      },
    },
    {
      id: "thermal-purification",
      title: {
        en: "Thermal Purification",
        ko: "열 정화",
        zh: "热净化",
      },
      body: {
        en: "The shells are thermally purified between 200-700°C to sterilize the material and develop the Ocean Bone and Forged Ash mineral tones.",
        ko: "패각은 200-700°C 열 정화 공정을 거치며 살균되고 Ocean Bone과 Forged Ash 컬러 톤이 형성됩니다.",
        zh: "贝壳在 200-700°C 的热净化工艺中完成杀菌，并形成 Ocean Bone 与 Forged Ash 两种矿物色调。",
      },
    },
    {
      id: "pellets",
      title: {
        en: "Pelletizing",
        ko: "펠릿화",
        zh: "制粒",
      },
      body: {
        en: "Purified shell minerals are refined into OXP pellets for stable forming, scalable batching, and repeatable quality control.",
        ko: "정제된 패각 미네랄은 안정적인 성형과 일관된 품질 관리를 위해 OXP 펠릿으로 가공됩니다.",
        zh: "净化后的贝壳矿物被精制成 OXP 颗粒，以支持稳定成型、批次控制与一致品质。",
      },
    },
    {
      id: "compression-moulding",
      title: {
        en: "Compression Moulding",
        ko: "압축성형",
        zh: "压缩成型",
      },
      body: {
        en: "The pellets are moulded under pressure into lightweight, high-impact products for tableware, planters, wellness objects, and architectural uses.",
        ko: "펠릿은 압축성형을 통해 가볍고 충격에 강한 테이블웨어, 플랜터, 웰니스 오브제, 건축용 제품으로 성형됩니다.",
        zh: "颗粒通过压缩成型转化为轻量且耐冲击的餐具、花器、康养家居与建筑类产品。",
      },
    },
  ],
  certifications: {
    title: {
      en: "Certifications & Test Data",
      ko: "인증 및 시험 데이터",
      zh: "认证与测试数据",
    },
    items: [
      {
        id: "water-absorption",
        name: {
          en: "Water Absorption 0.00%",
          ko: "흡수율 0.00%",
          zh: "吸水率 0.00%",
        },
        detail: {
          en: "Validated as a zero-absorption material body.",
          ko: "소재 본체 기준 흡수율 0.00% 데이터입니다.",
          zh: "验证为本体吸水率 0.00% 的材料。",
        },
      },
      {
        id: "toxicity-heavy-metals",
        name: {
          en: "Toxicity / Heavy Metals",
          ko: "유해성 / 중금속",
          zh: "毒性 / 重金属",
        },
        detail: {
          en: "Prepared for heavy-metal and toxicity review in food-contact and interior contexts.",
          ko: "식기 및 인테리어 적용을 위한 유해성 및 중금속 검토 항목입니다.",
          zh: "面向餐具与室内应用的毒性及重金属检测项目。",
        },
      },
      {
        id: "acid-resistance",
        name: {
          en: "Acid Resistance",
          ko: "내산성",
          zh: "耐酸性",
        },
        detail: {
          en: "Supports food-service and daily-use environments that require chemical stability.",
          ko: "식음 환경과 일상 사용에서 요구되는 화학적 안정성을 검토하는 항목입니다.",
          zh: "用于评估餐饮与日常使用环境中的化学稳定性。",
        },
      },
      {
        id: "non-toxic-fireproof",
        name: {
          en: "Non-Toxic Fireproof",
          ko: "무독성 난연",
          zh: "无毒阻燃",
        },
        detail: {
          en: "Confirms the material platform can be reviewed for safe, fire-resistant applications.",
          ko: "안전성과 난연 적용 가능성을 함께 검토하는 항목입니다.",
          zh: "用于验证材料在安全性与阻燃应用层面的可评估性。",
        },
      },
      {
        id: "otr-data",
        name: {
          en: "OTR Data",
          ko: "OTR 데이터",
          zh: "OTR 数据",
        },
        detail: {
          en: "Oxygen transmission data recorded at 500 cc/m²·day.",
          ko: "산소투과도 500 cc/m²·day 시험 데이터입니다.",
          zh: "氧气透过率测试数据为 500 cc/m²·day。",
        },
      },
    ],
  },
}
