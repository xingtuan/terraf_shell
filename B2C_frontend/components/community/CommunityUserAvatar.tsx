import Image from "next/image"

import type { CommunityUser } from "@/lib/types"
import { cn } from "@/lib/utils"

type CommunityUserAvatarProps = {
  user?: Pick<CommunityUser, "avatar_url" | "name" | "username"> | null
  src?: string | null
  name?: string | null
  className?: string
  imageClassName?: string
  fallbackClassName?: string
  sizes?: string
}

export function CommunityUserAvatar({
  user,
  src,
  name,
  className,
  imageClassName,
  fallbackClassName,
  sizes = "96px",
}: CommunityUserAvatarProps) {
  const resolvedSrc = src ?? user?.avatar_url ?? null
  const resolvedName = name ?? user?.name ?? user?.username ?? "Community member"
  const initials = resolvedName
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? "")
    .join("")

  return (
    <div
      className={cn(
        "relative flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted text-foreground",
        className,
      )}
    >
      {resolvedSrc ? (
        <Image
          src={resolvedSrc}
          alt={resolvedName}
          fill
          unoptimized
          sizes={sizes}
          className={cn("object-cover", imageClassName)}
        />
      ) : (
        <span className={cn("text-sm font-medium", fallbackClassName)}>
          {initials || "CM"}
        </span>
      )}
    </div>
  )
}
