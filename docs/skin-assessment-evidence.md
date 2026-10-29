# Skin assessment evidence and maintenance

PureVia is a research-informed shopping assessment, not a clinically validated instrument, diagnosis, allergy test or official Baumann Skin Type Questionnaire. No reliability coefficient, diagnostic accuracy or clinical validation from a source may be attributed to this implementation. A use survey or customer rating is not clinical validation.

Only the owner's supplied references are used. The source registry is `services/SkinEvidence.php`; it is also displayed to customers. All six supplied PDFs were inspected. No full questionnaire or textbook is republished.

## Source use

| Supplied source | Use and limit |
| --- | --- |
| Baumann et al., *A Validated Questionnaire for Quantifying Skin Oiliness* (2014), SCIRP paper 43949 | Supports asking several specific observations rather than trusting a single type label. Its six-item SOS measures oiliness; original wording and scoring matter. It does not validate PureVia's four classes or its equal-vote rules. |
| Brzozowska, Polish BSTQ validation part one (2025), PMC12163966 | Supports treating oiliness and sensitivity separately. Validation of a translated instrument in its study sample does not establish validation of adapted English questions or a different population. |
| Cho et al. (2023), doi:10.1111/jocd.15820 | Shortening and changing instruments needs comparison with measurements. A retrospective single-center Korean study does not validate another quiz's wording, thresholds or concern predictions. |
| Baumann, *Cosmetic Dermatology: Principles and Practice*, supplied textbook | Background for observations and cautious ingredient guidance. Chapters 17–18 discuss stinging/contact dermatitis; chapter 30 discusses retinoid irritation; chapter 36 discusses fragrance. Suspected contact allergy needs clinical investigation, not questionnaire identification. |
| Parraga et al. (2024), *Skin type classifications: does the perfect assessment exist?* | Distinguishes dimensions and supports attention to individual characteristics. A review/letter does not establish new diagnostic thresholds. |
| `jcdsa_2016031509371422.pdf`: Baumann (2016), doi:10.4236/jcdsa.2016.61005 | Reports original BSTI skin-type distributions across settings. Similar distributions are not proof that PureVia's individual predictions are accurate. |
| `273377050.pdf`: Diržaite, *Dry and oily skin types. Overview of their main conditions and care: a theoretical synthesis* (2025) | Care/classification background, not validation data. |
| `doi130046supp1_prod.pdf`: Eilers et al. (2013), Fitzpatrick self-report supplement | Sun-response phototype questions; not used to determine oiliness, sensitivity or ingredient allergy. |
| `doi160001supp1_prod.pdf`: Klassen et al. (2016), FACE-Q supplement | Appearance satisfaction/procedure outcomes; not used as a skin-type classifier. |
| Skin Type Solutions about-us page | Commercial context and identification of the proprietary system. No claim of affiliation, permission to reproduce its quiz or STS approval. |

## Rules

The seven core skin-type observations contribute one vote each to dry, normal, oily or combination. Unknown answers contribute nothing. At least five substantive observations, a strict majority and a lead of two votes are required. Opposing dry/oily observations trigger clarification. Each of two clarification answers contributes one vote; contradictory clarification or disagreement with the leading type remains undetermined. These are deliberately conservative **project rules**, not published clinical cutoffs or probabilities. `rule_version` and the full result explanation are stored.

Sensitivity is a separate self-report flag: at least two observations are needed for a level; any frequent reaction flags high reported reactivity, occasional reactions moderate, and all rare reactions low. Unknown information stays unknown. A frequent reaction still prompts caution even when too little information exists for a level.

Additional observation questions have three answer meanings: A present, B absent, C unsure. A present symptom can suggest a linked catalog concern; the customer must explicitly select it before matching. Oiliness alone does not infer acne, dehydration, rosacea or an allergy. Ingredient cautions do not silently add exclusions. User-selected exclusions retain the ingredient checker's existing filtering, including exclusion of incomplete lists. A screened catalog list is not a safety guarantee.

## Installation and admin

On an existing configured database, run `php tools/migrate-skin-questions.php` once. This creates `skin_questions` and seeds core plus symptom observation questions without overwriting existing rows. A default concern mapping is applied only if its active catalog name exists. On a new installation, first install the application's existing skin-profile/analytics schema; this migration assumes those features already exist.

Admins open **Assessment Questions** in the sidebar. They can create, read, edit, deactivate and delete additional observation questions, and edit core question wording/evidence notes. Core questions cannot be deleted or deactivated because the scoring and guided screens depend on them. Preserve answer meanings and order when editing; source selection is documentation, not clinical review. Phototype, FACE-Q and commercial references cannot be assigned as evidence for these questions.

Writes require an authenticated admin and valid CSRF token, use prepared statements and audit logs, and reject stale revisions. Active bank changes invalidate an open quiz's token version, requiring a refresh. Profiles retain question definitions, source notes, version and computed scoring; assessment history contains the full snapshot. Later question edits do not change old results. Legacy profiles are identified as legacy rather than rescored using new rules.

For a future clinical-validation claim, use an authorized unchanged validated instrument with its published scoring, or conduct a prospective study of this exact questionnaire and algorithm with clinician/objective reference assessments, relevant local participants, repeatability checks and predefined accuracy criteria. Obtain a dermatologist's review of questions, scoring and ingredient advice before making clinical claims.

## Verification

`php tools/test-skin-assessment.php` checks scoring, contradictions, unknowns, ingredient filtering, server-side validation, question CRUD/revision conflicts and historical save/load. Database writes use temporary tables shadowing application tables on that test connection, leaving customer records unchanged. These PHP checks do not establish clinical validity.
