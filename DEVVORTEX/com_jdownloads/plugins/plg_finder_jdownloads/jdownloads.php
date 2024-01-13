<?php
/**
 * @package     Joomla.Plugin
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * @extension jDownloads 
 * @copyright (C) Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;

use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;

use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Administrator\Table\DownloadTable;

Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_jdownloads/src/Table');

/**
 * Smart Search adapter for com_jdownloads
 */
class PlgFinderJdownloads extends Adapter
{
	protected $context = 'Jdownloads';

	protected $extension = 'com_jdownloads';

	protected $layout = 'download';

	protected $type_title = 'Download';

	protected $table = '#__jdownloads_files';

    protected $state_field = 'published';

	protected $autoloadLanguage = true;

	/**
	 * Method to update the item link information when the item category is
	 * changed. This is fired when the item category is published or unpublished
	 * from the list view.
	 *
	 * @param   string   $extension  The extension whose category has been updated.
	 * @param   array    $pks        A list of primary key ids of the content that has changed state.
	 * @param   integer  $value      The value of the state that the content has been changed to.
	 *
	 * @return  void
	 *
	 */
	public function onFinderCategoryChangeState($extension, $pks, $value)
	{
		if ($extension === 'com_jdownloads.category'){
			$this->categoryStateChangeJD($pks, $value);
		}
	}

	/**
	 * Method to remove the link information for items that have been deleted.
	 *
	 * @param   string  $context  The context of the action being performed.
	 * @param   JTable  $table    A JTable object containing the record to be deleted
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  Exception on database error.
	 */
	public function onFinderAfterDelete($context, $table)
	{
		if ($context === 'com_jdownloads.download'){
			$id = $table->id;
		} elseif ($context === 'com_finder.index'){
			$id = $table->link_id;
		} else {
			return true;
		}

		// Remove item from the index.
		return $this->remove($id);
	}

	/**
	 * Smart Search after save content method.
	 * Reindexes the link information for an Download that has been saved.
	 * It also makes adjustments if the access level of an item or the
	 * category to which it belongs has changed.
	 *
	 * @param   string   $context  The context of the content passed to the plugin.
	 * @param   JTable   $row      A JTable object.
	 * @param   boolean  $isNew    True if the content has just been created.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  Exception on database error.
	 */
	public function onFinderAfterSave($context, $row, $isNew)
	{
		// We only want to handle Downloads here. But note that we must handle also frontend editing here.
		if ($context === 'com_jdownloads.download' || $context === 'com_jdownloads.form'){
			// Check if the access levels are different.
			if (!$isNew && $this->old_access != $row->access){
				// Process the change.
				$this->itemAccessChange($row);
			}

			// Reindex the item.
			$this->reindex($row->id);
		}

		// Check for access changes in the category.
		if ($context === 'com_jdownloads.category'){
			// Check if the access levels are different.
			if (!$isNew && $this->old_cataccess != $row->access){
				$this->categoryAccessChange($row);
			}
		}

		return true;
	}

	/**
	 * Smart Search before content save method.
	 * This event is fired before the data is actually saved.
	 *
	 * @param   string   $context  The context of the content passed to the plugin.
	 * @param   JTable   $row      A JTable object.
	 * @param   boolean  $isNew    If the content is just about to be created.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  Exception on database error.
	 */
	public function onFinderBeforeSave($context, $row, $isNew)
	{
		// We only want to handle Downloads here.
		if ($context === 'com_jdownloads.download' || $context === 'com_jdownloads.form'){
			// Query the database for the old access level if the item isn't new.
			if (!$isNew){
				$this->checkItemAccess($row);
			}
		}

		// Check for access levels from the category.
		if ($context === 'com_jdownloads.category'){
			// Query the database for the old access level if the item isn't new.
			if (!$isNew){
				$this->checkCategoryAccessJD($row);
			}
		}

		return true;
	}

	/**
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished from the list view.
	 *
	 * @param   string   $context  The context for the content passed to the plugin.
	 * @param   array    $pks      An array of primary key ids of the content that has changed state.
	 * @param   integer  $value    The value of the state that the content has been changed to.
	 *
	 * @return  void
	 *
	 */
	public function onFinderChangeState($context, $pks, $value)
	{
		if ($context === 'com_jdownloads.download' || $context === 'com_jdownloads.form'){
			$this->itemStateChange($pks, $value);
		}

		// Handle when the plugin is disabled.
		if ($context === 'com_plugins.plugin' && $value === 0){
			$this->pluginDisable($pks);
		}
	}

	/**
	 * Method to index an item. The item must be a FinderIndexerResult object.
	 *
	 * @param   FinderIndexerResult  $item    The item to index as a FinderIndexerResult object.
	 * @param   string               $format  The item format.  Not used.
	 *
	 * @return  void
	 *
	 * @throws  Exception on database error.
	 */
	protected function index(Result $item)
	{
        $item->setLanguage();
        
		// Check if the extension is enabled.
		if (ComponentHelper::isEnabled($this->extension) === false){
			return;
		}

        $item->context = 'com_jdownloads.download';

		// Initialise the item parameters.
		$registry = new Registry($item->params);
		$item->params = ComponentHelper::getParams('com_jdownloads', true);
		$item->params->merge($registry);

		$item->metadata = new Registry($item->metadata);

		// Trigger the onContentPrepare event.
        $item->summary = self::prepareContentJD($item->summary, $item->params, $item);
        $item->body    = self::prepareContentJD($item->body, $item->params, $item);

		// Create a URL as identifier to recognise items again.
        $item->url = $this->getUrl($item->id, $this->extension, $this->layout);
		
        // Build the necessary route and path information.
        $item->route = RouteHelper::getDownloadRoute($item->slug, $item->catid, $item->language);
		
		// Get the menu title if it exists.
		$title = $this->getItemMenuTitle($item->url);

		// Adjust the title if necessary.
		if (!empty($title) && $this->params->get('use_menu_title', true)){
			$item->title = $title;
		}

		// Add the meta author.
		$item->metaauthor = $item->metadata->get('author');

		// Add the metadata processing instructions.
		$item->addInstruction(Indexer::META_CONTEXT, 'metakey');
		$item->addInstruction(Indexer::META_CONTEXT, 'metadesc');
		$item->addInstruction(Indexer::META_CONTEXT, 'author');

		// Translate the state. Downloads should only be published if the category is published.
		$item->state = $this->translateState($item->state, $item->cat_state);

		// Add the type taxonomy data.
		$item->addTaxonomy('Type', 'Download');

		// Add the author taxonomy data.
		if (!empty($item->author)){
			$item->addTaxonomy('Author', $item->author);
		}

		// Add the category taxonomy data.
        $categories = CategoriesHelper::getInstance('com_jdownloads', ['published' => false, 'access' => false]);
        $category   = $categories->get($item->catid);

        // Category does not exist, stop here
        if (!$category)
        {
            return;
        }
        
        //$item->addNestedTaxonomy('Category', $category, $this->translateState($category->published), $category->access, $category->language);
        
        // Add the category taxonomy data.
		$item->addTaxonomy('Category', $item->category, $item->cat_state, $item->cat_access);

		// Add the language taxonomy data.
		$item->addTaxonomy('Language', $item->language);

		// Get content extras.
		Helper::getContentExtras($item);

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to setup the indexer to be run.
	 *
	 * @return  boolean  True on success.
	 *
	 */
	protected function setup()
	{
		return true;
	}

	/**
	 * Method to get the SQL query used to retrieve the list of Download items.
	 *
	 * @param   mixed  $query  A JDatabaseQuery object or null.
	 *
	 * @return  JDatabaseQuery  A database object.
	 *
	 */
	protected function getListQuery($query = null)
	{
		$db = Factory::getDbo();

		// Check if we can use the supplied SQL query.
		$query = $query instanceof DatabaseQuery ? $query : $db->getQuery(true)
			->select('a.id, a.title, a.alias, a.description AS summary, a.description_long AS body')
			->select('a.images')
			->select('a.published AS state, a.catid, a.created AS start_date, a.created_by')
			->select('a.modified, a.modified_by AS params')
			->select('a.metakey, a.metadesc, a.language, a.access, a.user_access, a.release, a.ordering')
			->select('a.publish_up AS publish_start_date, a.publish_down AS publish_end_date')
			->select('c.title AS category, c.published AS cat_state, c.access AS cat_access');

		// Handle the alias CASE WHEN portion of the query
		$case_when_item_alias = ' CASE WHEN ';
		$case_when_item_alias .= $query->charLength('a.alias', '!=', '0');
		$case_when_item_alias .= ' THEN ';
		$a_id = $query->castAsChar('a.id');
		$case_when_item_alias .= $query->concatenate(array($a_id, 'a.alias'), ':');
		$case_when_item_alias .= ' ELSE ';
		$case_when_item_alias .= $a_id . ' END as slug';
		$query->select($case_when_item_alias);

		$case_when_category_alias = ' CASE WHEN ';
		$case_when_category_alias .= $query->charLength('c.alias', '!=', '0');
		$case_when_category_alias .= ' THEN ';
		$c_id = $query->castAsChar('c.id');
		$case_when_category_alias .= $query->concatenate(array($c_id, 'c.alias'), ':');
		$case_when_category_alias .= ' ELSE ';
		$case_when_category_alias .= $c_id . ' END as catslug';
		$query->select($case_when_category_alias)

			->select('u.name AS author')
			->from('#__jdownloads_files AS a')
			->join('LEFT', '#__jdownloads_categories AS c ON c.id = a.catid')
			->join('LEFT', '#__users AS u ON u.id = a.created_by');

		return $query;
	}
    
    protected function categoryStateChangeJD($pks, $value)
    {
        /*
         * The item's published state is tied to the category
         * published state so we need to look up all published states
         * before we change anything.
         */
        foreach ($pks as $pk){
            
            $query = $this->db->getQuery(true);

            // Item ID
            $query->select('a.id');

            // Item and category published state
            $query->select('a.published AS state, c.published AS cat_state');

            // Item and category access levels
            $query->select('a.access, a.user_access, c.access AS cat_access')
                ->from('#__jdownloads_files AS a')
                ->join('LEFT', '#__jdownloads_categories AS c ON c.id = a.catid');
            
            $query->where('c.id = ' . (int) $pk);

            // Get the published states.
            $this->db->setQuery($query);
            $items = $this->db->loadObjectList();

            // Adjust the state for each item within the category.
            foreach ($items as $item){
                // Translate the state.
                $temp = $this->translateState($item->state, $value);

                // Update the item.
                $this->change($item->id, 'state', $temp);

                // Reindex the item
                $this->reindex($item->id);
            }
        }
    }
    
    /**
     * Method to update index data on category access level changes
     *
     * @param   JTable  $row  A JTable object
     *
     * @return  void
     *
     */
    protected function categoryAccessChangeJD($row)
    {
        $query = clone $this->getStateQuery();
        $query->where('c.id = ' . (int) $row->id);

        // Get the access level.
        $this->db->setQuery($query);
        $items = $this->db->loadObjectList();

        // Adjust the access level for each item within the category.
        foreach ($items as $item){
            // Set the access level.
            $temp = max($item->access, $row->access);

            // Update the item.
            $this->change((int) $item->id, 'access', $temp);

            // Reindex the item
            $this->reindex($row->id);
        }
    }
    
    /**
     * Method to check the existing access level for categories
     *
     * @param   JTable  $row  A JTable object
     *
     * @return  void
     *
     */
    protected function checkCategoryAccessJD($row)
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('access'))
            ->from($this->db->quoteName('#__jdownloads_categories'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $row->id);
        $this->db->setQuery($query);

        // Store the access level to determine if it changes
        $this->old_cataccess = $this->db->loadResult();
    }
    
    public static function prepareContentJD($text, $params = null, Result $item = null)
    {
        static $loaded;

        // Load the content plugins if necessary.
        if (empty($loaded))
        {
            PluginHelper::importPlugin('content');
            $loaded = true;
        }

        // Instantiate the parameter object if necessary.
        if (!($params instanceof Registry))
        {
            $registry = new Registry($params);
            $params = $registry;
        }

        // Create a mock content object.
        $content = Table::getInstance('DownloadTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
        
        if (!$content){
            return '';
        }
        
        $content->text = $text;

        if ($item)
        {
            $content->bind((array) $item);
            $content->bind($item->getElements());
        }

        if ($item && !empty($item->context))
        {
            $content->context = $item->context;
        }

        // Fire the onContentPrepare event.
        Factory::getApplication()->triggerEvent('onContentPrepare', array('com_finder.indexer', &$content, &$params, 0));

        return $content->text;
    }    
}
