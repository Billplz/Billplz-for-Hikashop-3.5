<?php
//Prevent from direct access
defined('_JEXEC') or die('Restricted access');
?>
<!-- Here is the ending page, called at the end of the checkout, just before the user is redirected to the payment platform -->
<div class="hikashop_billplz_end" id="hikashop_billplz_end">
  <!-- Waiting message -->
    <span id="hikashop_billplz_end_message" class="hikashop_billplz_end_message"><?php
      echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X', $this->payment_name).'<br/>'. JText::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED');
    ?></span>
    <span id="hikashop_billplz_end_spinner" class="hikashop_billplz_end_spinner">
    </span>
    <br/>
    <?php
    
    require_once( dirname(__FILE__).DS.'api_class.php' );
    require_once( dirname(__FILE__).DS.'connect_class.php' );

    $api_key = trim($this->vars['api_key']);
    $collection_id = trim($this->vars['collection_id']);

    $connect = new Billplz\Hikashop\Connect($api_key);
    $connect->setStaging($this->vars['sandbox']);
    $billplz = new Billplz\Hikashop\API($connect);

    $parameter = array(
        'collection_id' => $collection_id,
        'email' => $this->vars['email'],
        'mobile' => $this->vars['phone'],
        'name' => $this->vars['name'],
        'amount' => strval($this->vars['amount'] * 100),
        'callback_url' => $this->vars['return_url'],
        'description' => mb_substr($this->vars['description'], 0, 200)
    );

    $optional = array(
        'redirect_url' => $this->vars['return_url']
    );

    $create_bill = $billplz->createBill($parameter, $optional);
    list($rheader, $rbody) = $billplz->toArray($create_bill);

    if ($rheader !== 200){
        throw new \Exception('Invalid API Key set!');
    }

    if ($this->payment_params->debug) {
        $this->writeToLog($rbody);
    }

    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $columns = array('bill_slug', 'order_id', 'amount_sens');
    $values = array($db->quote($rbody['id']), $this->vars['order_id'], $db->quote($parameter['amount']));

    $query
      ->insert($db->quoteName('#__hikashop_billplz'))
      ->columns($db->quoteName($columns))
      ->values(implode(',', $values));

    $db->setQuery($query);
    $success = $db->query();
    
    if(!$success){
        exit('Failed to insert record');
    }

    $url = $rbody['url'];

    ?>
    
    <form id="hikashop_billplz_form" name="hikashop_billplz_form" action="<?php echo $url;?>" method="get">
        <div id="hikashop_billplz_end_image" class="hikashop_billplz_end_image">
            <input id="hikashop_billplz_button" class="btn btn-primary" type="submit" value="<?php echo JText::_('PAY_NOW');?>" name="" alt="<?php echo JText::_('PAY_NOW');?>" />
        </div>
<?php
    hikaInput::get()->set('noform', 1);
?>
    </form>
    <script type="text/javascript">
        <!--
        function isIframe(){
            try{
                return window.self !== window.top;
            }catch(e){
                return false;
            }
        }
        if(isIframe()){
            document.getElementById('hikashop_billplz_form').target = '_blank';
        }
        document.getElementById('hikashop_billplz_form').submit();
        //-->
    </script>
</div>
