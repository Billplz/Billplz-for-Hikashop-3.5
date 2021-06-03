# Billplz for Hikashop 3.5 - 4.4

Integrate Billplz in Hikashop version 3.5 - 4.4. For Hikashop 2.6, please refer to Billplz for [Hikashop 2.6](https://github.com/Billplz/Billplz-for-Hikashop-2.6/)

## System Requirements

  * Hikashop Starter 3.5.x - 4.4.x

## Installation

  * [Download this repository](https://codeload.github.com/billplz/Billplz-for-Hikashop-3.5/zip/master)
  * Go to Joomla Administration >> Extension >> Manage >> Install
  * Upload package file >> Install
  * Enable the plugin >> Extension >> Plugin >> Hikashop Billplz Payment Plugin
  * Go to Hikashop Option >> System >> Payment Method >> New >> Billplz
  * Set the particular details (API Secret Key, Collection ID & etc)
  * Save & Close
  
### Specific Configuration
  * **Sandbox Mode** : Yes if Sandbox. No if Production
  * **API Secret Key** : Get the API Key at Billplz Setting Page
  * **Collection ID** : Get the Collection ID at Billplz Billing Page
  * **X Signature Key** : Get the X Signature Key at Billplz Setting Page
  * Debug : No
  * Invalid status : cancelled
  * Pending status : created
  * Verified status : Confirmed
  
### Custom Image

  * Upload **Billplz.png** file to **/media/com_hikashop/images/payment/**

## Uninstallation

You may remove the plugin as usual. However, the table with name *hikashop_billplz* need to be removed manually. 
  
# Other

Facebook: [Billplz Dev Jam](https://www.facebook.com/groups/billplzdevjam/)
  