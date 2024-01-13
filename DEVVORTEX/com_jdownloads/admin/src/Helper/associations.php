<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * Modified for jDownloads 
 */

namespace JDownloads\Component\JDownloads\Administrator\Helper;
 
\defined('_JEXEC') or die;


use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Association\AssociationExtensionInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Factory;


Table::addIncludePath(__DIR__ . '/../Table');
            
/**
 * Content associations helper.
 *
 * @since  3.7.0
 */
class JDownloadsAssociationsHelper extends AssociationExtensionHelper
{
	/**
	 * The extension name
	 *
	 * @var     array   $extension
	 *
	 * @since   3.7.0
	 */
	protected $extension = 'com_jdownloads';

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
	protected $associationsSupport = false;    // Must be false to get it not listed on the core 'associations' page     
    
    public static $category_association = true;

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
	public static function getAssociations($typeName, $id)
	{
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
	 * @return  JTable|null
	 *
	 * @since   3.7.0
	 */
	public function getItem($typeName, $id)
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
    
    function getAssociationsForItem($id = 0, $view = null){
        return;
    }
    
}
