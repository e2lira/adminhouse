# Compatibility Matrix

| Interface | Preserve | G2 Decision | G3 Evidence |
|---|---|---|---|
| Login entry | Existing `/` and `/users/login` flow | No SEC-001 behavior change | HTTP 200 tests |
| Login redirects | Existing profile behavior | TOTP deferred | Capture actual 3xx Location |
| Payment policy | Profiles 1/2/5 allowed | No business change | Direct policy assertions |
| Payment actions | Current persistence/PDF/mail/spreadsheet | Excluded | No dispatch/side effects |
| Protected file delivery | Current Comunicado, Ticket, Mensaje, and Excel public paths | Replace protected UI/controller paths with authorized delivery for owner/same-condominium admin; legacy direct URLs intentionally become 404 after migration | Category/record/checksum manifest, authorized/denied/cross-tenant tests, migration rehearsal |
| Presentation assets | Current Condominio logos and Areas images use public paths | Decide and document whether each asset remains explicitly public or moves behind policy; do not silently inherit attachment policy | Asset classification, owner/tenant evidence, and compatibility review |
| Filesystem metadata | Root and `Mensaje/adjunto` `.DS_Store` artifacts were publicly served with existing files | No consumer or compatibility contract; both were removed under user authorization | Verify 404/not-found at the web-server boundary and confirm no application reference or restoration |
| Runtime | CakePHP 3.9 legacy | No upgrade in hardening | Separate migration |
