# Legacy Feature Inventory (REBUILD-001)

- Status: DRAFT 2026-11-17 (unreachable-controller decisions taken the same day, see Decisions log), generated from `src/Controller` by static analysis, plus the authorization inventory and the page sweeps of the same day. Awaiting user review.
- Purpose: parity checklist for the CakePHP 5 rebuild (ADR-0006). Every public action of the legacy application is listed with a decision: **implement** (same behaviour in the new model), **redesign** (replaced by an ADR-0005 element), **retire** (not carried over), or **decide** (needs a user decision).
- Traits come from the code: `write`, `delete`, `form` (handles POST), `json` (AJAX endpoint), `pdf`, `excel`, `email`, `upload`. `tpl` means the action has its own template.
- Not covered by this static pass: behaviour inside templates and JavaScript, and business rules hidden in long actions; those are captured module by module when each is rebuilt.

## Summary by module

| Module (build order) | Legacy controllers | Actions | implement | redesign | retire | decide |
|---|---|---|---|---|---|---|
| Identity and RBAC | Administrador, Asigna, Perfil, Users, UsersCondominio | 39 | 0 | 34 | 5 | 0 |
| Developments and units | Condominio, Condomino | 23 | 0 | 22 | 1 | 0 |
| Fees and charges | Adeudos, Bloque, Cuotas, Indiviso | 35 | 0 | 34 | 1 | 0 |
| Payments and receipts | Favor, Folios, Pago, RecibosPago | 47 | 6 | 27 | 14 | 0 |
| Reports | Reportes | 15 | 0 | 15 | 0 | 0 |
| Expenses and bank | Bancaria, Banco, Caja, Conceptos, Gasto | 37 | 36 | 0 | 1 | 0 |
| Closings | CierreCondomino, SaldoCierre, SaldoCondominio, SaldoCondomino | 22 | 0 | 12 | 10 | 0 |
| Catalogs | Categoria, Proveedores, Rubro, TipoCuota, TipoGasto, TipoPago, TipoVisita | 36 | 36 | 0 | 0 | 0 |
| Communications | Comunicado, Email, Mensaje, Recordatorio, Reglamento, Respuestas, Ticket | 39 | 29 | 5 | 5 | 0 |
| Amenities | Amenidad, AmenidadCondominio, Calendario, Events, FullCalendar | 22 | 13 | 0 | 9 | 0 |
| Gate | Visitas | 5 | 0 | 5 | 0 | 0 |
| Resident portal | Usuario | 17 | 0 | 17 | 0 | 0 |
| Inspections | Areas, Supervision | 11 | 6 | 5 | 0 | 0 |
| Files and imports | Excel | 5 | 0 | 5 | 0 | 0 |
| Dashboard | Pages, Tablero | 4 | 0 | 2 | 2 | 0 |
| - | Pendientes, UsuarioCondomino | 10 | 0 | 0 | 10 | 0 |
| **Total** | | **367** | **126** | **183** | **58** | **0** |

## Cross-cutting flows

- **Email sending** (5): `Email::send`, `Pago::add`, `Pago::sendEmail`, `Pago::sendEmailToClient`, `Recordatorio::sendEmail`
- **PDF output** (5): `Condomino::view`, `Pago::createFilePdf`, `Reportes::pdfInforme`, `Tablero::recibo`, `Tablero::view`
- **Spreadsheet import or export** (15): `Cuotas::xlsx`, `Cuotas::excel`, `Cuotas::insertar`, `Excel::index`, `Excel::view`, `Excel::add`, `Excel::edit`, `Excel::delete`, `Pago::index`, `Pago::xlsx`, `Pago::excel`, `Pago::insertar`, `Pago::actualizaFavor`, `Pago::actualizaFavorActual`, `Pago::actualizaFavorActua`
- **File upload** (3): `Condominio::uploadFiles`, `Condomino::uploadFiles`, `Gasto::uploadFiles`

## Decisions needed from the user

1. **Controllers unreachable today**: resolved 2026-11-17, one by one (see Decisions log).
2. **Probable dead stubs**: resolved 2026-11-17 together with their controllers (Email retired, UsersCondominio redesigned as role assignments).
3. **Debug helpers exposed as actions**: `Condominio::pa` and `Gasto::pa` print their URL argument with `print_r` and no escaping, a reflected XSS for signed-in staff (risk AZ-8). Retired in the rebuild; in the legacy reference they can be made `protected` if the legacy app is ever served.
4. **Reports**: `ReportesController` is redesigned as a whole; the report list to rebuild (monthly close, half-year and annual reports, unit statements, receipts) is confirmed with the user when that module starts.

## Actions by module

### Identity and RBAC

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Administrador::add` | - | form, redirect, write | yes | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::asigna` | - | - | yes | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::borrar` | id | delete, json | no | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::delete` | id | delete, redirect | no | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::edit` | id | form, redirect, write | yes | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::getCondominios` | - | json | no | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::getPerfiles` | - | json | no | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::index` | - | json | yes | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::login` | - | form, redirect | yes | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::logout` | - | redirect | no | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::password` | - | - | yes | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Administrador::view` | id | json | yes | reachable | redesign | staff users and role assignments (RH-006) |  |
| `Asigna::add` | id_condomino | form, redirect, write | yes | reachable | redesign | `occupancies` and resident assignments |  |
| `Asigna::delete` | id | delete, redirect | no | reachable | redesign | `occupancies` and resident assignments |  |
| `Asigna::edit` | id | form, redirect, write | yes | reachable | redesign | `occupancies` and resident assignments |  |
| `Asigna::index` | - | - | yes | reachable | redesign | `occupancies` and resident assignments |  |
| `Asigna::view` | id | - | yes | reachable | redesign | `occupancies` and resident assignments |  |
| `Perfil::add` | - | form, redirect, write | yes | reachable | retire | replaced by the fixed role catalogue |  |
| `Perfil::delete` | id | delete, redirect | no | reachable | retire | replaced by the fixed role catalogue |  |
| `Perfil::edit` | id | form, redirect, write | yes | reachable | retire | replaced by the fixed role catalogue |  |
| `Perfil::index` | - | - | yes | reachable | retire | replaced by the fixed role catalogue |  |
| `Perfil::view` | id | - | yes | reachable | retire | replaced by the fixed role catalogue |  |
| `Users::add` | - | form, redirect, write | yes | reachable | redesign | users, sign-in, TOTP |  |
| `Users::asigna` | - | - | yes | reachable | redesign | users, sign-in, TOTP |  |
| `Users::borrar` | id | delete, json | no | reachable | redesign | users, sign-in, TOTP |  |
| `Users::delete` | id | delete, redirect | no | reachable | redesign | users, sign-in, TOTP |  |
| `Users::edit` | id | form, redirect, write | yes | reachable | redesign | users, sign-in, TOTP |  |
| `Users::getCondominios` | - | json | no | reachable | redesign | users, sign-in, TOTP |  |
| `Users::getPerfiles` | - | json | no | reachable | redesign | users, sign-in, TOTP |  |
| `Users::index` | - | json | yes | reachable | redesign | users, sign-in, TOTP |  |
| `Users::login` | - | form, redirect | yes | reachable | redesign | users, sign-in, TOTP |  |
| `Users::logout` | - | redirect | no | reachable | redesign | users, sign-in, TOTP |  |
| `Users::password` | - | - | yes | reachable | redesign | users, sign-in, TOTP |  |
| `Users::view` | id | json | yes | reachable | redesign | users, sign-in, TOTP |  |
| `UsersCondominio::add` | - | form, redirect, write | no | unreachable (no rule) | redesign | `role_assignments` management screen (module 1, RBAC) | User decision 2026-11-17: essential; today no screen writes this table |
| `UsersCondominio::delete` | id | delete, redirect | no | unreachable (no rule) | redesign | `role_assignments` management screen (module 1, RBAC) | User decision 2026-11-17: essential; today no screen writes this table |
| `UsersCondominio::edit` | id | form, redirect, write | no | unreachable (no rule) | redesign | `role_assignments` management screen (module 1, RBAC) | User decision 2026-11-17: essential; today no screen writes this table |
| `UsersCondominio::index` | - | - | no | unreachable (no rule) | redesign | `role_assignments` management screen (module 1, RBAC) | User decision 2026-11-17: essential; today no screen writes this table |
| `UsersCondominio::view` | id | - | no | unreachable (no rule) | redesign | `role_assignments` management screen (module 1, RBAC) | User decision 2026-11-17: essential; today no screen writes this table |

### Developments and units

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Condominio::BorrarFile` | imagepath | delete, helper-call | no | reachable | redesign | `developments` |  |
| `Condominio::add` | - | form, redirect, write | yes | reachable | redesign | `developments` |  |
| `Condominio::bienvenido` | - | - | yes | reachable | redesign | `developments` |  |
| `Condominio::borrar` | id | delete, json | no | reachable | redesign | `developments` |  |
| `Condominio::delete` | id | delete, redirect | no | reachable | redesign | `developments` |  |
| `Condominio::edit` | id | form, redirect, write | yes | reachable | redesign | `developments` |  |
| `Condominio::getAmenidades` | - | json | no | reachable | redesign | `developments` |  |
| `Condominio::getCondominios` | - | json | no | reachable | redesign | `developments` |  |
| `Condominio::index` | - | json | yes | reachable | redesign | `developments` |  |
| `Condominio::indexCompleto` | - | - | yes | reachable | redesign | `developments` |  |
| `Condominio::menu` | id | - | yes | reachable | redesign | `developments` |  |
| `Condominio::pa` | arr | debug-output | no | reachable | retire | `developments` | debug helper exposed as a public action; prints URL input unescaped (reflected XSS, AZ-8) |
| `Condominio::uploadFiles` | folder, file, sub_folder | upload | no | reachable | redesign | `developments` |  |
| `Condominio::view` | id | - | yes | reachable | redesign | `developments` |  |
| `Condomino::BorrarFile` | imagepath | delete, helper-call | no | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::add` | id_condominio | form, redirect, write | yes | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::borrar` | id | delete, json | no | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::delete` | id | delete, redirect | no | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::edit` | id | form, redirect, write | yes | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::estatus` | id | form, redirect, write | yes | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::index` | id_condomino | - | yes | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::uploadFiles` | folder, file, sub_folder | upload | no | reachable | redesign | `units`, `people`, `occupancies` |  |
| `Condomino::view` | id, id_condomino | json, pdf | yes | reachable | redesign | `units`, `people`, `occupancies` |  |

### Fees and charges

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Adeudos::actualizaSaldo` | id | json, write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::add` | - | form, redirect, write | yes | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::ceros` | id | write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::cerrar` | id | write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::delete` | id | delete, redirect | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::edit` | id | form, redirect, write | yes | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::index` | id_condomino | json | yes | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::movimientoPagos` | id | write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::pagoCerrado` | monto, id_condomino, id_condominio | write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::pagoParcial` | id | write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::restaCuota` | id | write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::restarAdeudos` | id_condomino | form, redirect, write | no | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Adeudos::view` | id | - | yes | reachable | redesign | `charges` with payer snapshot (MD-R5) |  |
| `Bloque::add` | id_condominio | form, redirect, write | yes | reachable | redesign | batch charge generation per fee |  |
| `Bloque::delete` | id | delete, redirect | no | reachable | redesign | batch charge generation per fee |  |
| `Bloque::edit` | id | form, redirect, write | yes | reachable | redesign | batch charge generation per fee |  |
| `Bloque::index` | id_condominio | - | yes | reachable | redesign | batch charge generation per fee |  |
| `Bloque::view` | id | - | yes | reachable | redesign | batch charge generation per fee |  |
| `Cuotas::add` | id_condominio | form, redirect, write | yes | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::borrar` | id | delete, json | no | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::delete` | id | delete, redirect | no | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::edit` | id | form, redirect, write | yes | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::excel` | - | excel, form, redirect, write | yes | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::index` | id_condominio | json | yes | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::insertar` | - | debug-output, excel, redirect, write | no | reachable | retire | `fees` with method per fee (MD-R7) | debug helper exposed as a public action; prints URL input unescaped (reflected XSS, AZ-8) |
| `Cuotas::montoletras` | - | json | no | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::saveAdeudo` | cuota, id_condominio | write | no | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::view` | id | - | yes | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Cuotas::xlsx` | - | excel, write | no | reachable | redesign | `fees` with method per fee (MD-R7) |  |
| `Indiviso::add` | id_condominio | form, redirect, write | yes | reachable | redesign | indiviso fee method |  |
| `Indiviso::borrar` | id | delete, json | no | reachable | redesign | indiviso fee method |  |
| `Indiviso::delete` | id | delete, redirect | no | reachable | redesign | indiviso fee method |  |
| `Indiviso::edit` | id | form, redirect, write | yes | reachable | redesign | indiviso fee method |  |
| `Indiviso::index` | id_condominio | json | yes | reachable | redesign | indiviso fee method |  |
| `Indiviso::view` | id | - | yes | reachable | redesign | indiviso fee method |  |

### Payments and receipts

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Favor::add` | - | form, redirect, write | yes | unreachable (no rule) | retire | `credit_entries` ledger | User decision 2026-11-17: manual credit editing retired; credit is a ledger of entries, adjustments recorded with a reason |
| `Favor::delete` | id | delete, redirect | no | unreachable (no rule) | retire | `credit_entries` ledger | User decision 2026-11-17: manual credit editing retired; credit is a ledger of entries, adjustments recorded with a reason |
| `Favor::edit` | id | form, redirect, write | yes | unreachable (no rule) | retire | `credit_entries` ledger | User decision 2026-11-17: manual credit editing retired; credit is a ledger of entries, adjustments recorded with a reason |
| `Favor::index` | - | - | yes | unreachable (no rule) | retire | `credit_entries` ledger | User decision 2026-11-17: manual credit editing retired; credit is a ledger of entries, adjustments recorded with a reason |
| `Favor::view` | id | - | yes | unreachable (no rule) | retire | `credit_entries` ledger | User decision 2026-11-17: manual credit editing retired; credit is a ledger of entries, adjustments recorded with a reason |
| `Folios::add` | id_condominio | form, redirect, write | yes | reachable | implement | `receipt_series` |  |
| `Folios::borrar` | id | delete, json | no | reachable | implement | `receipt_series` |  |
| `Folios::delete` | id | delete, redirect | no | reachable | implement | `receipt_series` |  |
| `Folios::edit` | id | form, redirect, write | yes | reachable | implement | `receipt_series` |  |
| `Folios::index` | - | json | yes | reachable | implement | `receipt_series` |  |
| `Folios::view` | id | - | yes | reachable | implement | `receipt_series` |  |
| `Pago::actualizaAdeudo` | pago | delete, write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::actualizaFavor` | - | debug-output, excel, redirect, write | no | reachable | retire | `payments`, allocations, intents, receipts | debug helper exposed as a public action; prints URL input unescaped (reflected XSS, AZ-8) |
| `Pago::actualizaFavorActua` | - | debug-output, excel, redirect, write | no | reachable | retire | `payments`, allocations, intents, receipts | debug helper exposed as a public action; prints URL input unescaped (reflected XSS, AZ-8) |
| `Pago::actualizaFavorActual` | - | debug-output, excel, redirect, write | no | reachable | retire | `payments`, allocations, intents, receipts | debug helper exposed as a public action; prints URL input unescaped (reflected XSS, AZ-8) |
| `Pago::actualizaSaldo` | pago, id_condominio | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::add` | id_condominio | email, form, helper-call, json, write | yes | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::borrar` | id | delete, helper-call, json | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::cierreCondomino` | pago | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::condominio` | - | json | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::createFilePdf` | pago | pdf | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::delete` | id | delete, helper-call, redirect | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::deleteAdeudo` | pago | delete | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::deleteBanco` | pago | delete | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::edit` | id, id_condominio | form, helper-call, redirect, write | yes | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::excel` | - | excel, form, redirect, write | yes | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::getCondominos` | id_condominio | json | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::getDepartamentos` | - | json | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::index` | id_condominio | excel, json, write | yes | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::insertar` | - | debug-output, excel, redirect, write | no | reachable | retire | `payments`, allocations, intents, receipts | debug helper exposed as a public action; prints URL input unescaped (reflected XSS, AZ-8) |
| `Pago::montoletras` | - | json | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::pdf` | id | helper-call | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::saldoCondominio` | pago | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::saldoCondomino` | pago | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::saldoFavor` | pago, id_condominio | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::saveBanco` | pago, id_condominio | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::saveCierre` | pago | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::sendEmail` | pago | email, helper-call | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::sendEmailToClient` | id | email, helper-call, redirect | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::updateBanco` | pago | write | no | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::view` | id | - | yes | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `Pago::xlsx` | - | excel, write | yes | reachable | redesign | `payments`, allocations, intents, receipts |  |
| `RecibosPago::add` | - | form, redirect, write | yes | unreachable (no rule) | retire | receipt batches regenerated from payments |  |
| `RecibosPago::delete` | id | delete, redirect | no | unreachable (no rule) | retire | receipt batches regenerated from payments |  |
| `RecibosPago::edit` | id | form, redirect, write | yes | unreachable (no rule) | retire | receipt batches regenerated from payments |  |
| `RecibosPago::index` | - | - | yes | unreachable (no rule) | retire | receipt batches regenerated from payments |  |
| `RecibosPago::view` | id | - | yes | unreachable (no rule) | retire | receipt batches regenerated from payments |  |

### Reports

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Reportes::cierre` | id_condominio, mes, anio | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::delete` | id | delete, redirect | no | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::departamento` | id_condominio, fechaInicial, fechaFinal, depto | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::index` | id_condominio | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::infoAnual` | id_condominio, fecha | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::informe` | id_condominio, fecha | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::informeSeg` | id_condominio, fecha | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::lista` | id_condominio | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::nuevo` | id_condominio | form, redirect, write | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::pdfInforme` | id_condominio, fecha | pdf | no | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::prim` | id_condominio | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::primSave` | id_condominio | redirect, write | no | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::recibos` | id | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::seg` | id_condominio | - | yes | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |
| `Reportes::segSave` | id_condominio | redirect, write | no | reachable | redesign | rebuilt reports (CAKE5-REPORTS-001) |  |

### Expenses and bank

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Bancaria::add` | id_condominio | form, redirect, write | yes | reachable | implement | `bank_accounts` |  |
| `Bancaria::delete` | id | delete, redirect | no | reachable | implement | `bank_accounts` |  |
| `Bancaria::edit` | id | form, redirect, write | yes | reachable | implement | `bank_accounts` |  |
| `Bancaria::index` | id_condominio | json | yes | reachable | implement | `bank_accounts` |  |
| `Bancaria::view` | id | - | yes | reachable | implement | `bank_accounts` |  |
| `Banco::add` | - | form, redirect, write | yes | reachable | implement | `bank_movements` |  |
| `Banco::delete` | id | delete, redirect | no | reachable | implement | `bank_movements` |  |
| `Banco::edit` | id | form, redirect, write | yes | reachable | implement | `bank_movements` |  |
| `Banco::index` | id_condomino | json | yes | reachable | implement | `bank_movements` |  |
| `Banco::view` | id | - | yes | reachable | implement | `bank_movements` |  |
| `Caja::add` | id_condominio | form, redirect, write | yes | reachable | implement | `petty_cash` |  |
| `Caja::delete` | id | delete, redirect | no | reachable | implement | `petty_cash` |  |
| `Caja::edit` | id | form, redirect, write | yes | reachable | implement | `petty_cash` |  |
| `Caja::index` | id_condominio, id | json | yes | reachable | implement | `petty_cash` |  |
| `Caja::view` | id | - | yes | reachable | implement | `petty_cash` |  |
| `Conceptos::add` | id_gasto | form, helper-call, redirect, write | yes | reachable | implement | `expense_lines` |  |
| `Conceptos::delete` | id | delete, redirect | no | reachable | implement | `expense_lines` |  |
| `Conceptos::edit` | id | form, redirect, write | yes | reachable | implement | `expense_lines` |  |
| `Conceptos::index` | id, id_gasto | form, helper-call, json, redirect, write | yes | reachable | implement | `expense_lines` |  |
| `Conceptos::montoletras` | - | json | no | reachable | implement | `expense_lines` |  |
| `Conceptos::saveCierre` | concepto | write | no | reachable | implement | `expense_lines` |  |
| `Conceptos::view` | id | - | yes | reachable | implement | `expense_lines` |  |
| `Gasto::BorrarFile` | imagepath | delete, helper-call | no | reachable | implement | `expenses` |  |
| `Gasto::add` | id_condominio | form, helper-call, redirect, write | yes | reachable | implement | `expenses` |  |
| `Gasto::borrar` | id | delete, json | no | reachable | implement | `expenses` |  |
| `Gasto::confirmacion` | id, id_condominio | form, redirect, write | yes | reachable | implement | `expenses` |  |
| `Gasto::delete` | id | delete, redirect | no | reachable | implement | `expenses` |  |
| `Gasto::deleteBanco` | gasto | delete | no | reachable | implement | `expenses` |  |
| `Gasto::edit` | id, id_condominio | form, redirect, write | yes | reachable | implement | `expenses` |  |
| `Gasto::index` | id_condominio | json | yes | reachable | implement | `expenses` |  |
| `Gasto::montoletras` | - | json | no | reachable | implement | `expenses` |  |
| `Gasto::pa` | arr | debug-output | no | reachable | retire | `expenses` | debug helper exposed as a public action; prints URL input unescaped (reflected XSS, AZ-8) |
| `Gasto::saveBanco` | gasto, id_condominio | write | no | reachable | implement | `expenses` |  |
| `Gasto::saveCierre` | gasto | write | no | reachable | implement | `expenses` |  |
| `Gasto::updateBanco` | gasto | write | no | reachable | implement | `expenses` |  |
| `Gasto::uploadFiles` | folder, file, sub_folder | upload | no | reachable | implement | `expenses` |  |
| `Gasto::view` | id | - | yes | reachable | implement | `expenses` |  |

### Closings

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `CierreCondomino::add` | - | form, redirect, write | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `CierreCondomino::delete` | id | delete, redirect | no | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `CierreCondomino::edit` | id | form, redirect, write | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `CierreCondomino::index` | - | - | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `CierreCondomino::view` | id | - | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `SaldoCierre::add` | - | form, redirect, write | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `SaldoCierre::delete` | id | delete, redirect | no | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `SaldoCierre::edit` | id | form, redirect, write | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `SaldoCierre::index` | - | - | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `SaldoCierre::view` | id | - | yes | unreachable (no rule) | retire | computed `period_closings` / `unit_balances` | User decision 2026-11-17: manual balance editing retired; closings are computed and frozen by an explicit period close |
| `SaldoCondominio::add` | id_condominio | form, redirect, write | yes | reachable | redesign | `period_closings` |  |
| `SaldoCondominio::delete` | id | delete, redirect | no | reachable | redesign | `period_closings` |  |
| `SaldoCondominio::edit` | id | form, redirect, write | yes | reachable | redesign | `period_closings` |  |
| `SaldoCondominio::index` | id_condominio | json | yes | reachable | redesign | `period_closings` |  |
| `SaldoCondominio::view` | id | - | yes | reachable | redesign | `period_closings` |  |
| `SaldoCondomino::actualizaAdeudo` | saldoCondomino, id_condominio | write | no | reachable | redesign | `unit_balances` and opening-balance charges |  |
| `SaldoCondomino::actualizaSaldo` | saldoCondomino, id_condominio | write | no | reachable | redesign | `unit_balances` and opening-balance charges |  |
| `SaldoCondomino::add` | id_condomino, id_condominio | form, redirect, write | yes | reachable | redesign | `unit_balances` and opening-balance charges |  |
| `SaldoCondomino::delete` | id, id_condomino | delete, redirect, write | no | reachable | redesign | `unit_balances` and opening-balance charges |  |
| `SaldoCondomino::edit` | id | form, redirect, write | yes | reachable | redesign | `unit_balances` and opening-balance charges |  |
| `SaldoCondomino::index` | id_condomino | json | yes | reachable | redesign | `unit_balances` and opening-balance charges |  |
| `SaldoCondomino::view` | id | - | yes | reachable | redesign | `unit_balances` and opening-balance charges |  |

### Catalogs

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Categoria::add` | id_condominio | form, redirect, write | yes | reachable | implement | `expense_categories` (global + per development) |  |
| `Categoria::delete` | id | delete, redirect | no | reachable | implement | `expense_categories` (global + per development) |  |
| `Categoria::edit` | id | form, redirect, write | yes | reachable | implement | `expense_categories` (global + per development) |  |
| `Categoria::index` | id_condominio | json | yes | reachable | implement | `expense_categories` (global + per development) |  |
| `Categoria::view` | id | - | yes | reachable | implement | `expense_categories` (global + per development) |  |
| `Proveedores::add` | id_condominio | form, redirect, write | yes | reachable | implement | `suppliers` (global + per development) |  |
| `Proveedores::borrar` | id | delete, json | no | reachable | implement | `suppliers` (global + per development) |  |
| `Proveedores::delete` | id | delete, redirect | no | reachable | implement | `suppliers` (global + per development) |  |
| `Proveedores::edit` | id | form, redirect, write | yes | reachable | implement | `suppliers` (global + per development) |  |
| `Proveedores::index` | id_condominio | json | yes | reachable | implement | `suppliers` (global + per development) |  |
| `Proveedores::view` | id | json | yes | reachable | implement | `suppliers` (global + per development) |  |
| `Rubro::add` | - | form, redirect, write | yes | reachable | implement | `supplier_categories` |  |
| `Rubro::delete` | id | delete, redirect | no | reachable | implement | `supplier_categories` |  |
| `Rubro::edit` | id | form, redirect, write | yes | reachable | implement | `supplier_categories` |  |
| `Rubro::index` | - | - | yes | reachable | implement | `supplier_categories` |  |
| `Rubro::view` | id | - | yes | reachable | implement | `supplier_categories` |  |
| `TipoCuota::add` | id_condominio | form, redirect, write | yes | reachable | implement | `fee_types` |  |
| `TipoCuota::delete` | id | delete, redirect | no | reachable | implement | `fee_types` |  |
| `TipoCuota::edit` | id | form, redirect, write | yes | reachable | implement | `fee_types` |  |
| `TipoCuota::index` | id_condominio | json | yes | reachable | implement | `fee_types` |  |
| `TipoCuota::view` | id | - | yes | reachable | implement | `fee_types` |  |
| `TipoGasto::add` | id_condominio | form, redirect, write | yes | reachable | implement | `expense_types` |  |
| `TipoGasto::delete` | id | delete, redirect | no | reachable | implement | `expense_types` |  |
| `TipoGasto::edit` | id | form, redirect, write | yes | reachable | implement | `expense_types` |  |
| `TipoGasto::index` | id_condominio | json | yes | reachable | implement | `expense_types` |  |
| `TipoGasto::view` | id | - | yes | reachable | implement | `expense_types` |  |
| `TipoPago::add` | id_condominio | form, redirect, write | yes | reachable | implement | `payment_types` |  |
| `TipoPago::delete` | id | delete, redirect | no | reachable | implement | `payment_types` |  |
| `TipoPago::edit` | id, id_condominio | form, redirect, write | yes | reachable | implement | `payment_types` |  |
| `TipoPago::index` | id_condominio | json | yes | reachable | implement | `payment_types` |  |
| `TipoPago::view` | id | - | yes | reachable | implement | `payment_types` |  |
| `TipoVisita::add` | - | form, redirect, write | yes | reachable | implement | `visit_types` |  |
| `TipoVisita::delete` | id | delete, redirect | no | reachable | implement | `visit_types` |  |
| `TipoVisita::edit` | id | form, redirect, write | yes | reachable | implement | `visit_types` |  |
| `TipoVisita::index` | id_condominio | - | yes | reachable | implement | `visit_types` |  |
| `TipoVisita::view` | id | - | yes | reachable | implement | `visit_types` |  |

### Communications

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Comunicado::add` | id_condominio | form, redirect, write | yes | reachable | implement | `notices` + `deliveries` |  |
| `Comunicado::delete` | id | delete, redirect | no | reachable | implement | `notices` + `deliveries` |  |
| `Comunicado::edit` | id | form, redirect, write | yes | reachable | implement | `notices` + `deliveries` |  |
| `Comunicado::index` | id_condomino | json | yes | reachable | implement | `notices` + `deliveries` |  |
| `Comunicado::view` | id | - | yes | reachable | implement | `notices` + `deliveries` |  |
| `Email::cierre` | id_condominio | - | no | unreachable (no rule) | retire | mailers + `deliveries` | User decision 2026-11-17: whole controller retired; real emails come from Pago and Recordatorio |
| `Email::departamento` | id_condominio | - | no | unreachable (no rule) | retire | mailers + `deliveries` | User decision 2026-11-17: whole controller retired; real emails come from Pago and Recordatorio |
| `Email::index` | - | - | yes | unreachable (no rule) | retire | mailers + `deliveries` | User decision 2026-11-17: whole controller retired; real emails come from Pago and Recordatorio |
| `Email::informeSeg` | id_condominio | - | no | unreachable (no rule) | retire | mailers + `deliveries` | User decision 2026-11-17: whole controller retired; real emails come from Pago and Recordatorio |
| `Email::send` | - | email | no | unreachable (no rule) | retire | mailers + `deliveries` | User decision 2026-11-17: whole controller retired; real emails come from Pago and Recordatorio |
| `Mensaje::add` | - | form, redirect, write | yes | reachable | implement | `messages` |  |
| `Mensaje::delete` | id | delete, redirect | no | reachable | implement | `messages` |  |
| `Mensaje::edit` | id | form, redirect, write | yes | reachable | implement | `messages` |  |
| `Mensaje::index` | id | json | yes | reachable | implement | `messages` |  |
| `Mensaje::leido` | id | redirect, write | no | reachable | implement | `messages` |  |
| `Mensaje::view` | id | form, redirect, write | yes | reachable | implement | `messages` |  |
| `Recordatorio::add` | - | form, redirect, write | yes | reachable | implement | `reminders` + `deliveries` |  |
| `Recordatorio::delete` | id | delete, redirect | no | reachable | implement | `reminders` + `deliveries` |  |
| `Recordatorio::edit` | id | form, redirect, write | yes | reachable | implement | `reminders` + `deliveries` |  |
| `Recordatorio::getCorreos` | id_condominio | json | no | reachable | implement | `reminders` + `deliveries` |  |
| `Recordatorio::index` | - | - | yes | reachable | implement | `reminders` + `deliveries` |  |
| `Recordatorio::recordatorio` | id_condominio | form, json, redirect, write | yes | reachable | implement | `reminders` + `deliveries` |  |
| `Recordatorio::sendEmail` | id_condominio | email | no | reachable | implement | `reminders` + `deliveries` |  |
| `Recordatorio::view` | id | - | yes | reachable | implement | `reminders` + `deliveries` |  |
| `Reglamento::add` | id_condominio | form, redirect, write | yes | reachable | implement | `regulations` |  |
| `Reglamento::delete` | id | delete, redirect | no | reachable | implement | `regulations` |  |
| `Reglamento::edit` | id | form, redirect, write | yes | reachable | implement | `regulations` |  |
| `Reglamento::index` | id_condominio | - | yes | reachable | implement | `regulations` |  |
| `Reglamento::view` | id | - | yes | reachable | implement | `regulations` |  |
| `Respuestas::add` | - | form, redirect, write | yes | unreachable (no rule) | redesign | `message_replies` inside the message thread | User decision 2026-11-17: replies are written and read inside the conversation and cannot be edited or deleted once sent |
| `Respuestas::delete` | id | delete, redirect | no | unreachable (no rule) | redesign | `message_replies` inside the message thread | User decision 2026-11-17: replies are written and read inside the conversation and cannot be edited or deleted once sent |
| `Respuestas::edit` | id | form, redirect, write | yes | unreachable (no rule) | redesign | `message_replies` inside the message thread | User decision 2026-11-17: replies are written and read inside the conversation and cannot be edited or deleted once sent |
| `Respuestas::index` | - | - | yes | unreachable (no rule) | redesign | `message_replies` inside the message thread | User decision 2026-11-17: replies are written and read inside the conversation and cannot be edited or deleted once sent |
| `Respuestas::view` | id | - | yes | unreachable (no rule) | redesign | `message_replies` inside the message thread | User decision 2026-11-17: replies are written and read inside the conversation and cannot be edited or deleted once sent |
| `Ticket::add` | - | form, redirect, write | yes | reachable | implement | `tickets` |  |
| `Ticket::delete` | id | delete, redirect | no | reachable | implement | `tickets` |  |
| `Ticket::edit` | id | form, redirect, write | yes | reachable | implement | `tickets` |  |
| `Ticket::index` | - | - | yes | reachable | implement | `tickets` |  |
| `Ticket::view` | id | - | yes | reachable | implement | `tickets` |  |

### Amenities

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Amenidad::add` | id_condominio | form, redirect, write | yes | reachable | implement | `amenities` |  |
| `Amenidad::borrar` | id | delete, json | no | reachable | implement | `amenities` |  |
| `Amenidad::delete` | id | delete, redirect | no | reachable | implement | `amenities` |  |
| `Amenidad::edit` | id, id_condominio | form, redirect, write | yes | reachable | implement | `amenities` |  |
| `Amenidad::getAmenidadByCondominio` | id_condominio | json | no | reachable | implement | `amenities` |  |
| `Amenidad::index` | id_condominio | json | yes | reachable | implement | `amenities` |  |
| `Amenidad::view` | id | - | yes | reachable | implement | `amenities` |  |
| `AmenidadCondominio::add` | - | form, redirect, write | yes | unreachable (no rule) | retire | merged into `amenities.development_id` | User decision 2026-11-17: retired; the relation is managed from the development's amenities screen |
| `AmenidadCondominio::delete` | id | delete, redirect | no | unreachable (no rule) | retire | merged into `amenities.development_id` | User decision 2026-11-17: retired; the relation is managed from the development's amenities screen |
| `AmenidadCondominio::edit` | id | form, redirect, write | yes | unreachable (no rule) | retire | merged into `amenities.development_id` | User decision 2026-11-17: retired; the relation is managed from the development's amenities screen |
| `AmenidadCondominio::index` | - | - | yes | unreachable (no rule) | retire | merged into `amenities.development_id` | User decision 2026-11-17: retired; the relation is managed from the development's amenities screen |
| `AmenidadCondominio::view` | id | - | yes | unreachable (no rule) | retire | merged into `amenities.development_id` | User decision 2026-11-17: retired; the relation is managed from the development's amenities screen |
| `Calendario::Calendario` | - | - | yes | unreachable (no rule) | retire | covered by `bookings` calendar | User decision 2026-11-17: retired; duplicates of the Events calendar and the resident calendar |
| `Calendario::insert` | - | write | no | unreachable (no rule) | retire | covered by `bookings` calendar | User decision 2026-11-17: retired; duplicates of the Events calendar and the resident calendar |
| `Calendario::load` | - | json | no | unreachable (no rule) | retire | covered by `bookings` calendar | User decision 2026-11-17: retired; duplicates of the Events calendar and the resident calendar |
| `Events::add` | - | form, json, write | yes | reachable | implement | `bookings` |  |
| `Events::delete` | id | delete, json | no | reachable | implement | `bookings` |  |
| `Events::edit` | id | form, json, write | yes | reachable | implement | `bookings` |  |
| `Events::findById` | - | json | no | reachable | implement | `bookings` |  |
| `Events::index` | - | json | yes | reachable | implement | `bookings` |  |
| `Events::load` | - | json | no | reachable | implement | `bookings` |  |
| `FullCalendar::index` | - | - | yes | unreachable (no rule) | retire | covered by `bookings` calendar | User decision 2026-11-17: retired; duplicates of the Events calendar and the resident calendar |

### Gate

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Visitas::add` | - | form, redirect, write | yes | reachable | redesign | `visits`, `access_log` (GATE-001) |  |
| `Visitas::delete` | id | delete, redirect | no | reachable | redesign | `visits`, `access_log` (GATE-001) |  |
| `Visitas::edit` | id | form, redirect, write | yes | reachable | redesign | `visits`, `access_log` (GATE-001) |  |
| `Visitas::index` | id_condominio | - | yes | reachable | redesign | `visits`, `access_log` (GATE-001) |  |
| `Visitas::view` | id | - | yes | reachable | redesign | `visits`, `access_log` (GATE-001) |  |

### Resident portal

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Usuario::allt` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::calendario` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::comunicado` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::configuracion` | id | form, json, redirect, write | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::mensajes` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::nuevo` | id | form, json, redirect, write | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::pagos` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::profile` | id | json, render | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::qr` | id, id_visita | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::qrList` | id, id_visita | json | no | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::registrar` | id, id_visita | form, json, redirect, write | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::regla` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::reglamento` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::ticket` | id | form, json, redirect, write | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::view` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::vigilante` | id, id_visita | json | yes | reachable | redesign | resident pages scoped to occupancies |  |
| `Usuario::visita` | id | json | yes | reachable | redesign | resident pages scoped to occupancies |  |

### Inspections

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Areas::add` | - | form, redirect, write | yes | unreachable (no rule) | redesign | `inspection_items` inside the inspection screen | User decision 2026-11-17: integrated into Inspections with add, edit and delete (edit and delete do not work today) |
| `Areas::delete` | id | delete, redirect | no | unreachable (no rule) | redesign | `inspection_items` inside the inspection screen | User decision 2026-11-17: integrated into Inspections with add, edit and delete (edit and delete do not work today) |
| `Areas::edit` | id | form, redirect, write | yes | unreachable (no rule) | redesign | `inspection_items` inside the inspection screen | User decision 2026-11-17: integrated into Inspections with add, edit and delete (edit and delete do not work today) |
| `Areas::index` | - | - | yes | unreachable (no rule) | redesign | `inspection_items` inside the inspection screen | User decision 2026-11-17: integrated into Inspections with add, edit and delete (edit and delete do not work today) |
| `Areas::view` | id | - | yes | unreachable (no rule) | redesign | `inspection_items` inside the inspection screen | User decision 2026-11-17: integrated into Inspections with add, edit and delete (edit and delete do not work today) |
| `Supervision::add` | id_condominio | form, redirect, write | yes | reachable | implement | `inspections` |  |
| `Supervision::areas` | id | form, redirect, write | yes | reachable | implement | `inspections` |  |
| `Supervision::delete` | id | delete, redirect | no | reachable | implement | `inspections` |  |
| `Supervision::edit` | id | form, redirect, write | yes | reachable | implement | `inspections` |  |
| `Supervision::index` | id_condominio | - | yes | reachable | implement | `inspections` |  |
| `Supervision::view` | id | - | yes | reachable | implement | `inspections` |  |

### Files and imports

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Excel::add` | - | excel, form, redirect, write | yes | reachable | redesign | `files` + import jobs |  |
| `Excel::delete` | id | delete, excel, redirect | no | reachable | redesign | `files` + import jobs |  |
| `Excel::edit` | id | excel, form, redirect, write | yes | reachable | redesign | `files` + import jobs |  |
| `Excel::index` | - | excel | yes | reachable | redesign | `files` + import jobs |  |
| `Excel::view` | id | excel | yes | reachable | redesign | `files` + import jobs |  |

### Dashboard

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Pages::display` | path | redirect, render | no | reachable | redesign | per-development dashboard |  |
| `Pages::tablero` | - | - | yes | reachable | redesign | per-development dashboard |  |
| `Tablero::recibo` | - | pdf | no | unreachable (no rule) | retire | - | User decision 2026-11-17: abandoned PDF experiments (undefined `$id`, no template, no data) |
| `Tablero::view` | - | pdf | no | unreachable (no rule) | retire | - | User decision 2026-11-17: abandoned PDF experiments (undefined `$id`, no template, no data) |

### -

| Legacy action | Params | Traits | tpl | Today | Decision | Target | Note |
|---|---|---|---|---|---|---|---|
| `Pendientes::add` | - | form, redirect, write | yes | unreachable (no rule) | retire | no table in production |  |
| `Pendientes::delete` | id | delete, redirect | no | unreachable (no rule) | retire | no table in production |  |
| `Pendientes::edit` | id | form, redirect, write | yes | unreachable (no rule) | retire | no table in production |  |
| `Pendientes::index` | - | - | yes | unreachable (no rule) | retire | no table in production |  |
| `Pendientes::view` | id | - | yes | unreachable (no rule) | retire | no table in production |  |
| `UsuarioCondomino::add` | - | form, redirect, write | yes | unreachable (no rule) | retire | no table in production |  |
| `UsuarioCondomino::delete` | id | delete, redirect | no | unreachable (no rule) | retire | no table in production |  |
| `UsuarioCondomino::edit` | id | form, redirect, write | yes | unreachable (no rule) | retire | no table in production |  |
| `UsuarioCondomino::index` | - | - | yes | unreachable (no rule) | retire | no table in production |  |
| `UsuarioCondomino::view` | id | - | yes | unreachable (no rule) | retire | no table in production |  |


## Decisions log

| Date | Item | Decision |
|---|---|---|
| 2026-11-17 | AmenidadCondominio (5 actions) | Retire. The amenity-development relation is covered by `amenities.development_id` and managed from the amenities screen. |
| 2026-11-17 | Areas (5 actions) | Redesign inside Inspections: items are added, edited and deleted from the inspection screen. Today items can only be added (`Supervision::areas`). |
| 2026-11-17 | Calendario (3) and FullCalendar (1) | Retire. Duplicates of `Events` (index, load, add) and `Usuario::calendario`; the new application has one bookings calendar per development. |
| 2026-11-17 | CierreCondomino (5) and SaldoCierre (5) | Retire the manual editing screens. Closings become computed from charges, payments and expenses, frozen by an explicit period close (reopen only by the super-administrator, ADR-0005). Today these balances are already updated automatically by Pago, Gasto, Conceptos and SaldoCondominio. |
| 2026-11-17 | Email (5) | Retire the whole controller: `send` is CakePHP 2 code that cannot run, `index` is empty, and `informeSeg`, `departamento`, `cierre` are stale report copies (the saldo port of 2026-11-17 stays only in the legacy reference). Real emails (receipts, reminders) come from Pago and Recordatorio and are redesigned as `deliveries`. |
| 2026-11-17 | Favor (5) | Retire manual editing. Credit balance becomes the `credit_entries` ledger: every credit or use of credit is an entry with source and reason, and manual corrections are adjustment entries by staff with permission. Today credit is already maintained automatically by Pago, Adeudos and SaldoCondomino. |
| 2026-11-17 | Respuestas (5) | Redesign as a conversation inside each message: replies are added and listed in the thread; a sent reply cannot be edited or deleted (corrections are new replies). Today replies are created and listed from `Mensaje::view`. |
| 2026-11-17 | Tablero (2) | Retire. Abandoned PDF experiments with no template and no data; real receipt PDFs come from Pago and the real dashboard is `Pages::bienvenido` / `Pages::tablero`. |
| 2026-11-17 | UsersCondominio (5) | Redesign as the RBAC role-assignment management screen in module 1. Today no screen writes `users_condominio` (the only row was inserted directly), which also means the interim AZ-2 check would leave non-Superusers without any condominium in the legacy app. |
