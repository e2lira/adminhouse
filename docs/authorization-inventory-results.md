# Authorization Inventory (RH-004)

- Status: Evidence collected 2026-11-17 (read-only). Spec: `docs/specs/authorization-inventory.md` (APPROVED).
- Method: static extraction of `isAuthorized()`, `beforeFilter()` and `Auth->allow()` from every file in `src/Controller`, review of `AppController` Auth configuration and templates, plus read-only in-process GET requests against the local PostgreSQL copy with and without a session. No role, permission, code or data was changed (AC-5).
- Scope: CakePHP 3.9 application as it is today. 53 controllers (`App` and `Error` excluded; `InvoicesController` disabled on 2026-11-17).

## Profiles (AC-1)

Source: table `perfil` and column `users.id_perfil`. One profile per user, global to the whole system.

| id | Profile | Users in the 2026-10-29 dump |
|---|---|---|
| 1 | Superuser | 3 (`dante`, `edgar`, `Alan`) |
| 2 | Admin | 0 |
| 3 | Supervisor | 0 |
| 4 | Contador | 0 |
| 5 | Capturista | 0 |
| 6 | Cliente | 0 |

No other source of roles was found: there is a single database user for the application, and no database grants per role.

## How authorization works today (AC-2)

| Rule | Evidence |
|---|---|
| Every controller requires a signed-in user, except the actions `login`, `logout` and `vigilante`, allowed in every controller. | `AppController::beforeFilter()` - `$this->Auth->allow(['login', 'logout', 'vigilante'])` |
| After sign-in, `AuthComponent` with `'authorize' => 'Controller'` calls the controller's `isAuthorized($user)`. A denied user is redirected to the referring page. | `AppController::initialize()` (`authorize`, `unauthorizedRedirect => $this->referer()`) |
| `isAuthorized()` grants the whole controller (every action) to a list of profile numbers, compared strictly (`$user['id_perfil'] === N`). No controller distinguishes actions inside `isAuthorized()`. | 39 controllers, matrix below |
| 14 controllers have no `isAuthorized()`. With `authorize => Controller`, every signed-in request to them ends in a 500 error (verified by GET on 2026-11-16/17). | matrix below |
| Login destination: profile 1 to `pages/bienvenido`, profile 6 to `usuario/profile`, any other profile to `Pages::bienvenido` through `loginRedirect`. | `UsersController::login()`, characterization tests (SEC-001) |
| Templates do not decide access by profile. The 7 templates that mention `id_perfil` only show it in forms or in the profile CRUD. | `src/Template/{Administrador,Users,Perfil}/*.ctp` |

## Controller x profile matrix (AC-1, AC-4)

`Y` = every action of the controller is allowed for the profile. `-` = no rule (500 error for everyone signed in).

| Controller | 1 Superuser | 2 Admin | 3 Supervisor | 4 Contador | 5 Capturista | 6 Cliente | Notes |
|---|---|---|---|---|---|---|---|
| Adeudos | Y | Y |  |  | Y |  |  |
| Administrador | Y | Y | Y |  | Y |  |  |
| AmenidadCondominio | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Amenidad | Y | Y |  |  | Y |  |  |
| Areas | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Asigna | Y |  |  |  |  |  |  |
| Bancaria | Y | Y | Y |  | Y |  |  |
| Banco | Y | Y |  | Y |  |  |  |
| Bloque | Y | Y | Y |  | Y |  |  |
| Caja | Y | Y |  |  | Y |  |  |
| Calendario | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Categoria | Y | Y |  |  | Y |  |  |
| CierreCondomino | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Comunicado | Y | Y |  |  | Y |  |  |
| Conceptos | Y | Y |  |  | Y |  |  |
| Condominio | Y | Y | Y |  | Y |  |  |
| Condomino | Y | Y |  |  | Y |  |  |
| Cuotas | Y | Y |  | Y |  |  |  |
| Email | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Events | Y | Y |  |  | Y | Y |  |
| Excel | Y | Y |  |  | Y |  |  |
| Favor | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Folios | Y | Y |  |  | Y |  |  |
| FullCalendar | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Gasto | Y | Y |  |  | Y |  |  |
| Indiviso | Y | Y |  | Y |  |  |  |
| Mensaje | Y | Y | Y |  | Y |  |  |
| Pages | Y | Y | Y | Y | Y |  | profile 3: redirects to condominio/bienvenido and calls deny(edit) inside isAuthorized |
| Pago | Y | Y |  |  | Y |  |  |
| Pendientes | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Perfil | Y |  |  |  |  |  |  |
| Proveedores | Y | Y |  |  | Y |  |  |
| RecibosPago | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Recordatorio | Y | Y |  |  | Y |  |  |
| Reglamento | Y |  |  |  |  |  |  |
| Reportes | Y | Y |  |  | Y |  |  |
| Respuestas | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Rubro | Y | Y |  |  | Y |  |  |
| SaldoCierre | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| SaldoCondominio | Y | Y |  |  | Y |  |  |
| SaldoCondomino | Y | Y |  |  | Y |  |  |
| Supervision | Y | Y |  |  | Y |  |  |
| Tablero | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Ticket | Y | Y | Y |  | Y |  |  |
| TipoCuota | Y | Y |  |  | Y |  |  |
| TipoGasto | Y | Y |  |  | Y |  |  |
| TipoPago | Y | Y |  |  | Y |  |  |
| TipoVisita | Y | Y |  |  | Y |  |  |
| UsersCondominio | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Users | Y |  |  |  |  |  |  |
| UsuarioCondomino | - | - | - | - | - | - | no `isAuthorized()`: every signed-in request ends in a 500 error |
| Usuario | Y |  |  |  |  | Y |  |
| Visitas | Y | Y |  |  | Y |  |  |

### Critical actions (AC-4)

| Action | Allowed | Evidence |
|---|---|---|
| Sign in (`/`, `/users/login`, `/administrador/login`) | Everyone, no session | `Auth->allow`; GET `/administrador/login` without session returns 200 |
| Payments (`PagoController`, including `add` and `pdf`) | Profiles 1, 2, 5 | `PagoController::isAuthorized()` |
| Debts and fees (`Adeudos`, `Cuotas`, `Indiviso`) | Adeudos 1, 2, 5; Cuotas and Indiviso 1, 2, 4 | matrix |
| Reports (`ReportesController`) | Profiles 1, 2, 5 (Contador, profile 4, cannot open reports) | matrix |
| Resident pages (`UsuarioController`) | Profiles 1, 6 | matrix |
| Uploaded files below `webroot/files/**` | Anyone who knows the URL, without a session | FILE-001, ADR-0003 |
| Guard visit view (`/usuario/vigilante/{id}`) | Anyone, without a session | see risks |

## Tenant (condominium) checks (AC-3)

- `isAuthorized()` never looks at the condominium. Access is granted per profile for every condominium.
- Most actions take the condominium id from the URL (`index($id_condominio)`, `informe($id_condominio, ...)`) and do not check it against `users_condominio`. `users_condominio` is only read to build the list of condominiums shown to the user (`CondominioController`, `AdministradorController`, `UsersController`).
- Observed consequence: a profile 2 or 5 user can open another condominium's pages by changing the id in the URL. This was observed in the code, not exercised against real multi-condominium data (the dump has one condominium).
- Resident pages (`UsuarioController`) derive the unit from the signed-in user through `asigna`, which does scope residents to their own unit for those pages.

## Risks found (flagged, not changed)

| ID | Risk | Evidence | Severity |
|---|---|---|---|
| AZ-1 | `UsuarioController::vigilante($id)` is reachable without a session and loads `Visitas->get($id)` with no ownership or condominium check: anyone can read a visit (visitor, unit, vehicle) by guessing sequential ids. | `AppController` allows `vigilante`; GET `/usuario/vigilante/1/1` without session reaches the action (404 only because the local copy has no visits). | High |
| AZ-2 | No condominium check on actions that receive a condominium id: cross-condominium access by URL for staff profiles. | Tenant checks above | High (once more than one condominium exists) |
| AZ-3 | Uploaded files are public under `webroot/files/**`. | FILE-001 | High (already tracked) |
| AZ-4 | 14 controllers without `isAuthorized()` answer with a 500 error instead of a clean denial; the intended audience of those screens is unknown. | matrix | Medium |
| AZ-5 | Residents (profile 6) are granted the whole `EventsController`, including `edit` and `delete`, with CSRF unlocked for `add`, `load`, `edit` and `delete`; nothing limits them to their own bookings. | `EventsController::isAuthorized()`, `beforeFilter()` | Medium |
| AZ-6 | `PagesController::isAuthorized()` performs a redirect and changes `Auth->deny()` while deciding authorization for profile 3. | matrix note | Low |
| AZ-7 | All three existing users are Superuser; the other five profiles have no users. | `users` table | Informational |

## Unresolved assumptions

- The intended audience of the 14 controllers without a rule cannot be derived from the code.
- Whether profile 4 (Contador) is meant to be excluded from reports and payments, or this is an omission.
- Production may hold users and condominiums that the 2026-10-29 dump does not contain; the matrix is code-based and does not depend on data, but the user counts do.

## Use for RBAC (RB-R10, AC-RB-6)

The matrix above is the baseline that the first `role_permissions` seed must reproduce for profiles 1 to 6. The risks AZ-1, AZ-2 and AZ-5 are behaviours the baseline reproduces only until an intentional change is approved; the 14 controllers without a rule have no baseline behaviour to keep.
