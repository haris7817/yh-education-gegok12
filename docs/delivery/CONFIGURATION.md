# Configuration Record — YH Education (GegoK12)

_Phase M, 2026-10-22. Local installation configured with the client's details using built-in settings and safe one-time configuration. No new features added._

---

## 1. Applied configuration
| Item | Value | How applied | Verified |
|---|---|---|---|
| School name | YH Education | one-time DB config (`schools.name`) | ✅ displays |
| Address (EN + 中文) | 111-112, Savannah, Chi Sin Street, Tseung Kwan O / 將軍澳至善街3號Savannah 111-112 | one-time DB config (`schools.address`) | ✅ displays (Chinese renders correctly) |
| Phone | +852 54001530 | one-time DB config (`schools.phone`) | ✅ stored |
| Email | yh.eduhk@gmail.com | one-time DB config (`schools.email`) | ✅ stored |
| Currency | HK$ | `.env` `CURRENCY_SYMBOL` → `config('app.currency_symbol')` | ✅ |
| Timezone | Asia/Hong_Kong | `.env` `TIMEZONE` | ✅ |
| WhatsApp country code | 852 | `.env` `WHATSAPP_COUNTRY_CODE` | ✅ (button unchanged) |

**Unicode:** Chinese text was confirmed saved (utf8mb4 round-trip) and displayed in the app (`將軍澳至善街` renders on the school profile page). Verified to persist across a full `docker compose restart`.

## 2. Why one-time DB configuration (not the built-in edit form)
The built-in school-profile edit form (`SchoolDetailsController` + `DetailRequest`) is **India-centric and cannot accept Hong Kong data**:
- `landline_no` → `numeric|digits:10` (HK numbers are 8 digits)
- `pincode` → `numeric|digits:6` (HK has no postal codes)
- `affiliation_no` → `required|numeric`, `board`/`date_of_establishment` → `required`
- `moto` → `required|max:50`

So the form rejects valid HK details. The task-permitted **safe one-time configuration** (direct update of the single school record) was used instead. Values display correctly; the form simply can't be used to re-edit them for a HK school without relaxing that validation (see §4).

## 3. Not configured — pending client asset
- **Logo:** not generated/invented. Upload is pending the client's official logo (built-in: school profile `school_logo`, or site `sitelogo`). Leave as-is until supplied.

## 4. Requires developer assistance (outside built-in config — not implemented)
- **Slogans (EN + 中文):** the only slogan-like field, `moto`, has `max:50` validation — the English slogan alone is 51 characters — **and `moto` is not rendered in any view**. So it can neither hold both slogans (validation) nor display them. Storing them there was therefore **not** done (per the task proviso "provided this does not break its display or validation"). A minimal change (raise `moto` to e.g. `max:150` and render it) would enable it. **Flagged for approval; not implemented.**
- **School colours / theme:** no built-in theme-colour setting exists. Not implemented (and a theme editor is explicitly out of scope).
- **HK-friendly profile form:** relaxing the India-centric validation (phone/pincode/affiliation) would make the built-in edit form usable for YH Education. Minor change, flagged for approval.
- **Chinese address as a separate field:** stored combined with the English address (one `address` field exists). A dedicated 中文 address field would need a minor addition.

## 5. Test-safety safeguard (see KNOWN_LIMITATIONS §7)
`tests/TestCase.php` now aborts (skips) any test whose database is not in-memory SQLite, **before** `RefreshDatabase`'s `migrate:fresh` can run — so the dev MySQL DB can never be wiped by the test suite. Backed by `.env.testing` and `phpunit.xml` (`force="true"` sqlite). Verified: the guard skips MySQL-targeted tests; the dev DB stayed intact; the Unit suite + import regression still pass.
