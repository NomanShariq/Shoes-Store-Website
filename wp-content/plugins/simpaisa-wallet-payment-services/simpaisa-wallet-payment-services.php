<?php
/*
 * Plugin Name: Simpaisa WooCommerce Jazzcash & Easypaisa Wallet
 * Plugin URI: https://www.simpaisa.com/pay-in.html
 * Description: Providing Easy To Integrate Digital Payment Services
 * Author: Simpaisa Pvt Ltd
 * Author URI: https://www.simpaisa.com
 * Version: 2.1.3
*/

header("Access-Control-Allow-Origin: *");

add_filter("woocommerce_payment_gateways", "simpaisa_jz_ep_wallet_add_gateway_class");

function simpaisa_jz_ep_wallet_add_gateway_class($gateways)
{
    $gateways[] = "WC_Simpaisa_Jz_Ep_Wallet_Gateway";

    return $gateways;
}

// load woo simpaisa plugin and its options
add_action('plugins_loaded', 'simpaisa_jz_ep_wallet_init_gateway_class');

function simpaisa_jz_ep_wallet_init_gateway_class()
{
    if (class_exists('WC_Payment_Gateway')) {
        class WC_Simpaisa_Jz_Ep_Wallet_Gateway extends WC_Payment_Gateway
        {
            public function __construct()
            {
                $this->id = "simpaisa_woo_jz_ep_wallet";
                $this->icon = "";
                $this->has_fields = true;
                $this->method_title = "Simpaisa Jazzcash & Easypaisa Wallet Payment";
                $this->method_description = "Pay With Your Mobile Wallet Account (Jazzcash , Easypaisa) via Simpaisa Payment Services";

                $this->supports = ["products"];

                $this->init_form_fields();
                $this->init_settings(); //for custom settings fields
                $this->title = $this->get_option("wallet_title");
                $this->description = $this->get_option("wallet_description");
                $this->enabled = $this->get_option("wallet_enabled");
                $this->base_url = $this->get_option("wallet_base_url");
                $this->wallet_options = $this->get_option("wallet_options");
                $this->merchant_id = $this->get_option("wallet_merchant_id");
                $this->is_items = $this->get_option("wallet_is_items");

                add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
                add_action("wp_enqueue_scripts", [$this, "payment_stylesheet"], 20);

                add_action("woocommerce_update_options_payment_gateways_" . $this->id, [$this, "process_admin_options"]);

                add_action("woocommerce_api_simpaisa_wallet_redirect", [$this, "simpaisa_wallet_redirect",]);

                add_action("woocommerce_api_simpaisa_wallet_get_payment_details", [$this, "payment_details",]);

                add_action("woocommerce_api_simpaisa_wallet_order_verify", [$this, "get_order_details_id",]);

                // Simpaisa - Dynamic Callback
                add_action("woocommerce_api_simpaisa_notify", [$this, "simpaisa_notify",]);
            }

            public function payment_fields()
            {
                $sp_wallet_account = "";
                $sp_wallet_account_type = "Easypaisa";
                if (isset($_SESSION["sp_wallet_account"])) {
                    $sp_wallet_account = $this->sanitize_input($_SESSION["sp_wallet_account"]);
                }

                if (isset($_SESSION["sp_wallet_account_type"])) {
                    $sp_wallet_account_type = $this->sanitize_input($_SESSION["sp_wallet_account_type"]);
                }

                // I will echo() the form, but you can close PHP tags and print it directly in HTML
                echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

                // Add this action hookif you want your custom payment gateway to support it
                do_action("simpaisa_wallet_form_start", $this->id);

                // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
                // echo '<p>Enter your wallet account details</p>';

                $this->HtmlContent($this->wallet_options);

                do_action("simpaisa_wallet_form_end", $this->id);

                echo '</fieldset>';
            }




            public function HtmlContent($wallet_options)
            {

                printf('<div class="simpaisa-jazz-easy-card">
                            <div class="simpaisa-jazz-easy-card-header">
                            <ul class="simpaisa-jazz-easy-nav simpaisa-jazz-easy-nav-tabs justify-content-center" role="tablist">');



                if ($wallet_options == 'both') :
                    printf('<li class="simpaisa-jazz-easy-nav-item">
                                    <a class="simpaisa-jazz-easy-nav-link active easypaisa" data-toggle="tab" role="tab">
                                        <img src="' . plugins_url("assets/easypaisa-logo.png", __FILE__) . '" class="simpaisa-jazz-easy-paymentgateway_logos">
                                    </a>
                                </li>
                                <li class="simpaisa-jazz-easy-nav-item">
                                    <a class="simpaisa-jazz-easy-nav-link jazzcash" data-toggle="tab" role="tab">
                                        <img src="' . plugins_url("assets/jazzcash-logo.png", __FILE__) . '" class="simpaisa-jazz-easy-paymentgateway_logos">
                                    </a>
                                </li>
                                <input name="sp_wallet_account_type" class="sp_wallet_account_type" type="hidden" value="%s">', esc_attr('Easypaisa'));
                endif;

                if ($wallet_options == 'easypaisa') :
                    printf('<li class="simpaisa-jazz-easy-nav-item">
                                    <a class="simpaisa-jazz-easy-nav-link  easypaisa active" data-toggle="tab" role="tab">
                                        <img src="' . plugins_url("assets/easypaisa-logo.png", __FILE__) . '" class="simpaisa-jazz-easy-paymentgateway_logos">
                                    </a>
                                </li>
                                <input name="sp_wallet_account_type" class="sp_wallet_account_type" type="hidden" value="%s">', esc_attr('Easypaisa'));
                endif;

                if ($this->wallet_options == 'jazzcash') :
                    printf('<li class="simpaisa-jazz-easy-nav-item">
                                    <a class="simpaisa-jazz-easy-nav-link jazzcash active" data-toggle="tab" role="tab">
                                        <img src="' . plugins_url("assets/jazzcash-logo.png", __FILE__) . '" class="simpaisa-jazz-easy-paymentgateway_logos">
                                    </a>
                                </li>
                                <input name="sp_wallet_account_type" class="sp_wallet_account_type" type="hidden" value="%s">', esc_attr('Jazzcash'));
                endif;

                printf('</ul>
                    </div>
                    <hr class="simpaisa-jazz-easy-card-hr">
                    <div class="simpaisa-jazz-easy-card-body">
                        <label>Enter Your Account Number</label>
                        <input name="sp_wallet_account"  minlength="11" maxlength="11" id="sp_wallet_account" placeholder="03xxxxxxxxx" type="text" autocomplete="off">
                        <span class="simpaisa-jazz-easy-phone-err"></span>
                    </div>
                </div>');
            }

            function checkWalletAccountNo($number)
            {
                $pattern = "/((\03)|03)[0-9]{9}$/";

                if (preg_match($pattern, $number)) {
                    return substr($number, 1);
                } else {
                    return false;
                }
            }

            public function process_payment($order_id)
            {
                global $woocommerce;

                if (!session_id()) {
                    session_start();
                }

                $currency = strtoupper(get_woocommerce_currency());
                if ($currency != "PKR") {
                    wc_add_notice(__("Invalid currency " . $currency . ", <strong>Wallet Payment is allowed only for Pakistani currency (PKR)</strong>"), "error");
                    return false;
                }

                if ($this->base_url == "" || $this->merchant_id == "") {
                    wc_add_notice(__("<strong>Payment Base URL and Merchant Id are required</strong>, please check your Simpaisa Payment Configuration."), "error");
                    return false;
                }

                if (!$_POST["sp_wallet_account"] || $this->checkWalletAccountNo($this->sanitize_input($_POST["sp_wallet_account"])) == false) {
                    wc_add_notice(__("Kindly enter valid Wallet Account No"), "error");
                    return false;
                }

                if ($this->sanitize_input($_POST["sp_wallet_account_type"]) != "Easypaisa" && $this->sanitize_input($_POST["sp_wallet_account_type"]) != "Jazzcash") {
                    wc_add_notice(__("Kindly choose your wallet account"), "error");
                    return false;
                }

                $msisdn = $this->checkWalletAccountNo($this->sanitize_input($_POST["sp_wallet_account"]));
                $operator = $this->sanitize_input($_POST["sp_wallet_account_type"]);
                $operatorId = $this->sanitize_input($_POST["sp_wallet_account_type"]) == "Easypaisa" ? "100007" : "100008";

                $merchant_id = $this->sanitize_input($this->merchant_id);
                $paymentUrl = rtrim($this->base_url, "/") . "/";
                $paymentUrl = str_replace("/index.php", "", $paymentUrl) . "api/wallet-backend-api.php";

                $order = wc_get_order($order_id);

                $_SESSION["sp_wallet_account"] = $this->sanitize_input($_POST["sp_wallet_account"]);
                $_SESSION["sp_wallet_account_type"] = $this->sanitize_input($_POST["sp_wallet_account_type"]);

                $msisdn = $this->checkWalletAccountNo($this->sanitize_input($_POST["sp_wallet_account"]));
                $transactionId = substr(md5(uniqid(rand(), true)), 0, 6);
                $transactionId = $order_id . "-" . $transactionId;
                $amountSimpaisa = $order->get_total();
                $_payment_method_title = "Mobile Wallet Payment, Order ID (" . $transactionId . ")";
                update_post_meta($order_id, "_sp_orderId", $transactionId);
                update_post_meta($order_id, "_payment_method_title", $_payment_method_title);
                update_post_meta($order_id, '_sp_payment_method', 'WALLET');
                $note = __("Simpaisa Mobile Wallet Payment initiate, Order ID # $transactionId - $operator");
                $order->add_order_note($note);
                $authorization = "Basic " . base64_encode($transactionId . ":" . $msisdn);

                $payload = array(
                    'body' => [
                        "operatorId" => $operatorId,
                        "merchantId" => $merchant_id,
                        "msisdn" => $msisdn,
                        "amount" => $amountSimpaisa,
                        "userKey" => $transactionId,
                        "transactionType" => "mobile"
                    ],
                    'timeout'     => 180,
                    'redirection' => 5,  // added
                    'httpversion' => '1.0',
                    'method' => 'POST',
                    'headers' => array('Authorization' => $authorization)
                );
                $response = wp_remote_post($paymentUrl, $payload);

                if (wp_remote_retrieve_response_code($response) != 200) {
                    $error_message = wp_remote_retrieve_response_code($response);
                    wc_add_notice(__(" Error: HTTP Response " . $error_message . ", <strong>Order payment transaction has been failed</strong> , please try again."), "error");
                    return false;
                } elseif (is_wp_error($response) && count($response->get_error_messages()) > 0) {
                    $error_message = '';
                    foreach ($response->get_error_messages() as $error) {
                        $error_message .= $error . '<br/>';
                    }

                    wc_add_notice(__(" Error: " . $error_message . " , <strong>Order payment transaction has been failed</strong> , please try again."), "error");
                    return false;
                }
                $__response     = json_decode(wp_remote_retrieve_body($response), true);

                $status = $__response["status"];
                $res_message = $__response["message"];

                if ($status == 0000) {
                    $order_status = $order->get_status();
                    $order->payment_complete();
                    wc_reduce_stock_levels($order_id);
                    $order_status = $order->get_status();
                    update_post_meta($order_id, '_sp_transactionId', $__response['transactionId']);
                    $note = __("Simpaisa Mobile Wallet Payment :: $operator -  Order Id : '$transactionId' , CB Status : $res_message , Order Status : $order_status");
                    $order->add_order_note($note);

                    return $this->redirect_page(["result" => "success", "redirect" => $order->get_checkout_order_received_url()]);
                } else {
                    $order_status = $order->get_status();
                    if ($order_status == "pending") {
                        $order->update_status("failed");
                    }

                    $order_status = $order->get_status();
                    $note = __("Simpaisa Mobile Wallet Payment :: $operator - Order Id : '$transactionId' , CB Status : $res_message , Order Status : $order_status");
                    $order->add_order_note($note);

                    wc_add_notice(__(" Error: " . $res_message . " , <strong>Order payment transaction has been failed</strong> , please try again."), "error");

                    return $this->redirect_page(["result" => "success", "redirect" => wc_get_checkout_url()]);
                }
            }

            public function payment_stylesheet()
            {
                wp_register_style("simpaisa_jz_ep_stylesheet", plugins_url("assets/css/style.css", __FILE__));
                wp_enqueue_style("simpaisa_jz_ep_stylesheet");
            }

            public function payment_scripts()
            {
                // we need JavaScript to process a token only on cart/checkout pages, right?
                if (!is_cart() && !is_checkout() && !isset($_GET["pay_for_order"])) {
                    return;
                }

                // //if our payment gateway is disabled, we do not have to enqueue JS too
                if ("no" === $this->enabled) {
                    return;
                }


                // and this is our custom JS in your plugin directory that works with token.js
                wp_register_script("simpaisa_wallets_script", plugins_url("assets/js/main.js", __FILE__), false, '1.0.0', true);
                wp_enqueue_script("simpaisa_wallets_script");
            }

            public function init_form_fields()
            {
                $this->form_fields = [
                    "wallet_enabled" => ["title" => "Enable/Disable", "label" => "Enable Simpaisa", "type" => "checkbox", "description" => "", "default" => "no"],
                    "wallet_is_items" => ["title" => "Enable/Disable", "label" => "Enable Items", "type" => "checkbox", "description" => "", "default" => "yes"],
                    "wallet_options" => ["title" => "Wallet options", "label" => __("Options", "woocommerce"), "type" => "select", "options" => ["both" => __("Jazzcash & Easypaisa", "woocommerce"), "jazzcash" => __("Only Jazzcash", "woocommerce"), "easypaisa" => __("Only Easypaisa", "woocommerce"),], "wallet_description" => "Enable/Disable wallet payment options", "desc_tip" => true, "default" => "both"],
                    "wallet_title" => ["title" => "Title", "type" => "text", "description" => "This controls the title which the user sees during checkout.", "default" => "Jazzcash/Easypaisa Wallet", "desc_tip" => true],
                    "wallet_description" => ["title" => "Description", "type" => "textarea", "description" => "This controls the description which the user sees during checkout.", "default" => "Pay With Your Jazzcash, Easypaisa Wallet Account via Simpaisa Payment Services", "desc_tip" => true],
                    "wallet_base_url" => ["title" => "Payment Base Url", "type" => "text"],
                    "wallet_merchant_id" => ["title" => "Merchant Id", "type" => "text"],
                    'wallet_webhookUrl' => ['css' => 'pointer-events:none;background:#00000024;font-size:12px;', 'title' => 'Webhook Url', 'description' => 'This is the notification URL. Simpaisa sends notification of each transactions on the provided URL.', 'default' => rtrim(site_url(), '/') . "/index.php/wc-api/simpaisa_notify"]
                ];
            }


            public function simpaisa_notify()
            {
                global $woocommerce;
                $json = $this->sanitize_input(file_get_contents("php://input"));


                error_log('Simpaisa log :: Wallet - Postback Data ' . $json);

                if (strpos($json, '=') !== false) {
                    $data = [];
                    $json =  str_replace('{', '', $json);
                    $json =  str_replace('}', '', $json);
                    foreach (explode(",", $json) as $value) {
                        $data[trim(explode("=", $value)[0])] = trim(explode("=", $value)[1]);
                    }
                } else {
                    $data = json_decode($json, true);
                }


                $transactionId = $this->sanitize_input($data["userKey"]);
                $status = $this->sanitize_input($data["status"]);
                $merchantId = $this->sanitize_input($data["merchantId"]);
                if (!isset($data['transactionId'])) {
                    $sp_transactionId = 'Null';
                } else {
                    $sp_transactionId = $this->sanitize_input($data['transactionId']);
                }

                $_orderID = explode('-', $transactionId)[0];

                if (get_post_meta($_orderID, '_sp_payment_method', true) == 'WALLET') {
                    error_log('Simpaisa log :: Postback Order No # ' . $_orderID . ' status ' . $status . ' merchantId ' . $merchantId);
                    if (isset($merchantId) && isset($status) && isset($_orderID)) {
                        $order = wc_get_order($_orderID);

                        $order_status = $order->get_status();

                        if ($order_status == "pending" || $order_status == "failed") {
                            if ($status == "0000") {
                                $order->payment_complete();
                                wc_reduce_stock_levels($_orderID);
                                $order_status = $order->get_status();
                                update_post_meta($_orderID, '_sp_transactionId', $sp_transactionId);
                                $note = __("Simpaisa Postback - Order Id : '$transactionId' , Trans Id : '$sp_transactionId' , CB Status : $status , Order Status : $order_status");
                                $order->add_order_note($note);

                                echo json_encode(["respose_code" => "0000", "order_status" => $order_status, "status" => $status, "message" => "Order status has been updated",]);
                            } else {
                                $order->update_status("failed");
                                $order_status = $order->get_status();

                                $note = __("Simpaisa Postback - Order Id : '$transactionId' , Trans Id : '$sp_transactionId' , CB Status : $status , Order Status : $order_status");
                                $order->add_order_note($note);

                                echo json_encode(["respose_code" => "0000", "order_status" => $order_status, "status" => $status, "message" => "Order status has been updated",]);
                            }
                        } else {
                            $note = __("Simpaisa Postback - Order Id : '$transactionId' , Trans Id : '$sp_transactionId'  , CB Status : $status , Order Status : $order_status");
                            $order->add_order_note($note);

                            echo json_encode(["respose_code" => "1003", "order_status" => $order_status, "status" => $status, "message" => "Order status already modified",]);
                        }
                    } else {
                        error_log('Simpaisa log :: Postback fields are missing');
                        echo json_encode(["respose_code" => "1001", "message" => "Field(s) are required",]);
                        exit();
                    }
                }
            }



            public function payment_details()
            {
                global $woocommerce;
                if (isset($_REQUEST["transactionId"])) {
                    $available_gateways = $woocommerce
                        ->payment_gateways
                        ->get_available_payment_gateways(); //getting all payment methods from woocommerce
                    $merchant_id = $available_gateways["simpaisa_woo_jz_ep_wallet"]->wallet_merchant_id;

                    $transactionId = $this->sanitize_input($_REQUEST["transactionId"]);
                   
                    if (isset($_orderID)) {
                        $order = new WC_Order($_orderID);
                        $order_data = $order->get_data(); // The Order data
                        $order_status = $order->get_status();
                        $order_id = trim(str_replace("#", "", $order->get_order_number()));
                        $amountSimpaisa = $order->get_total();
                        $currency = $order_data["currency"];
                        $first_name = $order_data["billing"]["first_name"];
                        $last_name = $order_data["billing"]["last_name"];
                        $phone = $order_data["billing"]["phone"];

                        echo json_encode(["respose_code" => "0000", "status" => $order_status, "message" => "Data successfully fetched", "transactionid" => $transactionId, "orderid" => $order_id, "merchant_id" => $merchant_id, "totalamount" => $amountSimpaisa, "currency" => $currency, "first_name" => $first_name, "last_name" => $last_name, "phone" => $phone,]);
                    } else {
                        echo json_encode(["respose_code" => "9999", "message" => "Request failed try again",]);
                    }

                    // print_r($order_data);
                    exit();
                } else {
                    echo json_encode(["respose_code" => "1001", "message" => "Transaction id is required",]);
                    exit();
                }
            }

            public function get_order_details_id()
            {
                global $woocommerce;
                if (isset($_GET["orderid"])) {
                    $available_gateways = $woocommerce
                        ->payment_gateways
                        ->get_available_payment_gateways(); //getting all payment methods from woocommerce
                    $merchant_id = $available_gateways["simpaisa_woo_jz_ep_wallet"]->wallet_merchant_id;

                    $_orderID = $this->sanitize_input($_GET["orderid"]);
                    $_transactionId = get_post_meta($_orderID, "_sp_orderId", true);
                    $_payment_method = get_post_meta($_orderID, "_payment_method_title", true);

                    if (isset($_transactionId)) {
                        $order = new WC_Order($_orderID);
                        $order_data = $order->get_data(); // The Order data
                        $order_id = trim(str_replace("#", "", $order->get_order_number()));
                        $amountSimpaisa = $order->get_total();
                        $currency = $order_data["currency"];
                        $first_name = $order_data["billing"]["first_name"];
                        $last_name = $order_data["billing"]["last_name"];
                        $phone = $order_data["billing"]["phone"];

                        echo json_encode(["respose_code" => "0000", "message" => "Data successfully fetched", "orderStatus" => $order_data["status"], "transactionid" => $_transactionId, "payment_method" => $_payment_method, "orderid" => $order_id, "merchant_id" => $merchant_id, "total" => $amountSimpaisa, "currency" => $currency, "first_name" => $first_name, "last_name" => $last_name, "phone" => $phone,]);
                    } else {
                        echo json_encode(["respose_code" => "9999", "message" => "Request failed try again",]);
                    }

                    // print_r($order_data);
                    exit();
                } else {
                    echo json_encode(["respose_code" => "1001", "message" => "Transaction id is required",]);
                    exit();
                }
            }

            public function simpaisa_wallet_redirect()
            {
                global $woocommerce;
                if (isset($_GET["transactionId"])) {
                    $transactionId = $this->sanitize_input($_GET["transactionId"]);
                    $_orderID = explode('-', $transactionId)[0];
                    $operatorId = $this->sanitize_input($_GET["operatorId"]);

                    $available_gateways = $woocommerce
                        ->payment_gateways
                        ->get_available_payment_gateways(); //getting all payment methods from woocommerce
                    $paymentUrl = rtrim($available_gateways["simpaisa_woo_jz_ep_wallet"]->base_url, "/") . "/";
                    $paymentUrl = str_replace("/index.php", "", $paymentUrl) . "api/wallet-inquiry.php";
                    $merchant_id = $available_gateways["simpaisa_woo_jz_ep_wallet"]->wallet_merchant_id;

                    $payload = array(
                        'body' => [
                            "operatorId" => $this->sanitize_input($operatorId),
                            "merchantId" => $this->sanitize_input($merchant_id),
                            "method" => "inquiry",
                            "userKey" => $this->sanitize_input($transactionId)
                        ],
                        'redirection' => 5,  // added
                        'httpversion' => '1.0',
                        'method' => 'POST',
                        'headers' => array('Authorization' => $authorization)
                    );
                    $response = wp_remote_post($paymentUrl, $payload);
                    if (wp_remote_retrieve_response_code($response) != 200) {
                        $error_message = wp_remote_retrieve_response_code($response);
                        wc_add_notice(__(" Error: HTTP Response " . $error_message . " , <strong>Order payment transaction has been failed</strong> , please try again."), "error");
                        return false;
                    } elseif (is_wp_error($response) && count($response->get_error_messages()) > 0) {
                        $error_message = $response->get_error_message();
                        wc_add_notice(__(" Error: " . $error_message . " , <strong>Order payment transaction has been failed</strong> , please try again."), "error");
                        return false;
                    }

                    $__response     = json_decode(wp_remote_retrieve_body($response), true);

                   
                    $status = "9999";

                    $order = new WC_Order($_orderID);
                    $status = $__response["code"];

                    if ($status == 0000 && $httpcode == 200) {
                        $order_status = $order->get_status();
                        if ($order_status == "pending" || $order_status == "failed") {
                            $order->payment_complete();
                            wc_reduce_stock_levels($_orderID);
                        }

                        $order_status = $order->get_status();
                        $note = __("Simpaisa Mobile Wallet Payment - Order Id : '$transactionId' , CB Status : $status , Order Status : $order_status");
                        $order->add_order_note($note);

                        $order_key = get_post_meta($_orderID, "_order_key", true);
                        $returnURL = site_url() . "/index.php/checkout/order-received/" . $_orderID . "/?key=" . $order_key . "&pay_for_order=false";
                        wp_redirect($returnURL);
                        exit();
                    } elseif ($status == 1002 || $status == 1001) {
                        $order_status = $order->get_status();
                        if ($order_status == "pending") {
                            $order->update_status("failed");
                        }

                        $order_status = $order->get_status();
                        $note = __("Simpaisa Mobile Wallet Payment - Order Id : '$transactionId' , CB Status : $status , Order Status : $order_status");
                        $order->add_order_note($note);

                        $order_key = get_post_meta($_orderID, "_order_key", true);
                        $returnURL = site_url() . "/index.php/checkout/order-pay/" . $_orderID . "/?key=" . $order_key . "&pay_for_order=true";
                        wp_redirect($returnURL);
                        wc_add_notice(__(" Error:" . $status . " , <strong>Order payment transaction has been failed</strong> , please try again."), "error");
                        exit();
                    } else {
                        wp_redirect(site_url("/"));
                        exit();
                    }
                } else {
                    wp_redirect(site_url("/"));
                }

                wp_redirect(site_url("/"));
                exit();
            }

            public function sanitize_input($sanitize_input, $default = null)
            {
                return isset($sanitize_input) ? sanitize_text_field($sanitize_input) : $default;
            }

            public function redirect_page($arr)
            {
                if (wp_doing_ajax()) {
                    return wp_send_json($arr);
                } else {
                    return $arr;
                }
            }
        }
    }
}

configure_plugin();

function configure_plugin()
{

    class PluginConfiguration
    {

        public function __construct()
        {

            //It will fire when the plugin is activated and stop the user to install this plugin if woocommerce is not installed.
            register_activation_hook(__FILE__, [$this, 'plugin_activate_hook']);

            // Admin Notice
            add_action("admin_notices", [$this, "my_plugin_admin_notices",]);

            // Woocommerce plugin Notice
            add_action("admin_notices", [$this, "woocommerce_related_notices",]);
        }

        public function plugin_activate_hook()
        {
            if (!class_exists("WC_Payment_Gateway")) {
                $notices = get_option("my_plugin_deferred_admin_notices", []);
                $url = admin_url("plugins.php?deactivate=true");
                $notices[] = "Error: Install <b>WooCommerce</b> before activating this plugin. <a href=" . $url . ">Go Back</a>";
                update_option("my_plugin_deferred_admin_notices", $notices);
            }
        }

        public function my_plugin_admin_notices()
        {
            if ($notices = get_option("my_plugin_deferred_admin_notices")) {
                foreach ($notices as $notice) {
                    echo "<div class='updated' style='background-color:#f2dede'><p>$notice</p></div>";
                }

                deactivate_plugins(plugin_basename(__FILE__), true);
                delete_option("my_plugin_deferred_admin_notices");
                die();
            }
        }

        public function woocommerce_related_notices()
        {
            global $woocommerce;

            if (!class_exists("WC_Payment_Gateway")) {
                echo "<div class='notice notice-success is-dismissible'>
                        <p>Simpaisa WooCommerce Jazzcash & Easypaisa Wallet requires <b>WooCommerce</b> Plugin to make it work!</p>
                    </div>";
            }

            if (class_exists("WC_Payment_Gateway") && get_woocommerce_currency_symbol() != get_woocommerce_currency_symbol("PKR")) {
                echo "<div class='notice notice-success is-dismissible'>
                        <p>Simpaisa WooCommerce Jazzcash & Easypaisa Wallet requires <b>PKR</b> Currency to make it work!</p>
                    </div>";
            }
        }
    }

    $PluginConfiguration = new PluginConfiguration();
}
