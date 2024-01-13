<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_associations
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JDownloads\Component\JDownloads\Administrator\View\Associations;  
 
\defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Plugin\PluginHelper;
use JLoader;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\AssociationsHelper;
use JDownloads\Component\JDownloads\Administrator\Field\JDItemTypeField;
use JDownloads\Component\JDownloads\Administrator\Field\ItemLanguageField;

use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

JLoader::register('AssociationsHelper', JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Helpers/AssociationsHelper.php');

/**
 * View class for a list of Associations.
 *
 * @since  4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * An array of items
	 *
	 * @var   array
	 *
	 * @since  3.7.0
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var    JPagination
	 *
	 * @since  3.7.0
	 */
	protected $pagination;
    
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
	 * The model state
	 *
	 * @var    object
	 *
	 * @since  3.7.0
	 */
	protected $state;
    
    protected $_defaultModel = 'associations';

	/**
	 * Selected item type properties.
	 *
	 * @var    Registry
	 *
	 * @since  3.7.0
	 */
	public $itemType = null;
    
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public function display($tpl = null)
	{
        // Load the backend helper
        require_once JPATH_COMPONENT.'/src/Helper/JDownloadsHelper.php';
        
        JDownloadsHelper::addSubmenu('associations');
        
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		if (!Associations::isEnabled())
		{
			$link = ROUTE::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . AssociationsHelper::getLanguagefilterPluginId());
			Factory::getApplication()->enqueueMessage(Text::sprintf('COM_JDOWNLOADS_ASSOCIATIONS_ERROR_NO_ASSOC', $link), 'warning');
		}
		elseif ($this->state->get('itemtype') != '' && $this->state->get('language') != '')
		{
			$type = null;

			list($extensionName, $typeName) = explode('.', $this->state->get('itemtype'), 2);

			$extension = AssociationsHelper::getSupportedExtension('com_jdownloads');

			$types = $extension->get('types');

			if (\array_key_exists($typeName, $types))
			{
				$type = $types[$typeName];
			}

			$this->itemType = $type;

			if (\is_null($type))
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_JDOWNLOADS_ASSOCIATIONS_ERROR_NO_TYPE'), 'warning');
			}
			else
			{
				$this->extensionName = $extensionName;
				$this->typeName      = $typeName;
				$this->typeSupports  = array();
				$this->typeFields    = array();

				$details = $type->get('details');

				if (\array_key_exists('support', $details))
				{
					$support = $details['support'];
					$this->typeSupports = $support;
				}

				if (\array_key_exists('fields', $details))
				{
					$fields = $details['fields'];
					$this->typeFields = $fields;
				}

				// Dynamic filter form.
				// This selectors doesn't have to activate the filter bar.
				unset($this->activeFilters['itemtype']);
				unset($this->activeFilters['language']);

				// Remove filters options depending on selected type.
				if (empty($support['state']))
				{
					unset($this->activeFilters['state']);
					$this->filterForm->removeField('state', 'filter');
				}

				if (empty($support['category']))
				{
					unset($this->activeFilters['category_id']);
					$this->filterForm->removeField('category_id', 'filter');
				}

				if ($extensionName !== 'com_menus')
				{
					unset($this->activeFilters['menutype']);
					$this->filterForm->removeField('menutype', 'filter');
				}

				if (empty($support['level']))
				{
					unset($this->activeFilters['level']);
					$this->filterForm->removeField('level', 'filter');
				}

				if (empty($support['acl']))
				{
					unset($this->activeFilters['access']);
					$this->filterForm->removeField('access', 'filter');
				}

				// Add extension attribute to category filter.
				if (empty($support['catid']))
				{
					$this->filterForm->setFieldAttribute('category_id', 'extension', $extensionName, 'filter');

					if ($this->getLayout() == 'modal')
					{
						// We need to change the category filter to only show categories tagged to All or to the forced language.
						if ($forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'CMD'))
						{
							$this->filterForm->setFieldAttribute('category_id', 'language', '*,' . $forcedLanguage, 'filter');
						}
					}
				}

				$this->items      = $this->get('Items');
				$this->pagination = $this->get('Pagination');

				$linkParameters = array(
					'layout'     => 'edit',
					'itemtype'   => $extensionName . '.' . $typeName,
					'task'       => 'association.edit',
				);

				$this->editUri = 'index.php?option=com_jdownloads&view=association&' . http_build_query($linkParameters);
			}
		}

		// Check for errors.
		if (\count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors));
		}

		// We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {        
            $this->addToolbar();
        }

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	protected function addToolbar()
	{
        require_once JPATH_COMPONENT.'/src/Helper/JDownloadsHelper.php';

        $params = ComponentHelper::getParams('com_jdownloads');

        $user = Factory::getApplication()->getIdentity();
        
		if (isset($this->typeName) && isset($this->extensionName)){
			$helper = AssociationsHelper::getExtensionHelper('com_jdownloads');
			$title  = $helper->getTypeTitle($this->typeName);

			$languageKey = strtoupper($this->extensionName . '_' . $title . 'S');

			if ($this->typeName === 'category'){
				$languageKey = strtoupper($this->extensionName) . '_CATEGORIES';
			}

			ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_MULTILINGUAL_ASSOCIATIONS').' ('.Text::_($languageKey).')', 'contract jddownloads');

		} else {
            ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_MULTILINGUAL_ASSOCIATIONS'), 'contract jddownloads');
		}
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');        

		if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')){
			
            if (!isset($this->typeName)){
				ToolBarHelper::custom('associations.purge', 'purge', 'purge', 'COM_JDOWNLOADS_ASSOCIATIONS_PURGE', false, false);
				ToolBarHelper::custom('associations.clean', 'refresh', 'refresh', 'COM_JDOWNLOADS_ASSOCIATIONS_DELETE_ORPHANS', false, false);
			}
		}

        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '163&tmpl=jdhelp'; //article  'Including Multilingual Support'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }
        
	}

}