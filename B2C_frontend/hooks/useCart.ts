"use client"

import {
  createElement,
  createContext,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react"
import { usePathname } from "next/navigation"

import {
  addCartItem,
  clearCart as clearServerCart,
  getCart,
  mergeGuestCart,
  removeCartItem,
  updateCartItem,
} from "@/lib/api/cart"
import { getErrorMessage } from "@/lib/api/client"
import {
  clearCartSessionKey,
  getCartSessionKey,
  syncCartSessionKeyFromCookie,
} from "@/lib/cart/session"
import type { CartSummary } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type CartContextValue = {
  cart: CartSummary | null
  loading: boolean
  error: string | null
  isOpen: boolean
  openCart: () => void
  closeCart: () => void
  toggleCart: () => void
  loadCart: () => Promise<CartSummary | null>
  addItem: (productId: number, quantity: number) => Promise<CartSummary | null>
  updateItem: (
    productId: number,
    quantity: number,
  ) => Promise<CartSummary | null>
  removeItem: (productId: number) => Promise<CartSummary | null>
  clearCart: () => Promise<void>
}

const CartContext = createContext<CartContextValue | null>(null)

type CartProviderProps = {
  children: ReactNode
}

function emptyCartState(previousCart: CartSummary | null): CartSummary | null {
  if (!previousCart) {
    return null
  }

  return {
    ...previousCart,
    item_count: 0,
    subtotal_usd: "0.00",
    estimated_shipping_usd: "0.00",
    estimated_tax_usd: "0.00",
    estimated_total_usd: "0.00",
    items: [],
  }
}

export function CartProvider({ children }: CartProviderProps) {
  const pathname = usePathname()
  const { token } = useAuthSession()
  const [cart, setCart] = useState<CartSummary | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [isOpen, setIsOpen] = useState(false)

  useEffect(() => {
    setIsOpen(false)
  }, [pathname])

  async function loadCart() {
    setLoading(true)
    setError(null)

    try {
      const nextCart = await getCart(token)
      syncCartSessionKeyFromCookie()
      setCart(nextCart)

      return nextCart
    } catch (nextError) {
      setError(getErrorMessage(nextError))
      return null
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    let isCancelled = false

    async function syncCart() {
      setLoading(true)
      setError(null)

      try {
        if (token) {
          const guestSessionKey = getCartSessionKey()

          if (guestSessionKey) {
            try {
              const mergedCart = await mergeGuestCart(guestSessionKey, token)

              if (!isCancelled) {
                clearCartSessionKey()
                setCart(mergedCart)
                syncCartSessionKeyFromCookie()
              }

              return
            } catch {
              // Fall back to loading the authenticated cart below.
            }
          }
        }

        const nextCart = await getCart(token)

        if (!isCancelled) {
          syncCartSessionKeyFromCookie()
          setCart(nextCart)
        }
      } catch (nextError) {
        if (!isCancelled) {
          setError(getErrorMessage(nextError))
        }
      } finally {
        if (!isCancelled) {
          setLoading(false)
        }
      }
    }

    void syncCart()

    return () => {
      isCancelled = true
    }
  }, [token])

  async function addItemToCart(productId: number, quantity: number) {
    setLoading(true)
    setError(null)

    try {
      const nextCart = await addCartItem(productId, quantity, token)
      syncCartSessionKeyFromCookie()
      setCart(nextCart)
      setIsOpen(true)

      return nextCart
    } catch (nextError) {
      setError(getErrorMessage(nextError))
      throw nextError
    } finally {
      setLoading(false)
    }
  }

  async function updateCartLine(productId: number, quantity: number) {
    setLoading(true)
    setError(null)

    try {
      const nextCart = await updateCartItem(productId, quantity, token)
      setCart(nextCart)

      return nextCart
    } catch (nextError) {
      setError(getErrorMessage(nextError))
      throw nextError
    } finally {
      setLoading(false)
    }
  }

  async function removeCartLine(productId: number) {
    setLoading(true)
    setError(null)

    try {
      const nextCart = await removeCartItem(productId, token)
      setCart(nextCart)

      return nextCart
    } catch (nextError) {
      setError(getErrorMessage(nextError))
      throw nextError
    } finally {
      setLoading(false)
    }
  }

  async function clearCartItems() {
    setLoading(true)
    setError(null)

    try {
      await clearServerCart(token)
      setCart((currentCart) => emptyCartState(currentCart))
    } catch (nextError) {
      setError(getErrorMessage(nextError))
      throw nextError
    } finally {
      setLoading(false)
    }
  }

  const value = useMemo<CartContextValue>(
    () => ({
      cart,
      loading,
      error,
      isOpen,
      openCart: () => setIsOpen(true),
      closeCart: () => setIsOpen(false),
      toggleCart: () => setIsOpen((currentValue) => !currentValue),
      loadCart,
      addItem: addItemToCart,
      updateItem: updateCartLine,
      removeItem: removeCartLine,
      clearCart: clearCartItems,
    }),
    [cart, loading, error, isOpen],
  )

  return createElement(CartContext.Provider, { value }, children)
}

export function useCart() {
  const context = useContext(CartContext)

  if (!context) {
    throw new Error("useCart must be used within CartProvider.")
  }

  return context
}
