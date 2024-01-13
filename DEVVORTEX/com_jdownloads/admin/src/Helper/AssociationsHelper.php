<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_associations
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JDownloads\Component\JDownloads\Administrator\Helper;
 
\defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Association\AssociationExtensionInterface;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filesystem\Path;
use JLoader;

use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Associations component helper.
 *
 * @since  3.7.0
 */
class AssociationsHelper extends AssociationExtensionHelper
{
    /**
	 * Array of Registry objects of extensions
	 *
	 * var      array   $extensionsSupport
	 *
	 * @since   3.7.0
	 */
	public static $extensionsSupport = null;
    
    /**
     * Array of item types
     *
     * @var     array   $itemTypes
     *
     * @since   3.7.0
     */
    protected $itemTypes = array('download', 'category');

    /**
     * Has the extension association support
     *
     * @var     boolean   $associationsSupport
     *
     * @since   3.7.0
     */
    protected $associationsSupport = true;
    
    /**
     * Get the associated items for an item
     *
     * @param   string  $typeName       The item type
     * @param   int     $itemId         The id of item for which we need the associated items
     *
     * @return  array
     *
     * @since  3.7.0
     */
    public static function getJDAssociationList($extensionName, $typeName, $itemId)
    {
        if (!self::hasSupport($extensionName))
        {
            return array();
        }

        // Get the extension specific helper method
        $helper = self::getExtensionHelper($extensionName);

        return $helper->getAssociationList($typeName, $itemId);

    }    

    /**
     * Method to get the associations for a given item.
     *
     * @param   integer  $id    Id of the item
     * @param   string   $view  Name of the view
     *
     * @return  array   Array of associations for the item
     *
     * @since  4.0.0
     */
    public function getAssociationsForItem($id = 0, $view = null)
    {
        return AssociationHelper::getAssociations($id, $view);
    }

    /**
     * Get the associated items for an item
     *
     * @param   string  $typeName  The item type
     * @param   int     $id        The id of item for which we need the associated items
     *
     * @return  array
     *
     * @since   3.7.0
     */
    public function getAssociations($typeName, $id)
    {
        $type = $this->getType($typeName);

        $extension  = 'com_jdownloads';

        $tablename  = '#__jdownloads_files';
        $context    = 'com_jdownloads.item';
        $idField    = 'id';
        $aliasField = 'alias';
        $catidField = ''; // must be empty to get correct results

        if ($typeName === 'category'){
            $tablename  = '#__jdownloads_categories';
            $context    = 'com_jdownloads.category.item';
            $idField    = 'id';
            $aliasField = 'alias';
            $catidField = '';
        }

        // Get the associations.
        $associations = Associations::getAssociations('', $tablename, $context, $id, $idField, $aliasField, $catidField);
        
        return $associations;
    }

    /**
     * Get item information
     *
     * @param   string  $typeName  The item type
     * @param   int     $id        The id of item for which we need the associated items
     *
     * @return  Table|null
     *
     * @since   3.7.0
     */
    public static function getItem($typeName, $id)
    {
        if (empty($id))
        {
            return null;
        }

        $table = null;

        switch ($typeName)
        {
            case 'download':
                $table = Table::getInstance('DownloadTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
                break;

            case 'category':
                $table = Table::getInstance('JDCategoryTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
                break;
        }

        if (is_null($table))
        {
            return null;
        }

        $table->load($id);
        
        return $table;
    }

    /**
     * Get information about the type
     *
     * @param   string  $typeName  The item type
     *
     * @return  array  Array of item types
     *
     * @since   3.7.0
     */
    public function getType($typeName = '')
    {
        $fields  = self::getFields($typeName);

        $tables  = array();
        $joins   = array();
        $support = $this->getSupportTemplate();
        $title   = '';

        if (in_array($typeName, $this->itemTypes))
        {
            switch ($typeName)
            {
                case 'download':
                    // the core script would use the catid field to display a correcponding item from the _categories table but not the correct category item from jDownloads. So we remove it from list.
                    //$fields['catid'] = '';
                
                    $support['state'] = true;
                    $support['acl'] = true;
                    $support['checkout'] = true;
                    $support['category'] = true;
                    $support['save2copy'] = true;

                    $tables = array(
                        'a' => '#__jdownloads_files'
                    );

                    $title = 'download';
                    break;

                case 'category':
                    $fields['created_user_id'] = 'a.created_user_id';
                    $fields['ordering'] = 'a.lft';
                    $fields['level'] = 'a.level';
                    $fields['catid'] = '';
                    $fields['state'] = 'a.published';
                    $fields['extension'] = 'a.extension';

                    $support['state'] = true;
                    $support['acl'] = true;
                    $support['checkout'] = true;
                    $support['level'] = true;

                    $tables = array(
                        'a' => '#__jdownloads_categories'
                    );

                    $title = 'category';
                    break;
            }
        }

        return array(
            'fields'  => $fields,
            'support' => $support,
            'tables'  => $tables,
            'joins'   => $joins,
            'title'   => $title
        );
    }
    
    protected function getFields($type)
    {
        if ($type == 'download'){
        
            return array(
                'id'                  => 'a.id',
                'title'               => 'a.title',
                'alias'               => 'a.alias',
                'ordering'            => 'a.ordering',
                'menutype'            => '',
                'level'               => '',
                'catid'               => 'a.catid',
                'language'            => 'a.language',
                'access'              => 'a.access',
                'state'               => 'a.published',
                'created_user_id'     => 'a.created_by',
                'checked_out'         => 'a.checked_out',
                'checked_out_time'    => 'a.checked_out_time'
            );
        } else {
            
            // Category fields
            return array(
                'id'                  => 'a.id',
                'title'               => 'a.title',
                'alias'               => 'a.alias',
                'ordering'            => 'a.ordering',
                'menutype'            => '',
                'level'               => 'level',
                'catid'               => '',
                'language'            => 'a.language',
                'access'              => 'a.access',
                'state'               => 'a.published',
                'created_user_id'     => 'a.created_user_id',
                'checked_out'         => 'a.checked_out',
                'checked_out_time'    => 'a.checked_out_time'
            );
            
        }
    }
    
    /**
     * Method to get the associations for a given category
     *
     * @param   integer  $id         Id of the item
     * @param   string   $layout     Category layout
     *
     * @return  array    Array of associations for the jDownloads categories
     *
     * @since  3.0
     */
    public static function getCategoryAssociations($id = 0, $extension = 'com_jdownloads', $layout = null)
    {
        $return = array();

        if ($id){
            $helperClassname = ucfirst(substr($extension, 4)) . 'HelperRoute';

            $associations = CategoriesHelper::getAssociations($id);

            foreach ($associations as $tag => $item){
                if (class_exists($helperClassname) && is_callable(array($helperClassname, 'getCategoryRoute'))){
                    $return[$tag] = $helperClassname::getCategoryRoute($item, $tag, $layout);
                } else {
                    $viewLayout = $layout ? '&layout=' . $layout : '';

                    $return[$tag] = 'index.php?option=com_jdownloads&view=category&id=' . $item . $viewLayout;
                }
            }
        }

        return $return;
    }
    
    /* old part */
    
	/**
	 * List of extensions name with support
	 *
	 * var      array   $supportedExtensionsList
	 *
	 * @since   3.7.0
	 */
	
    
    
    public static $supportedExtensionsList = array();

	/**
	 * Get the associated items for an item
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 * @param   int     $itemId         The id of item for which we need the associated items
	 *
	 * @return  array
	 *
	 * @since   3.7.0
	 */
    /*	public static function getAssociationList($extensionName, $typeName, $itemId)
	{
		if (!self::hasSupport($extensionName))
		{
			return array();
		}

		// Get the extension specific helper method
		$helper = self::getExtensionHelper($extensionName);

		return $helper->getAssociationList($typeName, $itemId);

	} */

	/**
	 * Get the the instance of the extension helper class
	 *
	 * @param   string  $extensionName  The extension name with com_
	 *
	 * @return  HelperClass|null
	 *
	 * @since   3.7.0
	 */
	public static function getExtensionHelper($extensionName)
	{
		if (!self::hasSupport($extensionName))
		{
			return null;
		}

		$support = self::$extensionsSupport[$extensionName];

		return $support->get('helper');
	}

	/**
	 * Get item information
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 * @param   int     $itemId         The id of item for which we need the associated items
	 *
	 * @return  JTable|null
	 *
	 * @since   3.7.0
	 */
	/*public static function getItem($extensionName, $typeName, $itemId)
	{
		if (!self::hasSupport($extensionName))
		{
			return array();
		}

		// Get the extension specific helper method
		$helper = self::getExtensionHelper($extensionName);

		return $helper->getItem($typeName, $itemId);
	} */

	/**
	 * Check if extension supports associations
	 *
	 * @param   string  $extensionName  The extension name with com_
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public static function hasSupport($extensionName)
	{
		if (is_null(self::$extensionsSupport))
		{
			self::getSupportedExtensions();
		}

		return in_array($extensionName, self::$supportedExtensionsList);
	}
    
    /**
     * Loads the helper for the given class.
     *
     * @param   string  $extensionName  The extension name with com_
     *
     * @return  AssociationExtensionInterface|null
     *
     * @since  4.0.0
     */
    private static function loadHelper($extensionName)
    {
        $component = Factory::getApplication()->bootComponent($extensionName);

        if ($component instanceof AssociationServiceInterface)
        {
            return $component->getAssociationsExtension();
        }

        // Check if associations helper exists
        if (!file_exists(JPATH_ADMINISTRATOR . '/components/' . $extensionName . '/src/Helper/associations.php'))
        {
            return null;
        }

        require_once JPATH_ADMINISTRATOR . '/components/' . $extensionName . '/src/Helper/associations.php';

        $componentAssociationsHelperClassName = self::getExtensionHelperClassName($extensionName);

        if (!class_exists($componentAssociationsHelperClassName, false))
        {
            return null;
        }

        // Create an instance of the helper class
        return new $componentAssociationsHelperClassName;
    }    

	/**
	 * Get the extension specific helper class name
	 *
	 * @param   string  $extensionName  The extension name with com_
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	private static function getExtensionHelperClassName($extensionName)
	{
		$realName = self::getExtensionRealName($extensionName);

		return ucfirst($realName) . 'AssociationsHelper';
	}

	/**
	 * Get the real extension name. This means without com_
	 *
	 * @param   string  $extensionName  The extension name with com_
	 *
	 * @return  string
	 *
	 * @since   3.7.0
	 */
	private static function getExtensionRealName($extensionName)
	{
		return strpos($extensionName, 'com_') === false ? $extensionName : substr($extensionName, 4);
	}

	/**
	 * Get the associated language edit links Html.
	 *
	 * @param   string   $extensionName   Extension Name
	 * @param   string   $typeName        ItemType
	 * @param   integer  $itemId          Item id.
	 * @param   string   $itemLanguage    Item language code.
	 * @param   boolean  $addLink         True for adding edit links. False for just text.
	 * @param   boolean  $assocLanguages  True for showing non associated content languages. False only languages with associations.
	 *
	 * @return  string   The language HTML
	 *
	 * @since   3.7.0
	 */
	public static function getAssociationHtmlList($extensionName, $typeName, $itemId, $itemLanguage, $addLink = true, $assocLanguages = true)
	{
		// Get the associations list for this item.
		$items = self::getJDAssociationList($extensionName, $typeName, $itemId);

		$titleFieldName = self::getJDTypeFieldName($extensionName, $typeName, 'title');

		// Get all content languages.
		$languages = LanguageHelper::getContentLanguages(array(0, 1), false);
        $content_languages = array_column($languages, 'lang_code');
        
        // Display warning if Content Language is trashed or deleted
        foreach ($items as $item)
        {
            if (!\in_array($item['language'], $content_languages))
            {
                Factory::getApplication()->enqueueMessage(Text::sprintf('JGLOBAL_ASSOCIATIONS_CONTENTLANGUAGE_WARNING', $item['language']), 'warning');
            }
        }

		$canEditReference = self::allowEdit($extensionName, $typeName, $itemId);
		$canCreate        = self::allowAdd($extensionName, $typeName);

		// Create associated items list.
		foreach ($languages as $langCode => $language)
		{
			// Don't do for the reference language.
			if ($langCode == $itemLanguage)
			{
				continue;
			}

			// Don't show languages with associations, if we don't want to show them.
			if ($assocLanguages && isset($items[$langCode]))
			{
				unset($items[$langCode]);
				continue;
			}

			// Don't show languages without associations, if we don't want to show them.
			if (!$assocLanguages && !isset($items[$langCode]))
			{
				continue;
			}

			// Get html parameters.
			if (isset($items[$langCode]))
			{
				$title       = $items[$langCode][$titleFieldName];
				$additional  = '';

				if (isset($items[$langCode]['catid']))
				{
					$db = Factory::getDbo();

					// Get the category name
					$query = $db->getQuery(true)
						->select($db->quoteName('title'))
						->from($db->quoteName('#__jdownloads_categories'))
						->where($db->quoteName('id') . ' = ' . $db->quote($items[$langCode]['catid']));

					$db->setQuery($query);
					$category_title = $db->loadResult();

					$additional = '<strong>' . Text::sprintf('JCATEGORY_SPRINTF', $category_title) . '</strong> <br />';
				}
				elseif (isset($items[$langCode]['menutype']))
				{
					$db = Factory::getDbo();

					// Get the menutype name
					$query = $db->getQuery(true)
						->select($db->quoteName('title'))
						->from($db->quoteName('#__menu_types'))
						->where($db->quoteName('menutype') . ' = ' . $db->quote($items[$langCode]['menutype']));

					$db->setQuery($query);
					$menutype_title = $db->loadResult();

					$additional = '<strong>' . Text::sprintf('COM_MENUS_MENU_SPRINTF', $menutype_title) . '</strong><br />';
				}

				$labelClass = 'bg-secondary';
				$target      = $langCode . ':' . $items[$langCode]['id'] . ':edit';
				$allow       = $canEditReference
								&& self::allowEdit($extensionName, $typeName, $items[$langCode]['id'])
								&& self::canCheckinItem($extensionName, $typeName, $items[$langCode]['id']);

				$additional .= $addLink && $allow ? Text::_('COM_JDOWNLOADS_ASSOCIATIONS_EDIT_ASSOCIATION') : '';
			}
			else
			{
				$items[$langCode] = array();

				$title      = Text::_('COM_JDOWNLOADS_ASSOCIATIONS_NO_ASSOCIATION');
				$additional = $addLink ? Text::_('COM_JDOWNLOADS_ASSOCIATIONS_ADD_NEW_ASSOCIATION') : '';
				$labelClass = 'bg-warning text-dark';
				$target     = $langCode . ':0:add';
				$allow      = $canCreate;
			}

			// Generate item Html.
			$options   = array(
				'option'   => 'com_jdownloads',
				'view'     => 'association',
				'layout'   => 'edit',
				'itemtype' => $extensionName . '.' . $typeName,
				'task'     => 'association.edit',
				'id'       => $itemId,
				'target'   => $target,
			);

			$url     = ROUTE::_('index.php?' . http_build_query($options));
			$url     = $allow && $addLink ? $url : '';
			$text    = $language->lang_code;

            $tooltip = '<strong>' . htmlspecialchars($language->title, ENT_QUOTES, 'UTF-8') . '</strong><br>'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '<br><br>' . $additional;
            $classes = 'badge ' . $labelClass;

            $items[$langCode]['link'] = '<a href="' . $url . '" class="' . $classes . '">' . $text . '</a>'
                . '<div role="tooltip">' . $tooltip . '</div>';
		}

		return LayoutHelper::render('joomla.content.associations', $items);
	}

	/**
	 * Get all extensions with associations support.
	 *
	 * @return  array  The extensions.
	 *
	 * @since   3.7.0
	 */
	public static function getSupportedExtensions()
	{
		if (!is_null(self::$extensionsSupport))
		{
			return self::$extensionsSupport;
		}

		self::$extensionsSupport = array();

		$extensions = self::getEnabledExtensions();

		foreach ($extensions as $extension)
		{
			$support = self::getSupportedExtension($extension->element);

			if ($support->get('associationssupport') === true)
			{
				self::$supportedExtensionsList[] = $extension->element;
			}

			self::$extensionsSupport[$extension->element] = $support;
		}

		return self::$extensionsSupport;
	}

	/**
	 * Get item context based on the item key.
	 *
	 * @param   string  $extensionName  The extension identifier.
	 *
	 * @return  Joomla\Registry\Registry  The item properties.
	 *
	 * @since   3.7.0
	 */
	public static function getSupportedExtension($extensionName)
	{
		$result = new Registry;

		$result->def('component', $extensionName);
		$result->def('associationssupport', false);
		$result->def('helper', null);
        
        $helper = self::loadHelper($extensionName);

        if (!$helper)
        {
            return $result;
        }
        
        $result->set('helper', $helper);

        if ($helper->hasAssociationsSupport() === false)
        {
            return $result;
        }

		$result->set('associationssupport', true);

		// Get the translated titles.
		$languagePath = JPATH_ADMINISTRATOR . '/components/' . $extensionName;
		$lang         = Factory::getLanguage();

		$lang->load($extensionName . '.sys', JPATH_ADMINISTRATOR);
		$lang->load($extensionName . '.sys', $languagePath);
		$lang->load($extensionName, JPATH_ADMINISTRATOR);
		$lang->load($extensionName, $languagePath);

		$result->def('title', Text::_(strtoupper($extensionName)));

		// Get the supported types
		$types  = $helper->getItemTypes();
		$rTypes = array();

		foreach ($types as $typeName)
		{
			$details     = $helper->getType($typeName);
			$context     = 'component';
			$title       = $helper->getTypeTitle($typeName);
			$languageKey = $typeName;

			if ($typeName === 'category')
			{
				$title = Text::_('COM_JDOWNLOADS_CATEGORIES');
				$context     = 'category';
			} else {
                $title = Text::_('COM_JDOWNLOADS_DOWNLOADS');
                //$context     = 'category';
			}


			$rType = new Registry;

			$rType->def('name', $typeName);
			$rType->def('details', $details);
			$rType->def('title', $title);
			$rType->def('context', $context);

			$rTypes[$typeName] = $rType;
		}

		$result->def('types', $rTypes);

		return $result;
	}

	/**
	 * Get all installed and enabled extensions
	 *
	 * @return  mixed
	 *
	 * @since   3.7.0
	 */
	private static function getEnabledExtensions()
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'))
			->where($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_jdownloads'))
            ;

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get all the content languages.
	 *
	 * @return  array  Array of objects all content languages by language code.
	 *
	 * @since   3.7.0
	 */
	public static function getContentLanguages()
	{
		return LanguageHelper::getContentLanguages(array(0, 1));
	}

	/**
	 * Get the associated items for an item
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 * @param   int     $itemId         The id of item for which we need the associated items
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public static function allowEdit($typeName, $itemId)
	{
		$app = Factory::getApplication();
        
		return $app->getIdentity()->authorise('core.edit', 'com_jdownloads');
	}

	/**
	 * Check if user is allowed to create items.
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 *
	 * @return  boolean  True on allowed.
	 *
	 * @since   3.7.0
	 */
	public static function allowAdd($typeName)
	{
		$app = Factory::getApplication();
        
        return $app->getIdentity()->authorise('core.create', 'com_jdownloads');
	}

	/**
	 * Check if an item is checked out
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 * @param   int     $itemId         The id of item for which we need the associated items
	 *
	 * @return  boolean  True if item is checked out.
	 *
	 * @since   3.7.0
	 */
	public static function isCheckoutItem($typeName, $itemId)
	{
		if (!self::hasSupport('com_jdownloads'))
		{
			return false;
		}

		if (!self::typeSupportsCheckout('com_jdownloads', $typeName))
		{
			return false;
		}

		// Get the extension specific helper method
		$helper = self::getExtensionHelper('com_jdownloads');

		$item = self::getItem('com_jdownloads', $typeName, $itemId);

		$checkedOutFieldName = $helper->getTypeFieldName($typeName, 'checked_out');

		return $item->{$checkedOutFieldName} != 0;
	}

	/**
	 * Check if user can checkin an item.
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 * @param   int     $itemId         The id of item for which we need the associated items
	 *
	 * @return  boolean  True on allowed.
	 *
	 * @since   3.7.0
	 */
	public static function canCheckinItem($typeName, $itemId)
	{
		$app = Factory::getApplication();
        
        if (!self::hasSupport('com_jdownloads'))
		{
			return false;
		}

		if (!self::typeSupportsCheckout('com_jdownloads', $typeName))
		{
			return true;
		}

		// Get the extension specific helper method
		$helper = self::getExtensionHelper('com_jdownloads');

		$item = self::getItem('com_jdownloads', $typeName, $itemId);

		$checkedOutFieldName = $helper->getTypeFieldName($typeName, 'checked_out');

		$userId = $app->getIdentity()->id;

		return ($item->{$checkedOutFieldName} == $userId || $item->{$checkedOutFieldName} == 0);
	}

	/**
	 * Check if the type supports checkout
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 *
	 * @return  boolean  True on allowed.
	 *
	 * @since   3.7.0
	 */
	public static function typeSupportsCheckout($typeName)
	{
		if (!self::hasSupport('com_jdownloads'))
		{
			return false;
		}

		// Get the extension specific helper method
		$helper = self::getExtensionHelper('com_jdownloads');

		$support = $helper->getTypeSupport($typeName);

		return !empty($support['checkout']);
	}

	/**
	 * Get a table field name for a type
	 *
	 * @param   string  $extensionName  The extension name with com_
	 * @param   string  $typeName       The item type
	 * @param   string  $fieldName      The item type
	 *
	 * @return  boolean  True on allowed.
	 *
	 * @since   3.7.0
	 */
	public static function getJDTypeFieldName($extensionName, $typeName, $fieldName)
	{
		if (!self::hasSupport($extensionName))
		{
			return false;
		}

		// Get the extension specific helper method
		$helper = self::getExtensionHelper($extensionName);

		return $helper->getTypeFieldName($typeName, $fieldName);
	}

	/**
	 * Gets the language filter system plugin extension id.
	 *
	 * @return  integer  The language filter system plugin extension id.
	 *
	 * @since   3.7.2
	 */
	public static function getLanguagefilterPluginId()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
			->where($db->quoteName('element') . ' = ' . $db->quote('languagefilter'));
		$db->setQuery($query);

		try
		{
			$result = (int) $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			Error::raiseWarning(500, $e->getMessage());
		}

		return $result;
	}
}
