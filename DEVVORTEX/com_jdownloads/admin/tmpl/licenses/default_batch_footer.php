<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>

<a class="btn btn-secondary" type="button" onclick="document.getElementById('batch-license-id').value='';document.getElementById('batch-language-id').value=''" data-bs-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</a>
<button class="btn btn-success" type="submit" onclick="Joomla.submitbutton('license.batch');">
	<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>