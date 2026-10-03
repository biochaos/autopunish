# Changelog

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
