# Changelog

## 1.1.1 — 2026-10-15

### Fixed

- **A deactivation could lapse and leave the account deactivated with nothing to undo it.** The Punishment Tiers page hides the duration of a "deactivate account" tier, but still submitted and saved it, so a tier switched from "add to group" to "deactivate" kept its old duration. Its punishments were given an end time, and when that came the expiry cron marked them expired, which only ever removes a group membership. The account stayed deactivated, and with no active punishment left there was no *End punishment early* button to reactivate it from.

  Deactivation is now always permanent, as documented: a deactivate tier is saved without a duration, and one already stored is ignored when punishing. The update clears the duration from existing deactivate tiers and makes any active deactivation permanent. A deactivation that has already lapsed is left as it is — if one of your users is in that state, reactivate the account from **Manage users** in the usual way.

- **Dry-run mode logged punishments that would not have happened, and changed active punishments for real.** The dry-run log entry was written before the warning threshold was checked, so every warning given to a user without an active punishment logged "would be punished", even far below the threshold. And for a user who already had an active punishment, dry run was not checked at all: the next warning restarted, replaced or escalated that punishment exactly as if dry run were off.

  Dry run now logs only a punishment that would really be applied, and leaves active punishments untouched in all three *Warning during active punishment* modes, logging what it would have done instead.

- **The missing-sender warning said email notifications had stopped.** When the account chosen as the notification sender is deleted, only private messages stop — emails are sent from the board's own address and keep going. The warning on the Settings page and the admin log entry now say PMs only.

### Upgrading

This release includes a database change; run `php bin/phpbbcli.php db:migrate`, or disable and re-enable the extension in the ACP (keeping its data), then purge the cache.

## 1.1.0 — 2026-10-15

### Changed

- **The AutoPunish tab in Manage Users has its own permission.** It used to require *Can alter board settings* (`a_board`), so the only way to show it to someone was to give them the board settings as well. It now requires the new admin permission *Can view and manage AutoPunish in Manage Users* (`a_autopunish`), listed under *Users & Groups* in the admin permissions. You can grant it to any user, group or role, for example global moderators who already have access to other Manage Users pages.

  The AutoPunish Settings and Punishment Tiers pages still require `a_board`.

- The README now documents all of the extension's behaviour: how punishments are triggered, the three modes for a warning during an active punishment, notifications, permissions, the admin log, updating, and what uninstalling leaves behind.

### Upgrading

This release includes a database change. After replacing the files, disable and re-enable AutoPunish in the ACP (keeping its data), or run `php bin/phpbbcli.php db:migrate`, then purge the cache. The update step adds the permission to the stock Full, Standard, Forum and User & Groups admin roles, so admins who use those roles keep the tab. Admins whose permissions are set directly rather than through one of those roles need to be granted `a_autopunish` by hand.

## 1.0.3 — 2026-10-03

Addresses the notes from the phpBB Extension Customisations Team's validation of 1.0.0.

### Fixed

- **Email notifications were never sent, and the request that asked for one died.** phpBB's messenger looks for an extension's email template under `language/<lang>/email/`, appending `.txt` to the name itself. The template shipped at `styles/all/template/notification_email.html`, so the lookup never found it and threw. With *Enable email notifications* switched on, the punishment row was written and the request then aborted: a moderator issuing a warning got a general error page after the punishment had already been applied, and a run of the expiry cron stopped at the first punishment it had to notify about, leaving the rest of that run's due punishments in place until a later run — which failed the same way.

  The template now lives at `language/<lang>/email/notification_email.txt` in all five bundled languages.

- **The Punishment History table never appeared.** `{punishment_history.S_NUM_ROWS}` referenced outside its own loop resolves to nothing in phpBB 3.3, so `not punishment_history.S_NUM_ROWS` was always true and the "No punishment history." branch always won — users with confirmed punishment rows showed as having none. The condition is now phpBB's empty-block test, `<!-- IF not .punishment_history -->`.

- **"End punishment early" skipped its confirmation on some translations.** The confirmation text was interpolated raw into a JavaScript string literal, so a translation containing an apostrophe — the bundled French `L'infraction`, for one — terminated the string early, broke the handler, and let the click through without asking. The punishment itself always ended correctly; what was lost was the accidental-click guard. The text now goes through phpBB's JS-escaped `{LA_...}` variable.

- **Group names and tier text are now escaped for the context they are rendered into.** phpBB's template engine does not autoescape, and group names, tier reasons and notification texts are all free-form. A group name containing a double quote broke out of the `data-name` attribute on the Punishment Tiers page, and that same name was then concatenated into HTML by the *Add tier* button's JavaScript; a tier reason containing a quote broke the reason field's `value` attribute. Group names can be set by anyone with group-management permission and are displayed to board administrators.

  Rows added by *Add tier* are now assembled with DOM calls instead of HTML strings, so a group name is only ever handed to `textContent` or `setAttribute` and can never be parsed as markup.

### Changed

- The default punishment tiers now install in the administrator's language, falling back to the board default and then to English, instead of always installing English text. They remain editable in the ACP afterwards, exactly as before.
- Tier rows are written in a single batched statement in both the installer migration and the ACP save, rather than one statement per tier.
- The "(autocomplete)" hint beside the notification sender field is now a translatable language key rather than text baked into the template.
- Removed two language keys that nothing referenced, `ACP_AUTOPUNISH_USER` and `AUTOPUNISH_SELECT_USER`.
- The 1.0.1 upgrade backfill stamps every pre-existing punishment in one statement rather than one per distinct warning id, which leaves no SQL query inside a loop anywhere in the extension. The stamps it produces are unchanged.

### Upgrading

Nothing to do beyond the usual update step, and no database changes. Note that boards which already had email notifications enabled will begin actually sending them.

## 1.0.2 — 2026-09-19

### Fixed

- **Adding a punishment tier overwrote the last existing tier instead of appending a new one.** The tier rows rendered by the server were keyed from one (`tiers[1]` … `tiers[4]`) while the "Add tier" button keyed the row it appended from zero, so on a board with four tiers the new row was named `tiers[4]` — the same key as the existing fourth row. The browser submitted both under that key, the later one won, and the fourth tier was replaced by the blank new one. The table showed five rows; only four were saved.

  The row number shown in the first column and the form array key are now separate values, so the appended row can never collide, and the rows are re-keyed after every add and remove.

## 1.0.1 — 2026-09-19

### Fixed

- **The retroactive scan no longer re-punishes users for warnings they have already served.** phpBB leaves a user's warning count on their account after a punishment ends, so a user who was punished at three warnings still had three warnings once it expired. Any later run of the retroactive scan saw them as eligible and punished them again — one tier higher each time, since the offense counter had gone up. Repeated over enough scans this escalated an unchanged account all the way to deactivation.

  Punishments now record the warning that triggered them, and a new punishment requires at least one warning newer than the last one already punished for. Warning ids are never reused, so this stays correct even after phpBB's warning pruning removes the underlying rows.

- The retroactive scan's log entry counted every user it examined rather than every user it punished, overstating "%d users punished".

### Upgrading

Punishments recorded before this release are backfilled automatically when the extension's migrations run: each is stamped with the newest warning that was already on the account when it was applied. No action is needed beyond the usual enable/update step, and no user is punished as a side effect of upgrading.

### Notes for administrators

Nothing changes for punishments triggered by a moderator issuing a warning — that path was never affected. The fix only stops punishments being issued a second time for the same warnings.

If a punishment tier's group is also managed by another extension (Auto Groups, for example, has a warning-count condition), that extension can re-add users after AutoPunish removes them on expiry, and AutoPunish has no record of that membership to clean up later. Give AutoPunish sole ownership of any group used by a tier.

## 1.0.0 — 2026-04-19

- Initial release.
