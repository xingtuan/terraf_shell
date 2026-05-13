# Admin UI Manual Checks

Run these checks after deploying moderation changes to a seeded or staging admin environment.

## Account Status

1. Ban a user from the Users table.
2. Confirm the user cannot log in.
3. Restore active from the Users table.
4. Confirm the user can log in.
5. Confirm the restored user can post, comment, like, favorite, and follow after logging in again.

## Account-Level Violations

1. Ban a user again.
2. Open the related AccountBanned violation.
3. Click Resolve violation only and confirm the user remains banned.
4. Create or keep another open AccountBanned or AccountRestricted violation for the same user.
5. Click Resolve and restore account.
6. Confirm all open AccountBanned and AccountRestricted violations are resolved.
7. Confirm the user account status is active and `is_banned` is false.

## Reports

1. Resolve a pending or reviewed report.
2. Confirm no other normal report processing buttons are visible.
3. Dismiss a pending or reviewed report.
4. Confirm no other normal report processing buttons are visible.
5. Confirm finalized reports show completion state as resolved or dismissed.
