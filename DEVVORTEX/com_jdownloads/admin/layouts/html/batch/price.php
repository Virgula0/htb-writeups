<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
                                                                              
?>

    <label id="batch-price-lbl" for="batch-price" class="">
        <?php echo Text::_('COM_JDOWNLOADS_BATCH_PRICE_LABEL'); ?>
    </label>
    <input id="batch_price" name="batch[price]" class="form-control" type="text">

<div id="batch-price" class="hide-aware-inline-help">
    <small class="form-text"><?php echo Text::_('COM_JDOWNLOADS_BATCH_PRICE_DESC'); ?></small>
</div>
