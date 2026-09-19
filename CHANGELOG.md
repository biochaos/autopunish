# Changelog

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
