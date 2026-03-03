=== WP Psychometric Test Pro ===
Contributors: wp-psycho
Tags: psychometric, assessment, test, quiz, personality
Requires at least: 5.5
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise psychometric testing platform. No login required. Guest entry via name/email/phone. Passkey-protected tests with beautiful results, trait charts, and downloadable reports.

== Description ==

WP Psychometric Test Pro is a fully-featured enterprise psychometric assessment platform for WordPress. Allow guests to take personality, aptitude, and skill-based tests without needing to create an account.

**Key Features:**

* Guest access — no WordPress login required
* Name, email, and phone registration
* Passkey-protected tests with hint support
* Multiple question types: Likert, Multiple Choice, True/False, Rating
* Automatic scoring with customizable scoring rules
* Trait-based scoring with radar chart visualization
* Beautiful results page with animated score ring
* Downloadable HTML report
* Email notifications to participant and admin
* Full admin backend for managing tests, questions, and results
* CSV export for results
* Custom report template builder
* WhatsApp contact integration
* Fully responsive design

== Installation ==

1. Upload the `wp-psychometric-test` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Create your tests via **Psycho Tests** in the admin menu
4. Add questions and scoring rules to each test
5. Place `[wp_psycho_portal]` shortcode on any page
6. Configure settings under **Psycho Tests → Settings**

== Frequently Asked Questions ==

= Do users need to be logged in? =
No. Guests simply enter their name, email, and phone number to access tests.

= Can I password-protect tests? =
Yes. Each test can have an optional passkey with a hint shown to participants.

= How are results stored? =
Results are stored in the WordPress database and can be viewed/exported from the admin panel.

= Can I download reports? =
Yes. An HTML report is generated for each completed test and can be downloaded by the participant.

== Shortcodes ==

`[wp_psycho_portal]` — Displays the full psychometric assessment portal.

== Changelog ==

= 2.0.0 =
* Complete plugin rewrite with enterprise features
* Added passkey protection for tests
* Added trait-based scoring with radar charts
* Added downloadable HTML reports
* Added email notifications
* Added CSV export
* Added report template builder
* Improved responsive design

= 1.0.0 =
* Initial release
