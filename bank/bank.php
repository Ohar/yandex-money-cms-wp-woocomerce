<?php	
	function bank_gateway_icon( $gateways ) {
		if ( isset( $gateways['bank'] ) ) {
			$url=WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) );
			$gateways['bank']->icon = $url . '/logo_cards_small.png';
		}
	 
		return $gateways;
	}
	 
	add_filter( 'woocommerce_available_payment_gateways', 'bank_gateway_icon' );

add_action('plugins_loaded', 'woocommerce_bank_payu_init', 0);
function woocommerce_bank_payu_init(){
  if(!class_exists('WC_Payment_Gateway')) return;
 
  class WC_bank_Payu extends WC_Payment_Gateway{
    public function __construct(){
      $this -> id = 'bank';
      $this -> method_title = 'Яндекс.Банк';
      $this -> has_fields = false;
 
      $this -> init_form_fields();
      $this -> init_settings();
 
      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      $this -> scid = $this -> settings['scid'];
      $this -> ShopID = $this -> settings['ShopID'];
      $this -> demomode = $this -> settings['demomode'];
 
      $this -> msg['message'] = "";
      $this -> msg['class'] = "";
 
   //   add_action('init', array(&$this, 'check_payu_response'));
      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
      add_action('woocommerce_receipt_bank', array(&$this, 'receipt_page'));
   }
    function init_form_fields(){

 	$this -> form_fields = array(
		'enabled' => array(
			'title' => __('Включить/Выключить','yandex_money'),
			'type' => 'checkbox',
			'label' => __('Включить модуль оплаты Яндекс.Банк','yandex_money'),
			'default' => 'no'),
		'demomode' => array (
			'title' => __('Включить/Выключить','yandex_money'),
			'type' => 'checkbox',
			'label' => __('Включить тестовый режим','yandex_money'),
			'default' => 'no'),
		'title' => array(
			'title' => __('Заголовок','yandex_money'),
			'type'=> 'text',
			'description' => __('Название, которое пользователь видит во время оплаты','yandex_money'),
			'default' => __('Яндекс.Банк','yandex_money')),
		'description' => array(
			'title' => __('Описание','yandex_money'),
			'type' => 'textarea',
			'description' => __('Описание, которое пользователь видит во время оплаты','yandex_money'),
			'default' => __('Оплата через систему Яндекс.Банк','yandex_money')),
		'scid' => array(
			'title' => 'Scid',
			'type' => 'text',
			'description' => __('Номер витрины магазина ЦПП','yandex_money')),
		'ShopID' => array(
			'title' => 'ShopID',
			'type' => 'text',
			'description' => __('Номер магазина ЦПП','yandex_money') )
		);
		
    }
 
       public function admin_options(){
		echo '<h3>'.__('Оплата Яндекс.Банк','yandex_money').'</h3>';
		echo '<h5>'.__('Для подключения системы Яндекс.Банк нужно одобрить заявку на подключение https://money.yandex.ru/shoprequest/ , после этого Вы получите и ShopID, и Scid','yandex_money').'</h5>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';
 
    }
 
    /**
     *  There are no payment fields for payu, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }
    /**
     * Receipt Page
     **/
    function receipt_page($order){
        //echo '<p>Thank you for your order, please click the button below to pay with PayU</p>';
        echo $this -> generate_payu_form($order);
    }
    /**
     * Generate payu button link
     **/
    public function generate_payu_form($order_id){
 
        global $woocommerce;

	if($this->demomode == "yes") {
	  $yahost = "https://demomoney.yandex.ru/eshop.xml";
	}	
	else {
	  $yahost = "https://money.yandex.ru/eshop.xml";
	}
 
        $order = new WC_Order($order_id);
        $txnid = $order_id;
	    $result ='';
		$result .= '<form name=ShopForm method="POST" id="submit_bank_payment_form" action="'.$yahost.'">'; 
			$result .= '<input type="hidden" name="firstname" value="'.$order -> billing_first_name.'">';
			$result .= '<input type="hidden" name="lastname" value="'.$order -> billing_last_name.'">';
			$result .= '<input type="hidden" name="scid" value="'.$this->scid.'">';
			$result .= '<input type="hidden" name="ShopID" value="'.$this->ShopID.'"> ';
			$result .= '<input type=hidden name="CustomerNumber" value="'.$txnid.'" size="43">';
			$result .= '<input type=hidden name="Sum" value="'.$order->order_total.'" size="43">'; 
			$result .= '<input type=hidden name="CustName" value="'.$order->billing_first_name.' '.$order->billing_last_name.'" size="43">';
			$result .= '<input type=hidden name="CustAddr" value="'.$order->billing_city.', '.$order->billing_address_1.'" size="43">';
			$result .= '<input type=hidden name="CustEMail" value="'.$order->billing_email.'" size="43">'; 
			$result .= '<textarea style="display:none" rows="10" name="OrderDetails"  cols="34">'.$order->customer_note.'</textarea>';
			$result .= '<input name="paymentType" value="AC" type="hidden">';
			$result .= '<input type=submit value="Оплатить">';
		$result .='<script type="text/javascript">';
		$result .='jQuery(function(){
		jQuery("body").block(
        {
            message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />Спасибо за заказ. Сейчас Вы будете перенаправлены на страницу оплаты.",
                overlayCSS:
        {
            background: "#fff",
                opacity: 0.6
		},
		css: {
			padding:        20,
				textAlign:      "center",
				color:          "#555",
				border:         "3px solid #aaa",
				backgroundColor:"#fff",
				cursor:         "wait",
				lineHeight:"32px"
		}
		});
		});
		';
		$result .='jQuery(document).ready(function ($){ jQuery("#submit_bank_payment_form").submit(); });';
		$result .='</script>';
		$result .='</form>';
		
		return $result;
 
    }
    /**
     * Process the payment and return the result
     **/
   function process_payment($order_id){
        $order = new WC_Order($order_id);
		
       /* return array('result' => 'success', 'redirect' => add_query_arg('order',
            $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
        );*/
		return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ));
		
    }
 
    
    function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
     // get all pages
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
}
   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_bank_payu_gateway($methods) {
        $methods[] = 'WC_bank_Payu';
        return $methods;
    }
 
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_bank_payu_gateway' );
}





