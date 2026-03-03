=== WP Psychometric Test Pro ===
Contributors: wp-psycho
Tags: psychometric, test, assessment, personality, quiz
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise psychometric testing platform. No WordPress login required. Guest entry via name/email/phone + test passkey.

== Description ==

WP Psychometric Test Pro is a complete enterprise-grade psychometric assessment platform for WordPress.

**Key Features:**

* **No Login Required** – Guests enter only name, email, and phone number
* **Passkey-Protected Tests** – Optionally lock tests with passkeys
* **Multiple Question Types** – Likert scale, multiple choice, true/false, rating
* **Trait Scoring** – Multi-trait scoring with breakdown
* **Beautiful Results** – Animated score ring, radar chart, trait bars
* **Downloadable Reports** – HTML report generation per result
* **Email Notifications** – Auto-notify participant and admin on completion
* **Admin Dashboard** – Full CRUD for tests, questions, scoring rules, report templates
* **CSV Export** – Export all results to CSV
* **Pagination** – Paginated results list

**Shortcode:** `[wp_psycho_portal]`

Place this shortcode on any page to display the psychometric assessment portal.

== Installation ==

1. Upload the `wp-psychometric-test` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **Psychometric Tests** in your admin panel to create your first test
4. Add questions and scoring rules to your test
5. Create a page and add the shortcode `[wp_psycho_portal]`
6. Configure settings under **Psychometric Tests → Settings**

== Frequently Asked Questions ==

= Do participants need a WordPress account? =

No. Participants only need to provide their name, email address, and phone number.

= Can I password-protect individual tests? =

Yes. Each test can have an optional passkey. When set, participants must enter the passkey before starting the test.

= How are reports generated? =

Reports are generated as styled HTML files and stored in the WordPress uploads directory. Participants can download them after completing a test.

= Can I export results? =

Yes. Go to **Psychometric Tests → All Reports** and click the Export CSV button.

== Screenshots ==

1. Guest entry form
2. Test listing with passkey modal
3. Test-taking interface with progress bar
4. Results page with score ring and trait radar chart
5. Admin tests listing
6. Admin questions management

== Changelog ==

= 2.0.0 =
* Complete rewrite with enterprise features
* Added passkey protection per test
* Added trait-based scoring
* Added radar chart visualization
* Added HTML report generation
* Added email notifications
* Added CSV export
* Added report template builder

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major rewrite. Please backup your database before upgrading.
