# Mobile App Integration Feasibility — GegoK12 Teacher & Parent Apps

_2026-10-27 · Answers the ten investigation questions for Requirement 7. No APKs were downloaded; no mobile software was introduced._

## Answers to the investigation questions

**1. Do official apps exist?** Yes. Google Play lists **"GegoK12 - Teacher App"** and **"GegoK12 Parent: School App"**, publisher **GegoSoft Technologies** (the GegoK12 vendor): [developer page](https://play.google.com/store/apps/dev?id=8582811272927365111). [gegok12.com](https://gegok12.com/) links both and markets attendance notifications, homework, and exam results in-app.

**2. Versions available?** Unknown — the Play listings expose no version info to our tooling and the open-source repo contains none.

**3. Source files or installable builds included in our distribution?** **No.** The repo has no mobile source. The server even has self-update endpoints for the apps (`/api/apk/parent`, `/api/apk/teacher` → `ApkController`) that read `public/android-apk/{parent,teacher}_apk_detail.json` — **that folder is absent**, and on production those endpoints return an empty body (verified 2026-10-27). This strongly suggests GegoSoft normally ships APKs + metadata per installation.

**4. Is the backend API implemented and compatible?** The API layer exists in our deployed code and responds on production (verified 2026-10-27): public endpoints `/api/schools/list` (200), `/api/school/info` (200), `POST /api/parent/login` (responds; parents authenticate with mobile number + password + device id, usergroup 7, sanctum token via `createToken`) — plus an authenticated `v2` suite (children, attendance view, leave, messages, events, holidays, magazine) and a parallel teacher API (`routes/teacherapi.php`: attendance **entry**, assignments/marks, leave, messages). Caveats: **fee and exam API routes are commented out upstream** (add-on-dependent); `config/auth.php` declares an unused `passport` driver for the `api` guard (routes actually use `auth:sanctum`, so this is dormant, but it would break anything that ever referenced the `api` guard).

**5. Can the server's API URL be configured in the apps?** **Unknown — this is the decisive question.** No public documentation describes how the Play-Store builds choose a server (options: hardcoded vendor cloud; per-customer APK builds — consistent with the `/api/apk/*` design; or an in-app school/server selector, possibly fed by `/api/schools/list`). docs.gegok12.com's getting-started page doesn't cover it and its Parent-App page 404s.

**6. Login endpoints compatible?** Server-side, yes (above). App-side unprovable without the app pointing at our host.

**7. Can data synchronize?** Attendance, messages, leave, events, children data: the endpoints exist and the web side writes the same tables, so **if** the apps can reach our server, those should work. Fees and exam marks **cannot** sync — their API routes are disabled and the modules are paid add-ons. Push notifications additionally require Firebase (FCM) credentials we do not have (`SinglePushEvent` infrastructure exists; no keys configured).

**8. Does the original five-phase specification include this integration?** **Cannot be determined — the specification is not in the workspace** (see `ORIGINAL_SCOPE_VS_CLIENT_SPECIFICATION.md`). The *recorded* scope excludes "native mobile apps", and the parent-app gap was documented to the client on 2026-10-22 (`SCOPE_REVIEW.md`: "the GegoK12 Android parent app is NOT bundled… client must obtain it from GegoK12 separately. Backend parent API is present.").

**9. What would be required?**
1. **Vendor contact (GegoSoft / gegok12.com)** — ask: how do the Play-Store apps select a server? Do you provide per-customer APK builds for self-hosted installs, and at what cost?
2. If apps support pointing at our server: configure `android-apk` metadata, verify TLS chain on Android (Let's Encrypt is fine), create parent/teacher accounts, set up FCM for push, end-to-end test on real devices — est. 4–8 h plus vendor dependency.
3. If apps are vendor-cloud-only: integration is impossible without a vendor-built APK or custom app development (80+ h — out of all proportion).

**10. What can be proven without devices/builds?** Only what is proven above: the server API surface is deployed, reachable over HTTPS, and token issuance code is in place. **No claim is made that the mobile apps work with this server.**

## Verdict

Backend: ready (within core limits). App connection: **unprovable today; vendor-gated**. Classification: **REQUIRES CLIENT INPUT** (vendor inquiry) and, per the recorded agreement, **OUTSIDE CONFIRMED SCOPE**. Recommended next step costs nothing: the client emails GegoSoft with their server URL and asks question 9.1.

Sources: [Google Play — GegoSoft developer page](https://play.google.com/store/apps/dev?id=8582811272927365111) · [gegok12.com](https://gegok12.com/) · [GitHub Gego-K12/gegok12](https://github.com/Gego-K12/gegok12) · [docs.gegok12.com getting started](https://docs.gegok12.com/documentation/getting-started)
