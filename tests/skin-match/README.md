# Skin Match verification

From the project root, after applying the migration:

```powershell
C:\xampp\php\php.exe tests/skin-match/run.php
```

The 62 checks use MySQL temporary tables to shadow all customer, catalog, history, and audit tables they read or write. They do not change live customer or catalog records. Fixtures and the local HTTP test router refuse ordinary web-server execution.

Run the nine real product-handler cases to check suitability assignments and routine settings:

```powershell
foreach ($case in @('edit','add','forged','invalid-role','inactive','retain-inactive','legacy','csrf','stale')) {
    C:\xampp\php\php.exe tests/skin-match/product-form.php $case
    if ($LASTEXITCODE -ne 0) { throw "Product form case failed: $case" }
}
```

These cases cover new and edited products, preserved variants and stock, invalid selections, inactive tag retention, older submissions, CSRF protection, and concurrent-edit protection.

Coverage includes the supplied seven-question scoring matrix; optional manual selection; unknown, contradictory, and ambiguous observations; follow-ups; separate sensitivity thresholds; validated profile preferences; ingredient exclusions and maintained fragrance/parfum/perfume synonyms; ranked available products; catalog routine guidance; customer isolation; idempotent assessment saving; date-filtered analytics; primary-type protection; suitability revisions; transaction rollback; and audit logging.

The browser checks performed during implementation additionally exercised both full quiz paths, profile editing, feedback ownership, admin create/edit/delete, suitability forms, assessment detail viewing, analytics empty states, and 320/390px layouts against the real PHP endpoints with isolated fixtures.

The questionnaire uses the detailed 16-question specification supplied for PureVia. Q2–Q8 use the provided matrix, at least five observed answers, a margin greater than two, and the sample PHP threshold of seven leading points. F1 and F2 each add three points before final classification. Q9–Q11 are scored separately. These are project shopping rules, not a clinically validated diagnostic scale.
