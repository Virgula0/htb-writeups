<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2022 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Plugin\PluginHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

$app = Factory::getApplication();

if ($app->isClient('site')){
    Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
}

// Get jD Editor Button Plugin params
$plugin = PluginHelper::getPlugin('editors-xtd', 'jdownloads');

// Check if plugin is enabled
if ($plugin){
    // Get plugin param
    $pluginParams = new Registry($plugin->params);
}

// Load scripte
$wa = $this->document->getWebAssetManager();
$wa->useScript('core')
    ->useScript('multiselect')
    ->useScript('com_jdownloads.admin-downloads-modal');

// Load jD language
Factory::getLanguage()->load('com_jdownloads', JPATH_ADMINISTRATOR);

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

$function  = $app->input->getCmd('function', 'jSelectDownload');
$editor    = $app->input->getCmd('editor', '');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$onclick   = $this->escape($function);
$multilang = Multilanguage::isEnabled();

if (!empty($editor))
{
    $this->document->addScriptOptions('xtd-downloads', array('editor' => $editor));
    $onclick = "jSelectDownload";
}

?>

<div class="container-popup">

    <form action="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads&layout=modal&tmpl=component&function='.$function.'&'.Session::getFormToken().'=1&editor=' . $editor); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">
	
    <?php if ($function == 'jSelectArticle_jform_other_file_id'):?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_ONLY_WITH_FILES'); ?>
        </div>
    <?php endif; ?>    
	
    <?php 
    echo LayoutHelper::render('searchtools.default', array('view' => $this), $basePath, $options); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_JDOWNLOADS_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <table class="table table-sm">
                <caption class="visually-hidden">
                    <?php echo Text::_('COM_JDOWNLOADS_TABLE_CAPTION_DOWNLOADS'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                </caption>
                <thead>
                    <tr>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_STATUS', 'a.published', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="title">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_TITLE', 'a.title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-5 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_RELEASE', 'a.release', $listDirn, $listOrder ); ?>
                        </th>                        
                        <th scope="col" class="w-5 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_DESCRIPTION', 'a.description', $listDirn, $listOrder ); ?>
                        </th> 
                        <th scope="col" class="w-5 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_FILENAME', 'a.url_download', $listDirn, $listOrder ); ?>
                        </th> 
                        <th scope="col" class="w-15 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort',  'COM_JDOWNLOADS_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                        </th>
                        <?php if ($multilang) : ?>
                            <th scope="col" class="w-15">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_LANGUAGE', 'language', $listDirn, $listOrder); ?>
                            </th>
                        <?php endif; ?>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_DADDED', 'a.created', $listDirn, $listOrder ); ?>
                        </th>
                        <th scope="col" class="w-1 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_ID', 'a.id', $listDirn, $listOrder); ?>
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
                <?php 
                foreach ($this->items as $i => $item) : ?>
                    <?php if ($item->language && $multilang)
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
                        else {
                            $lang = '';
                        }
                    }
                    elseif (!$multilang)
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
                            <?php $attribs = 'data-function="' . $this->escape($onclick) . '"'
                                . ' data-id="' . $item->id . '"'
                                . ' data-title="' . $this->escape($item->title) . '"'
                                . ' data-cat-id="' . $this->escape($item->catid) . '"'
                                . ' data-uri="' . $this->escape(RouteHelper::getDownloadRoute($item->id, $item->catid, $item->language)) . '"'
                                . ' data-language="' . $this->escape($lang) . '"';
                            ?>
                            <a class="select-link" href="javascript:void(0)" <?php echo $attribs; ?>>
                                <?php echo $this->escape($item->title); ?>
                            </a>
                            <div class="small break-word">
                                <?php if (empty($item->notes)) : ?>
                                    <?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_CAT') . ": " . $this->escape($item->category_title); ?>
                                    <?php echo Text::sprintf('COM_JDOWNLOADS_LIST_ALIAS', $this->escape($item->alias)); ?>
                                <?php else : ?>
                                    <?php echo Text::sprintf('COM_JDOWNLOADS_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->notes)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="small">
                                <?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_CAT') . ': ' . $this->escape($item->category_title); ?>
                            </div>
                        </th>
                            
                        <!-- <td>
                            <a href="javascript:void(0);" onclick="if (window.parent) window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>', '<?php echo $this->escape($item->catid); ?>', null, '<?php echo $this->escape(RouteHelper::getDownloadRoute($item->id, $item->catid, $item->language)); ?>', '<?php echo $this->escape($lang); ?>', null);">
                                <?php // echo $this->escape($item->title); ?></a>
                            <div class="small">
                                <?php // echo Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_CAT') . ": " . $this->escape($item->category_title); ?>
                            </div>
                        </td> -->
                        <td class="small d-none d-md-table-cell">
                            <?php echo $this->escape($item->release); ?>
                        </td>
                        <td class="small d-none d-md-table-cell text-center">
                            <?php
                            if (strlen($item->description) > 200 ) {
                                $description_short = $this->escape(substr($item->description, 0, 200).' ...');
                            } else {
                                $description_short = $this->escape($item->description);
                            }
                            if ($description_short != '') {
                                echo HtmlHelper::_('tooltip', $description_short, '', Uri::root().'administrator/components/com_jdownloads/assets/images/tooltip_blue.gif'); 
                            }
                            ?>
                        </td>
                        <td class="small d-none d-md-table-cell text-center">
                            <?php
                            if ($item->url_download !=''){
                                echo HtmlHelper::_('tooltip', $item->url_download, '', Uri::root().'administrator/components/com_jdownloads/assets/images/file_blue.gif'); 
                            } elseif ($item->extern_file != ''){
                                echo HtmlHelper::_('tooltip', $item->extern_file, '', Uri::root().'administrator/components/com_jdownloads/assets/images/external_orange.gif'); 
                            } elseif ($item->other_file_id > 0){
                                echo HtmlHelper::_('tooltip', Text::sprintf('COM_JDOWNLOADS_BACKEND_FILESLIST_OTHER_DOWNLOADS_FILE_NAME', $item->other_file_name), Text::sprintf('COM_JDOWNLOADS_BACKEND_FILESLIST_OTHER_DOWNLOADS_FILE_USED', $item->other_download_title), Uri::root().'administrator/components/com_jdownloads/assets/images/file_orange.gif'); 
                            } else {
                                // only a document without any files     
                                echo HtmlHelper::_('tooltip', Text::_('COM_JDOWNLOADS_DOCUMENT_DESC1'), Text::_('COM_JDOWNLOADS_BACKEND_TEMPPANEL_TABTEXT_INFO'), Uri::root().'administrator/components/com_jdownloads/assets/images/tooltip_red.gif'); 
                            }
                            ?>
                        </td>
                        <td class="small d-none d-md-table-cell">
                            <?php 
                            if ($item->user_access && $item->single_user_access){
                                echo '<span class="badge bg-danger" style="font-weight:normal;">'.$this->escape($item->single_user_access).'</span>';
                            } else {
                                echo $this->escape($item->access_level);
                            }
                             ?>                            
                        </td>
                        <?php if ($multilang) : ?>
                            <td class="small">
                                <?php if ($item->language == '*'): ?>
                                    <?php echo Text::alt('COM_JDOWNLOADS_ALL', 'language'); ?>
                                <?php else:?>
                                    <?php echo $item->language_title ? HtmlHelper::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array('title' => $item->language_title), true) . '&nbsp;' . $this->escape($item->language_title) : Text::_('COM_JDOWNLOADS_UNDEFINED'); ?>
                                <?php endif;?>
                            </td>
                        <?php endif; ?>    
                        <td class="small d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
                        </td>
                        <td class="small d-none d-md-table-cell">
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
        <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </form>
</div>