# Changelog

## 1.2.1 — 2026-11-23

### Fixed

- **The Manage Users form acted on whatever it was sent.** *End punishment early* took the punishment id and the offense number from the posted form, and checked neither against the member whose tab was open. A forged post could end another member's punishment, and with *step down* ticked it could apply any tier to any member: account deactivation for a member with no warnings, or for a member of an exempt group. A stale form did the same without any forging: after the cron expired a punishment, submitting the old form still answered "Punishment ended" and applied a new one. The form now only says which punishment the administrator was looking at. Everything else is read from that member's stored active punishment, and nothing is ended or applied when it no longer matches. Ending a punishment is also atomic now, so two administrators, or an administrator and the cron, cannot both end it, and the notification and the log entry are not repeated. A step-down no longer punishes a member of an exempt group.
- **Saving the Tiers page with an emoji emptied the tiers.** On MySQL a reason or notification text containing an emoji, or any other character outside the Basic Multilingual Plane, ended the save with an SQL error, and because the old tiers were deleted before the new ones were inserted, nobody was punished until every tier was typed in again. Such characters are now stored as numeric references, the way phpBB stores titles, and shown again exactly as typed. The delete and the insert also run in one transaction, so a failed save leaves the saved tiers as they were. The texts on the Settings page are stored the same way, as phpBB's own board settings are.
- **The expiry task ran on every cron check, not once per interval.** The time of its last run was kept as an ordinary config value, which phpBB serves from its cache, while the task wrote it without refreshing that cache. Every request after a run therefore read the time from before it, and the task counted as due again each time. It also took the single cron slot phpBB gives each request. The value is now flagged as one phpBB reads from the database each time.
- **A tier could not last longer than about 194 days.** Its length was stored in a three-byte column that tops out at 16,777,215 seconds, so a tier set to seven months or a year ended the save with a raw SQL error. The column is wider, and the Tiers page now accepts up to 50 years and says so when a tier is longer.
- **A text that was too long ended a save with an SQL error.** The notification texts on the Settings page are kept in the config table, whose values hold 255 characters, and a tier's reason has the same limit, counted after escaping and encoding (an `&` takes five). A longer one now gets a message naming the field. The Settings page also saved its fields one by one, so a failure part way left some of them changed: it now checks everything first and writes all or nothing. A warning mode that is not one of the three is ignored, and the check interval cannot go below one minute.
- **The admin log showed identifiers.** A punishment was logged as "action: group, group: 8". The log now says "Add to group" or "Deactivate account" and names the group (built-in groups by their translated names), in the language of whoever was acting. Entries written before still read as they did.
- **A deleted user's punishments stayed behind.** Their records, and their commuted-offense count, are now removed with the account, and the update removes the ones already left by earlier deletions.

### Changed

- Founders and bots are never punished, as the retroactive scan already assumed. phpBB does not deactivate them, so a deactivation tier would have recorded a punishment and sent the notice without changing the account. This only comes up when someone warns a founder.
- **phpBB 3.3.2 or later is required.** The installation step that grants the new permission to the stock administrator roles uses `permission.role_exists`, which phpBB's migration tools only have from 3.3.2.
- **A migration of a released version is no longer changed.** 1.2.0 edited `install_default_tiers`, which had shipped in 1.0.0, so a new board was seeded differently from a board that had installed 1.0.0. That file is back exactly as 1.0.0 shipped it, and a new migration, `localize_default_tiers`, makes the change instead: a default tier that still holds its original English notification text gets an empty one, so recipients get the built-in text in their own language, and one that still holds its original reason gets the reason in the administrator's language. A tier you edited by hand is left alone. A new board ends up with the same four tiers as before.

### Upgrading

This release includes migrations: they widen the tier duration column, flag the cron timestamp, and remove the records of users who no longer exist. Run `php bin/phpbbcli.php db:migrate`, or disable and re-enable the extension in the ACP (keeping its data), then purge the cache.

## 1.2.0 — 2026-11-07

### Fixed

- **Disabling the extension broke the board for anyone with an AutoPunish notification.** The three notification types stayed switched on after the extension was disabled, so phpBB went on loading those members' notifications and failed to find the code behind them. Every page they opened showed a "Whoops" error, and *Delete data* did not clear it. The types are now switched off when the extension is disabled and back on when it is enabled, and *Delete data* removes them along with their notifications.
- **Ending a punishment could remove a membership AutoPunish had not added.** A user who was already in a tier's group was "added" again, and when the punishment ended they were taken out of the group they had been in all along. Each punishment now records whether it made the change itself, and ending it only undoes that change. The same applies to deactivation: an account that was already deactivated stays deactivated when the punishment ends.
- **A group tier with no group, or a deleted one, still "punished" users.** A punishment was recorded and the user notified, but nothing happened to the account. A group tier now needs a group that exists. The Tiers page refuses to save one without it, and keeps what you typed so you can fix it. A stored tier without a valid group, such as the default tiers before you choose their groups, is **inactive**: nobody is punished at it, the skip goes to the admin log, and the Tiers page and the Manage Users tab both show which tiers are inactive. An active punishment is no longer ended to make way for an inactive tier.
- **Each save of the Tiers page escaped the text again.** A reason or notification text containing `&`, `"`, `<` or `>` gained another layer of HTML escaping every time the page was saved, so `Q&A` became `Q&amp;A`, then `Q&amp;amp;A`. Text now stays exactly as it was typed. Text already stored with extra escaping keeps it, and needs correcting by hand once.
- **Bell notifications from the system cron showed raw language keys.** An expiry run by `bin/phpbbcli.php cron:run` produced text like "Your AUTOPUNISH_DURATION_PERMANENT punishment has expired", because the command line does not load the extension's language file. Bell text is now worded when it is shown, in the reader's own language, as PMs and emails already were. Notifications created by earlier versions still show as they were.
- **Bell notifications ignored the user's notification settings.** Turning off an AutoPunish notification in the UCP had no effect. Those settings are now respected. The bell is the only method offered for these notifications, since PMs and emails are AutoPunish's own channels, controlled on its Settings page.
- **Emails contained HTML entities and BBCode.** The email body was the PM text as stored, so a recipient could see `&quot;` and `[b]...[/b]`. Emails are now plain text, with BBCode removed and entities decoded.
- **A rejected Settings save still changed some settings.** The sender was checked after seven settings had already been written, so turning PM notifications on with no sender was refused but left PMs switched on with nothing to send them. The sender is now checked first, and a refused save changes nothing.
- **The Manage Users tab showed internal identifiers.** The action column showed `group` and `deactivate`, the group column showed a number, and built-in groups appeared as `REGISTERED` and the like, both there and in the group lists on the Settings and Tiers pages. All of them now show their translated names.

### Changed

- **phpBB 3.3.1 or later is required.** The Manage Users tab builds its return links from information that phpBB only passes to extensions from 3.3.1 on.
- The default tiers install with empty notification texts, so each recipient gets the built-in default in their own language. Text filled in at install time was in the administrator's language and replaced that default for every recipient. Boards that already have the default tiers keep their texts.
- The retroactive scan reads users in batches of 500, with three queries per batch for their punishment counts and warnings, instead of several queries per user.
- The Hebrew translation of the sender field described a numeric user ID that could be left as 0. It now describes the username the field actually takes. The subject-line help text in all five languages said a blank subject used the tier's notification text, when it uses the notification title.
- The one-line conditionals the validation notes pointed out are written out with braces, as the phpBB coding guidelines ask.

### Upgrading

This release includes a database change; run `php bin/phpbbcli.php db:migrate`, or disable and re-enable the extension in the ACP (keeping its data), then purge the cache. Punishments recorded before the update are treated as having made their own change, as before.

After updating, open the Tiers page: any tier listed as inactive needs a group before it will punish anyone.

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
