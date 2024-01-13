<?php

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

$published = $this->state->get('filter.published');

?>

<div class="row">
    <div class="p-3">
        <div class="row">
	        <div class="form-group col-md-6">
		        <div class="controls">
                <?php echo LayoutHelper::render('joomla.html.batch.language', array()); ?>
		        </div>
	        </div>
        </div>    
    </div>    
</div>