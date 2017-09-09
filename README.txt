=== Eighty/20 Results - Add License for user based on PMPro Membership Level for PMPro ===
Contributors: sjolshag
Tags: customizations, memberships, paid memberships pro, license management
Requires at least: 4.7
Tested up to: 4.7.4
Stable tag: 1.8.1

Add License for user based on PMPro Membership Level for PMPro

== Description ==
This plugin requires the Paid Memberships Pro plugin by Stranger Studios, LLC
and the Software License Manager Plugin for WordPress.

== Installation ==

1. Upload the `e20r-add-license-for-level` directory to the `/wp-content/plugins/` directory of your site.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

== 1.8.1 ==

* BUG/FIX: Didn't handle non-MMPU check-outs/updates

== 1.8 ==

* ENHANCEMENT/FIX: No license generated if no order existed

== 1.7 ==

* ENHANCEMENT/FIX: Support both profile updates & checkouts for generating license

== 1.6 ==

* * ENH: Add plugin dependency check & warning messages
* * ENH: Simplified settings

== 1.5.9 ==
* BUG: Tried to create license for free membership levels

== 1.5.8 ==
* BUG: Don't show license content if the user doesn't have any licenses.

== 1.5.2 ==
* BUG: Fixed PHP Warnings due to empty license info.
* ENH: No table shown when no license info present for the user.

== 1.5.1 ==
* BUG: Would try to show shortcode content for non-users.

== 1.5 ==
* Updated with shortcode [e20r_user_licenses] and one-click update support
