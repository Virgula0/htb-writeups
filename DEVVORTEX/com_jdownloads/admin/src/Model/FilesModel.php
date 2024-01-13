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
 
namespace JDownloads\Component\JDownloads\Administrator\Model;

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Pagination\Pagination;


use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

class FilesModel extends ListModel
{
	/**
	 * jDownloads data array
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * jDownloads total amount files
	 *
	 * @var integer
	 */
	var $_total = null;

	/**
	 * Pagination object
	 *
	 * @var object
	 */
	var $_pagination = null;


	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
         
    }   
     
    /**
    * Method to auto-populate the model state.
    *
    * Note. Calling getState in this method will result in recursion.
    *
    */
    protected function populateState($ordering = null, $direction = null)
    {
        // Initialise variables.
        $app = Factory::getApplication('administrator');
        
        // Load the filter state.
        $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Load the parameters.
        $params = ComponentHelper::getParams('com_jdownloads');
        $this->setState('params', $params);

        // List state information.
        $limit = 0;
        
        // Receive & set list options
        $default_limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'), 'uint');
        if ($list = $app->getUserStateFromRequest($this->context . '.list', 'list', array(), 'array')){
            if (isset($list['limit'])){
                $limit = (int)$list['limit'];
            } else {
                $limit = $default_limit;
            }
        } else {
             $limit = $default_limit;
        }
        $this->setState('list.limit', $limit);
         
        $value = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0);
        $limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
        $this->setState('list.start', $limitstart);        
	}

    /**
     * Method to load files data in array
     *
     * @access    public
     * @return    array  An array of results.
     */
    public function getItems()
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $app = Factory::getApplication('administrator');
        $option = 'com_jdownloads';
        
        $lang = $app->getLanguage();
        $lang->load('com_jdownloads', JPATH_ADMINISTRATOR);
        $lang->load('com_jdownloads.sys', JPATH_ADMINISTRATOR);
        
        // Lets load the file data if it doesn't already exist
       if (empty($this->_data))
       {
         // Get all file names from upload root dir       
         $files_dir = $params->get('files_uploaddir').'/';
         $filenames = Folder::files( $params->get('files_uploaddir'), $filter = '.', $recurse = false, $fullpath = false, $exclude = array('index.htm', 'index.html', '.htaccess') ); 
         $files_info = array();
        
         // Build data array for files list
         for ($i=0; $i < count($filenames); $i++)
         {
             $files_info[$i]['id']   = $i+1;
             $files_info[$i]['name'] = $filenames[$i];
             $date_format = JDownloadsHelper::getDateFormat();
             $files_info[$i]['date'] = date($date_format['long'], filemtime($files_dir.$filenames[$i]));               
             $files_info[$i]['size'] = JDownloadsHelper::fsize($files_dir.$filenames[$i]);    
         }
         
         // Search in file names
         $search = $this->getState('filter.search');
         if ($search)
         {
             $search_result = JDownloadsHelper::arrayRegexSearch( '/'.$search.'/i', $files_info, TRUE, TRUE ); 
             
             if ($search_result){
                 foreach ($search_result as $result){
                    $files_info_result[] = $files_info[$result]; 
                 }
                 $files_info = $files_info_result;
             } else {
                 $files_info = array();
             }   
         }  

         // Build pagination data
         $limitstart = $this->getState('list.start');
         $limit      = $this->getState('list.limit');
         $pageNav = new Pagination( count($files_info), $limitstart, $limit );
         $this->_pagination = $pageNav;
         
         if ($limit > 0){
             $items = array_splice ( $files_info, $limitstart, $limit );
         } else {
             $items = array_splice ( $files_info, $limitstart );
         }
         
         $this->_data = $items; 
        }
        return $this->_data;
    }
    
     /**
     * Method to set the pagination value
     *
     * @access    public
     * @return    boolean    True on success
     */
    
    public function getPagination()
    {
        return $this->_pagination;
    }	
	  	

}
