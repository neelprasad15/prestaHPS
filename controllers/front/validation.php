<?php
/**
 *
 * @author    Latpay Team
 * @copyright Copyright (c) Latpay
 * @license   Addons PrestaShop license limitation
 * @version   2.0.0
 * @link      https://www.latpay.com.au/
 *
 */

class LatpayValidationModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
    }
    public function initcontent()
    {
        parent::initContent();
        $this->context->smarty->assign(
            array(
              'Merchant_User_Id' => Tools::getValue("Merchant_User_Id"),
              'merchantpwd' => Tools::getValue("merchantpwd"),
              'currencydesc' => Tools::getValue("currencydesc"),
              'amount' => Tools::getValue("amount"),
              'processurl' => Tools::getValue("processurl"),
              'merchant_ref_number' => Tools::getValue("merchant_ref_number")
            )
        );
        $this->setTemplate('module:latpay/views/templates/front/validation.tpl');
    }
    public function postProcess()
    {
      /**
           * Get current cart object from session
           */
        $cart = $this->context->cart;
        $authorized = false;
          /**
           * Verify if this module is enabled and if the cart has
           * a valid customer, delivery address and invoice address
           */
        if (!$this->module->active || $cart->id_customer == 0 || $cart->id_address_delivery == 0
          || $cart->id_address_invoice == 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }
          /**
           * Verify if this payment modules is authorized
           */
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'latpay') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->l('This payment method is not available.'));
        }
        /** @var CustomerCore $customer */
        $customer = new Customer($cart->id_customer);
        /**
         * Check if this is a vlaid customer account
         */
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
          /**
           * Place the order
           */
        if ($_POST) {
            $token         = Tools::getValue("token");
            $transtokenval = Tools::getValue("transtokenval");
            $status        = Tools::getValue("status");
            $amount        = Tools::getValue("amount");
            $currency      = Tools::getValue("currency");
            $merchantid    = Tools::getValue("merchantid");
            $datakey       = Tools::getValue("datakey");
            $description   = Tools::getValue("description");
            $reference     = Tools::getValue("reference");
            $jsonData = array(
              'transtoken'     => $transtokenval,
              'status'         => $status,
              'amount'         => $amount,
              'currency'       => $currency,
              'merchantuserid' => $merchantid,
              'datakey'        => $datakey,
              'description'    => $description,
              'reference'      => $reference
            );
            //Cancel order
            if ($token == "1") {
              // $this->module->validateOrder(
              //         (int) $this->context->cart->id,
              //         Configuration::get('PS_OS_CANCELED'),
              //         (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
              //         $this->module->displayName,
              //         null,
              //         null,
              //         (int) $this->context->currency->id,
              //         false,
              //         $customer->secure_key
              //     );
                $cart_id = (int)$this->context->cart->id;
                $this->context->cart = new Cart($cart_id);
                $duplicated_cart = $this->context->cart->duplicate();
                $this->context->cart = $duplicated_cart['cart'];
                $this->context->cookie->id_cart = (int)$this->context->cart->id;
                Tools::redirect('index.php?controller=order&step=1');
            }
           //Post transtoken values to capture
            if ($transtokenval) {
                $url = 'https://lateralpayments.com/checkout/Checkout/Capture';
                $data_json = json_encode($jsonData);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . Tools::strlen($data_json)));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response  = curl_exec($ch);
                curl_close($ch);
                $jdecode     = json_decode(Tools::stripslashes($response), true);
                $status_code = $jdecode['Capture']['status']['StatusCode'];
                //$errorcode   = $jdecode['Capture']['status']['errorcode'];
                //$statusdesc  = $jdecode['Capture']['status']['errordesc'];
                $cart_id = (int)$this->context->cart->id;
                if ($status_code =='0') {
                    $this->module->validateOrder(
                        (int) $this->context->cart->id,
                        Configuration::get('PS_OS_PAYMENT'),
                        (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
                        $this->module->displayName,
                        null,
                        null,
                        (int) $this->context->currency->id,
                        false,
                        $customer->secure_key
                    );
                    Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
                } elseif ($status_code =='1') {
                    $this->module->validateOrder(
                        (int) $this->context->cart->id,
                        Configuration::get('PS_OS_ERROR'),
                        (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
                        $this->module->displayName,
                        null,
                        null,
                        (int) $this->context->currency->id,
                        false,
                        $customer->secure_key
                    );
                    $this->context->cart = new Cart($cart_id);
                    $duplicated_cart = $this->context->cart->duplicate();
                    $this->context->cart = $duplicated_cart['cart'];
                    $this->context->cookie->id_cart = (int)$this->context->cart->id;
                    Tools::redirect('index.php?controller=order&step=1');
                }
            }
        }
    }
}
