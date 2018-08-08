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
    
    require 'API.php';
    require 'Connect.php';

    $api_key = trim($this->vars['api_key']);
    $collection_id = trim($this->vars['collection_id']);

    $connnect = (new Billplz\Hikashop\Connect($api_key))->detectMode();
    $billplz = new Billplz\Hikashop\API($connnect);

    $parameter = array(
        'collection_id' => $collection_id,
        'email' => $this->vars['email'],
        'mobile' => $this->vars['phone'],
        'name' => $this->vars['name'],
        'amount' => strval($this->vars['amount'] * 100),
        'callback_url' => $this->vars['return_url'],
        'description' => mb_substr($this->vars['description'], 0, 199)
    );

    $optional = array(
        'redirect_url' => $this->vars['return_url'],
        'reference_2_label' => $this->vars['reference_2_label'],
        'reference_2' => $this->vars['reference_2']
    );

    $create_bill = $billplz->createBill($parameter, $optional, '0');
    list($rheader, $rbody) = $billplz->toArray($create_bill);
    error_log(print_r($rbody, true));
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
