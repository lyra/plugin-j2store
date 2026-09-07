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

use Joomla\CMS\Form\FormField;
use Lyranetwork\Payzen\Sdk\Form\Api as PayzenApi;

/**
 * Renders a documentation link element.
 */
class JFormFieldPayzenDoc extends FormField
{
    protected $type = 'payzendoc';

    protected function getInput()
    {
        // Get documentation links.
        $docs = '' ;

        $languages = [
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch',
            'pt' => 'Português'
            // Complete when other languages are managed.
        ];

        foreach (PayzenApi::getOnlineDocUri() as $lang => $docUri) {
            $label = $languages[$lang] ?? strtoupper((string) $lang);
            $docs .= '<a style="margin-left: 10px; text-decoration: none; text-transform: uppercase; font-weight: bold;" href="' . $docUri . 'j2store/sitemap.html" target="_blank">' . $label . '</a>';
        }

        $html = JText::_($this->description) . $docs;

        return '<div class="control-group"><span>' . $html . '</span></div>';
    }

    public function renderField($options = [])
    {
        return $this->getInput();
    }
}
