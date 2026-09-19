# AutoPunish

A phpBB extension that automatically punishes users when they accumulate warnings, with configurable punishment tiers and durations.

## Features

- Define any number of punishment tiers, each triggered at a configurable warning threshold
- Two punishment actions per tier: add user to a group (e.g. a restricted posting group) or deactivate the account
- Configurable duration per tier: days, weeks, months, or permanent
- Automatic expiry via cron — no manual intervention required
- Escalation options when a warning is issued while a punishment is already active: restart the timer, apply normal threshold rules, or escalate immediately to the next tier
- Commute (reduce) a user's effective offense count from the ACP Manage Users page
- End an active punishment early from the ACP, with optional step-down to the previous tier
- Three notification types (bell, PM, email) for punishment applied, expired, and ended early — each with configurable subject, body, and footer, supporting `{USERNAME}`, `{DURATION}`, `{REASON}`, and `{OFFENSE_NUMBER}` placeholders
- Dry-run mode: logs what would happen without applying punishments — useful for verifying thresholds before going live
- One-shot retroactive scan: punishes users who already meet a threshold when the extension is first enabled
- Exempt groups: users in selected groups are never punished
- Available in English, French, Spanish, Portuguese, and Hebrew

## Requirements

- phpBB 3.3.0 or later
- PHP 7.1.3 or later

## Installation

1. [Download the latest release archive](https://github.com/biochaos/autopunish/releases/latest).
2. Extract and copy the `biochaos/autopunish/` directory into your board's `ext/` directory, so the path is `ext/biochaos/autopunish/`.
3. In the ACP, go to **Customise → Manage extensions** and enable **AutoPunish**.

## Configuration

All settings are in the ACP under **Extensions → AutoPunish**.

### Settings page

| Setting | Description |
|---|---|
| Enable AutoPunish | Master on/off switch. |
| Warning during active punishment | What to do when a user receives a warning while already under a punishment: restart the timer, apply normal threshold rules, or escalate immediately to the next tier. |
| Expiry check interval | How often (in minutes) the cron task checks for expired punishments. |
| Exempt groups | Users in these groups are never punished. |
| Dry-run mode | Log punishments without applying them. Disable once you are satisfied with your tier configuration. |
| Retroactive scan | Tick and save once to immediately punish all users who already meet a threshold. The checkbox resets automatically after the scan runs. |
| Notifications | Enable bell notifications, PMs, and/or emails. PM and email notifications require a sender account. Each notification type (applied, expiry, early end) has its own configurable subject line and body text. |

### Tiers page

Each row defines one punishment tier. The row position is the offense number (1st offense uses row 1, 2nd uses row 2, and so on). The last tier repeats for all offenses beyond the last configured row.

| Column | Description |
|---|---|
| Warning threshold (≥) | Minimum active warning count that triggers this tier. |
| Action | Add to group or deactivate the account. |
| Group | The group to add the user to (only for the "add to group" action). Use a group that AutoPunish alone manages — if another extension also controls its membership, it can re-add users after AutoPunish removes them on expiry. |
| Duration | Length of the punishment in days, weeks, or months. Set to 0 for permanent. |
| Reason | Internal note visible to administrators in the log. |
| Notification text | Body text sent to the user via PM/email for this tier. Supports `{USERNAME}`, `{DURATION}`, `{REASON}`, `{OFFENSE_NUMBER}`. |

### Managing individual users

AutoPunish adds an **AutoPunish** tab to the ACP **Manage Users** page (**Users → Manage users → select user → AutoPunish**).

**Status summary** — shows the user's current state at a glance:

| Field | Description |
|---|---|
| Active warnings | Current warning count on the account. |
| Total punishments | How many punishments the user has received in total. |
| Commuted offenses | How many offenses have been manually forgiven. |
| Effective offense count | Total minus commuted — determines which tier applies next. |
| Next offense would be | The tier that would fire on the next warning, including the threshold it activates at. Marked *repeating last tier* when the user is beyond the last configured tier. |
| Current active punishment | The ongoing punishment's offense number, action, reason, and expiry date (or *Permanent*). Only shown when a punishment is currently active. |

**End punishment early** — available when a punishment is active. Removes the punishment immediately; the offense remains on the record. An optional *step-down* checkbox simultaneously applies the previous tier as a new, lighter punishment.

**Commute offenses** — reduces the user's effective offense counter by a chosen amount. The next punishment will use a correspondingly lower tier. Does not affect any currently active punishment.

**Punishment history** — a full log of every punishment the user has received, including offense number, action, group, reason, start date, expiry date, and status (active / expired).

## Changelog

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

## License

[GNU General Public License v2](license.txt)
