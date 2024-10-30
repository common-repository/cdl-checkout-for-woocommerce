=== CDL Checkout Payment Gateway for WooCommerce ===
Contributors: cdlcheckout, shadrachodek
Tags: credit direct, cdl checkout, woocommerce, payment gateway, debit card
Requires at least: 4.4
Tested up to: 6.6.1
Stable tag: 1.4.4
Requires PHP: 7.4
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take payments on your store using CDL Checkout.

== Description ==

Rake in more sales as a merchant with the CDL Checkout BNPL (Buy-Now-Pay-Later) plugin. CDL Checkout allows your customers make purchases and spread the payment in convenient installments. This means you can instantly convert more website visitors to customers.
The application, assessment and disbursement is completely paperless and would only take minutes!

Signup for an account [here](https://creditdirect.ng)

= Plugin Features =
* Payment: Card & Bank transfer.
* Recurring payments: Card & Bank Tokenization.

= Requirements =
* CDL Merchant Portal API Keys
* WooCommerce

== Installation ==

= Automatic Installation =

1. Login to your WordPress Dashboard.
2. Click on "Plugins > Add New" from the left menu.
3. In the search box type **CDL Checkout for WooCommerce**.
4. Click on **Install Now** on **CDL Checkout for WooCommerce** to install the plugin on your site.
5. Confirm the installation.
6. Activate the plugin.
7. Click on "WooCommerce > Settings" from the left menu and click the **"Payments"** tab.
8. Click on the **CDL Checkout** link from the available Checkout Options.
9. Configure your **CDL Checkout Payment Gateway** settings accordingly.

= Manual Installation =

1. Download the plugin zip file.
2. Login to your WordPress Admin. Click on "Plugins > Add New" from the left menu.
3. Click on **Upload Plugin** button at the top.
4. Choose the downloaded zip file of the plugin and click **Install Now**.
5. Confirm the installation and activate the plugin.
6. Click on "WooCommerce > Settings" from the left menu and click the **"Payments"** tab.
7. Click on the **CDL Checkout** link from the available Checkout Options.
8. Configure your **CDL Checkout Payment Gateway** settings accordingly.

== Configuration ==

- **Enable/Disable** - Tick to enable CDL Checkout.
- **Title** - This controls the title which the user sees during checkout.
- **Description** - This controls the description which the user sees during checkout.
- **Test Mode** - Tick to enable test mode for transactions. When enabled, the provided public key and secret key will be used in test mode.
- **Public Key** - Enter your public key. This key will be used for both test and live transactions depending on the Test Mode setting.
- **Secret Key** - Enter your secret key. This key will be used for both test and live transactions depending on the Test Mode setting.
- Click **Save Changes** to save your changes.

== Screenshots ==

1. CDL Checkout WooCommerce Payment Gateway Setting Page
![Screenshot 1](assets/images/screenshot-1.png)

2. CDL Checkout WooCommerce Payment Gateway on WooCommerce order checkout page
![Screenshot 2](assets/images/screenshot-2.png)

3. CDL Checkout pay modal showing card payment option
![Screenshot 3](assets/images/screenshot-3.png)

== External Service ==

This plugin relies on the external service [Credit Direct](https://github.com/lenda-saas/vanilla-javascript-for-checkout/blob/main/README.md) for processing payments via their checkout system. You can find the service's terms of use [here](https://creditdirect.ng/terms-of-use) and their privacy policy [here](https://creditdirect.ng/privacy-policy).


== Suggestions / Contributions ==

To contribute, fork the repo, add your changes and modifications then create a pull request.

== Changelog ==

= 1.4.3 - July 02, 2024 =
* First release