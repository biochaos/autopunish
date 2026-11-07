# AutoPunish
![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/biochaos/autopunish/total?color=red)

A phpBB extension that automatically punishes users when they accumulate warnings, with configurable punishment tiers and durations.

## Features

- Define any number of punishment tiers, each triggered at a configurable warning threshold
- Two punishment actions per tier: add user to a group (e.g. a restricted posting group) or deactivate the account
- Configurable duration per tier: days, weeks, months, or permanent
- Automatic expiry via cron — no manual intervention required
- Escalation options when a warning is issued while a punishment is already active: restart the timer, apply normal threshold rules, or escalate immediately to the next tier
- Commute (reduce) a user's effective offense count from the ACP Manage Users page
- End an active punishment early from the ACP, with optional step-down to the previous tier
- Three notification channels (bell, PM, email) for punishment applied, expired, and ended early — each with configurable subject, body, and footer, supporting `{USERNAME}`, `{DURATION}`, `{REASON}`, and `{OFFENSE_NUMBER}` placeholders
- PMs and emails are written in each recipient's own board language
- Dry-run mode: logs what would happen without applying punishments — useful for verifying thresholds before going live
- One-shot retroactive scan: punishes users who already meet a threshold when the extension is first enabled
- Exempt groups: users in selected groups are never punished
- Its own admin permission for the Manage Users tab, so moderators can be given access without access to the board settings
- Every action is recorded in the admin log
- Available in English, French, Spanish, Portuguese, and Hebrew

## Requirements

- phpBB 3.3.1 or later
- PHP 7.1.3 or later

## Installation

1. [Download the latest release archive](https://github.com/biochaos/autopunish/releases/latest).
2. Extract and copy the `biochaos/autopunish/` directory into your board's `ext/` directory, so the path is `ext/biochaos/autopunish/`.
3. In the ACP, go to **Customise → Manage extensions** and enable **AutoPunish**.

Enabling installs four example tiers: a 7-day, a 30-day and a 180-day group restriction at 3 warnings each, then account deactivation at 1 warning. Their reasons are written in the administrator's language. Their notification texts are left empty, so each user gets the built-in text in their own language. The three group tiers have no group yet, which makes them **inactive**: nobody is punished at them until you choose a group for each on the Tiers page. Review the tiers before turning AutoPunish on.

## Updating

1. Download the new release and extract it over the existing `ext/biochaos/autopunish/` directory.
2. If the release notes say it includes a database change, apply it. From the command line, run `php bin/phpbbcli.php db:migrate`. Without shell access, **disable** AutoPunish in the ACP and then **enable** it again — this keeps all data, but do not click *Delete data*. (`php bin/phpbbcli.php extension:enable` does not work for this: it does nothing for an extension that is already enabled.)
3. Purge the board cache (**ACP → General → Purge the cache**).

## How it works

AutoPunish acts at the moment a moderator issues a warning — on a post or directly on a user. It then compares the user's **current warning count** with the tier for their **next offense**:

- The **offense number** is the number of punishments the user has received, minus any offenses an administrator has commuted, plus one. A user's first punishment uses tier 1, their second uses tier 2, and so on; once a user is past the last tier, the last tier repeats.
- The tier fires when the user's warning count is at or above that tier's threshold. Below the threshold, nothing happens until the next warning.
- The warning count is phpBB's own. If your board expires warnings (**ACP → General → Board settings → Warnings → Warning duration**), expired warnings no longer count towards a threshold.
- A user is never punished twice for the same warnings. phpBB keeps the warning count on the account after a punishment ends, so AutoPunish records which warning triggered each punishment and requires a newer one before it will punish again.
- Users in an exempt group are skipped entirely.
- A group tier whose group is not set, or no longer exists, is **inactive**. A user who reaches it is not punished, and the skip is written to the admin log. An active punishment is not ended to make way for an inactive tier.

When a punishment's time is up, the expiry cron task ends it: the user is removed from the punishment group and notified. Ending a punishment, whether on expiry or early, only undoes what the punishment itself did. A user who was already in the group before the punishment stays in it, and an account that was already deactivated stays deactivated. Like all phpBB cron tasks, it runs on page views, so on a quiet board an expiry can lag behind the exact end time until the next visitor arrives.

**Deactivate account** is always permanent. The tiers page does not offer a duration for it; the account stays deactivated until an administrator ends the punishment early from the Manage Users tab, which reactivates it.

## Configuration

All settings are in the ACP under **Extensions → AutoPunish**.

### Settings page

| Setting | Description |
|---|---|
| Enable AutoPunish | Master on/off switch. While off, warnings trigger nothing and punishments do not expire. |
| Warning during active punishment | What to do when a user receives a warning while already under a punishment (see below). |
| Expiry check interval | How often (in minutes) the cron task checks for expired punishments. |
| Exempt groups | Users in these groups are never punished. |
| Dry-run mode | Log the punishments that would be applied, without applying them. Nothing is logged for a warning below the threshold, and an active punishment is never restarted, replaced or escalated while dry run is on. Disable once you are satisfied with your tier configuration. |
| Retroactive scan | Tick and save once to immediately punish all users who already meet a threshold. Founders and users who already have an active punishment are skipped. The checkbox resets automatically after the scan runs. |
| Notifications | Enable bell notifications, PMs, and/or emails, and set the texts — see [Notifications](#notifications). |

**Warning during active punishment** has three modes:

| Mode | Effect |
|---|---|
| Restart timer | The current punishment starts again from now, with its original length. A permanent punishment is left as it is. |
| Normal | The warning is treated like any other: if the user now meets the threshold of their next tier, the current punishment ends and the next tier is applied; otherwise nothing changes. |
| Escalate immediately | The current punishment ends and the next tier is applied straight away, regardless of its threshold. |

### Tiers page

Each row defines one punishment tier. The row position is the offense number (1st offense uses row 1, 2nd uses row 2, and so on). The last tier repeats for all offenses beyond the last configured row. Use **Add tier** and **Remove** to change the number of rows.

| Column | Description |
|---|---|
| Warning threshold (≥) | Minimum active warning count that triggers this tier. |
| Action | Add to group or deactivate the account. |
| Group | The group to add the user to (only for the "add to group" action, and required for it: the page will not save a group tier without one). Use a group that AutoPunish alone manages — if another extension also controls its membership, it can re-add users after AutoPunish removes them on expiry. |
| Duration | Length of the punishment in days, weeks, or months (a month is 30 days). Set to 0 for permanent. Not used by "deactivate account", which is always permanent. |
| Reason | Internal note visible to administrators in the log. |
| Notification text | Text sent to the user for this tier, in the bell notification and as the PM/email body. Supports `{USERNAME}`, `{DURATION}`, `{REASON}`, `{OFFENSE_NUMBER}`. Leave it blank to send the built-in text in each user's own language. |

A tier whose group is not set, or has since been deleted, is listed at the top of the page as inactive until a group is chosen.

### Notifications

A user can be told about three events: a punishment being **applied**, **expiring**, and being **ended early** by an administrator. Each channel is switched on separately on the Settings page:

| Channel | Notes |
|---|---|
| Bell (forum notification) | Shown in the user's notification list on the board. Users can turn each of the three off in their UCP notification options. |
| Private message | Sent from the **sender** account you choose on the Settings page. |
| Email | Sent from the board's own email address as plain text, with any BBCode removed; needs board email enabled. |

A sender account must be chosen whenever PM or email notifications are on. If that account is later deleted, AutoPunish skips the PMs, logs each one it skipped, and shows a warning on the Settings page until a new sender is chosen.

What each message says:

| | Body | Subject |
|---|---|---|
| Applied | The tier's **Notification text** | *PM/Email subject line* |
| Expired | *Expiry notification text* | *Expiry PM/Email subject line* |
| Ended early | *Commutation notification text* | *Early end PM/Email subject line* |

Any field left blank falls back to a built-in default text in the recipient's language (for example "Your punishment has expired."). The *PM/Email footer text* is appended to every PM and email. All of these support the four placeholders, and BBCode is allowed in PM and email texts. Every notification is written in the recipient's own board language, including the wording of `{DURATION}`: PMs and emails when they are sent, bell notifications when they are shown.

### Managing individual users

AutoPunish adds an **AutoPunish** tab to the ACP **Manage Users** page (**Users → Manage users → select user → AutoPunish**).

**Status summary** — shows the user's current state at a glance:

| Field | Description |
|---|---|
| Active warnings | Current warning count on the account. |
| Total punishments | How many punishments the user has received in total. |
| Commuted offenses | How many offenses have been manually forgiven. |
| Effective offense count | Total minus commuted — determines which tier applies next. |
| Next offense would be | The tier that would fire on the next warning, including the threshold it activates at. Marked *repeating last tier* when the user is beyond the last configured tier, and *inactive* when it is a group tier without a valid group. |
| Current active punishment | The ongoing punishment's offense number, action, reason, and expiry date (or *Permanent*). Only shown when a punishment is currently active. |

**End punishment early** — available when a punishment is active. Removes the punishment immediately — taking the user out of the punishment group, or reactivating a deactivated account, unless that membership or deactivation was already there before the punishment — and notifies the user. The offense remains on the record. An optional *step-down* checkbox simultaneously applies the previous tier as a new, lighter punishment.

**Commute offenses** — reduces the user's effective offense counter by a chosen amount. The next punishment will use a correspondingly lower tier. Does not affect any currently active punishment.

**Punishment history** — a full log of every punishment the user has received, including offense number, action, group, reason, start date, expiry date, and status (active / expired).

## Permissions

| Page | Who can use it |
|---|---|
| **Extensions → AutoPunish** (Settings and Tiers) | Administrators with *Can alter board settings* |
| **Manage Users → AutoPunish** tab | Anyone with *Can view and manage AutoPunish in Manage Users* |

The Manage Users tab has its own admin permission, *Can view and manage AutoPunish in Manage Users* (`a_autopunish`), listed under **Users & Groups** in the admin permissions. On install it is granted to the four standard admin roles — Full, Standard, Forum and User & Groups admin — so administrators have it by default. Administrators whose permissions are set directly rather than through one of those roles need to be granted it by hand.

You can grant it to anyone else who should manage AutoPunish for individual users without being able to change its settings — for example, global moderators:

1. Go to **Permissions → Group permissions** (under *Global permissions*), select **Global moderators**, and open **Admin permissions**.
2. Under **Users & Groups**, set *Can view and manage AutoPunish in Manage Users* to **Yes**, and apply.

The group also needs some way into the ACP and the Manage Users page — in practice, *Can manage users*. Changing a group's admin permissions requires the Full Admin role.

## Admin log

Everything AutoPunish does is written to the admin log (**Maintenance → Admin log**): punishments applied, expired, restarted, escalated and ended early, commutations, dry-run results, retroactive scan totals, users not punished because their tier is inactive or the group could not be joined, and PMs skipped because the sender account is missing. Actions taken from the Manage Users tab are logged under the name of the person who took them.

## Changelog

### 1.2.0 — 2026-11-07

**Fixed**

- **Disabling the extension broke the board for anyone with an AutoPunish notification.** The three notification types stayed switched on after the extension was disabled, so phpBB went on loading those members' notifications and failed to find the code behind them. Every page they opened showed a "Whoops" error, and *Delete data* did not clear it. The types are now switched off when the extension is disabled and back on when it is enabled, and *Delete data* removes them along with their notifications.
- **Ending a punishment could remove a membership AutoPunish had not added.** A user who was already in a tier's group was "added" again, and when the punishment ended they were taken out of the group they had been in all along. Each punishment now records whether it made the change itself, and ending it only undoes that change. The same applies to deactivation: an account that was already deactivated stays deactivated when the punishment ends.
- **A group tier with no group, or a deleted one, still "punished" users.** A punishment was recorded and the user notified, but nothing happened to the account. A group tier now needs a group that exists. The Tiers page refuses to save one without it, and keeps what you typed so you can fix it. A stored tier without a valid group, such as the default tiers before you choose their groups, is **inactive**: nobody is punished at it, the skip goes to the admin log, and the Tiers page and the Manage Users tab both show which tiers are inactive. An active punishment is no longer ended to make way for an inactive tier.
- **Each save of the Tiers page escaped the text again.** A reason or notification text containing `&`, `"`, `<` or `>` gained another layer of HTML escaping every time the page was saved, so `Q&A` became `Q&amp;A`, then `Q&amp;amp;A`. Text now stays exactly as it was typed. Text already stored with extra escaping keeps it, and needs correcting by hand once.
- **Bell notifications from the system cron showed raw language keys.** An expiry run by `bin/phpbbcli.php cron:run` produced text like "Your AUTOPUNISH_DURATION_PERMANENT punishment has expired", because the command line does not load the extension's language file. Bell text is now worded when it is shown, in the reader's own language, as PMs and emails already were. Notifications created by earlier versions still show as they were.
- **Bell notifications ignored the user's notification settings.** Turning off an AutoPunish notification in the UCP had no effect. Those settings are now respected. The bell is the only method offered for these notifications, since PMs and emails are AutoPunish's own channels, controlled on its Settings page.
- **Emails contained HTML entities and BBCode.** The email body was the PM text as stored, so a recipient could see `&quot;` and `[b]...[/b]`. Emails are now plain text, with BBCode removed and entities decoded.
- **A rejected Settings save still changed some settings.** The sender was checked after seven settings had already been written, so turning PM notifications on with no sender was refused but left PMs switched on with nothing to send them. The sender is now checked first, and a refused save changes nothing.
- **The Manage Users tab showed internal identifiers.** The action column showed `group` and `deactivate`, the group column showed a number, and built-in groups appeared as `REGISTERED` and the like, both there and in the group lists on the Settings and Tiers pages. All of them now show their translated names.

**Changed**

- **phpBB 3.3.1 or later is required.** The Manage Users tab builds its return links from information that phpBB only passes to extensions from 3.3.1 on.
- The default tiers install with empty notification texts, so each recipient gets the built-in default in their own language. Text filled in at install time was in the administrator's language and replaced that default for every recipient. Boards that already have the default tiers keep their texts.
- The retroactive scan reads users in batches of 500, with three queries per batch for their punishment counts and warnings, instead of several queries per user.
- The Hebrew translation of the sender field described a numeric user ID that could be left as 0. It now describes the username the field actually takes. The subject-line help text in all five languages said a blank subject used the tier's notification text, when it uses the notification title.
- The one-line conditionals the validation notes pointed out are written out with braces, as the phpBB coding guidelines ask.

**Upgrading**

This release includes a database change; see [Updating](#updating). Punishments recorded before the update are treated as having made their own change, as before.

After updating, open the Tiers page: any tier listed as inactive needs a group before it will punish anyone.

### 1.1.1 — 2026-10-15

**Fixed**

- **A deactivation could lapse and leave the account deactivated with nothing to undo it.** The Punishment Tiers page hides the duration of a "deactivate account" tier, but still submitted and saved it, so a tier switched from "add to group" to "deactivate" kept its old duration. Its punishments were given an end time, and when that came the expiry cron marked them expired, which only ever removes a group membership. The account stayed deactivated, and with no active punishment left there was no *End punishment early* button to reactivate it from.

  Deactivation is now always permanent, as documented: a deactivate tier is saved without a duration, and one already stored is ignored when punishing. The update clears the duration from existing deactivate tiers and makes any active deactivation permanent. A deactivation that has already lapsed is left as it is — if one of your users is in that state, reactivate the account from **Manage users** in the usual way.

- **Dry-run mode logged punishments that would not have happened, and changed active punishments for real.** The dry-run log entry was written before the warning threshold was checked, so every warning given to a user without an active punishment logged "would be punished", even far below the threshold. And for a user who already had an active punishment, dry run was not checked at all: the next warning restarted, replaced or escalated that punishment exactly as if dry run were off.

  Dry run now logs only a punishment that would really be applied, and leaves active punishments untouched in all three *Warning during active punishment* modes, logging what it would have done instead.

- **The missing-sender warning said email notifications had stopped.** When the account chosen as the notification sender is deleted, only private messages stop — emails are sent from the board's own address and keep going. The warning on the Settings page and the admin log entry now say PMs only.

**Upgrading**

This release includes a database change; see [Updating](#updating).

### 1.1.0 — 2026-10-15

**Changed**

- **The AutoPunish tab in Manage Users has its own permission.** It used to require *Can alter board settings* (`a_board`), so the only way to show it to someone was to give them the board settings as well. It now requires the new admin permission *Can view and manage AutoPunish in Manage Users* (`a_autopunish`), listed under *Users & Groups* in the admin permissions, which can be granted to any user, group or role — for example global moderators who already have access to other Manage Users pages. The AutoPunish Settings and Punishment Tiers pages still require `a_board`. See [Permissions](#permissions).
- The README now documents all of the extension's behaviour: how punishments are triggered, the three modes for a warning during an active punishment, notifications, permissions, the admin log, updating, and what uninstalling leaves behind.

**Upgrading**

This release includes a database change; see [Updating](#updating). It adds the permission to the stock Full, Standard, Forum and User & Groups admin roles, so admins who use those roles keep the tab. Admins whose permissions are set directly rather than through one of those roles need to be granted `a_autopunish` by hand.

### 1.0.3 — 2026-10-03

Addresses the notes from the phpBB Extension Customisations Team's validation of 1.0.0.

**Fixed**

- **Email notifications were never sent, and the request that asked for one died.** phpBB's messenger looks for an extension's email template under `language/<lang>/email/`, appending `.txt` to the name itself. The template shipped at `styles/all/template/notification_email.html`, so the lookup never found it and threw. With *Enable email notifications* switched on, the punishment row was written and the request then aborted: a moderator issuing a warning got a general error page after the punishment had already been applied, and a run of the expiry cron stopped at the first punishment it had to notify about. The template now lives at `language/<lang>/email/notification_email.txt` in all five bundled languages.

- **The Punishment History table never appeared.** `{punishment_history.S_NUM_ROWS}` referenced outside its own loop resolves to nothing in phpBB 3.3, so the "No punishment history." branch always won — users with confirmed punishment rows showed as having none. The condition is now phpBB's empty-block test, `<!-- IF not .punishment_history -->`.

- **"End punishment early" skipped its confirmation on some translations.** The confirmation text was interpolated raw into a JavaScript string literal, so a translation containing an apostrophe — the bundled French `L'infraction`, for one — terminated the string early and let the click through without asking. The punishment itself always ended correctly; what was lost was the accidental-click guard.

- **Group names and tier text are now escaped for the context they are rendered into.** A group name containing a double quote broke out of the `data-name` attribute on the Punishment Tiers page and was then concatenated into HTML by the *Add tier* JavaScript; a tier reason containing a quote broke the reason field's `value` attribute. Rows added by *Add tier* are now assembled with DOM calls instead of HTML strings.

**Changed**

- The default punishment tiers now install in the administrator's language, falling back to the board default and then to English. They remain editable in the ACP afterwards, exactly as before.
- Tier rows are written in a single batched statement in both the installer migration and the ACP save.
- The "(autocomplete)" hint beside the notification sender field is now a translatable language key.
- Removed two language keys that nothing referenced, `ACP_AUTOPUNISH_USER` and `AUTOPUNISH_SELECT_USER`.
- The 1.0.1 upgrade backfill stamps every pre-existing punishment in one statement rather than one per distinct warning id, which leaves no SQL query inside a loop anywhere in the extension. The stamps it produces are unchanged.

**Upgrading**

Nothing to do beyond the usual update step, and no database changes. Boards which already had email notifications enabled will begin actually sending them.

### 1.0.2 — 2026-09-19

**Fixed**

- **Adding a punishment tier overwrote the last existing tier instead of appending a new one.** The tier rows rendered by the server were keyed from one (`tiers[1]` … `tiers[4]`) while the "Add tier" button keyed the row it appended from zero, so on a board with four tiers the new row was named `tiers[4]` — the same key as the existing fourth row. The browser submitted both under that key, the later one won, and the fourth tier was replaced by the blank new one. The table showed five rows; only four were saved.

  The row number shown in the first column and the form array key are now separate values, so the appended row can never collide, and the rows are re-keyed after every add and remove.

### 1.0.1 — 2026-09-19

**Fixed**

- **The retroactive scan no longer re-punishes users for warnings they have already served.** phpBB leaves a user's warning count on their account after a punishment ends, so a user punished at three warnings still had three warnings once it expired. Any later run of the retroactive scan saw them as eligible and punished them again — one tier higher each time, since the offense counter had gone up. Repeated over enough scans this escalated an unchanged account all the way to deactivation.

  Punishments now record the warning that triggered them, and a new punishment requires at least one warning newer than the last one already punished for. Warning ids are never reused, so this stays correct even after phpBB's warning pruning removes the underlying rows.

- The retroactive scan's log entry counted every user it examined rather than every user it punished, overstating "%d users punished".

**Upgrading**

Punishments recorded before this release are backfilled automatically when the extension's migrations run: each is stamped with the newest warning that was already on the account when it was applied. No action is needed beyond the usual enable/update step, and no user is punished as a side effect of upgrading.

Nothing changes for punishments triggered by a moderator issuing a warning — that path was never affected.

### 1.0.0 — 2026-04-19

- Initial release.

## Uninstallation

1. In the ACP, go to **Customise → Manage extensions**.
2. Disable **AutoPunish**, then click **Delete data**.
3. Remove the `ext/biochaos/autopunish/` directory from your board.

Disabling AutoPunish switches its notifications off, so members who have one see nothing from it while it is disabled. *Delete data* removes AutoPunish's tables, settings, permission and notifications, but does not undo punishments that are still active: users stay in their punishment groups and deactivated accounts stay deactivated. End any active punishments you want lifted from the Manage Users tab before deleting the data.

## License

[GNU General Public License v2](license.txt)
