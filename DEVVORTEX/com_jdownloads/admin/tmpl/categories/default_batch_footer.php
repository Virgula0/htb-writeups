<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<a class="btn btn-secondary" type="button" onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-access').value='';document.getElementById('batch-language-id').value='';document.getElementById('batch-user-id').value='';document.getElementById('batch-tag-id').value=''" data-bs-dismiss="modal">
    <?php echo Text::_('COM_JDOWNLOADS_CANCEL'); ?>
</a>
<button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('category.batch');">
    <?php echo Text::_('COM_JDOWNLOADS_BATCH_PROCESS'); ?>
</button>