<?php
/**
 * Plugin Name: Pasarela de pago Woocommerce
 * Plugin URI: localhost
 * Author Name: Edwin y Jeffries
 * Author URI: localhost
 * Description: Este plugin admites sistemas de pagos local.
 * Version: 0.1.0
*

 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'misha_add_gateway_class' );
function misha_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_ej_Gateway';
	return $gateways;
}

/*parte del hash
 <?php
    //$orderid= "test";
  //$amount=1.00;
    //$time=1279302634;
//$key=232323322222222222222222222222222;
$tarjeta=$_POST['cvc'];


echo hash('sha512',$tarjeta);
$cuenta=50000;
?>
 */
add_action( 'plugins_loaded', 'ej_init_gateway_class' );
function ej_init_gateway_class() {

	class WC_ej_Gateway extends WC_Payment_Gateway {

 		/**parte del formulario
 		 <html>
<head>
 <title>Ejemplo de PHP</title>
</head>
<body>

<H1>Ejemplo de procesado de formularios</H1>
<!-- display errors returned by createToken -->
<span class="payment-errors"></span>

<!-- stripe payment form -->
<form action="submit.php" method="POST" id="paymentFrm">
    <p>
        <label>Name</label>
        <input type="text" name="name" size="50" />
    </p>
    <p>
        <label>Email</label>
        <input type="text" name="email" size="50" />
    </p>
    <p>
        <label>Card Number</label>
        <input type="text" name="card_num" size="20" autocomplete="off" 
class="card-number" />
    </p>
    <p>
        <label>CVC</label>
        <input type="text" name="cvc" size="4" autocomplete="off" class="card-cvc" />
    </p>
    <p>
        <label>Expiration (MM/YYYY)</label>
        <input type="text" name="exp_month" size="2" class="card-expiry-month"/>
        <span> / </span>
        <input type="text" name="exp_year" size="4" class="card-expiry-year"/>
    </p>
    
    <!--<input type="submit" name="procesar" value="Send">-->
    <a href="santiago.php" type="submit" id="boton">Enviar</a>
    </form>
   
</body>
</html>
 		 */
 		public function __construct() {
            $this->id = 'ej';
            $this->icon = '';
            $this->has_fields = true; 
            $this->method_title = 'Pasarela de pago Edwin-Jeffri';
            $this->method_description = 'Pasarela de pago local';
        
            $this->supports = array(
                'products'
            );

            $this->init_form_fields();
        
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
        
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );

 		}

 		public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Habilitar/Deshabilitar',
                    'label'       => 'Habilitar Pasarela de pago de Jeffri-edwin',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Titulo',
                    'type'        => 'text',
                    'description' => 'Esto controla la descripción que ve el usuario durante el pago.',
                    'default'     => 'Tarjeta de credito',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Descripción',
                    'type'        => 'textarea',
                    'description' => 'Esto controla la descripción que ve el usuario durante el pago.',
                    'default'     => 'Pague con su tarjeta de crédito a través de nuestra fantástica pasarela de pago.',
                ),
                'testmode' => array(
                    'title'       => 'Modo de prueba',
                    'label'       => 'Habilitar modo de prueba',
                    'type'        => 'checkbox',
                    'description' => 'Coloque la pasarela de pago en modo de prueba utilizando claves API de prueba.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'test_publishable_key' => array(
                    'title'       => 'Prueba de clave publicable',
                    'type'        => 'text'
                ),
                'test_private_key' => array(
                    'title'       => 'Prueba de clave privada',
                    'type'        => 'password',
                ),
                'publishable_key' => array(
                    'title'       => 'Clave publicable en vivo',
                    'type'        => 'text'
                ),
                'private_key' => array(
                    'title'       => 'Clave privada en vivo.',
                    'type'        => 'password'
                )
            );
	 	}

		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
 
            if ( $this->description ) {
                
                if ( $this->testmode ) {
                    $this->description .= ' MODO DE PRUEBA HABILITADO. En el modo de prueba, puede utilizar los números de tarjeta que figuran en <a href="#">documentation</a>.';
                    $this->description  = trim( $this->description );
                }
                echo wpautop( wp_kses_post( $this->description ) );
            }
        
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
        
            do_action( 'woocommerce_credit_card_form_start', $this->id );
        
            echo '<div class="form-row form-row-wide"><label>Numero de Tarjeta <span class="required">*</span></label>
                <input id="ej_ccNo" type="text" autocomplete="off">
                </div>
                <div class="form-row form-row-first">
                    <label>Fecha de expiración <span class="required">*</span></label>
                    <input id="ej_expdate" type="text" autocomplete="off" placeholder="MM / YY">
                </div>
                <div class="form-row form-row-last">
                    <label>Codigo de tarjeta (CVC) <span class="required">*</span></label>
                    <input id="ej_cvv" type="password" autocomplete="off" placeholder="CVC">
                </div>
                <div class="clear"></div>';
        
            do_action( 'woocommerce_credit_card_form_end', $this->id );
        
            echo '<div class="clear"></div></fieldset>';
        
				 
		}

	 	public function payment_scripts() {
            // we need JavaScript to process a token only on cart/checkout pages, right?
            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ( 'no' === $this->enabled ) {
                return;
            }

            // no reason to enqueue JavaScript if API keys are not set
            if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
                return;
            }

            // do not work with card detailes without SSL unless your website is in a test mode
            if ( ! $this->testmode && ! is_ssl() ) {
                return;
            }

            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            wp_enqueue_script( 'misha_js', 'https://www.mishapayments.com/api/token.js' );

            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script( 'woocommerce_misha', plugins_url( 'misha.js', __FILE__ ), array( 'jquery', 'misha_js' ) );

            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script( 'woocommerce_misha', 'misha_params', array(
                'publishableKey' => $this->publishable_key
            ) );

            wp_enqueue_script( 'woocommerce_misha' );
	
	 	}

		public function validate_fields() {

            if( empty( $_POST[ 'billing_first_name' ]) ) {
                wc_add_notice(  'First name is required!', 'error' );
                return false;
            }
            return true;
		}

		public function process_payment( $order_id ) {

            global $woocommerce;
            $order = wc_get_order( $order_id );
        
        
            /*
            * Array with parameters for API interaction
            */
            $args = array(
        
                
        
            );
        
            /*
            * Your API interaction could be built with wp_remote_post()
            */
            $response = wp_remote_post( '{payment processor endpoint}', $args );
        
        
            if( !is_wp_error( $response ) ) {
        
                $body = json_decode( $response['body'], true );
        
                // it could be different depending on your payment processor
                if ( $body['response']['responseCode'] == 'APPROVED' ) {
        
                    // we received the payment
                    $order->payment_complete();
                    $order->reduce_order_stock();

                    $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
        
                    $woocommerce->cart->empty_cart();

                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );
        
                } else {
                    wc_add_notice(  'Please try again.', 'error' );
                    return;
                }
        
            } else {
                wc_add_notice(  'Connection error.', 'error' );
                return;
            }
	 	}

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {

					
	 	}
 	}
}
