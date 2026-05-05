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
      en: "Lower-density target",
      ko: "저밀도 목표",
      zh: "低密度目标",
    },
    detail: {
      en: "Weight depends on final formulation, moulding, and product geometry.",
      ko: "무게는 최종 배합, 성형 조건, 제품 형상에 따라 달라집니다.",
      zh: "重量取决于最终配方、成型条件和产品结构。",
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
      en: "Compression-moulding compatible",
      ko: "압축성형 호환",
      zh: "适配压缩成型",
    },
    detail: {
      en: "Durability should be confirmed against the final product shape and use case.",
      ko: "내구성은 최종 제품 형상과 사용 환경에 따라 확인해야 합니다.",
      zh: "耐用性需结合最终产品形态和使用场景确认。",
    },
  },
  {
    id: "absorption",
    icon: "badge",
    label: {
      en: "Water Resistance",
      ko: "내수성",
      zh: "耐水性",
    },
    value: {
      en: "Testing pending",
      ko: "시험 대기",
      zh: "测试待确认",
    },
    detail: {
      en: "Water performance depends on final formulation and verified test conditions.",
      ko: "내수 성능은 최종 배합과 검증된 시험 조건에 따라 달라집니다.",
      zh: "耐水表现取决于最终配方和已验证的测试条件。",
    },
  },
  {
    id: "antibacterial",
    icon: "leaf",
    label: {
      en: "Surface Hygiene Review",
      ko: "표면 위생 검토",
      zh: "表面卫生评估",
    },
    value: {
      en: "Application testing required",
      ko: "적용 시험 필요",
      zh: "需按应用测试",
    },
    detail: {
      en: "Food-contact or hygiene claims should be supported by approved documents.",
      ko: "식품 접촉 또는 위생 관련 주장은 승인된 문서로 뒷받침되어야 합니다.",
      zh: "食品接触或卫生相关声明需由已批准文件支持。",
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
      en: "Mineral texture",
      ko: "미네랄 질감",
      zh: "矿物质感",
    },
    detail: {
      en: "Grip and slip resistance should be checked for the final surface finish.",
      ko: "그립감과 미끄럼 저항은 최종 표면 마감 기준으로 확인해야 합니다.",
      zh: "握持与防滑表现需按最终表面处理进行确认。",
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
      en: "Data on request",
      ko: "자료 요청 가능",
      zh: "数据可按需提供",
    },
    detail: {
      en: "Barrier and breathability data should be reviewed against the intended application.",
      ko: "차단성과 통기성 데이터는 적용 목적에 맞춰 검토해야 합니다.",
      zh: "阻隔与透气数据应结合目标应用进行审查。",
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
          en: "Water Absorption Test",
          ko: "흡수율 시험",
          zh: "吸水率测试",
        },
        detail: {
          en: "Certification data is being prepared for publication.",
          ko: "게시용 인증 데이터가 준비 중입니다.",
          zh: "用于发布的认证数据正在准备中。",
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
          en: "Review scope depends on final application and client testing requirements.",
          ko: "검토 범위는 최종 적용 분야와 고객 시험 요구사항에 따라 달라집니다.",
          zh: "评估范围取决于最终应用和客户测试要求。",
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
          en: "Chemical stability should be confirmed under the intended use conditions.",
          ko: "화학적 안정성은 의도된 사용 조건에서 확인해야 합니다.",
          zh: "化学稳定性应在目标使用条件下确认。",
        },
      },
      {
        id: "non-toxic-fireproof",
        name: {
          en: "Fire Behaviour Review",
          ko: "화재 거동 검토",
          zh: "燃烧行为评估",
        },
        detail: {
          en: "Fire-related claims require application-specific test documents.",
          ko: "화재 관련 주장은 적용 분야별 시험 문서가 필요합니다.",
          zh: "与燃烧相关的声明需要按应用提供测试文件。",
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
          en: "Barrier and breathability data can be shared when available.",
          ko: "차단성과 통기성 데이터는 준비된 경우 공유할 수 있습니다.",
          zh: "阻隔与透气数据可在可用时共享。",
        },
      },
    ],
  },
}
