== WHMCS Helcim Commerce Payment Gateway ==
Copyright (C) 2018 Helcim Inc.

Version: 1.0.1

== Requirements ==

1. WHMCS 7.4 - 7.7.1
2. PHP with cURL module enabled
3. Valid SSL cert and PCI DSS validation

== Description ==

This module provides integration with the Helcim Commerce Payment Gateway.

Follow installation instructions below to install and configure the module.

For development purposes please contact Helcim (https://www.helcim.com) for a development account.

A Helcim account is required to use this module. Please visit https://www.helcim.com for more information.

** NOTICE **
While this module uses tokenization for card storage due to the way WHMCS is currently designed there are 
instances where local storage will be used. WHMCS will only call the storeremote function after a client
has at least one invoice set to the Helcim Gateway.

This is currently a feature request with WHMCS, more information can be found at:

https://requests.whmcs.com/responses/never-store-credit-card-information-locally-when-using-a-tokenized-gateway




== Create API Token ==

1. Log into your Helcim Commerce account

2. Go to the API access page "Integrations > API Access"

3. Click "New API Access"

4. Ensure Status is set to "Active", and enter a nickname for the token you will use (example: WHMCS Token)

5. Check all options for transactions (example: process, view, void, refund, and settle)

6. Check the terminal you will be using to process payments

7. Press "Save"

8. Copy the token seen under your entered "Nickname"




== Installation ==

1. Extract helcimcommerce.php to: <whmcs-install-dir>/modules/gateways/ directory

2. Activate the plugin in the WHMCS Admin area through the "Setup -> Payments -> Payment Gateways" menu

3. Choose "Helcim Commerce" from the "Activate Module" drop-down and click "Activate"

4. Make sure "Show on Order Form" is ticked

5. Enter the title to display on the payment selection portion of the checkout page

6. Enter your Account ID and API Token (created above)

7. Enter the gateway URL. Use https://secure.myhelcim.com/api/

8. Click "Save Changes"




== Frequently Asked Questions ==

= How can I get a Helcim account? =

Please visit our website at https://www.helcim.com/ for information on signing up for a Helcim account.




== Changelog ==

= 1.0.0 =
* Initial release.

= 1.0.1 =
* Fixed Tokenization Issue
