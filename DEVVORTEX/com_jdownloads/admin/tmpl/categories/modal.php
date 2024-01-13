<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_jdownloads
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

require_once JPATH_ROOT . '/components/com_jdownloads/src/Helper/RouteHelper.php';

$app = Factory::getApplication();

if ($app->isClient('site'))
{
    Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
}

HTMLHelper::_('behavior.core');

$function  = $app->input->getCmd('function', 'jSelectCategory');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

// Path to the layouts folder 
$basePath = JPATH_ROOT.'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

?>
<div class="container-popup">

	<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=categories&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1'); ?>" method="post" name="adminForm" id="adminForm">

		<?php echo LayoutHelper::render('searchtools.default', array('view' => $this), $basePath, $options); ?>

		<?php if (empty($this->items)) : ?>
			<div class="alert alert-info">
				<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
		<?php else : ?>
			<table class="table" id="categoryList">
                <caption class="visually-hidden">
                    <?php echo Text::_('COM_JDOWNLOADS_TABLE_CAPTION_CATS'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                </caption>
				<thead>
					<tr>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_STATUS', 'a.published', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_TITLE', 'a.title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-15 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language_title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-1 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
				</thead>
				<tbody>
                    <?php
                    $iconStates = array(
                        0  => 'icon-times',
                        1  => 'icon-check',
                    );
                    ?>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php if ($item->language && Multilanguage::isEnabled())
                        {
                            $tag = strlen($item->language);
                            if ($tag == 5)
                            {
                                $lang = substr($item->language, 0, 2);
                            }
                            elseif ($tag == 6)
                            {
                                $lang = substr($item->language, 0, 3);
                            }
                            else
                            {
                                $lang = '';
                            }
                        }
                        elseif (!Multilanguage::isEnabled())
                        {
                            $lang = '';
                        }
                        ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <span class="tbody-icon">
                                    <span class="<?php echo $iconStates[$this->escape($item->published)]; ?>" aria-hidden="true"></span>
                                </span>
                            </td>
                            <th scope="row">
                                <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                                <a href="javascript:void(0)" onclick="if (window.parent) window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>', null, '<?php echo $this->escape(RouteHelper::getCategoryRoute($item->id, $item->language)); ?>', '<?php echo $this->escape($lang); ?>', null);">
                                    <?php echo $this->escape($item->title); ?></a>
                                <span class="small" title="<?php echo $this->escape($item->cat_dir); ?>">
                                    <?php if (empty($item->note)) : ?>
                                        <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                    <?php else : ?>
                                        <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->note)); ?>
                                    <?php endif; ?>
                                </div>
                            </th>
                            <td class="small d-none d-md-table-cell">
                                <?php echo $this->escape($item->access_level); ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php echo (int) $item->id; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php // load the pagination. ?>
            <?php echo $this->pagination->getListFooter(); ?>

        <?php endif; ?>

		<input type="hidden" name="task" value="">
		<input type="hidden" name="boxchecked" value="0">
		<input type="hidden" name="forcedLanguage" value="<?php echo $app->input->get('forcedLanguage', '', 'CMD'); ?>">
		<?php echo HtmlHelper::_('form.token'); ?>

	</form>
</div>
