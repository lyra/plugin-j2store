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
 * Renders a documentation link element.
 */
class JFormFieldPayzenDoc extends JFormField
{

    protected $type = 'payzendoc';

    protected function getInput()
    {
        // Get documentation links.
        $docs = '' ;
        $filenames = glob(JPATH_ROOT . '/' . $this->value);

        if (!empty($filenames)) {
            $languages = array(
                'fr' => 'Français',
                'en' => 'English',
                'es' => 'Español',
                'de' => 'Deutsch',
                // Complete when other languages are managed.
            );

            foreach ($filenames as $filename) {
                $base_filename = basename($filename, '.pdf');
                $lang = substr($base_filename, -2); // Extract language code.

                $docs .= ' <a target="_blank" href="' . JURI::root(). 'plugins/j2store/payment_payzen/installation_doc/' . $base_filename . '.pdf" >' . $languages[$lang] . '</a>';
            }
        }

        $html = JText::_($this->description) . $docs;
        return '<div class="control-group"><span style="color: red; font-weight: bold; text-transform: uppercase;">' . $html . '</span></div>';
    }

    public function renderField($options = array())
    {
        return $this->getInput();
    }
}
