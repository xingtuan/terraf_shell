"use client"

import { useEffect, useRef, useState } from "react"

export function useSectionInView<T extends HTMLElement>(threshold = 0.2) {
  const sectionRef = useRef<T>(null)
  const [isVisible, setIsVisible] = useState(false)

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsVisible(true)
        }
      },
      { threshold },
    )

    if (sectionRef.current) {
      observer.observe(sectionRef.current)
    }

    return () => observer.disconnect()
  }, [threshold])

  return {
    sectionRef,
    isVisible,
  }
}
