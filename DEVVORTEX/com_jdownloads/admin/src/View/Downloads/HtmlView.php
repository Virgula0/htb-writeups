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

namespace JDownloads\Component\JDownloads\Administrator\View\Downloads; 
 
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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * View downloads list
  * @package    jDownloads
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The item authors
     *
     * @var  stdClass
     */
    protected $authors;

    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  JPagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  object
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var  JForm
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;
    
    /**
     * Is this view an Empty State
     *
     * @var   boolean
     * @since 4.0.0
     */
    private $isEmptyState = false;    
        
    protected static $rows = array();
    
    
    /**
	 * Downloads list view method
     * 
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise a Error object.
	 **/
	public function display($tpl = null)
	{
        $app = Factory::getApplication();
        $doc = Factory::getDocument();

        if ($this->getLayout() !== 'modal')
        {
            $app->setUserState( 'jd_modal', false );
        } else {
            // set a switch so we can build later a valid: db query
            $app->setUserState( 'jd_modal', true );
        }
        
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->authors       = $this->get('Authors');

        // the filter form file must exist in the models/forms folder (e.g. filter_downloads.xml) 

        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        
        $this->exist_menu_item = jDownloadsHelper::existAllCategoriesMenuItem();
        
        $params = ComponentHelper::getParams('com_jdownloads');
        
        if (!$this->items && $this->isEmptyState = $this->get('IsEmptyState'))
        {
            $this->setLayout('emptystate');
        }
        
        // build categories list box 
        $lists = array();
        $config = array('filter.published' => array(0, 1));
        $select[] = HTMLHelper::_('select.option', 0, Text::_('JLIB_HTML_BATCH_NO_CATEGORY'));
        
		// get the categories data for filter listbox
        $categories = $this->getCategoriesList($config);
        $this->categories = @array_merge($select, $categories);        
        
        // Check for errors.
        if (\count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors));
        }
        
        if ($params->get('use_lightbox_function')){
            $doc->addScript( URI::base().'components/com_jdownloads/assets/lightbox/src/js/lightbox.js' );
            $doc->addStyleSheet( URI::base()."components/com_jdownloads/assets/lightbox/src/css/lightbox.css", 'text/css', null, array() );
        }
        
        // Display ratings in list?
        if ($params->get('view_ratings_in_downloads_list')){
            $this->vote = true;
        } else {
            $this->vote = false;
        }            
        
        // We need icomoon font
        $doc->addStyleSheet(Uri::root(true).'/media/system/scss/_icomoon.scss');
   
        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {        
            $this->addToolbar();
            
            // We do not need to filter by language when multilingual is disabled
            if (!Multilanguage::isEnabled())
            {
                unset($this->activeFilters['language']);
                $this->filterForm->removeField('language', 'filter');
            }
            
        } else {
            // Added to support the Joomla Language Associations
            // In download associations modal we need to remove language filter if forcing a language.
            // We also need to change the category filter to show categories with All or the forced language.
            if ($forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'CMD'))
            {
                // If the language is forced we can't allow to select the language, so transform the language selector filter into a hidden field.
                $languageXml = new \SimpleXMLElement('<field name="language" type="hidden" default="' . $forcedLanguage . '" />');
                $this->filterForm->setField($languageXml, 'filter', true);

                // Also, unset the active language filter so the search tools is not open by default with this filter.
                unset($this->activeFilters['language']);

                // One last changes needed is to change the category filter to just show categories with All language or with the forced language.
                $this->filterForm->setFieldAttribute('category_id', 'language', '*,' . $forcedLanguage, 'filter');
            }
        }    
        return parent::display($tpl);
	}
    
    /**
     * Add the page title and toolbar.
     *
     * 
     */                                          
    protected function addToolbar()
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        $app    = Factory::getApplication();
        $canDo  = JDownloadsHelper::getActions();
        $user   = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        // Get the toolbar object instance
        $toolbar = ToolBar::getInstance('toolbar');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_DOWNLOADS'), 'stack jddownloads');
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');
        
        if ($canDo->get('core.create')) {
            ToolBarHelper::addNew('download.add');
        }

        if ($canDo->get('core.edit.state')) 
        {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            
            if ($canDo->get('core.edit.state')){
                $childBar->publish('downloads.publish')->listCheck(true);
                $childBar->unpublish('downloads.unpublish')->listCheck(true);
                
                $childBar->standardButton('featured')
                    ->text('COM_JDOWNLOADS_FEATURE')
                    ->task('downloads.featured')
                    ->listCheck(true);

                $childBar->standardButton('unfeatured')
                    ->text('COM_JDOWNLOADS_UNFEATURE')
                    ->task('downloads.unfeatured')
                    ->listCheck(true);
            }

            if ($app->getIdentity()->authorise('core.admin')){
                $childBar->checkin('downloads.checkin')->listCheck(true);
            }
            
            if ($canDo->get('core.delete')) {
                $childBar->delete('downloads.delete')
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
        
        if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        } 
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '293&tmpl=jdhelp'; //Create a Download in the Backend-{V4}
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
                self::$rows[$hash][] = HTMLHelper::_('select.option', $row->id, $row->title);
            }
        }

        return static::$rows[$hash];       
    }
}
