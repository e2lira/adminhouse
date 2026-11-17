# Dependency Map

```text
/ -> UsersController::login -> Auth -> Users table/session -> redirect
/pago/:id -> PagoController -> payment/accounting tables -> PDF/email/spreadsheet effects
tests/bootstrap.php -> config/bootstrap.php -> app configuration -> datasource
Comunicado/Ticket/Mensaje templates -> direct public attachment URL -> web server -> attachment bytes
Cuotas/Pago views and controllers -> `webroot/files/Excel/archivo` -> export/import bytes
Condominio/Usuario/Reportes views -> `webroot/files/Condominio/logo` -> logo bytes
Supervision view -> `webroot/files/Areas/imagen` -> area image bytes
Apache static delivery -> previously `webroot/files/**/.DS_Store` -> filesystem metadata bytes (both artifacts removed; runtime non-reachability pending)
```

| Consumer | Dependency | Control |
|---|---|---|
| Login tests | Cake Auth and Users fixture | G3 uses test-only hash and captures observable redirects. |
| Payment-policy tests | `PagoController::isAuthorized()` | Direct policy call only; no route dispatch. |
| Test bootstrap | app bootstrap/datasource | G3 requires explicit dedicated datasource and fail-fast assertion. |
| File access | public-webroot attachment, import/export, and presentation-asset paths | FILE-001 inventories each category, separates binary records from presentation assets, migrates private content, and serves protected content only through authorization. |
| TOTP | Auth session lifecycle | Deferred until login regression evidence exists. |

External effects: Dompdf/TCPDF/FPDF, PhpSpreadsheet/Cake Excel, and Cake Mailer. G3 must invoke none of them.
