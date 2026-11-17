# Traceability Matrix

| Requirement | Design Ref | Implementation Ref | Test Ref | Status |
|---|---|---|---|---|
| RH-001 | `docs/specs/login-payment-characterization.md`; `docs/security-hardening-task-1.md`; ADR-0002 | SEC-001 (G3 after design-only G2) | Focused login/payment-policy characterization tests; `docs/test-results.md` | G3 IN_PROGRESS — focused Users/Pago tests pass on PHP 7.4.33; pending HTTP/static non-reachability evidence and user approval |
| RH-002 | `docs/specs/auth-totp.md` | TOTP slice (not started) | TOTP-T01 protected enrollment; TOTP-T02 valid challenge/session; TOTP-T03 invalid/missing/reused challenge denial; TOTP-T04 10 hashed one-time backup codes, single display, atomic consumption, and regeneration revocation; TOTP-T05 existing redirect regression | Approved — implementation blocked by G2 |
| RH-003 | `docs/specs/private-file-access.md`; `docs/adr/ADR-0003-private-file-containment.md`; comprehensive FILE-001 inventory | FILE-001 after G3 (not started) | FILE-T01 no public-webroot retrieval; FILE-T02 owner allowed; FILE-T03 same-condominium administrator allowed; FILE-T04 other profile/cross-condominium returns 404 without metadata; FILE-T05 category/record/consumer/checksum manifest, private-storage migration, safe storage identifier, presentation-asset classification, and non-reachability/no-restoration evidence for the two removed `.DS_Store` artifacts | Approved — implementation blocked by G2 |
| RH-004 | `docs/specs/authorization-inventory.md` | Read-only inventory task (not started) | Evidence-backed role/action matrix review | Approved — implementation blocked by G2 |

| CR-2026-11-06T23:41:51.516903+00:00 | docs/requirements.md | implementation TBD | tests TBD | TODO |

| RH-001 (SEC-001-FOCUSED-RUN, 2026-11-16) | `docs/specs/login-payment-characterization.md`; `docs/security-hardening-task-1.md` | `config/routes.php:377` (`Router::scope`, authorized production change); `tools/run-php74-phpunit.ps1` | `tests/TestCase/Controller/UsersControllerTest.php` (6 tests, exact `Location` assertions); `tests/TestCase/Controller/PagoControllerTest.php` (2 tests); `docs/test-results.md` | Focused tests PASS (8 tests, 27 assertions); G3 IN_PROGRESS - pending HTTP/static non-reachability evidence and user approval |

| RH-005 (DRAFT) | `docs/specs/multi-development.md` | MD-001 design; implementation in the CakePHP 5.x stage (ADR-0004) | Acceptance criteria pending | DRAFT - G0 intake |

| RH-006 (DRAFT) | `docs/specs/rbac.md`; prerequisite `docs/specs/authorization-inventory.md` (RH-004) | RBAC-001 design; implementation in the CakePHP 5.x stage with MD-001 | Acceptance criteria pending | DRAFT - G0 intake |

| RH-005 (REVIEW, supersedes the DRAFT row) | `docs/specs/multi-development.md` (MD-R1 to MD-R14) | MD-001 and GATE-001; implementation in the CakePHP 5.x stage (ADR-0004) | AC-MD-1 to AC-MD-12 | REVIEW - awaiting G1 approval and four decisions |
| RH-006 (REVIEW, supersedes the DRAFT row) | `docs/specs/rbac.md` (RB-R1 to RB-R11); prerequisite `docs/specs/authorization-inventory.md` (RH-004) | RBAC-001; implementation with MD-001 in the CakePHP 5.x stage | AC-RB-1 to AC-RB-8 | REVIEW - awaiting G1 approval and two decisions |

| RH-005 (APPROVED) | `docs/specs/multi-development.md` (MD-R1 to MD-R14) | MD-001 and GATE-001; G2 design next; implementation in the CakePHP 5.x stage | AC-MD-1 to AC-MD-13 | G1 PASS 2026-11-17 |
| RH-006 (APPROVED) | `docs/specs/rbac.md` (RB-R1 to RB-R11); prerequisite RH-004 | RBAC-001; G2 design next; implementation with MD-001 in the CakePHP 5.x stage | AC-RB-1 to AC-RB-9 | G1 PASS 2026-11-17 |

| RH-005 / RH-006 (design) | `docs/adr/ADR-0005-multi-development-rbac-design.md` revision 4 | MD-001, RBAC-001, GATE-001; CakePHP 5.x remodel stage | AC-MD-1 to AC-MD-13, AC-RB-1 to AC-RB-9, plus the RLS policy test suite (RLS-SEC-001) | G2 PASS with condition 2026-11-17 |

| RH-004 | `docs/specs/authorization-inventory.md` | Read-only inventory | AC-1 to AC-5 evidence in `docs/authorization-inventory-results.md` | Evidence collected 2026-11-17; awaiting user review |
