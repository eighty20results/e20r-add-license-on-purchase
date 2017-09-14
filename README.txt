=== Eighty/20 Results - Sell Licenses with WooCommerce and Paid Memberships Pro ===
Contributors: eighty20results
Tags: customizations, memberships, paid memberships pro, license management, woocommerce, sell licenses, pmpro, mmpu, software license manager
Requires at least: 4.7
Tested up to: 4.8.1
Stable tag: 1.1

Sell Software License Manager licenses from WooCommerce or Paid Memberships Pro. Includes support for the Multiple Memberships Per User PMPro add-on.

== Description ==

Sell licenses managed by the Software License Manager software from your WooCommerce shopping cart, or with a Paid Memberships Pro membership level (the PMPro checkout page).

Once the sale is complete, this plugin will generate one or more software license(s) for the customer. The resulting unique license key will be emailed to the customer, as well as can be embedded in any post or page using the [e20r_user_licenses] short code. The license(s) are also, listed on the user's WooCommerce or PMPro account page.

To utilize the license, you need to include the E20R Licensing Client Toolkit with your plugin.

This plugin requires that your sales process uses either the Paid Memberships Pro plugin by Stranger Studios, LLC, or WooCommerce plugin by Automattic. You also need to have the Software License Manager software installed somewhere. The Software License Manager software can be hosted as a WordPress plugin on your sales, or any other another site. Yes, it can be separate from your sales site (If you take this path, to keep things safe and secure, make sure the server where the licensing software also has HTTPS (TLS) configured.

If your licenses are recurring (i.e. have something other than 0 as it's "License renewal period", you need a payment gateway/membership level configuration that supports recurring payment plans!

We use the "WooCommerce Stripe Subscription Payment Gateway" plugin by Wsspg for WooCommerce (not an endorsement, just an example of what we use right now).
For Paid Memberships Pro we configure the license settings as part of an annual recurring membership level.

NOTE: This plugin will simplify selling license keys from your WooCommerce Shop, or as part of a Paid Memberships Pro membership level. It does *not* include a kit to check licenses from a plugin. To do that, you would need something like our E20R Licensing Client Toolkit (for sale), or you can create your own.

== Missing features ==

* Renewal option for a license from the [e20r_user_licenses] short code
* Manual renewal on checkout page

== Installation ==

1. Upload the `e20r-add-license-on-purchase` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Configure the License Manager connection using either the WooCommerce "Settings" -> "Products" -> "Software Licenses" tab, or the Paid Memberships Pro "Memberships" -> "Software Licenses" Settings page. You will need to supply the Create License secret key, and the Verify License Secret key, plus the URL to the server where the Software License Manager server software is running (even if that is the same server as you're using for WooCommerce or PMPro).

== Changelog ==

== v1.1 ==

* BUG FIX: Didn't always trigger the Orders::complete() action handler

== v1.0 ==

* ENHANCEMENT: Add WooCommerce support
* ENHANCEMENT: Add JavaScript for WooCommerce product options page
* ENHANCEMENT: Add Paid Memberships Pro checkout page support
* ENHANCEMENT: Configure own settings menu option when using Paid Memberships Pro
* ENHANCEMENT: Add PMPro settings page for Software License Manager
* ENHANCEMENT: Add AJAX save of PMPro/Software License Manager settings page
* ENHANCEMENT: Use global options/settings for Software License Manager configuration (keys, URLs)
* ENHANCEMENT: Styling for the PMPro/Software License Manager settings page ('Software Licenses')
* ENHANCEMENT: Transition WooCommerce settings array to filter for global settings (PMPro & WooCommerce uses same structure in this plugin)
* ENHANCEMENT: Standardize path to plugin directory
* ENHANCEMENT: Load shortcode handler [for e20r_user_licenses]
* ENHANCEMENT: Display license settings on Membership Level configuration page for Paid Memberships Pro
* ENHANCEMENT: Use filters to include payment service specific settings/info when generating license
* ENHANCEMENT: Don't expect the Software License Manager software to be installed locally
* ENHANCEMENT: Let admin specify the number of domains a license can be applied to in the PMPro Level settings
* BUG FIX: Include all required files/directories in build script
* BUG FIX: Append the new membership level(s) to the list of levels to check out (MMPU/Single level handling for Paid Memberships Pro)
* BUG FIX: Load correct path to the License email for Paid Memberships Pro
* BUG FIX: Argument order for getBillingInfo() filter handler (lost user info)


