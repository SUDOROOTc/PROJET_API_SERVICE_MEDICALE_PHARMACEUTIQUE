# Final Design - users table (Firebase integration)

## Goal
Use Firebase for authentication and keep a minimal local `users` table in Laravel for authorization and backend metadata.

## Final schema
- `id` (bigint, primary key)
- `firebase_uid` (string, unique, indexed, required)
- `name` (string, nullable)
- `email` (string, nullable, indexed)
- `role` (string, default `viewer`, indexed)
- `is_active` (boolean, default `true`, indexed)
- `last_seen_at` (timestamp, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## Not used anymore for auth logic
- `password`
- `remember_token`
- `email_verified_at`

## Migration strategy used in this project
- Existing database: keep legacy auth columns/tables temporarily to avoid breaking running environments.
- New clean installs (`migrate:fresh`): `users` is now created with Firebase-first fields.
- Cleanup (optional next step): remove legacy auth columns/tables once API security middleware is fully switched to Firebase.

## Sync rule at each protected request
1. Verify Firebase ID token.
2. Extract `uid`, `email`, `name` claims.
3. Upsert local user by `firebase_uid`.
4. Update `last_seen_at`.
5. Enforce local authorization using `role` and `is_active`.

## Notes
- Keep `sessions`, `password_reset_tokens` only if you still need Laravel session/password features.
- If API is fully token-based with Firebase, these tables can be removed in a cleanup migration later.
