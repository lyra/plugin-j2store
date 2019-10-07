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

class plgJ2StorePayment_payzenInstallerScript
{
    function preflight($type, $parent)
    {
        $xml_file = JPATH_ADMINISTRATOR . '/components/com_j2store/com_j2store.xml';
        $xml = JFactory::getXML($xml_file);
        $version = (string)$xml->version;

        // Check for minimum requirement.
        if (version_compare($version, '3.0.0', 'lt')) {
            Jerror::raiseWarning(null, 'You are using an old version of J2Store. Please upgrade to the latest version.');
            return false;
        }
    }
}
