import type { LocalizedValue } from "@/lib/i18n"
import type { MaterialSpec } from "@/lib/types"

type RawMaterialSpecRecord = {
  id: string
  icon: MaterialSpec["icon"]
  label: LocalizedValue<string>
  value: LocalizedValue<string>
  detail: LocalizedValue<string>
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
      en: "Lighter body",
      ko: "더 가벼운 바디",
      zh: "更轻的材质体量",
    },
    detail: {
      en: "Engineered to feel lighter in hand than traditional porcelain formats.",
      ko: "기존 도자기 대비 손에 들었을 때 더 가볍게 느껴지도록 설계되었습니다.",
      zh: "相较传统瓷器规格，手持体验更轻盈。",
    },
  },
  {
    id: "durability",
    icon: "shield",
    label: {
      en: "Durability",
      ko: "내구성",
      zh: "耐久性",
    },
    value: {
      en: "Compress-moulded strength",
      ko: "압축 성형 강도",
      zh: "压缩模塑强度",
    },
    detail: {
      en: "Dense pellet-based forming supports stronger edges and dependable daily use.",
      ko: "고밀도 펠릿 성형으로 가장자리 강도와 일상 사용성을 높였습니다.",
      zh: "高密度颗粒成型可提供更强边缘与稳定的日常使用表现。",
    },
  },
  {
    id: "health",
    icon: "leaf",
    label: {
      en: "Health Positioning",
      ko: "건강 지향성",
      zh: "健康导向",
    },
    value: {
      en: "Natural mineral story",
      ko: "자연 유래 미네랄 스토리",
      zh: "天然矿物叙事",
    },
    detail: {
      en: "Built around reclaimed oyster shell minerals for health-conscious brand positioning.",
      ko: "재생 굴 패각 미네랄을 기반으로 건강을 중시하는 브랜드 포지셔닝에 적합합니다.",
      zh: "以再生牡蛎壳矿物为基础，适合强调健康意识的品牌定位。",
    },
  },
  {
    id: "traceability",
    icon: "badge",
    label: {
      en: "Material Sheet",
      ko: "소재 시트",
      zh: "材料说明",
    },
    value: {
      en: "Ready for review",
      ko: "검토용 준비 완료",
      zh: "可供审核",
    },
    detail: {
      en: "Sample documentation can support material reviews, sourcing discussions, and pilot programs.",
      ko: "샘플 문서는 소재 검토, 소싱 논의, 파일럿 프로그램에 활용할 수 있습니다.",
      zh: "样本文件可用于材料评估、采购讨论与试点项目。",
    },
  },
]
