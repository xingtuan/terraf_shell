import type { LocalizedValue } from "@/lib/i18n"

type RawCommunityIdeaRecord = {
  id: string
  image: string
  title: LocalizedValue<string>
  summary: LocalizedValue<string>
  stage: LocalizedValue<string>
  supportType: LocalizedValue<string>
  focus: LocalizedValue<string>
  tags: LocalizedValue<string[]>
}

export const communityIdeaRecords: RawCommunityIdeaRecord[] = [
  {
    id: "chef-table-collection",
    image: "/images/application-tableware.jpg",
    title: {
      en: "Chef's Table Capsule",
      ko: "셰프 테이블 캡슐",
      zh: "主厨餐桌限定系列",
    },
    summary: {
      en: "A limited dining collection co-developed with chefs exploring lighter premium service ware.",
      ko: "더 가벼운 프리미엄 서비스웨어를 탐구하는 셰프와 함께 개발하는 다이닝 컬렉션입니다.",
      zh: "与主厨共同开发的限量餐饮系列，探索更轻盈的高端器皿。",
    },
    stage: {
      en: "Prototype review",
      ko: "프로토타입 검토",
      zh: "原型评审",
    },
    supportType: {
      en: "Design collaboration",
      ko: "디자인 협업",
      zh: "设计协作",
    },
    focus: {
      en: "Hospitality",
      ko: "호스피탈리티",
      zh: "餐饮与酒店",
    },
    tags: {
      en: ["tableware", "chef-led", "sampling"],
      ko: ["테이블웨어", "셰프 협업", "샘플링"],
      zh: ["餐具", "主厨合作", "样品测试"],
    },
  },
  {
    id: "coastal-home-line",
    image: "/images/application-retail.jpg",
    title: {
      en: "Coastal Home Line",
      ko: "코스탈 홈 라인",
      zh: "海岸居家系列",
    },
    summary: {
      en: "A concept family of trays, stands, and home accents designed for calm domestic spaces.",
      ko: "차분한 주거 공간을 위한 트레이, 스탠드, 홈 액센트 콘셉트 라인입니다.",
      zh: "面向安静居家空间的托盘、支架与家居点缀概念系列。",
    },
    stage: {
      en: "Seeking retail partner",
      ko: "리테일 파트너 모집",
      zh: "寻找零售合作方",
    },
    supportType: {
      en: "Concept support",
      ko: "컨셉 지원",
      zh: "概念支持",
    },
    focus: {
      en: "Homeware",
      ko: "홈웨어",
      zh: "家居用品",
    },
    tags: {
      en: ["home objects", "retail", "branding"],
      ko: ["홈 오브제", "리테일", "브랜딩"],
      zh: ["家居物件", "零售", "品牌化"],
    },
  },
  {
    id: "material-lab-fund",
    image: "/images/process-refined.jpg",
    title: {
      en: "Shellfin Material Lab Fund",
      ko: "쉘핀 머티리얼 랩 펀드",
      zh: "Shellfin 材料实验基金",
    },
    summary: {
      en: "A small funding track for pilot moulds, sample sets, and student or studio-led concept testing.",
      ko: "파일럿 금형, 샘플 세트, 학생 및 스튜디오 콘셉트 테스트를 위한 소규모 펀드입니다.",
      zh: "面向试验模具、样品套组及学生或工作室概念测试的小型资助计划。",
    },
    stage: {
      en: "Open for applicants",
      ko: "지원 접수 중",
      zh: "开放申请",
    },
    supportType: {
      en: "Fundraising support",
      ko: "펀딩 지원",
      zh: "资金支持",
    },
    focus: {
      en: "Material experimentation",
      ko: "소재 실험",
      zh: "材料实验",
    },
    tags: {
      en: ["fund", "student studios", "pilot moulds"],
      ko: ["펀드", "학생 스튜디오", "파일럿 금형"],
      zh: ["资助", "学生工作室", "试验模具"],
    },
  },
]
