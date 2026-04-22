"use client"

import { useEffect, useState } from "react"
import { useParams } from "next/navigation"

import { Button } from "@/components/ui/button"
import { getErrorMessage } from "@/lib/api/client"
import { followUser, unfollowUser } from "@/lib/api/users"
import { dispatchCommunityAuthOpen } from "@/lib/community-events"
import { defaultLocale, getMessages, isValidLocale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"
import { toast } from "@/hooks/use-toast"

type FollowButtonProps = {
  userId: number
  initialIsFollowing: boolean
  followerCount: number
  className?: string
  onChange?: (state: { isFollowing: boolean; followerCount: number }) => void
}

export function FollowButton({
  userId,
  initialIsFollowing,
  followerCount,
  className,
  onChange,
}: FollowButtonProps) {
  const params = useParams<{ locale?: string }>()
  const requestedLocale = params?.locale ?? defaultLocale
  const locale = isValidLocale(requestedLocale) ? requestedLocale : defaultLocale
  const messages = getMessages(locale).community.profile
  const session = useAuthSession()
  const [isFollowing, setIsFollowing] = useState(initialIsFollowing)
  const [currentFollowerCount, setCurrentFollowerCount] = useState(followerCount)
  const [isPending, setIsPending] = useState(false)

  useEffect(() => {
    setIsFollowing(initialIsFollowing)
  }, [initialIsFollowing])

  useEffect(() => {
    setCurrentFollowerCount(followerCount)
  }, [followerCount])

  return (
    <Button
      type="button"
      variant={isFollowing ? "default" : "outline"}
      className={className}
      disabled={isPending}
      onClick={() => {
        if (!session.user || !session.token) {
          dispatchCommunityAuthOpen()
          return
        }

        const previousIsFollowing = isFollowing
        const previousFollowerCount = currentFollowerCount
        const nextIsFollowing = !isFollowing
        const nextFollowerCount = Math.max(
          0,
          currentFollowerCount + (nextIsFollowing ? 1 : -1),
        )

        setIsPending(true)
        setIsFollowing(nextIsFollowing)
        setCurrentFollowerCount(nextFollowerCount)
        onChange?.({
          isFollowing: nextIsFollowing,
          followerCount: nextFollowerCount,
        })

        void (nextIsFollowing
          ? followUser(userId, session.token)
          : unfollowUser(userId, session.token))
          .catch((error) => {
            setIsFollowing(previousIsFollowing)
            setCurrentFollowerCount(previousFollowerCount)
            onChange?.({
              isFollowing: previousIsFollowing,
              followerCount: previousFollowerCount,
            })
            toast({
              title: getErrorMessage(error),
            })
          })
          .finally(() => {
            setIsPending(false)
          })
      }}
    >
      {isFollowing ? messages.unfollow : messages.follow}
    </Button>
  )
}
