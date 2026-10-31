# Philippine delivery location directory

Source: Philippine Statistics Authority (PSA), **PSGC 3Q 2026 Publication Datafile**,
as of 30 September 2026, released 12 October 2026.

- Official publication: https://psa.gov.ph/classification/psgc
- Original workbook: https://psa.gov.ph/system/files/scd/PSGC-3Q-2026-Publication-Datafile.xlsx
- Download mirror: https://github.com/rbillyjames0221/psgc-api/blob/main/seed/PSGC-3Q-2026-Publication-Datafile.xlsx

The direct PSA download was blocked, so the mirrored workbook was parsed locally
with PHP ZipArchive/XMLReader. Workbook metadata and all level counts were
checked against PSA's publication: **18 regions, 82 provinces, 149 cities,
1,493 municipalities, and 42,010 barangays**. The workbook SHA-256 is recorded
inside ph-locations.json. No Python or external address API is used at runtime.

Names and codes are PSA data. The four-level delivery form flattens Manila's
sub-municipalities into the City of Manila barangay list. NCR uses Metro Manila
as its delivery province label. Independent cities are grouped with the
geographic province indicated by the correspondence code when it belongs to the
same region; otherwise a clearly labeled province-less group is offered. Special
Geographic Area municipalities use a separate delivery grouping. These display
groups do not change the official province count in the metadata.

PSA requires acknowledgment as the data source. Its website publishes content
under Creative Commons Attribution 4.0 unless otherwise stated. Keep this source
note with the generated directory. PSGC updates are quarterly; replacing the
dataset requires checking hierarchy and counts and updating its version tag.
