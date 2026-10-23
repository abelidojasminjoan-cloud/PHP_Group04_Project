# PureVia: A Skin Care E-commerce System with Skin type Matching and Ingredient Compatibility

# Group Members: 
1. Abelido, Jasmin Joan - @abelidojasminjoan
2. Cuvin, Maria Dianne - @diannecuvs
3. Macadato, Ahmhida - @ahmhida
4. Tambe, Bryan Roi - @tambebryanroi

# Project Description
A web-based e-commerce system developed to provide customers with an easy, convenient, 
and personalized way to browse and purchase skincare products. The system offers a variety of skincare 
products and provides relevant product information to help customers make informed purchasing decisions.

# Technology Used
- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Security Tools

# System Features
See [the website XSS review](docs/XSS-REVIEW.md) for output encoding, browser policy, upload protections, and verification details.

- Checkout and Order Placement 
- Shopping Cart
- Skin Type Matching
- Category Management
- Product Management
- Product Catalog, Search, and Filtering
- Ingredient Compatibility
- Order Management
- Administration Dashboard
- Payment Management 
- Customer Profile Management 
- Audit Logs

# Database 
purevia_db.sql

# Installation/Setup

Install the following:
- PHP
- MySQL
- XAMPP
- Visual Studio Code
- HTML
- CSS
- JavaScript


1. Open XAMPP and start Apache and MySQL
2. Copy the System Project PureVia Folder above into the "htdocs" folder
3. Open the PHP/MyAdmin in your browser
4. Create a Database for the System Project PureVia and import the SQL database file
5. Open a browser and go to "https://localhost//PureVia/"
6. Then the PureVia website should be accessible now

## Authenticator setup

For password-only account testing, set `TWO_FACTOR_ENABLED=false` in `.env`. This temporarily disables authenticator setup and challenges for all roles. Set it back to `true` to restore the authenticator requirement; existing enrolled secrets and recovery codes are preserved.

Apply `database/migrations/20261024_two_factor_auth.sql` after importing the database. The existing login flow requires these authenticator fields and recovery-code storage. Set `TOTP_ENCRYPTION_KEY` in the private `.env` to a base64-encoded random 32-byte key. Preserve that key across deployments so enrolled authenticator secrets remain readable. After password verification, existing accounts enroll in Google Authenticator before completing their first login.

## Skin Match and admin setup

After importing the database on a new installation, apply `database/migrations/20261021_skin_match_admin.sql`, followed by `database/migrations/20261021_suitability_tags.sql`. These migrations are additive and repeatable. They preserve existing profiles and product links, add stable quiz keys for the four primary skin types, and create suitability metadata, assessment history, and reusable suitability tags.

- Open `skin-match.php` for manual skin-type selection or the optional guided assessment. `skin-type-finder.php` starts the guided questions directly.
- Customers can update saved preferences under My Account → Skin Profile without retaking the seven primary questions.
- Administrators can use Skin Types, Suitability Management, and Quiz Analytics from the sidebar. Additional skin types are catalog tags; the four primary scoring keys remain stable when display labels change.
- Suitability Management lets administrators create, edit, and deactivate reusable tags, see assignment counts, and search or filter by tag status and assigned product category. Assigned tags must be removed from products before deletion.
- Add/Edit Product includes suitability tag selections, Routine Role, Usage Period, and ingredient completeness confirmation alongside the existing skin-type, concern, and ingredient selections. Unverified ingredient lists are excluded when a customer has selected ingredients to avoid. Suitability labels are assigned by administrators, not inferred from ingredient records.
- Routine products require an assigned routine role. Sunscreen labels must be confirmed as broad-spectrum SPF 30+. Optional targeted products need reviewed directions and approval; they are omitted when frequent reactions or high sensitivity are reported.
- Analytics count saved quiz completions once per assessment token. Profile edits and previews do not inflate totals. Usefulness averages come from customer ratings; missing ratings display a dash. Result clarity describes questionnaire results, not medical certainty.
