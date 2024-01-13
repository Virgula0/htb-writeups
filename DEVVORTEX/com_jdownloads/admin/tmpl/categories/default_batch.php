<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2021 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$published = (int) $this->state->get('filter.published');
$basePath  = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';

?> 

<div class="p-3">
    <div class="row">
        <div class="alert alert-info">
            <ul>
                <?php echo Text::_('COM_JDOWNLOADS_BATCH_FOLDER_NOTE'); ?>
                <?php echo Text::_('COM_JDOWNLOADS_BATCH_DESC'); ?>
            </ul>
        </div>
    </div>
    
    <div class="row">
        <div class="form-group col-md-6">
            <div class="controls">
                <?php echo LayoutHelper::render('joomla.html.batch.language', array()); ?>
            </div>
        </div>
        <div class="form-group col-md-6">
            <div class="controls">
                <?php echo LayoutHelper::render('joomla.html.batch.access', array()); ?>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if ($published >= 0) : ?>
            <div class="form-group col-md-6">
                <div class="controls">
                  <?php // display category list box ?>
                  <?php echo LayoutHelper::render('html.batch.categories', $this->categories, $basePath); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-group col-md-6">
            <div class="controls">
                <?php echo LayoutHelper::render('joomla.html.batch.tag', array()); ?>
            </div>
        </div>
    </div>
</div>