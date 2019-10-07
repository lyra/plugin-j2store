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
?>

<div class="note">
    <?php if ($logo = $this->params->get('display_image', null)) : ?>
        <span class="j2store-payment-image">
            <img class="payment-plugin-image payment-payzen" src="<?php echo JUri::root() . JPath::clean($logo); ?>" alt="PayZen">
        </span>
    <?php endif; ?>

    <?php echo JText::_($this->params->get('display_name', 'J2STORE_PAYZEN_PAYMENT_NAME_DEFAULT')); ?>
    <br />
</div>

<form action="<?php echo $vars->action; ?>" method="post" id="payzen_form" name="payzen_form">
    <input type="submit" id="payzen_submit_button" class="button btn btn-primary" value="<?php echo JText::_('J2STORE_PLACE_ORDER'); ?>">

    <?php echo $vars->fields; ?>
</form>