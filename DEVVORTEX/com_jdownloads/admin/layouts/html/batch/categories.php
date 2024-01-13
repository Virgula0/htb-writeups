<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * modified for jDownloads
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

extract($displayData);

// Create the copy/move options.
$options = array(
	HtmlHelper::_('select.option', 'n', Text::_('COM_JDOWNLOADS_NO')),
    HtmlHelper::_('select.option', 'c', Text::_('COM_JDOWNLOADS_BATCH_ONLY_COPY')),
	HtmlHelper::_('select.option', 'm', Text::_('COM_JDOWNLOADS_BATCH_MOVE'))
);
?>
 
<label id="batch-choose-action-lbl" for="batch-choose-action" class="modalTooltip" title="<?php echo HtmlHelper::_('tooltipText', 'COM_JDOWNLOADS_BATCH_CATEGORY_LABEL', 'COM_JDOWNLOADS_BATCH_CATEGORY_LABEL_DESC'); ?>">
    <?php echo Text::_('COM_JDOWNLOADS_BATCH_CATEGORY_LABEL'); ?>
</label>

<div id="batch-choose-action" class="control-group">
        <?php echo HtmlHelper::_('select.genericlist', $displayData, 'batch[category_id]', 'name="batch[category_id]" class="inputbox"', 'value', 'text'); ?>
</div>

<div id="batch-copy-move-jd" class="control-group radio">
	<?php 
    echo Text::_('JLIB_HTML_BATCH_MOVE_QUESTION'); ?>
	<?php 
    echo HtmlHelper::_('select.radiolist', $options, 'batch[move_copy]', '', 'value', 'text', 'n'); ?>
</div>
