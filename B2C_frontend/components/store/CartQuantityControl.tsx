"use client"

import { useEffect, useId, useRef, useState } from "react"
import { Minus, Plus } from "lucide-react"

import { cn } from "@/lib/utils"

export type CartQuantityControlLabels = {
  quantityInput: string
  decreaseQuantity: string
  increaseQuantity: string
  quantityUnavailable: string
  onlyAvailable: string
  enterValidQuantity: string
  maxQuantityReached: string
  updatingQuantity?: string
}

type CartQuantityControlProps = {
  quantity: number
  min: number
  max?: number | null
  disabled?: boolean
  loading?: boolean
  error?: string | null
  onCommit: (nextQuantity: number) => void | Promise<void>
  onRemove?: () => void | Promise<void>
  labels: CartQuantityControlLabels
  className?: string
}

function formatCountMessage(template: string, count: number) {
  return template.replace("{count}", String(count))
}

function maxQuantityMessage(
  max: number,
  labels: CartQuantityControlLabels,
) {
  if (max <= 0) {
    return labels.quantityUnavailable
  }

  return formatCountMessage(labels.onlyAvailable, max)
}

export function CartQuantityControl({
  quantity,
  min,
  max = null,
  disabled = false,
  loading = false,
  error = null,
  onCommit,
  onRemove,
  labels,
  className,
}: CartQuantityControlProps) {
  const messageId = useId()
  const skipBlurCommitRef = useRef(false)
  const [inputValue, setInputValue] = useState(String(quantity))
  const [localMessage, setLocalMessage] = useState<string | null>(null)
  const normalizedMax =
    typeof max === "number" && Number.isFinite(max) ? Math.max(0, max) : null
  const isUnavailable = normalizedMax === 0
  const isAtMax = normalizedMax !== null && quantity >= normalizedMax
  const isBusy = disabled || loading
  const maxMessage =
    normalizedMax !== null ? maxQuantityMessage(normalizedMax, labels) : null
  const validationMessage = error ?? localMessage ?? (isAtMax ? maxMessage : null)
  const statusMessage =
    loading && labels.updatingQuantity
      ? labels.updatingQuantity
      : validationMessage
  const canDecrease = !isBusy && quantity > min
  const canIncrease =
    !isBusy && !isUnavailable && (normalizedMax === null || quantity < normalizedMax)

  useEffect(() => {
    setInputValue(String(quantity))
    setLocalMessage(null)
  }, [quantity])

  async function commitQuantity(nextQuantity: number) {
    if (disabled || loading) {
      return
    }

    let normalizedQuantity = nextQuantity

    if (normalizedQuantity < min) {
      setInputValue(String(quantity))
      setLocalMessage(labels.enterValidQuantity)
      return
    }

    if (normalizedMax !== null && normalizedQuantity > normalizedMax) {
      normalizedQuantity = normalizedMax
      setInputValue(String(normalizedQuantity))
      setLocalMessage(maxMessage)
    }

    if (normalizedQuantity === quantity) {
      setInputValue(String(quantity))
      return
    }

    try {
      if (normalizedQuantity === 0 && onRemove) {
        await onRemove()
      } else {
        await onCommit(normalizedQuantity)
      }

      setLocalMessage(null)
    } catch {
      setInputValue(String(quantity))
    }
  }

  async function commitInputValue() {
    const trimmedValue = inputValue.trim()

    if (trimmedValue === "") {
      setInputValue(String(quantity))
      setLocalMessage(labels.enterValidQuantity)
      return
    }

    const nextQuantity = Number(trimmedValue)

    if (!Number.isInteger(nextQuantity)) {
      setInputValue(String(quantity))
      setLocalMessage(labels.enterValidQuantity)
      return
    }

    await commitQuantity(nextQuantity)
  }

  return (
    <div className={cn("space-y-2", className)}>
      <div
        className={cn(
          "inline-flex h-10 items-center overflow-hidden rounded-full border border-border/70 bg-background",
          disabled ? "opacity-60" : null,
        )}
        aria-busy={loading}
      >
        <button
          type="button"
          className="flex h-10 w-10 items-center justify-center text-foreground transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-45"
          onClick={() => {
            void commitQuantity(quantity - 1)
          }}
          disabled={!canDecrease}
          aria-disabled={!canDecrease}
          aria-label={labels.decreaseQuantity}
          title={labels.decreaseQuantity}
        >
          <Minus className="size-4" />
        </button>
        <input
          type="number"
          inputMode="numeric"
          min={min}
          max={normalizedMax ?? undefined}
          value={inputValue}
          disabled={isBusy}
          className="h-10 w-14 border-x border-border/70 bg-transparent text-center text-sm font-medium text-foreground outline-none [appearance:textfield] disabled:cursor-not-allowed [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
          onChange={(event) => {
            setInputValue(event.target.value)
            setLocalMessage(null)
          }}
          onBlur={() => {
            if (skipBlurCommitRef.current) {
              skipBlurCommitRef.current = false
              return
            }

            void commitInputValue()
          }}
          onKeyDown={(event) => {
            if (event.key === "Enter") {
              event.preventDefault()
              event.currentTarget.blur()
            }

            if (event.key === "Escape") {
              event.preventDefault()
              skipBlurCommitRef.current = true
              setInputValue(String(quantity))
              setLocalMessage(null)
              event.currentTarget.blur()
            }
          }}
          aria-label={labels.quantityInput}
          aria-invalid={Boolean(validationMessage)}
          aria-describedby={statusMessage ? messageId : undefined}
        />
        <button
          type="button"
          className="flex h-10 w-10 items-center justify-center text-foreground transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-45"
          onClick={() => {
            void commitQuantity(quantity + 1)
          }}
          disabled={!canIncrease}
          aria-disabled={!canIncrease}
          aria-label={labels.increaseQuantity}
          title={!canIncrease && maxMessage ? maxMessage : labels.increaseQuantity}
        >
          <Plus className="size-4" />
        </button>
      </div>

      {statusMessage ? (
        <p
          id={messageId}
          className={cn(
            "max-w-48 text-xs leading-snug",
            loading ? "text-muted-foreground" : "text-destructive",
          )}
          role={loading ? "status" : "alert"}
        >
          {statusMessage}
        </p>
      ) : null}
    </div>
  )
}
