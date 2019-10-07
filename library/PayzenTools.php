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

use Joomla\CMS\Log\Log;

class PayzenTools
{
    /**
     * Features.
     */
    static $plugin_features = array(
        'qualif' => false,
        'prodfaq' => true,
        'shatwo' => true,
        'shatwoonly' => false
    );

    /**
     * Method to initiate the logger.
     */
     public static function initLog() {
         JLog::addLogger(
             array(
                 'text_file' => 'payment_payzen-' . date('Y-m') . '.log.php', // Sets file name.
                 'text_entry_format' => '{DATETIME} {CATEGORY} {MESSAGE}' // Sets the format of each line.
             ),
             JLog::ALL, // Sets messages of all log levels to be sent to the file.
             array('J2STORE_PAYMENT_PAYZEN')
         );
     }

    /**
     * Write log messages.
     *
     * @param string $message
     */
     public static function log($message) {
         try {
             JLog::add($message, JLog::INFO, 'J2STORE_PAYMENT_PAYZEN');
         } catch (\Exception $e){
             Log::add('JLog::add() can\'t write to the log file.', Log::ERROR, 'J2STORE_PAYMENT_PAYZEN');
         }
     }
}
