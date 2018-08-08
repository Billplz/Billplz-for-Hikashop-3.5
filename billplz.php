<?php

/**
 * Billplz for Hikashop Plugin
 * @ package Billplz_Hikashop
 * @ version 3.2.0
 * @ author Wan @ Billplz
 */

//Prevent from direct access
defined('_JEXEC') or die('Restricted access');

// You need to extend from the hikashopPaymentPlugin class which already define lots of functions in order to simplify your work
class plgHikashoppaymentBillplz extends hikashopPaymentPlugin
{

    //List of the plugin's accepted currencies. The plugin won't appear on the checkout if the current currency is not in that list. You can remove that attribute if you want your payment plugin to display for all the currencies
    var $accepted_currencies = array("MYR", "RM");
    // Multiple plugin configurations. It should usually be set to true
    var $multiple = true;
    //Payment plugin name (the name of the PHP file)
    var $name = 'billplz';
    var $doc_form = 'billplz';
    // This array contains the specific configuration needed (Back end > payment plugin edition), depending of the plugin requirements.
    // They will vary based on your needs for the integration with your payment gateway.
    // The first parameter is the name of the field. In upper case for a translation key.
    // The available types (second parameter) are: input (an input field), html (when you want to display some custom HTML to the shop owner), textarea (when you want the shop owner to write a bit more than in an input field), big-textarea (when you want the shop owner to write a lot more than in an input field), boolean (for a yes/no choice), checkbox (for checkbox selection), list (for dropdown selection) , orderstatus (to be able to select between the available order statuses)
    
    // The third parameter is the default value.
    var $pluginConfig = array(
        // Billplz API Secret Key
        'billplzapikey' => array("API Secret Key", 'input'),
        // Billplz Collection ID
        'billplzcollectionid' => array("Collection ID", 'input'),
        // Billplz X Signature Key
        'billplzxsignature' => array("X Signature Key", 'input'),
        //Custom Redirect Path
        'successurl' => array("Success return url", 'input'),
        'cancelurl' => array("Cancel return url", 'input'),
        // Write some things on the debug file
        'debug' => array('DEBUG', 'boolean', '0'),
        'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
        // Valid status for order if the payment has been done well
        'verified_status' => array('VERIFIED_STATUS', 'orderstatus')
    );

    /**
     * The constructor is optional if you don't need to initialize some parameters of some fields of the configuration and not that it can also be done in the getPaymentDefaultValues function as you will see later on
     */
    
    public function onBeforeOrderCreate(&$order, &$do)
    {
        if (parent::onBeforeOrderCreate($order, $do) === true) {
            return true;
        }

        if ((empty($this->payment_params->billplzapikey) || empty($this->payment_params->billplzxsignature)) && $this->plugin_data->payment_id == $order->order_payment_id) {
            $this->app->enqueueMessage('Please check your &quot;Billplz&quot; plugin configuration');
            $do = false;
        }
    }

    function __construct(&$subject, $config)
    {
        return parent::__construct($subject, $config);
    }

    /**
     * This function is called at the end of the checkout. That's the function which should display your payment gateway redirection form with the data from HikaShop
     */
    function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        // This is a mandatory line in order to initialize the attributes of the payment method
        parent::onAfterOrderConfirm($order, $methods, $method_id);

        // Here we can do some checks on the options of the payment method and make sure that every required parameter is set and otherwise display an error message to the user
        // The plugin can only work if those parameters are configured on the website's backend
        if ((empty($this->payment_params->billplzapikey) || empty($this->payment_params->billplzxsignature))) {
            // Enqueued messages will appear to the user, as Joomla's error messages
            $this->app->enqueueMessage('You have to configure an API Secret Key and X Signature Key for the Billplz plugin payment. Check your plugin\'s parameters, on your website backend', 'error');
            return false;
        } elseif (empty($this->payment_params->billplzcollectionid)) {
            $this->app->enqueueMessage('You have to configure a Collection ID for the Billplz plugin payment. Check your plugin\'s parameters, on your website backend', 'error');
            return false;
        } else {
            // Here, all the required parameters are valid, so we can proceed to the payment platform
            // The order's amount, here in cents and rounded with 2 decimals because of the payment platform's requirements
            // There is a lot of information in the $order variable, such as price with/without taxes, customer info, products... you can do a var_dump here if you need to display all the available information

            $amount = round($order->cart->full_total->prices[0]->price_value_with_tax, 2);

            $order_id = $order->order_id;
            $item_id = $this->url_itemid;
            
            $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=billplz&tmpl=component&lang=' . $this->locale.'&order_id='. $order_id . $item_id;
            
            $name = $order->cart->billing_address->address_firstname . " " . $order->cart->billing_address->address_lastname;

            $all_parameter = array(
                'name' => $name,
                'email' => $this->user->user_email,
                'phone' => @$order->cart->billing_address->address_telephone,
                'api_key' => $this->payment_params->billplzapikey,
                'collection_id' => $this->payment_params->billplzcollectionid,
                'description' => "Order Number: " . $order->order_number,
                'reference_2_label' => "Order ID",
                'reference_2' => $order_id,
                'amount' => strval($amount * 100),
                'return_url' => $return_url
            );

            $this->vars = $all_parameter;

            if (!empty($this->payment_params->debug)) {
                $this->writeToLog($all_parameter);
            }

            // Ending the checkout, ready to be redirect to the plateform payment final form
            // The showPage function will call the example_end.php file which will display the redirection form containing all the parameters for the payment platform
            return $this->showPage('end');
        }
    }

    /**
     * To set the specific configuration (back end) default values (see $pluginConfig array)
     */
    function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = 'Billplz';
        $element->payment_description = 'Pay using <strong>FPX, Boost Wallet</strong>';
        $element->payment_images = 'logo-billplz';
        $element->payment_params->currency = $this->accepted_currencies[0];
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->verified_status = 'confirmed';
    }

    /**
     * After submiting the platform payment form, this is where the website will receive the response information from the payment gateway servers and then validate or not the order
     */
    function onPaymentNotification(&$statuses)
    {
        require 'API.php';
        require 'Connect.php';

        $vars = array();

        $order_id = (int)$_GET['order_id'];
        $dbOrder = $this->getOrder($order_id);

        $this->loadPaymentParams($dbOrder);
        $this->loadOrderData($dbOrder);

        if (empty($this->payment_params)) {
            return false;
        }

        $x_signature = $this->payment_params->billplzxsignature;
        $data = Billplz\Hikashop\Connect::getXSignature($x_signature);

        $api_key = trim($this->payment_params->billplzapikey);
        $connnect = (new Billplz\Hikashop\Connect($api_key))->detectMode();
        $billplz = new Billplz\Hikashop\API($connnect);
        list($rheader, $rbody) = $billplz->toArray($billplz->getBill($data['id']));

        if ($order_id != $rbody['reference_2']) {
            return false;
        }
        // Debug mode activated or not
        if ($this->payment_params->debug) {
            // Here we display debug information which will be  catched by HikaShop and stored in the payment log file available in the configuration's Files section.
            echo print_r($data, true) . "\n\n\n";
            echo print_r($rbdoy, true) . "\n\n\n";
            echo print_r($dbOrder, true) . "\n\n\n";
        }
        $history = new stdClass();
        $history->notified = 0;
        $history->amount = number_format($data['amount'] / 100, 2);
        $history->data = $rbody['url'];
        $email = new stdClass();
            
        if ($rbody['paid']) {
            $history->notified = 1;
            $order_status = $this->payment_params->verified_status;
            $url = HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id=' . $order_id;
            $order_text = "\r\n" . JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE', $dbOrder->order_number, HIKASHOP_LIVE);
            $order_text .= "\r\n" . str_replace('<br/>', "\r\n", JText::sprintf('ACCESS_ORDER_WITH_LINK', $url));
                
            $email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER', 'Billplz', $data['state'], $dbOrder->order_number);
            $email->body = str_replace('<br/>', "\r\n", JText::sprintf('PAYMENT_NOTIFICATION_STATUS', 'Billplz', $data['state'])).' '.JText::sprintf('ORDER_STATUS_CHANGED', hikashop_orderStatus($order_status))."\r\n\r\n".$order_text;

            // Save to DB only 1 times.
            if ($dbOrder->order_status != $this->payment_params->verified_status) {
                $this->modifyOrder($order_id, $this->payment_params->verified_status, $history, $email);
            }
        } else {
            $this->modifyOrder($order_id, $this->payment_params->invalid_status, $history, $email);
        }

        $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order_id . $this->url_itemid;
        
        $cancel_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' . $order_id . $this->url_itemid;

        //If user set custom success redirect path
        if (!empty($this->payment_params->successurl)) {
            $return_url = $this->payment_params->successurl;
        }
            
        //If user set custom cancel redirect path
        if (!empty($this->payment_params->cancelurl)) {
            $cancel_url = $this->payment_params->cancelurl;
        }

        if ($data['type'] === 'redirect') {
            if ($rbody['paid']) {
                $this->app->redirect($return_url);
            } else {
                $this->app->redirect($cancel_url);
            }
        } else {
            // Check the callback status header code
            return false;
        }
    }

    function onPaymentConfigurationSave(&$element)
    {
        if (empty($element->payment_params->currency)) {
            $element->payment_params->currency = $this->accepted_currencies[0];
        }
        return true;
    }
}
