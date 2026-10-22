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

## 4. Phase N — approved minor changes (implemented)
- **Bilingual slogans (EN + 中文):** stored in the existing `moto` meta field as
  `Nurturing Youthful Minds, Embracing Holistic Growth | 言傳身教 希冀成材`. `moto`
  validation raised **50 → 150** chars (`DetailRequest`) to hold both. Displayed in the
  existing **login branding area** (`auth/login.blade.php`, under the logo) — split on
  `|` so the English and Chinese lines each show; mobile-safe (`text-center`, `break-words`,
  responsive text). Only this view is affected; editable via the existing `moto` form field.
- **Hong Kong school-profile form:** `DetailRequest` validation relaxed so legitimate HK
  data is accepted — `landline_no` and `pincode` are now optional and format-free (HK
  phones are 8 digits / +852; HK has no PIN codes), and `affiliated_by`/`affiliation_no`/
  `board`/`date_of_establishment` are now optional. Name/address/location unchanged.
  Verified: HK data passes, existing Indian-format data still passes, over-150 `moto`
  still rejected (`tests/Regression/hk_profile_validation_check.php`).
- **Chinese Unicode:** stored and displayed correctly (utf8mb4; Eloquent round-trip
  verified). **Note:** editing Chinese via the web profile form should be confirmed in a
  real browser at UAT — the automated curl test harness (Windows) mangled multibyte
  argument bytes, which is a test-tool limitation, not an app defect (the app's data
  layer preserves Chinese).

## 5. Still requires developer assistance / client input (not implemented)
- **School colours / theme:** no built-in theme-colour setting (a theme editor is out of scope).
- **Logo:** pending the client's official asset (upload via school profile / site settings).
- **Dedicated 中文 address field:** the Chinese address is currently combined with the
  English one in the single `address` field; a separate field would need a minor addition.

## 6. Test-safety safeguard (see KNOWN_LIMITATIONS §7)
`tests/TestCase.php` now aborts (skips) any test whose database is not in-memory SQLite, **before** `RefreshDatabase`'s `migrate:fresh` can run — so the dev MySQL DB can never be wiped by the test suite. Backed by `.env.testing` and `phpunit.xml` (`force="true"` sqlite). Verified: the guard skips MySQL-targeted tests; the dev DB stayed intact; the Unit suite + import regression still pass.
