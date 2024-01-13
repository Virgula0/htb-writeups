<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_search
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */
/**
 * @package jDownloads
 * @version 4.0  
 * Some parts from the search component 3.x (and search content plugin) adapted and modified to can use it in jDownloads 4.x as an internal search function. 
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

?>

<dl class="search-results<?php echo $this->pageclass_sfx; ?>">
    <div class="container">
    <?php foreach($this->results as $result) : ?>
	    <dt class="result-title">
		    <?php echo $this->pagination->limitstart + $result->count.'. ';?>
		    <?php if ($result->href) :?>
			    <a href="<?php echo Route::_($result->href); ?>"<?php if ($result->browsernav == 1) :?> target="_blank"<?php endif;?>>
				    <?php // $result->title should not be escaped in this case, as it may ?>
				    <?php // contain span HTML tags wrapping the searched terms, if present ?>
				    <?php // in the title. ?>
				    <?php echo $result->title; ?>
			    </a>
		    <?php else:?>
			    <?php // see above comment: do not escape $result->title ?>
			    <?php echo $result->title; ?>
		    <?php endif; ?>
	    </dt>
	    <?php if ($result->section) : ?>
		    <dd class="result-category">
			    <span class="small<?php echo $this->pageclass_sfx; ?>">
				    (<?php echo $this->escape($result->section); ?>)
			    </span>
		    </dd>
	    <?php endif; ?>
	    <dd class="result-text">
		    <?php echo $result->text; ?>
	    </dd>
	    <?php 
        if ($this->params->get('show_date') == 1) : ?>
		    <dd class="result-created<?php echo $this->pageclass_sfx; ?>"><small>
			    <?php echo Text::sprintf('JGLOBAL_CREATED_DATE_ON', $result->created); ?>
                </small>
		    </dd>
	    <?php endif; ?>
    <?php endforeach; ?>
    </div>
</dl>

<div class="pagination">
	<?php echo $this->pagination->getPagesLinks(); ?>
</div>
