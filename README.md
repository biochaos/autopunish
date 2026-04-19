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

1. Download the latest release archive.
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
| Group | The group to add the user to (only for the "add to group" action). |
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

## Uninstallation

1. In the ACP, go to **Customise → Manage extensions**.
2. Disable **AutoPunish**, then click **Delete data**.
3. Remove the `ext/biochaos/autopunish/` directory from your board.

## License

[GNU General Public License v2](license.txt)
