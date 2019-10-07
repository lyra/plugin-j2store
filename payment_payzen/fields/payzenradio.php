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

JFormHelper::loadFieldClass('radio');

/**
 * Renders a radio element.
 */
class JFormFieldPayzenRadio extends JFormFieldRadio
{

    var $type = 'payzenradio';

    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        if (! class_exists('PayzenTools')) {
            require_once(JPATH_PLUGINS . DS . 'j2store' . DS . 'payment_payzen' . DS . 'library' . DS . 'PayzenTools.php');
        }

        $plugin_features = PayzenTools::$plugin_features;

        if ($plugin_features['qualif'] && ($this->fieldname === 'ctx_mode')) {
            foreach ($data['options'] as $key => $value) {
                if ($value->value === 'TEST') {
                    unset($data['options'][$key]);
                }
            }
        }

        if ($plugin_features['shatwo'] && ($this->fieldname === 'sign_algo')) {
            $data['description'] = preg_replace('#<br /><b>[^<>]+</b>#', '', $data['description']);

            if ($plugin_features['shatwoonly'] && ($this->fieldname === 'sign_algo')) {
                foreach ($data['options'] as $key => $value) {
                    if ($value->value !== 'SHA-256') {
                        unset($data['options'][$key]);
                    }
                }
            }
        }

        return $data;
    }
}
