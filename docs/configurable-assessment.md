# Configurable assessment questions

Run `php tools/migrate-question-configuration.php` once after updating an existing installation. It adds citation/mapping/scoring columns and converts only questions that have not been configured yet. It does not create, delete, or reseed questions. Existing assessment snapshots remain unchanged.

Every question supports create, read, update, deactivate and delete. Former core questions have editable purposes, answer counts and score weights. Deleted/inactive questions are absent from the customer quiz and active scoring; no runtime fallback recreates them. The older seed installer leaves a configured bank unchanged unless `--seed-defaults` is explicitly supplied.

Question purposes:
- Skin-type scoring: configure each answer's Normal, Dry, Oily and Combination weights (0–5).
- Clarification scoring: the same weights, asked only if the initial result needs clarification.
- Sensitivity scoring: 0–2 points per answer; leave unavailable observations unscored.
- Concern observation: no skin-type points; map a selected answer to any number of concerns for customer confirmation.

A question can have 2–8 distinct answer labels. All-zero skin-type weights skip an answer. Configure at least one scored answer. Related concern and skin-type checkbox mappings allow multiple entries; applicability tags do not themselves award skin-type points. Use the answer weight grid for classification changes.

The active question bank determines the customer quiz screens, sensitivity questions, clarification questions, answer validation and rule fingerprint. Fewer than five primary questions require all available primary observations; otherwise at least five are required. A suggestion needs a majority of awarded points and a two-point lead (one point with fewer than three configured primary questions). Conflicting results can remain undetermined. Empty scoring banks ask the customer to select manually. These are custom project rules, not clinically validated thresholds.

Choose a supplied reference or Other source. Other sources require a title, author/organization and HTTP(S) URL; existing textbook citations can omit a URL. The system records administrator-entered citations; it does not verify research quality or claim validation. URLs are displayed as escaped external links and are never fetched by the server.

Concurrent changes reject stale revisions. CRUD changes are audited. Saved profiles/assessments retain their question wording, citations, mappings, weights and results in snapshots, and in-progress tokens reject a changed bank. Product recommendations still use the existing four primary skin types and require customers to confirm their own concerns.
If a catalog was recreated after clearing the database, the upgrade connects unambiguously named active Normal, Dry, Oily and Combination records to their matching assessment keys. It preserves IDs, names and product assignments; it does not create or rename catalog entries. Sensitive remains a separate sensitivity/catalog tag.
