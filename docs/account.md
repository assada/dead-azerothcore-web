# My Account

Laravel authenticates against `acore_auth.account` using the existing SRP6 verifier. Registration creates that same account, with email as `username`. Password changes and resets update its SRP6 credentials. No game authentication code or protocol changes are required.

`account_profiles` stores website metadata: email verification, pending email changes, remembered login tokens, and deactivation state. It has no separate password or identity. Laravel sessions and `account_operations` also use the auth connection.

## Account changes

- Registration sends Laravel's signed email verification link. Password reset and remembered login remain available.
- A login email change requires the current password, a signed link sent to the new address, and the current password again on confirmation. The account ID and characters stay the same. Previous password reset links are invalidated.
- Password changes invalidate other website sessions when they next make a request. Ending other sessions also revokes remembered login tokens.
- Deactivation requires confirmation, the current password, and logging out of the game first. It creates a permanent native account ban, ends website sessions, and preserves the account and characters.
- An administrator can restore access with `php artisan account:restore ACCOUNT_ID`. This removes only the ban created for that deactivation. Other bans remain active.

## Character actions

The website dispatches the existing AzerothCore SOAP commands. Each request checks ownership, the default realm, online state, existing login flags, and cooldown. Unstuck also requires a home location.

| Action | Command | Limit per character |
| --- | --- | --- |
| Unstuck | `unstuck GUID inn` | `WOW_UNSTUCK_COOLDOWN=PT3H` |
| Rename | `character rename GUID` | `WOW_RENAME_COOLDOWN=P1Y` |
| Appearance | `character customize GUID` | `WOW_CUSTOMIZE_COOLDOWN=calendar_quarter` |

Each action has a `WOW_*_ENABLED` switch. Disabled actions are hidden and rejected by the server. Missing SOAP configuration makes actions unavailable.

Cooldowns accept ISO 8601 durations, such as `PT3H`, `P7D`, or `P1Y`. `P0D` removes the cooldown. `calendar_quarter` resets at the next quarter in `APP_TIMEZONE`. Rolling months and years do not overflow the end of a month.

Rename and appearance changes are completed in the game client at the next login. Their limit starts when the server accepts the request. A failed request does not consume the limit.

Requests have UUIDs and a lock per realm and character. A duplicate UUID never dispatches twice. A timeout or invalid response is recorded as `unknown`; a process interrupted after dispatch can leave `pending`. Both states block another request of that action. The website never automatically retries an uncertain command.

After checking the actual game state, an administrator can resolve such an operation:

```sh
php artisan account:resolve-operation OPERATION_UUID completed --reason='The character change was confirmed.'
php artisan account:resolve-operation OPERATION_UUID failed --reason='The action did not take effect. You can try again.'
```

Use only the result verified on the server. Resolution sends no game command. A confirmed completion starts its cooldown at the time of resolution; a confirmed failure allows a fresh request. The explanation is visible to the account owner.

Operations and dispatch configuration carry a realm ID. Character reads still use the existing single character database, and action routes reject other realms. This is preparation for future multi-realm support, not a second realm implementation.

## Deployment

Back up the auth database and website configuration before applying the migration. Deploy dependencies and built assets together with the source. Run migrations before bringing the updated website online.

The session list and revocation require Laravel database sessions:

```dotenv
DB_CONNECTION=acore_auth
SESSION_DRIVER=database
SESSION_CONNECTION=acore_auth
TRUSTED_PROXIES=
REALM_SOAP_URL=
REALM_SOAP_USERNAME=
REALM_SOAP_PASSWORD=
```

Set `REALM_SOAP_URL` to the worldserver SOAP endpoint reachable from the application container. Use a dedicated SOAP account with permission for the enabled commands. Keep SOAP on a private network.

Set `TRUSTED_PROXIES` to the reverse proxy IPs or CIDRs. Configure SMTP in `.env` and keep credentials outside Git. See the [installation guide](../README.md).

Switching the session driver signs existing website sessions out. Game sessions are unaffected. Keep the existing shared Redis cache for character action locks. Clear the Laravel configuration and view caches after updating runtime settings.

The migration adds `account_profiles`, `account_operations`, and `sessions`. The earlier password reset migration creates `password_reset_tokens` if it has not run on the auth database. Keep the default connection on `acore_auth` so migration records also persist there. These migrations do not alter AzerothCore's account schema or delete game data.
