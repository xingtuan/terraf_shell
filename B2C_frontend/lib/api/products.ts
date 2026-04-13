import { productCategoryRecords, productRecords } from "@/lib/data/products"
import {
  getIntlLocale,
  pickLocalizedValue,
  type Locale,
} from "@/lib/i18n"
import type { Product, ProductCategory } from "@/lib/types"

export async function getProductCategories(
  locale: Locale,
): Promise<ProductCategory[]> {
  // Intentionally mock-only until a real commerce API exists.
  return productCategoryRecords.map((category) => ({
    id: category.id,
    label: pickLocalizedValue(category.label, locale),
    description: pickLocalizedValue(category.description, locale),
  }))
}

export async function getProducts(locale: Locale): Promise<Product[]> {
  // Intentionally mock-only until a real product listing endpoint exists.
  const categories = await getProductCategories(locale)
  const categoryMap = new Map(
    categories.map((category) => [category.id, category.label]),
  )
  const formatter = new Intl.NumberFormat(getIntlLocale(locale), {
    style: "currency",
    currency: "KRW",
    maximumFractionDigits: 0,
  })

  return productRecords.map((product) => ({
    id: product.id,
    slug: product.slug,
    name: pickLocalizedValue(product.name, locale),
    description: pickLocalizedValue(product.description, locale),
    categoryId: product.categoryId,
    categoryLabel: categoryMap.get(product.categoryId) ?? product.categoryId,
    image: product.image,
    priceFrom: product.priceFrom,
    currency: product.currency,
    priceLabel: formatter.format(product.priceFrom),
    availability: pickLocalizedValue(product.availability, locale),
    features: pickLocalizedValue(product.features, locale),
    featured: product.featured,
  }))
}
