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

if (! class_exists('PayzenTools')) {
    require_once JPath::clean(__DIR__ . '/../../library/sdk-autoload.php');
    JLoader::register('PayzenTools', JPath::clean(__DIR__ . '/../../library/PayzenTools.php'));
}

use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('text');

/**
 * Renders a text element.
 */
class JFormFieldPayzenText extends JFormFieldText
{
    public $type = 'payzentext';

    public function renderField($options = [])
    {
        $plugin_features = PayzenTools::$plugin_features;
        if ($plugin_features['qualif'] && ($this->fieldname === 'key_test')) {
            return '';
        }

        $this->value = JText::_($this->value); // Translate default value.

        return parent::renderField($options);
    }
}
