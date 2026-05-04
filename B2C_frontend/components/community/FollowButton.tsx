"use client"

import { useEffect, useState } from "react"
import { useParams } from "next/navigation"

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
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
  userName?: string
  className?: string
  onChange?: (state: { isFollowing: boolean; followerCount: number }) => void
}

export function FollowButton({
  userId,
  initialIsFollowing,
  followerCount,
  userName,
  className,
  onChange,
}: FollowButtonProps) {
  const params = useParams<{ locale?: string }>()
  const requestedLocale = params?.locale ?? defaultLocale
  const locale = isValidLocale(requestedLocale) ? requestedLocale : defaultLocale
  const siteMessages = getMessages(locale)
  const messages = siteMessages.community.profile
  const session = useAuthSession()
  const [isFollowing, setIsFollowing] = useState(initialIsFollowing)
  const [currentFollowerCount, setCurrentFollowerCount] = useState(followerCount)
  const [isPending, setIsPending] = useState(false)
  const [pendingUnfollow, setPendingUnfollow] = useState(false)

  useEffect(() => {
    setIsFollowing(initialIsFollowing)
  }, [initialIsFollowing])

  useEffect(() => {
    setCurrentFollowerCount(followerCount)
  }, [followerCount])

  function performUnfollow() {
    const previousFollowerCount = currentFollowerCount
    const nextFollowerCount = Math.max(0, currentFollowerCount - 1)

    setIsPending(true)
    setIsFollowing(false)
    setCurrentFollowerCount(nextFollowerCount)
    onChange?.({ isFollowing: false, followerCount: nextFollowerCount })

    void unfollowUser(userId, session.token!)
      .catch((error) => {
        setIsFollowing(true)
        setCurrentFollowerCount(previousFollowerCount)
        onChange?.({ isFollowing: true, followerCount: previousFollowerCount })
        toast({ title: getErrorMessage(error) })
      })
      .finally(() => {
        setIsPending(false)
      })
  }

  const confirmTitle = siteMessages.common.confirm.unfollowUser.title.replace(
    "{name}",
    userName ?? "",
  )

  return (
    <>
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

          if (isFollowing) {
            setPendingUnfollow(true)
            return
          }

          const previousFollowerCount = currentFollowerCount
          const nextFollowerCount = currentFollowerCount + 1

          setIsPending(true)
          setIsFollowing(true)
          setCurrentFollowerCount(nextFollowerCount)
          onChange?.({ isFollowing: true, followerCount: nextFollowerCount })

          void followUser(userId, session.token)
            .catch((error) => {
              setIsFollowing(false)
              setCurrentFollowerCount(previousFollowerCount)
              onChange?.({ isFollowing: false, followerCount: previousFollowerCount })
              toast({ title: getErrorMessage(error) })
            })
            .finally(() => {
              setIsPending(false)
            })
        }}
      >
        {isFollowing ? messages.unfollow : messages.follow}
      </Button>

      <AlertDialog
        open={pendingUnfollow}
        onOpenChange={(open) => {
          if (!open) setPendingUnfollow(false)
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{confirmTitle}</AlertDialogTitle>
            <AlertDialogDescription>
              {siteMessages.common.confirm.unfollowUser.description}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>
              {siteMessages.common.confirm.unfollowUser.cancel}
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                setPendingUnfollow(false)
                performUnfollow()
              }}
            >
              {siteMessages.common.confirm.unfollowUser.confirm}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}
