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

if (!defined('_PS_VERSION_')) {
    exit;
}
class Latpay extends PaymentModule
{
    private $html = '';
    private $postErrors = array();
    public $address;
    const LATPAY_CHECKOUT_URL = 'http://martfury.latdev.latpay.com.au/wp-content/plugins/hps/js/Latpayjs.js';
    /**
     * latpay constructor.
     *
     * Set the information about this module
     */
    public function __construct()
    {
        $this->name                   = 'latpay';
        $this->tab                    = 'payments_gateways';
        $this->version                = '2.0.0';
        $this->author                 = 'Latpay Team';
        $this->controllers            = array('payment', 'validation');
        $this->currencies             = true;
        $this->currencies_mode        = 'checkbox';
        $this->bootstrap              = true;
        $this->module_key             = 'f6ecded2da654b506311e449e8472f22';
        $this->displayName            = 'Latpay';
        $this->description            = 'LPS Payment Gateway (HPS) prestashop Payment Gateway module.';
        $this->confirmUninstall       = 'Are you sure you want to uninstall this module?';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        parent::__construct();
    }
    /**
     * Install this module and register the following Hooks:
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
        && $this->registerHook('header')
        && $this->registerHook('paymentOptions')
        && $this->registerHook('paymentReturn');
    }
    /**
     * Uninstall this module and remove it from all hooks
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }
    /**
     * Returns a string containing the HTML necessary to
     * generate a configuration screen on the admin
     *
     * @return string
     */
    public function getContent()
    {
         /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitLatpay_hpsModule')) == true) {
            $this->postProcess();
        }
        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output.$this->renderForm();
    }
    public function hookHeader()
    {
        $this->context->controller->registerJavascript(
            'jquery-local-script',
            'modules/' . $this->name . './views/js/jquery-3.4.0.min.js',
            ['position' => 'head', 'priority' => 9]
        );
        $this->context->controller->registerJavascript(
            'remote-latpay-checkout',
            self::LATPAY_CHECKOUT_URL,
            ['server' => 'remote', 'position' => 'head', 'priority' => 10]
        );
    }
     /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLatpay_hpsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));
    }
    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Latpay Account Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'LATPAY_HPS_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'desc' => $this->l('Enter Merchant ID'),
                        'name' => 'LATPAY_HPS_ACCOUNT_MERCHANT_ID',
                        'label' => $this->l('Merchant ID'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'desc' => $this->l('Enter Merchant Password'),
                        'name' => 'LATPAY_HPS_ACCOUNT_MERCHANT_PASSWORD',
                        'label' => $this->l('Merchant Password'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'desc' => $this->l('Enter Secret Key'),
                        'name' => 'LATPAY_HPS_ACCOUNT_SECRET_KEY',
                        'label' => $this->l('Secret Key'),
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }
    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'LATPAY_HPS_LIVE_MODE' => Configuration::get('LATPAY_HPS_LIVE_MODE', true),
            'LATPAY_HPS_ACCOUNT_MERCHANT_ID' => Configuration::get('LATPAY_HPS_ACCOUNT_MERCHANT_ID', true),
            'LATPAY_HPS_ACCOUNT_MERCHANT_PASSWORD' => Configuration::get('LATPAY_HPS_ACCOUNT_MERCHANT_PASSWORD', true),
            'LATPAY_HPS_ACCOUNT_SECRET_KEY' => Configuration::get('LATPAY_HPS_ACCOUNT_SECRET_KEY', true),
        );
    }
    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }
    /**
     * Display this module as a payment option during the checkout
     *
     * @param array $params
     * @return array|void
     */
    public function hookPaymentOptions($params)
    {
        /*
         * Verify if this module is active
         */
        if (!$this->active) {
            return;
        }
        $formAction = $this->context->link->getModuleLink($this->name, 'validation', array(), true);
        $inputs = $this->payInput();
        $this->smarty->assign(['action' => $formAction]);
        // $paymentForm = $this->fetch('module:latpay/views/templates/hook/payment_options.tpl');
        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->displayName)
            ->setCallToActionText($this->displayName)
            ->setAction($formAction)
            ->setInputs($inputs);
        return [$newOption];
    }
    protected function payInput()
    {
        $cart = $this->context->cart;
        $merchant_id = Configuration::get('LATPAY_HPS_ACCOUNT_MERCHANT_ID');
        $merchant_pw = Configuration::get('LATPAY_HPS_ACCOUNT_MERCHANT_PASSWORD');
        $Secret_Key = Configuration::get('LATPAY_HPS_ACCOUNT_SECRET_KEY');
        $customer = new Customer($cart->id_customer);
        $address = new Address($cart->id_address_invoice);
        $state = new State($address->id_state);
        $country = new Country($address->id_country);
        $firstName = $address->firstname;
        $lastName = $address->lastname;
        $zipcode = $address->postcode;
        $email = $customer->email;
        $phone = $address->phone;
        $city = $address->city;
        $id_currency = Configuration::get('PS_CURRENCY_DEFAULT');
        $currency = new Currency($id_currency);
        $currency_code =$currency->iso_code;
        $orderAmount =number_format(Tools::convertPrice($cart->getOrderTotal(), $currency), 2, '.', '');
        $return_url = $this->context->link->getModuleLink($this->name, 'validation', array(), true);
        $orderId = $cart->id;
        $productInfo = "Product Information";
        $purl = $return_url;
        $nurl = $return_url;
        $curl = $return_url;
        $str =sha1($currency_code.$orderAmount.$orderId.$Secret_Key);
        $customer_ipaddress = $_SERVER['REMOTE_ADDR'];
        $values  = array(
            'Merchant_User_Id'=> $merchant_id,
            'merchantpwd'=> $merchant_pw,
            'currencydesc'=> $currency_code,
            'merchant_ref_number'=> $orderId,
            'Purchase_summary'=> $orderId,
            'customer_ipaddress'=> $customer_ipaddress,
            'amount'=> $orderAmount,
            'productinfo'=> $productInfo,
            'customer_firstname'=> $firstName,
            'customer_lastname'=> $lastName,
            'customer_phone'=> $phone,
            'customer_email'=> $email,
            'bill_firstname'=> $firstName,
            'bill_lastname'=> $lastName,
            'bill_address1'=> $address->address1,
            'bill_city'=> $city,
            'bill_state'=> $state,
            'bill_country'=> $country->iso_code,
            'bill_zip'=> $zipcode,
            'transactionkey'=> $str,
            'processurl'=> $purl,
            'notifyurl'=> $nurl,
            'cancelurl'=> $curl,
        );
        $inputs = array();
        foreach ($values as $k => $v) {
            $inputs[$k] = array(
                'name' => $k,
                'type' => 'hidden',
                'value' => $v,
            );
        }
        return $inputs;
    }
    public function hookPaymentReturn($params)
    {
        /**
         * Verify if this module is enabled
         */
        if (!$this->active) {
            return;
        }
        return $this->fetch('module:latpay/views/templates/hook/payment_return.tpl');
    }
}
