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

if (! class_exists('Lyranetwork\Payzen\Sdk\Form\Api')) {
    require_once JPath::clean(__DIR__ . '/../../library/sdk-autoload.php');
}

use Joomla\CMS\Form\FormHelper;
use Lyranetwork\Payzen\Sdk\Form\Api as PayzenApi;

FormHelper::loadFieldClass('filelist');

/**
 * Renders an item select element (with multiple choice possibility).
 */
class JFormFieldPayzenList extends JFormFieldList
{
    protected $type = 'payzenlist';

    public function getOptions()
    {
        $payzen_options = [];

        if ($this->fieldname === 'payment_cards') {
            $payzen_options = PayzenApi::getSupportedCardTypes();
        } else {
            foreach (PayzenApi::getSupportedLanguages() as $code => $lang) {
                $payzen_options[$code] = 'J2STORE_PAYZEN_' . strtoupper($lang);
            }
        }

        // Construct an array of HTML option tags.
        $options = [];
        foreach ($payzen_options as $key => $value) {
            $options[] = JHTML::_('select.option', $key, JText::_($value));
        }

        return array_merge(parent::getOptions(), $options);
    }
}
