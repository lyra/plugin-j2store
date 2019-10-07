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

jimport('joomla.form.formfield');

/**
 * Renders a label element.
 */
class JFormFieldPayzenLabel extends JFormField
{

    protected $type = 'payzenlabel';

    public function getInput()
    {
        $element = $this->element;
        if (isset($element['url']) && (bool) $element['url']) {
            $this->value = JURI::root() . $this->value;
        }

        return '<label style="font-weight: bold;">' . $this->value . '</label>';
    }
}
