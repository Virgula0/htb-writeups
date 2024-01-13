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
use Joomla\CMS\Factory;

extract($displayData);

// Create the copy/move options.
$options = array(
	HtmlHelper::_('select.option', 'n', Text::_('COM_JDOWNLOADS_NONE')),
    HtmlHelper::_('select.option', 'c', Text::_('COM_JDOWNLOADS_BATCH_COPY')),
    HtmlHelper::_('select.option', 'cc', Text::_('COM_JDOWNLOADS_BATCH_COPY_WITH_FILES')),
    HtmlHelper::_('select.option', 'ca', Text::_('COM_JDOWNLOADS_BATCH_COPY_FILE_ASSIGNED_FROM_SOURCE')),
	HtmlHelper::_('select.option', 'm', Text::_('COM_JDOWNLOADS_BATCH_MOVE'))
);

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('joomla.batch-copymove');

?>
 
<label id="batch-choose-action-lbl" for="batch-category-id">
    <?php echo Text::_('COM_JDOWNLOADS_BATCH_CATEGORY_LABEL'); ?>
</label>
<div id="batch-choose-action" class="control-group">
        <?php echo HtmlHelper::_('select.genericlist', $displayData, 'batch[category_id]', 'name="batch[category_id]" class="form-select"', 'value', 'text', null, 'batch-category-id'); ?>
</div>

<label id="batch-copy-move-jd-lbl" for="batch-copy-move-jd">
    <?php echo Text::_('JLIB_HTML_BATCH_MOVE_QUESTION'); ?>
</label>

<div id="batch-copy-move-jd" class="control-group radio">
	<?php echo HtmlHelper::_('select.radiolist', $options, 'batch[move_copy]', '', 'value', 'text', 'n'); ?>
</div>

<div id="batch-choose" class="hide-aware-inline-help">
    <small class="form-text"><?php echo Text::_('COM_JDOWNLOADS_BATCH_CATEGORY_LABEL_DESC'); ?></small>
</div>
