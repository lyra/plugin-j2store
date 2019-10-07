<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for J2Store. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_j2store/library/plugins/payment.php';
require_once JPATH_ADMINISTRATOR . '/components/com_j2store/helpers/j2store.php';

require_once JPath::clean(dirname(__FILE__) . '/library/PayzenApi.php');
require_once JPath::clean(dirname(__FILE__) . '/library/PayzenTools.php');

class plgJ2StorePayment_payzen extends J2StorePaymentPlugin
{

    /**
     * @var $_element string Should always correspond with the plugin's filename, forcing it to be unique.
     */
    public $_element = 'payment_payzen';

    /**
     * Class constructor.
     *
     * @param string $subject
     * @param string $config
     */
    function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $this->loadLanguage('', JPATH_ADMINISTRATOR);
        PayzenTools::initLog();
    }

    function onJ2StoreGetPaymentOptions($element, $order)
    {
        $result = parent::onJ2StoreGetPaymentOptions($element, $order);
        if (! $result) {
            return $result;
        }

        $currency = J2Store::currency(); // Current store currency.
        if (! PayzenApi::findCurrencyByAlphaCode($currency->getCode())) {
            return false;
        }

        $amount = $order->order_total; // Order total in default currency.
        if (! empty($this->params->get('min_amount')) && ($amount < $this->params->get('min_amount'))) {
            return false;
        }

        if (! empty($this->params->get('max_amount')) && ($amount > $this->params->get('max_amount'))) {
            return false;
        }

        return true;
    }

    /**
     * Prepare redirect form to payment gateway.
     *
     * @param array $data
     * @return string|void
     */
    function _prePayment($data)
    {
        F0FTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_j2store/tables');
        $order = F0FTable::getInstance('Order', 'J2StoreTable');
        $order->load($data['orderpayment_id']);

        PayzenTools::log('Generating payment form for order #' . $order->order_id);

        $data = array();

        // Set config parameters.
        $param_names = array(
            'site_id', 'key_test', 'key_prod', 'ctx_mode',
            'available_languages', 'capture_delay', 'validation_mode', 'payment_cards',
            'redirect_enabled', 'redirect_success_timeout', 'redirect_success_message',
            'redirect_error_timeout', 'redirect_error_message', 'return_mode', 'sign_algo'
        );

        foreach ($param_names as $name) {
            $value = $this->params->get($name);
            if (is_array($value)) {
                $value = implode(';', $value);
            }

            $data[$name] = $value;
        }

        // Set return URL.
        $url_return = JROUTE::_(JURI::root() . 'index.php?option=com_j2store&view=checkout&task=confirmPayment&orderpayment_type='.$this->_element);
        $data['url_return'] = $url_return;

        // Contrib param.
        $data['contrib'] = 'J2Store_3.x_1.0.0/' . JVERSION . '_' . J2STORE_VERSION . '/' . PHP_VERSION;

        // Set the language code.
        $lang = JFactory::getLanguage();
        $tag = substr($lang->get('tag'), 0, 2);
        $language = PayzenApi::isSupportedLanguage($tag) ? $tag : $this->params->get('language');
        $data['language'] = $language;

        // Set currency.
        $currency_values = $this->getCurrency($order);
        $currency = PayzenApi::findCurrencyByAlphaCode($currency_values['currency_code']);
        if (! $currency) {
            PayzenTools::log('Could not find currency numeric code for currency : ' . $currency_values['currency_code']);
            return false;
        }

        $data['currency'] = $currency->getNum();

        // Set customer info.
        $data['cust_email'] = $order->user_email;
        $data['cust_id'] = $order->user_id;

        $order_info = $order->getOrderInformation();

        $data['cust_first_name'] = $order_info->billing_first_name;
        $data['cust_last_name'] = $order_info->billing_last_name;
        $data['cust_address'] = $order_info->billing_address_1 . ' ' . $order_info->billing_address_2;
        $data['cust_zip'] = $order_info->billing_zip;
        $data['cust_city'] = $order_info->billing_city;
        $data['cust_state'] = $this->getZoneById($order_info->billing_zone_id)->zone_code ;
        $data['cust_country'] = $this->getCountryById($order_info->billing_country_id)->country_isocode_2;
        $data['cust_phone'] = $order_info->billing_phone_1;
        $data['cust_cell_phone'] = $order_info->billing_phone_2;

        $data['ship_to_first_name'] = $order_info->shipping_first_name;
        $data['ship_to_last_name'] = $order_info->shipping_last_name;
        $data['ship_to_city'] = $order_info->shipping_city;
        $data['ship_to_street'] = $order_info->shipping_address_1;
        $data['ship_to_street2'] = $order_info->shipping_address_2;
        $data['ship_to_state'] = $this->getZoneById($order_info->shipping_zone_id)->zone_code ;
        $data['ship_to_country'] = $this->getCountryById($order_info->shipping_country_id)->country_isocode_2;
        $data['ship_to_phone_num'] = $order_info->shipping_phone_1 ? $order_info->shipping_phone_1 : $order_info->shipping_phone_2;
        $data['ship_to_zip'] = $order_info->shipping_zip;

        // Set order ID.
        $data['order_id'] = $order->order_id;

        // Set the amount to pay.
        $amount = J2Store::currency()->format(
            $order->order_total,
            $currency_values['currency_code'],
            $currency_values['currency_value'],
            false
        );
        $data['amount'] = $currency->convertAmountToInteger($amount);

        // 3DS activation according to amount.
        $threeds_mpi = null;
        if ($this->params->get('threeds_min_amount') && ($order->order_total < $this->params->get('threeds_min_amount'))) {
            $threeds_mpi = '2';
        }

        $data['threeds_mpi'] = $threeds_mpi;

        require_once JPath::clean(dirname(__FILE__) . '/library/PayzenRequest.php');
        $request = new PayzenRequest();
        $request->setFromArray($data);

        PayzenTools::log('Data to be sent to payment gateway : ' . print_r($request->getRequestFieldsArray(true /* To hide sensitive data. */), true));

        // Prepare the payment form.
        $vars = new JObject();
        $vars->action = $this->params->get('platform_url');
        $vars->fields = $request->getRequestHtmlFields();

        // Let's check the values submitted.
        $html = $this->_getLayout('prepayment', $vars);
        return $html;
    }

    /**
     * Processes the payment form and returns HTML to be displayed to the user
     * generally with a success/failed message.
     *
     * @param array $data
     * @return string|void
     */
    function _postPayment($data)
    {
        $app = JFactory::getApplication();
        $data = $app->input->getArray($_REQUEST);

        require_once JPath::clean(dirname(__FILE__) . '/library/PayzenResponse.php');
        $response = new PayzenResponse(
            $data,
            $this->params->get('ctx_mode'),
            $this->params->get('key_test'),
            $this->params->get('key_prod'),
            $this->params->get('sign_algo')
        );

        $vars = new JObject();

        $from_server = ($response->get('hash') != null);

        if (! $response->isAuthentified()) {
            PayzenTools::log('Authentication failed: received invalid response with parameters: ' . print_r($data, true));
            PayzenTools::log('Signature algorithm selected in module settings must be the same as one selected in gateway Back Office.');

            if ($from_server) {
                PayzenTools::log('IPN URL PROCESS END.');
                echo($response->getOutputForPlatform('auth_fail'));
                $app->close();
            } else {
                PayzenTools::log('RETURN URL PROCESS END.');
                $vars->message = JText::_('J2STORE_PAYZEN_ERROR_MSG');
                return $this->_getLayout('postpayment', $vars);
            }
        }

        // Retrieve order info from database.
        F0FTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_j2store/tables');
        $order = F0FTable::getInstance('Order', 'J2StoreTable');
        $order->load(array('order_id' => $response->get('order_id')));

        if (empty($order->order_id)) {
            // Order not found.
            PayzenTools::log("Error: order #{$response->get('order_id')} not found in database.");

            if ($from_server) {
                PayzenTools::log('IPN URL PROCESS END.');
                echo($response->getOutputForPlatform('order_not_found'));
                $app->close();
            } else {
                PayzenTools::log('RETURN URL PROCESS END.');
                $vars->message = JText::_('J2STORE_PAYZEN_ERROR_MSG');
                return $this->_getLayout('postpayment', $vars);
            }
        }

        $payzen_plugin_features = PayzenTools::$plugin_features;
        if (($this->params->get('ctx_mode') === 'TEST')  && $payzen_plugin_features['prodfaq']) {
            $app->enqueueMessage(JText::_('J2STORE_PAYZEN_SHOP_TO_PROD_INFO'), 'notice');
        }

        // Process according to order status and payment result.
        if (in_array($order->order_state_id, array(4, 5))) {
            // Order not processed yet.

            $order->transaction_id = $response->get('trans_id');
            $order->transaction_details = $this->_formatTransactionDetails($data);
            $order->transaction_status = $response->getTransStatus();

            $new_order_state_id = $this->_newOrderState($response);

            // Case of pending payments.
            if (($order->order_state_id == 4) && ($order->order_state_id == $new_order_state_id)) {
                PayzenTools::log("Payment successful confirmed for order #{$response->get('order_id')}.");

                if ($from_server) {
                    PayzenTools::log('IPN URL PROCESS END.');
                    echo($response->getOutputForPlatform('payment_ok_already_done'));
                    $app->close();
                } else {
                    PayzenTools::log('RETURN URL PROCESS END.');
                    $vars->message = JText::_('J2STORE_PAYZEN_SUCCESS_MSG');
                    $html = $this->_getLayout('postpayment', $vars);
                    $html .= $this->_displayArticle();
                    return $html;
                }
            }

            if ($new_order_state_id == 1) {
                // Payment complete.
                $order->payment_complete();
            } else {
                $order->update_status($new_order_state_id);

                if ($new_order_state_id == 4) {
                    // Reduce stock if pending payment.
                    $order->reduce_order_stock();
                }
            }

            if (! $order->store()) {
                $vars->message = JText::_('J2STORE_PAYZEN_ERROR_MSG');
                return $this->_getLayout('postpayment', $vars);
            }

            if ($response->isAcceptedPayment()) {
                PayzenTools::log("Payment successful confirmed for order #{$response->get('order_id')}.");

                $order->empty_cart();

                if ($from_server) {
                    PayzenTools::log('IPN URL PROCESS END.');
                    echo($response->getOutputForPlatform('payment_ok'));
                    $app->close();
                } else {
                    if ($this->params->get('ctx_mode') === 'TEST') {
                        // TEST mode warning.

                        if (JFactory::getConfig()->get('offline') == 1) {
                            // Maintenance mode, check URL cannot work.
                            $app->enqueueMessage(JText::_('J2STORE_PAYZEN_MAINTENANCE_MODE'));
                        } else {
                            // IPN URL not correctly called.
                            $app->enqueueMessage(
                                JText::_('J2STORE_PAYZEN_CHECK_URL_WARN') . '<br />' . JText::_('J2STORE_PAYZEN_CHECK_URL_WARN_DETAILS'),
                                'warning'
                            );
                        }
                    }

                    PayzenTools::log('RETURN URL PROCESS END.');
                    $vars->message = JText::_('J2STORE_PAYZEN_SUCCESS_MSG');
                    $html = $this->_getLayout('postpayment', $vars);
                    $html .= $this->_displayArticle();
                    return $html;
                }
            } else {
                PayzenTools::log("Payment failed or cancelled confirmed for order #{$response->get('order_id')}.");

                if ($from_server) {
                    PayzenTools::log('IPN URL PROCESS END.');
                    echo($response->getOutputForPlatform('payment_ko'));
                    $app->close();
                } else {
                    $vars->message = JText::_('J2STORE_PAYZEN_FAILURE_MSG');
                    return $this->_getLayout('postpayment', $vars);
                }
            }
        } else {
            // Order already processed.
            PayzenTools::log("Order #{$response->get('order_id')} is already saved.");

            $expected_order_state_id = $this->_newOrderState($response);

            if ($expected_order_state_id != $order->order_state_id) {
                PayzenTools::log("Error! Invalid payment result received for already saved order #{$response->get('order_id')}." .
                    "Payment result : {$response->getTransStatus()}, Order status : {$order->order_state}.");

                if ($from_server) {
                    PayzenTools::log('IPN URL PROCESS END.');
                    echo($response->getOutputForPlatform('payment_ko_on_order_ok'));
                    $app->close();
                } else {
                    PayzenTools::log('RETURN URL PROCESS END.');
                    $vars->message = JText::_('J2STORE_PAYZEN_ERROR_MSG');
                    return $this->_getLayout('postpayment', $vars);
                }
            } elseif ($response->isAcceptedPayment()) {
                PayzenTools::log("Payment successful confirmed for order #{$response->get('order_id')}.");

                if ($from_server) {
                    PayzenTools::log('IPN URL PROCESS END.');
                    echo($response->getOutputForPlatform('payment_ok_already_done'));
                    $app->close();
                } else {
                    PayzenTools::log('RETURN URL PROCESS END.');
                    $vars->message = JText::_('J2STORE_PAYZEN_SUCCESS_MSG');
                    $html = $this->_getLayout('postpayment', $vars);
                    $html .= $this->_displayArticle();
                    return $html;
                }
            } else {
                PayzenTools::log("Payment failed or cancelled confirmed for order #{$response->get('order_id')}.");

                if ($from_server) {
                    PayzenTools::log('IPN URL PROCESS END.');
                    echo($response->getOutputForPlatform('payment_ko_already_done'));
                    $app->close();
                } else {
                    PayzenTools::log('RETURN URL PROCESS END.');
                    $vars->message = JText::_('J2STORE_PAYZEN_FAILURE_MSG');
                    return $this->_getLayout('postpayment', $vars);
                }
            }
        }
    }

    /**
     * Format the transaction details received from the payment platform.
     *
     * @param array $data
     * @return string
     */
    private function _formatTransactionDetails($data)
    {
        $separator = ' | ';
        $formatted = array();

        foreach ($data as $key => $value) {
            if (($key !== 'signature') && (strpos($key, 'vads_') !== 0)) {
                continue;
            }

            $formatted[] = $key . ' = ' . $value;
        }

        return count($formatted) ? implode($separator, $formatted) : '';
    }

    private function _newOrderState($response)
    {
        if ($response->isPendingPayment()) {
            return 4;
        } elseif ($response->isAcceptedPayment()) {
            return 1;
        } elseif ($response->isCancelledPayment()) {
            return 6;
        } else {
            return 3;
        }
    }
}
