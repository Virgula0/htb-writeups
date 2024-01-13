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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

HTMLHelper::_('behavior.multiselect');

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts'; 

//Load required asset javascript files
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

$this->hiddenFieldsets  = array();

// Create shortcut to parameters.
$params = $this->state->get('params'); 
 
?>

<form accept-charset="utf-8" action="<?php echo ROUTE::_('index.php?option=com_jdownloads&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="license-form" accept-charset="utf-8" class="form-validate">

    <?php echo LayoutHelper::render('edit.title_license', $this, $basePath); ?>
    
    <div>
        <?php echo HtmlHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>
        <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_JDOWNLOADS_GENERAL')); ?>
        <div class="row">
            <div class="col-lg-9">
                 <?php echo $this->form->getLabel('description'); ?>
                 <div class="clr"></div> 
                 <?php echo $this->form->getInput('description'); ?>
            </div>
            <div class="col-lg-3">
                <div class="bg-white px-3">
                    <?php echo LayoutHelper::render('edit.global', $this, $basePath); ?>
                </div>
            </div>
        </div>
        <?php echo HtmlHelper::_('uitab.endTab'); ?>
        <?php echo HtmlHelper::_('uitab.endTabSet'); ?>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="view" value="license" />         
        <?php echo HtmlHelper::_('form.token'); ?>
    </div>
</form>    
    
