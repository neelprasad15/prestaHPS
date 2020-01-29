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
}
