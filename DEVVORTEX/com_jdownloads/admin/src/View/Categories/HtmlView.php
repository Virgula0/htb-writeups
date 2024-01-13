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

namespace JDownloads\Component\JDownloads\Administrator\View\Categories;

\defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * View class for the categories list view.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $string;
    protected $assoc;
    
    public $activeFilters;
    public $filterForm;
    
    protected static $rows = array();
    protected $canDo;
    
    private $isEmptyState = false;
    
    /**
	 * Categories view display
	 *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
	 **/
	function display($tpl = null)
	{
        $this->state        = $this->get('State');
        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');
        
        // The filter form file must exist in the models/forms folder (e.g. filter_categories.xml) 
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        
        // What Access Permissions does this user have? What can (s)he do?
        $this->canDo = jDownloadsHelper::getActions();
        
        $this->exist_menu_item = jDownloadsHelper::existAllCategoriesMenuItem();
        
        $this->assoc = $this->get('Assoc');
        
        if (!$this->items && $this->isEmptyState = $this->get('IsEmptyState'))
        {
            $this->setLayout('emptystate');
        }        
       
        // Check for errors.
        if (count($errors = $this->get('Errors'))){
           throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Preprocess the list of items to find ordering divisions.
        foreach ($this->items as &$item){
            $this->ordering[$item->parent_id][] = $item->id;
        }
        
        // build categories list box for batch operations 
        $lists = array();
        $config = array('filter.published' => array(0, 1));
        $select[] = HtmlHelper::_('select.option', 0, Text::_('COM_JDOWNLOADS_SELECT_CATEGORY'));
        $select[] = HtmlHelper::_('select.option', 1, Text::_('COM_JDOWNLOADS_BATCH_ROOT_CAT'));
        
        // get the categories data
        $categories = $this->getCategoriesList($config);
        $this->categories = @array_merge($select, $categories);        

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal'){
            $this->addToolbar();
            
            // We do not need to filter by language when multilingual is disabled
            if (!Multilanguage::isEnabled())
            {
                unset($this->activeFilters['language']);
                $this->filterForm->removeField('language', 'filter');
            }
            
        } else {
            // In Download associations modal we need to remove language filter if forcing a language.
            if ($forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'CMD'))
            {
                // If the language is forced we can't allow to select the language, so transform the language selector filter into a hidden field.
                $languageXml = new \SimpleXMLElement('<field name="language" type="hidden" default="' . $forcedLanguage . '" />');
                $this->filterForm->setField($languageXml, 'filter', true);

                // Also, unset the active language filter so the search tools is not open by default with this filter.
                unset($this->activeFilters['language']);
            }
        }

        return parent::display($tpl);       
	}
    
    /**
     * Add the page title and toolbar.
     *
     * @since    1.6
     */
    protected function addToolbar()
    {
        require_once JPATH_COMPONENT.'/src/Helper/JDownloadsHelper.php';

        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $categoryId = $this->state->get('filter.category_id');
        $canDo      = JDownloadsHelper::getActions($categoryId, 'category');        
        $state      = $this->get('State');
        $user       = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        // Get the toolbar object instance
        $toolbar = ToolBar::getInstance('toolbar');
        
        JDownloadsHelper::addSubmenu('categories');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_CATEGORIES'), 'folder jdcategories');
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');        
        
        if ($canDo->get('core.create')) {
            ToolBarHelper::addNew('category.add');
        }
        
        /*if ($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
            ToolBarHelper::editList('category.edit');
        } */   
        
        if ($canDo->get('core.edit.state') || $app->getIdentity()->authorise('core.admin')) {
            
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            
            if ($canDo->get('core.edit.state')){
                $childBar->publish('categories.publish')->listCheck(true);
                $childBar->unpublish('categories.unpublish')->listCheck(true);
            }

            if ($app->getIdentity()->authorise('core.admin')){
                $childBar->checkin('categories.checkin')->listCheck(true);
            }
            
            if ($canDo->get('core.delete')) {
                $childBar->delete('categories.delete')
                ->text('COM_JDOWNLOADS_TOOLBAR_REMOVE')
                ->message('COM_JDOWNLOADS_DELETE_LIST_ITEM_CONFIRMATION')
                ->listCheck(true);
            }

            // Add a batch button
            if ($canDo->get('core.create')
                && $canDo->get('core.edit')
                && $canDo->get('core.edit.state'))
            {
                $childBar->popupButton('batch')
                    ->text('JTOOLBAR_BATCH')
                    ->selector('collapseModal')
                    ->listCheck(true);
            }
        }

        if ($canDo->get('core.admin', 'com_jdownloads') || $canDo->get('core.options', 'com_jdownloads')) {
            $toolbar->standardButton('refresh')
                ->text('COM_JDOWNLOADS_REBUILD')
                ->task('categories.rebuild');
        }
        
        if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
        }         
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '107&tmpl=jdhelp'; //article 'Customising how Downloads are listed in different Categories '
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }
    }  
    
    /**
     * Returns an array of the categories 
     *
     * @param   array   $config     An array of configuration options. By default, only
     *                              published and unpublished categories are returned.
     *
     * @return  array
     *
     */    
    public static function getCategoriesList($config = array('filter.published' => array(0, 1)))
    {
        $hash = md5('com_jdownloads' . '.categories.' . serialize($config));

        if (!isset(static::$rows[$hash]))
        {
            $config = (array) $config;
            $db = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select('a.id, a.title, a.level');
            $query->from('#__jdownloads_categories AS a');
            $query->where('a.parent_id > 0');

            // Filter on the published state
            if (isset($config['filter.published']))
            {
                if (is_numeric($config['filter.published']))
                {
                    $query->where('a.published = ' . (int) $config['filter.published']);
                }
                elseif (is_array($config['filter.published']))
                {
                    ArrayHelper::toInteger($config['filter.published']);
                    $query->where('a.published IN (' . implode(',', $config['filter.published']) . ')');
                }
            }
            
            // Filter on the language
            if (isset($config['filter.language']))
            {
                if (is_string($config['filter.language']))
                {
                    $query->where('a.language = ' . $db->quote($config['filter.language']));
                }
                elseif (is_array($config['filter.language']))
                {
                    foreach ($config['filter.language'] as &$language)
                    {
                        $language = $db->quote($language);
                    }

                    $query->where('a.language IN (' . implode(',', $config['filter.language']) . ')');
                }
            }

            // Filter on the access
            if (isset($config['filter.access']))
            {
                if (is_string($config['filter.access']))
                {
                    $query->where('a.access = ' . $db->quote($config['filter.access']));
                }
                elseif (is_array($config['filter.access']))
                {
                    foreach ($config['filter.access'] as &$access)
                    {
                        $access = $db->quote($access);
                    }

                    $query->where('a.access IN (' . implode(',', $config['filter.access']) . ')');
                }
            }

            $query->order('a.lft');

            $db->setQuery($query);
            $rows = $db->loadObjectList();

            // Assemble the list options.
            static::$rows[$hash] = array();

            foreach ($rows as &$row)
            {
                $repeat = ($row->level - 1 >= 0) ? $row->level - 1 : 0;
                $row->title = str_repeat('- ', $repeat) . $row->title;
                self::$rows[$hash][] = HtmlHelper::_('select.option', $row->id, $row->title);
            }
        }

        return static::$rows[$hash];              
    }
}
?>