=== Sitewide Sales ===
Contributors: strangerstudios, dlparker1005
Tags: sales, sale, woocommerce, paid memberships pro, pmpro, black friday, cyber monday, discount
Requires at least: 5.2
Tested up to: 6.3
Stable tag: 1.4

Run Black Friday, Cyber Monday, or other flash sales on your WordPress-powered eCommerce or membership site.

== Description ==

The Sitewide Sales plugin allows you to create flash or sitewide sales. Use the Sitewide Sale CPT to create multiple sales, each with an associated discount code, banners and landing page. The plugin will automatically apply a discount code for users who comlete checkout after visiting the sale landing page. 

The plugin also adds the option to display sitewide page banners to advertise your sale and gives you statistics about the outcome of your sale.

This plugin offers modules for [WooCommerce](https://sitewidesales.com/modules/woocommerce/), [Paid Memberships Pro](https://sitewidesales.com/modules/paid-memberships-pro/), and [Easy Digital Downloads](https://sitewidesales.com/modules/easy-digital-downloads/). You can also use the [Custom sale module](https://sitewidesales.com/modules/custom-module/) to track any banner > landing page > conversion workflow. New integrations will be built as requested.

== Installation ==

1. Upload the `sitewide-sales` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Create a new `Sitewide Sale` under `Sitewide Sales` > `Add New`.

== Changelog ==

= 1.4 - 2023-11-01 =
* FEATURE: Added feature to compare sale data for two separate sales for Reports.
* FEATURE: Added feature to download a single sale's report and daily sales data to CSV.
* FEATURE: Added support to completely hide the sale by role or membership level.
* ENHANCEMENT: Refresh admin area design.
* BUG FIX/ENHANCEMENT: Optimized discount codes/coupons query for sites with a very large number of codes.
* BUG FIX/ENHANCEMENT: Now supporting variation prices in WooCommerce when there is no range and we want to reflect strikethrough pricing for auto discount.
* BUG FIX: Fixed edge case to prevent creating multiple landing pages, coupon codes, or banners.
* BUG FIX: Removed Chart JS from enqueue on the sale CPT edit screen because it isn't needed.

= 1.3.1 - 2022-11-16 =
* ENHANCEMENT: Added Multiple Members Per User (MMPU) compatibility for banners (PMPro Module).
* ENHANCEMENT: Adjusted order of metaboxes on Sitewide Sale CPT edit screen to reflect recommended workflow.
* ENHANCEMENT: Now allowing certain HTML in custom banner title and banner text.
* BUG FIX/ENHANCEMENT: Improved new swsale start date/time and generated coupon end date for all modules. 
* BUG FIX/ENHANCEMENT: Now properly showing strike through prices for product variations (WooCommerce module).
* BUG FIX: Adjusted logic to preview sale strike pricing when previewing the sale as admin.
* BUG FIX: Now only striking price if actually discounted (WooCommerce module).
* BUG FIX: Style fix for Ocean landing page template background color.
* BUG FIX: Fixed edge case where an orphaned swsale ID stored in post meta wasn't actually a swsale CPT.
* BUG FIX: Fixed case where site has no existing reusable blocks to pull into dropdown.

= 1.3 - 2022-07-27 =
* FEATURE: Added "Reusable Blocks" as a banner type.
* FEATURE: Added a "Sale Content" block that works similar to the sitewide_sale shortcode. The content from the sale settings will show up depending on the period.
* FEATURE: Added a "Sale Period" nested block. You can nest other blocks inside of it and set the period (before/during/after) to show that content.
* FEATURE: Added a "Sale Period Visibility" advanced option to Group blocks and Column blocks. You can set these nested blocks to only show before/during/after a sale.
* FEATURE: Added support for the Popup Maker plugin. You can choose a popup to use as your banner.
* FEATURE: Added "Sale Period Visibility" to Elementor and Divi elements/sections.
* FEATURE: Built-in Block Patterns and Styles for Sale Banners.
* FEATURE: Added Countdown Timer Block for use with sales.
* ENHANCEMENT: Added close (x) to banners and setting to "close until new session" so closed banners don't show up again.
* ENHANCEMENT: Added filter swsales_banner_dismiss_link_html so custom code can hide or change the dismiss link on banners.
* ENHANCEMENT: Added new sale banner templates.
* ENHANCEMENT: Updated WooSelect to latest version.
* ENHANCEMENT: Using the woocommerce_product_is_on_sale filter to show products in the shop as "on sale" if the code is applied to the view.
* ENHANCEMENT: Moved the mini report on the edit sale page to the sidebar, with links to the detailed report.
* ENHANCEMENT: Added setting for hiding banners per role.
* ENHANCEMENT: Sorting level dropdown/multiselects by sorted order in PMPro
* ENHANCEMENT: Formatting strike prices for accessibility; improvement to WC pricing to use new is_type function.
* ENHANCEMENT: Now using the input type date and time to set start and end dates/times on sales; input field width formatting.
* ENHANCEMENT: Updated plugin links and admin header display.
* ENHANCEMENT: Improved the "Vintage" template.
* ENHANCEMENT: Adjusting "Photo" template for EDD and other improvements.
* ENHANCEMENT: Now showing 'Best Day' and 'Today' on the sale report chart.
* ENHANCEMENT: Added filter swsales_daily_revenue_chart_days to limit days shown on chart. Default is 31 days.
* BUG FIX/ENHANCEMENT: Fixed warning for PMPro module and hiding banner by membership level.
* BUG FIX/ENHANCEMENT: Hiding the banner if the "shop" page is chosen as the landing page.
* BUG FIX/ENHANCEMENT: Removed strikethrough pricing on variable downloads.
* BUG FIX/ENHANCEMENT: PMPro daily revenue chart now shows in local time.
* BUG FIX: Fixed WC coupon expiration warning message showing when shouldnt.
* BUG FIX: Fixed issue where report charts would break if using certain date format settings.

= 1.2 - 2021-09-22 =
* FEATURE: Added EDD module
* FEATURE: Added "Custom" module
* ENHANCEMENT: Start and end times can now be set for Sitewide Sales
* ENHANCEMENT: Added daily revenue chart to reports
* ENHANCEMENT: Clicking the discount code link in PMPro reports now shows the orders that have used that code
* ENHANCEMENT: Added filter `swsales_pmpro_landing_page_default_discount_code`
* BUG FIX/ENHANCEMENT: Now hiding discount code option for PMPro checkout on SWS landing page
* BUG FIX: Now checking that PMPro discount code is valid before applying on landing page
* BUG FIX: WooCommerce coupons are no longer being applied on every page
* BUG FIX: Removed strike price from WC subscriptions as it wasn't showing consistently
* BUG FIX: Resolved issue where `swsales_show_banner filter` would not always fire
* BUG FIX: Fixed issues where checks for landing/checkout pages failed if no landing or checkout page was set.

= 1.1 - 2020-09-21 =
* NOTE: Sending launch emails today.
* FEATURE: Added a one click migration from PMPro Sitewide Sales.
* BUG FIX: Fixed issue where the wrong discount code/coupon might show up on the "Fancy Coupon" landing page.
* BUG FIX: Fixed the banner tracking code and a few other reporting inaccuracies.
* BUG FIX/ENHANCEMENT: Fixed issue with the WooCommerce landing pages not always showing the discounts if the setting to apply the discount code automatically wasn't set.
* BUG FIX/ENHANCEMENT: Fixed warning message when a sale doesn't have a type set.
* BUG FIX/ENHANCEMENT: Better error handling when checking for updates with an active license.
* ENHANCEMENT: Improved the HTML and CSS for some of the templates.
* ENHANCEMENT: Fixed styling of notices in the admin.
* ENHANCEMENT: Updated styling of the admin pages to be more responsive.
* ENHANCEMENT: Updated the recommended privacy policy text.
* REFACTOR: Updated prefixes on options, functions, and hooks to make them consistently swsales_.
* REFACTOR: Moved the classes folder out of the includes folder. This is a bit more consistent with how PMPro code is structured.

= 1.0 =
* NOTE: Initial soft launch.
* ENHANCEMENT: Adding support for updates through the Stranger Studios license server.

= .1 =
* Ported from PMPro Sitewide Sales
