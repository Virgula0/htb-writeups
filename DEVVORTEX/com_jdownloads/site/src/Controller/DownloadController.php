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
 
namespace JDownloads\Component\JDownloads\Site\Controller;

\defined('_JEXEC') or die;

setlocale(LC_ALL, 'C.UTF-8', 'C');

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use JLoader;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\JDownloader;
use JDownloads\Component\JDownloads\Site\Model\DownloadModel;

/**
*       
 */
class DownloadController extends FormController
{
    protected $items;
    protected $params;
    protected $state;
    protected $user;
    protected $user_rules;    
    
    /**
     * The URL view item variable.
     *
     * @var    string
     * @since  1.6
     */
    protected $view_item = 'form';

    /**
     * The URL view list variable.
     *
     * @var    string
     * @since  1.6
     */
    protected $view_list = 'categories';

    /**
     * The URL edit variable.
     *
     * @var    string
     * @since  3.2
     */
    protected $urlVar = 'a.id';

	/**
	 * Method to add a new record.
	 *
	 * @return  mixed  True if the record can be added, an error object if not.
	 */
	public function add()
	{
		if (!parent::add()) {
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());
            
            return;
		}
        
        // Redirect to the edit screen.
        $this->setRedirect(
            Route::_(
                'index.php?option=' . $this->option . '&view=' . $this->view_item . '&a_id=0'
                . $this->getRedirectToItemAppend(), false
            )
        );

        return true;
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param	array	An array of input data.
	 *
	 * @return	boolean
	 */
	protected function allowAdd($data = array())
	{
        // Initialise variables.
		$user		= $this->app->getIdentity();
		$categoryId = ArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');
		$allow		= null;

		if ($categoryId) {
			// If the category has been passed in the data or URL check it.
			$allow	= $user->authorise('core.create', 'com_jdownloads.category.'.$categoryId);
		}

		if ($allow === null) {
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd();
		}
		else {
			return $allow;
		}
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param	array	$data	An array of input data.
	 * @param	string	$key	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Initialise variables.
		$recordId	= (int) isset($data[$key]) ? $data[$key] : 0;
		$user       = $this->app->getIdentity();
		
        // Zero record (id:0), return component edit permission by calling parent controller method
        if (!$recordId)
        {
            return parent::allowEdit($data, $key);
        }

        // Check edit on the record asset (explicit or inherited)
        if ($user->authorise('core.edit', 'com_jdownloads.download.' . $recordId))
        {
            return true;
        }

        // Check edit own on the record asset (explicit or inherited)
        if ($user->authorise('core.edit.own', 'com_jdownloads.download.' . $recordId))
        {
            // Existing record already has an owner, get it
            $record = $this->getModel()->getItem($recordId);

            if (empty($record))
            {
                return false;
            }

            // Grant if current user is owner of the record
            return $user->get('id') == $record->created_by;
        }

        return false;
	} 

	/**
	 * Method to cancel an edit.
	 *
	 * @param	string	$key	The name of the primary key of the URL variable.
	 *
	 * @return	Boolean	True if access level checks pass, false otherwise.
	 */
	public function cancel($key = 'a_id')
	{
		$result = parent::cancel($key);

		$app = Factory::getApplication();

        // Load the parameters.
        $params = $app->getParams();

        $menuitemId = (int) $params->get('redirect_menuitem');
        $lang = '';

        if ($menuitemId > 0)
        {
            $lang = '';
            $item = $app->getMenu()->getItem($menuitemId);

            if (Multilanguage::isEnabled())
            {
                $lang = !is_null($item) && $item->language != '*' ? '&lang=' . $item->language : '';
            }

            // Redirect to the general (redirect_menuitem) user specified return page.
            $redirlink = $item->link . $lang . '&Itemid=' . $menuitemId;
        }
        else
        {   
		    // Redirect to the return page.
            $redirlink = $this->getReturnPage();
        }

        $this->setRedirect(Route::_($redirlink, false));
        
        return $result;
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param	string	$key	The name of the primary key of the URL variable.
	 * @param	string	$urlVar	The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return	Boolean	True if access level check and checkout passes, false otherwise.
	 */
	public function edit($key = null, $urlVar = 'a_id')
	{
		$result = parent::edit($key, $urlVar);

        if (!$result)
        {
            $this->setRedirect(Route::_($this->getReturnPage(), false));
        }

        return $result;
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param	string	$name	The model name. Optional.
	 * @param	string	$prefix	The class prefix. Optional.
	 * @param	array	$config	Configuration array for model. Optional.
	 *
	 * @return	object	The model.
	 *
	 */
	public function getModel($name = 'Form', $prefix = 'Site', $config = array('ignore_request' => true))
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jdownloads/src/Model');
        
        if ($this->task == 'send')
        {
            // We need the send model
            return $model = BaseDatabaseModel::getInstance('Send', 'jdownloads');
            
        } else {
            
            // We need the download model
            return $model = BaseDatabaseModel::getInstance('Form', 'jdownloads');
            
        }
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param	int		$recordId	The primary key id for the item.
	 * @param	string	$urlVar		The name of the URL variable for the id.
	 *
	 * @return	string	The arguments to append to the redirect URL.
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'a_id')
	{
		// Need to override the parent method completely.
        $tmpl   = $this->input->get('tmpl');

        $append = '';

        // Setup redirect info.
        if ($tmpl)
        {
            $append .= '&tmpl=' . $tmpl;
        }

        $append .= '&layout=edit';

        if ($recordId)
        {
            $append .= '&' . $urlVar . '=' . $recordId;
        }

        $itemId = $this->input->getInt('Itemid');
        $return = $this->getReturnPage();
        $catId  = $this->input->getInt('catid');

        if ($itemId)
        {
            $append .= '&Itemid=' . $itemId;
        }

        if ($catId)
        {
            $append .= '&catid=' . $catId;
        }

        if ($return)
        {
            $append .= '&return=' . base64_encode($return);
        }

        return $append;
	}
    
    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param    type    The table type to instantiate
     * @param    string    A prefix for the table class name. Optional.
     * @param    array    Configuration array for model. Optional.
     * @return    JTable    A database object
     * @since    1.6
     */
    public function getTable($type = 'download', $prefix = 'jdownloadsTable', $config = array()) 
    {
        return Table::getInstance($type, $prefix, $config);
    }    

	/**
	 * Get the return URL.
	 *
	 * If a "return" variable has been passed in the request
	 *
	 * @return	string	The return URL.
	 */
	protected function getReturnPage()
	{
        $return = $this->input->get('return', null, 'base64');

        if (empty($return) || !Uri::isInternal(base64_decode($return)))
        {
            return Uri::base();
        }
        else
        {
            return base64_decode($return);
        }
	}

	/**
	 * Method to save a record.
	 *
	 * @param	string	$key	The name of the primary key of the URL variable.
	 * @param	string	$urlVar	The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return	Boolean	True if successful, false otherwise.
	 */
	public function save($key = null, $urlVar = 'a_id')
	{
		$result     = parent::save($key, $urlVar);
        $app        = Factory::getApplication();
        $downloadId = $app->input->getInt('a_id');

        // Load the parameters.
        $params   = $app->getParams();
        $menuitem = (int) $params->get('redirect_menuitem');

        // Check for redirection after submission when creating a new Download only
        if ($menuitem > 0 && $downloadId == 0){
            $lang = '';

            if (Multilanguage::isEnabled()){
                $item = $app->getMenu()->getItem($menuitem);
                $lang = !is_null($item) && $item->language != '*' ? '&lang=' . $item->language : '';
            }

			// If ok, redirect to the return page.
			if ($result){
	                $this->setRedirect(Route::_('index.php?Itemid=' . $menuitem . $lang, false));
            }
        } else {
            // If ok, redirect to the return page.
            if ($result){
                $this->setRedirect(Route::_($this->getReturnPage(), false));
            }
		}

		return $result;
	}
    
    /**
     * Method to delete an assigned file from the download.
     *
     *
     * @return    Boolean    True if successful, false otherwise.
     */
    public function deletefile()
    {
        $app = Factory::getApplication();
        $jinput = Factory::getApplication()->input;
        
        $type   = $jinput->get('type', '');
        $id     = $jinput->get('id', 0, 'int');        
        
        // load the download data
        $data = $this->getModel()->getItem($id);
        
        // check permissions
        if ($data->params->get('access-edit') == true){

            if ($type == 'prev'){
                // delete the preview file
                $result = JDownloadsHelper::deletePreviewFile($id);
            } else {
                // delete the main file
                $result = JDownloadsHelper::deleteFile($id);
            }    
        } else {
            // no permissions - do nothing
            $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            return false;
        } 
        
        // Add a message to the message queue
        if ($result){
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_FILE_DELETED_MSG'), 'message'); 
        } else {
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_FILE_DELETED_MSG_ERROR'), 'error'); 
        }      
        
        // Make sure that the Download is checked in 
        if ($data->checked_out){
            $checked_in = $this->getModel()->checkIn($id);
        }
        
        // Redirect to the download view page.
        $this->setRedirect(Route::_('index.php?option=com_jdownloads&view=download&id='.$id, false));

        return $result;
    } 
    
    /**
     * Method to delete an download.
     *
     *
     * @return    Boolean    True if successful, false otherwise.
     */
    public function delete()
    {
        $jinput = Factory::getApplication()->input;
        $id     = $jinput->get('a_id', 0, 'int');
        
        // Load the backend download model
        BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model');
        $model_download = BaseDatabaseModel::getInstance('Download', 'jdownloads');

        // Load the form model
        $data = $this->getModel()->getItem($id);
        $this->option = 'com_jdownloads';
         
        // Check permissions
        if ($data->params->get('access-delete') == true){

            if ($id > 0){
                $result = $model_download->delete($id);
            }    
            
        } else {
            // No permissions - do nothing
            $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            return false;
        } 
        
        // Add a message to the message queue
        $application = Factory::getApplication();
        if ($result){
            $application->enqueueMessage( Text::_('COM_JDOWNLOADS_DOWNLOAD_DELETED_MSG'), 'message'); 
        } else {
            $application->enqueueMessage( Text::_('COM_JDOWNLOADS_DOWNLOAD_DELETED_MSG_ERROR'), 'error'); 
        }      
        
        // Redirect to the download view page.
        //$this->setRedirect(Route::_('index.php?option=com_jdownloads&view=downloads'));
        $this->setRedirect(Route::_($this->getReturnPage(), false));

        return $result;
    }
    
    /**
     * Method to submit the downloads file to the browser.
     *
     *
     * @return    null
     */
    public function send()
    {
        $app    = Factory::getApplication();
        $db     = Factory::getDBO();
        $jinput = $app->input;
        $params = $app->getParams();
        $user   = Factory::getUser();
        $groups = implode (',', $user->getAuthorisedViewLevels());
        $user_rules = JDHelper::getUserRules();            
        
        $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;
        
        $active = $app->getMenu()->getActive();
        if ($active) {
            $current_link = $active->link;
        } else {
            $current_link = Route::_(Uri::current().'?option=com_jdownloads');
        }
        
        // Downloader Class is required for file send handling.
        $classname = 'JDownloads\Component\JDownloads\Site\Helper\JDownloader';
        if (!class_exists($classname))
        {
            $path = JPATH_SITE . '/components/com_jdownloads/src/Helper/JDownloader.php';
            
            if (is_file($path)){
                include_once $path;
                JLoader::register($classname, $path);
            } else {
                $msg = Text::_('COM_JDOWNLOADS_FILE_NOT_FOUND').': Helper/JDownloader.php';
                $app->enqueueMessage(Text::_($msg), 'notice');
                $app->redirect(Route::_($current_link, false));
            }
        }

        
        $config = array('ignore_request' => true);        
        $model = $this->getModel('Send', 'Model', $config);
        
        clearstatcache();
        
        $active = $app->getMenu()->getActive();
        if ($active) {
            $current_link = $active->link;
        } else {
            $current_link = Route::_(Uri::current().'?option=com_jdownloads');
        } 
        
        // Abort if the frontend download area is in maintenance mode (not online) 
        if ($params->get('offline')) {
            $msg = JDHelper::getOnlyLanguageSubstring($params->get('offline_text'));
            $app->enqueueMessage(Text::_($msg), 'notice');
            $app->redirect(Route::_($current_link, false));
        }    

        $allow        = false;
        $extern       = false;
        $extern_site  = false;
        $can_download = false;
        $aup_exist    = false;  
        $altaup_exist = false;
        $profile       = '';
        
        // Which file types shall be viewed in browser 
        $view_types = array();
        $view_types = explode(',', $params->get('file_types_view')); 

        // Get request data
        $catid     = $db->escape($jinput->get('catid', 0, 'int'));
        $fileid    = $db->escape($jinput->get('id', 0, 'int'));
        $mirror     = $db->escape($jinput->get('m', 0, 'int'));
        
        // Make sure that we have only a valid comma seperated integer value list when we use 'mass download' feature
        $files_rawlist = $db->escape($jinput->get('list', '', 'string'));
        $files_arraylist = ArrayHelper::toInteger(explode(',', $files_rawlist));
        $files_arraylist = array_filter($files_arraylist);
        $files_list = implode(',', $files_arraylist);
        
        $zip_file   = $db->escape($jinput->get('user', 0, 'cmd'));
        
        $itemid     = $db->escape($jinput->get('Itemid', 0, 'int'));
        
        // Get session data
        $stored_random_id   = (int)JDHelper::getSessionDecoded('jd_random_id');
        $stored_file_id     = (int)JDHelper::getSessionDecoded('jd_fileid');
        $stored_cat_id      = (int)JDHelper::getSessionDecoded('jd_catid');
        $stored_files_list  = JDHelper::getSessionDecoded('jd_list');
        $stored_survey         = (int)JDHelper::getSessionDecoded('jd_survey_form_send');
        $stored_survey_catid   = JDHelper::getSessionDecoded('jd_survey_cat_id');
        $stored_survey_file_id = JDHelper::getSessionDecoded('jd_survey_file_id');

        // Compare and check it 
        if (($catid > 0 && $catid != $stored_cat_id) || ($fileid > 0 && $fileid != $stored_file_id) || ($zip_file > 0 && $zip_file != $stored_random_id) || ($files_list != '' && $files_list != $stored_files_list)){
            if ($fileid){
                // Perhaps use it a direct download option
                $this->items = $model->getItems($fileid);
                if ($this->items){
                    $this->state = $model->getState();
                    
                    $sum_selected_files   = $this->state->get('sum_selected_files');
                    $sum_selected_volume  = $this->state->get('sum_selected_volume');
                    $sum_files_prices     = $this->state->get('sum_files_prices');
                    $must_confirm_license = $this->state->get('must_confirm_license');
                    $directlink           = $this->state->get('directlink_used');
                    $marked_files_id      = $this->state->get('download.marked_files.id');
                    
                    // Check the permission access for direct download option
                    $within_the_user_limits = JDHelper::checkDirectDownloadLimits($catid, $fileid, $files_list, $user_rules, $sum_selected_files, $sum_selected_volume);
                    
                    if ($within_the_user_limits !== true){
                        // User has his limits reached or not enough points 
                        $msg = Text::_($within_the_user_limits);
                        
                        $app->enqueueMessage(Text::_($within_the_user_limits), 'notice');
                        $app->redirect(Route::_($current_link, false));
                    }
                } else {
                    // Invalid data found / Maybe URL manipulation?
                    $app->enqueueMessage(Text::_('COM_JDOWNLOADS_INVALID_DOWNLOAD_DATA_MSG'), 'notice');
                    $app->redirect(Route::_($current_link, false));
                }
            } else {
                // Invalid data or user has not really the access 
                $app->enqueueMessage(Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_ALLOWED_MSG'), 'error');
                $app->redirect(Route::_($current_link, false));
            }              
        }
        
        // Check leeching
        if ($is_leeching = JDHelper::useAntiLeeching()){
            // Download stopped - view hint
            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_ANTILEECH_MSG').' '.Text::_('COM_JDOWNLOADS_ANTILEECH_MSG2'), 'notice');
            $app->redirect(Route::_($current_link, false));
        }
        
        // Check whether it is activated the customers survey option
        if ($user_rules->view_inquiry_form){
            // Already processed for the requested item?  
            if (!$stored_survey || ($stored_survey && $stored_survey_catid != $catid) || ($stored_survey && $stored_survey_file_id != $fileid)){
                // No - so view the form
                $root_url = Uri::base();
                $survey_url = Route::_($root_url.'index.php?option=com_jdownloads&view=survey&id='.$fileid.'&catid='.$catid.'&Itemid='.$itemid);
                $app->redirect($survey_url);                                                                
            }
        }
                
        if ($zip_file){
            // User has selected more as a single file
            $zip_file = $params->get('zipfile_prefix').$zip_file.'.zip';
            $filename  = $params->get('files_uploaddir').'/'.$params->get('tempzipfiles_folder_name').'/'.$zip_file;
            
            if (!file_exists($filename)){
                // Download stopped - zip file not found
                $app->enqueueMessage(Text::_('COM_JDOWNLOADS_FILE_NOT_FOUND'), 'notice');
                $app->redirect(Route::_($current_link, false));
            }
        }

        //  Has the current user the permissions to download this file? Check in Category and Download data / The special single user access field option is later a part in the DB query 
        if ($catid > 1) {
            // If the category has been passed in the data or URL check it.
            $allow = $user->authorise('download', 'com_jdownloads.category.'.$catid);
            if ($fileid && $allow){
                // If the category has been passed in the data or URL check it.
                $allow = $user->authorise('download', 'com_jdownloads.download.'.$fileid);
            }            
        } else {
            if ($fileid){
                // If the category has been passed in the data or URL check it.
                $allow = $user->authorise('download', 'com_jdownloads.download.'.$fileid);
            }            
        }
        
        if (!$allow){
            // Download stopped - user has not the right to download it
            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_ALLOWED_MSG'), 'notice');
            $app->redirect(Route::_($current_link, false));
        }
        
        $transfer_speed = (int)$user_rules->transfer_speed_limit_kb;
        
        if ($params->get('use_alphauserpoints')){
            
            // Get AUP user info
            
            // Marked as deprecated - only altauserpoints will be supported in the future
            // #####################
            $api_AUP = JPATH_SITE.'/components/com_alphauserpoints/helper.php';
            
            if (file_exists($api_AUP)){
                require_once ($api_AUP);
                $aup_exist = true;
                // Get user profile data from AUP
                $profile = AlphaUserPointsHelper::getUserInfo('', $user->id);

                // Get standard points value from AUP
                $db->setQuery("SELECT points FROM #__alpha_userpoints_rules WHERE published = 1 AND plugin_function = 'plgaup_jdownloads_user_download'");
                $aup_fix_points = floatval($db->loadResult());
                //$aup_fix_points = JDHelper::strToNumber($aup_fix_points);
            } else {
                // Get AltaUP user info
                $api_AUP = JPATH_SITE.'/components/com_altauserpoints/helper.php';
                
                if (file_exists($api_AUP)){
                    require_once ($api_AUP);
                    $altaup_exist = true;
                    // get user profile data from AUP
                    $profile = AltaUserPointsHelper::getUserInfo('', $user->id);

                    // get standard points value from AUP
                    $db->setQuery("SELECT points FROM #__alpha_userpoints_rules WHERE published = 1 AND plugin_function = 'plgaup_jdownloads_user_download'");
                    $aup_fix_points = floatval($db->loadResult());
                    //$aup_fix_points = JDHelper::strToNumber($aup_fix_points);                
                }
            }
        }    

        // Build an array with IDs
        $files_arr = explode(',', $files_list);
        
        // Get the files data for multi or single download
        $query = $db->getQuery(true);
        $query->select('a.*');
        $query->from('#__jdownloads_files AS a');
        
        // Join on category table.
        $query->select('c.title AS category_title, c.id AS category_id, c.cat_dir AS category_cat_dir, c.cat_dir_parent AS category_cat_dir_parent');
        $query->join('LEFT', '#__jdownloads_categories AS c on c.id = a.catid');
        
        // Join on license table.
        $query->select('l.title AS license_title');
        $query->join('LEFT', '#__jdownloads_licenses AS l on l.id = a.license');
        
        $query->where('(a.published = '.$db->Quote('1').')');
        if ($files_list){
            $query->where('a.id IN (' .$files_list.')');
        } else {
            $query->where('a.id = '.$db->Quote($fileid));
        }    

        // Filter by access level (and by user_access field) so when we get not a result this user has not the access to view (or download) it 
        if ($user->id > 0){
            // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
            if ($is_admin){
                // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access != 0))');
                $query->where('c.access IN ('.$groups.')');
            } else {
                $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access = '.$db->quote($user->id). '))');
                $query->where('c.access IN ('.$groups.')');
            }
        } else {    
            $query->where('a.access IN ('.$groups.') AND a.user_access = 0');
            $query->where('c.access IN ('.$groups.')');
        }

        $db->setQuery($query);
        $files = $db->loadObjectList();

        if (!$files){
            // Invalid data or user has not really the access 
            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_ALLOWED_MSG'), 'error');
            $app->redirect(Route::_($current_link, false));
        }            

        // When we have a regged user, we must check whether he downloads the file in parts.
        // If so, we may only once write the download data in log and compute the AUP points etc.
        $download_in_parts = JDHelper::getLastDownloadActivity($user->id, $files_list, $fileid, $user_rules->download_limit_after_this_time);
            
        if (count($files) > 1){
            
            // Mass download
            if (!$download_in_parts){            
                // Add AUP points
                if ($params->get('use_alphauserpoints')){
                    if ($params->get('use_alphauserpoints_with_price_field')){
                        $db->setQuery("SELECT SUM(price) FROM #__jdownloads_files WHERE id IN ($files_list)");
                        $sum_points = (int)$db->loadResult();
                        if ($profile->points >= $sum_points){
                            foreach($files as $aup_data){
                                $db->setQuery("SELECT price FROM #__jdownloads_files WHERE id = '$aup_data->id'");
                                if ($price = floatval($db->loadResult())){
                                    $can_download = JDHelper::setAUPPointsDownloads($user->id, $aup_data->title, $aup_data->id, $price, $profile);
                                }                                                   
                            }
                        }
                    } else {
                        // Use fix points
                        $sum_points = $aup_fix_points * count($files_arr);
                        if ($profile->points >= $sum_points){
                            foreach($files as $aup_data){
                                $can_download = JDHelper::setAUPPointsDownloads($user->id, $aup_data->title, $aup_data->id, 0, $profile);
                            }
                        } else {
                            $can_download = false;
                        }    
                    }
                } else {
                    // No AUP active
                    $can_download = true;
                }
                if ($params->get('user_can_download_file_when_zero_points') && !$user->guest){
                    $can_download = true;
                }
            } else {
                $can_download = true;
            }        
        
        } else {

            // Single file download           

            // We must be ensure that the user cannot skiped special options or settings
            // Check at first the password option
            if ($files[0]->password_md5 != ''){
                // Captcha is activated for this user
                $session_result = (int)JDHelper::getSessionDecoded('jd_password_run');
                if ($session_result < 2){
                    // Abort !!!
                    $app->enqueueMessage(Text::_('COM_JDOWNLOADS_ANTILEECH_MSG'), 'error');
                    $app->redirect(Route::_($current_link ,false));
                } else {
                    JDHelper::writeSessionEncoded('0', 'jd_password_run');
                }
            } else {
                // when is not use a password,  we must check captcha
                if ($user_rules->view_captcha){
                    // captcha is activated for this user
                    $session_result = (int)JDHelper::getSessionDecoded('jd_captcha_run');
                    if ($session_result < 2){
                        // Abort !!!
                        $app->enqueueMessage(Text::_('COM_JDOWNLOADS_ANTILEECH_MSG'), 'error');
                        $app->redirect(Route::_($current_link, false));
                    } else {
                        JDHelper::writeSessionEncoded('0', 'jd_captcha_run');
                    }
                }
            }              
            
           if (!$mirror){
               
               if ($files[0]->url_download){
                   // Build the complete category path
                   if ($files[0]->catid > 1){
                       // Download has a category
                       if ($files[0]->category_cat_dir_parent != ''){
                           $cat_dir = $files[0]->category_cat_dir_parent.'/'.$files[0]->category_cat_dir;
                       } else {
                           $cat_dir = $files[0]->category_cat_dir;
                       }               
                       
                       $filename        = $params->get('files_uploaddir').'/'.$cat_dir.'/'.$files[0]->url_download;
                       $filename_direct = $params->get('files_uploaddir').'/'.$cat_dir.'/'.$files[0]->url_download;        
                   }     
               
               } elseif ($files[0]->other_file_id) {
                            // A file from another Download was assigned         
                            $query = $db->getQuery(true);
                            $query->select('a.*');
                            $query->from('#__jdownloads_files AS a');
                            
                            // Join on category table.
                            $query->select('c.id AS category_id, c.cat_dir AS category_cat_dir, c.cat_dir_parent AS category_cat_dir_parent');
                            $query->join('LEFT', '#__jdownloads_categories AS c on c.id = a.catid');
                            $query->where('a.published = '.$db->Quote('1'));
                            $query->where('a.id = '.$db->Quote($files[0]->other_file_id));
                            $query->where('a.access IN ('.$groups.')');
                            $db->setQuery($query);
                            $other_file_data = $db->loadObject();
                            
                            if ($other_file_data->catid > 1){
                                // The assigned Download has a category
                                if ($other_file_data->category_cat_dir_parent != ''){
                                    $cat_dir = $other_file_data->category_cat_dir_parent.'/'.$other_file_data->category_cat_dir;
                               } else {
                                    $cat_dir = $other_file_data->category_cat_dir;
                               }               
                               $filename        = $params->get('files_uploaddir').'/'.$cat_dir.'/'.$other_file_data->url_download;
                               $filename_direct = $params->get('files_uploaddir').'/'.$cat_dir.'/'.$other_file_data->url_download;
                            } else {
                               // Download is 'uncategorized'
                               $filename = $params->get('files_uploaddir').'/'.$params->get('uncategorised_files_folder_name').'/'.$other_file_data->url_download;
                            }                   
                            
                            $files[0]->other_file_name = $other_file_data->url_download;
                            $files[0]->other_file_size = $other_file_data->size;
                            
               } else {
                   $filename = $files[0]->extern_file; 
                   if ($files[0]->extern_site){
                       $extern_site = true;
                   }
                   $extern = true;
               }
           } else {
             // Is mirror 
             if ($mirror == 1){
                 $filename = $files[0]->mirror_1; 
                 if ($files[0]->extern_site_mirror_1){
                     $extern_site = true;
                 }
             } else {
                 $filename = $files[0]->mirror_2; 
                 if ($files[0]->extern_site_mirror_2){
                     $extern_site = true;
                 }
             }
             $extern = true;    
           }      

           $price = '';
           
           // Is AUP rule or price option used - we need the price for it
           if ($aup_exist || $altaup_exist){
               if ($params->get('use_alphauserpoints_with_price_field')){
                   $price = floatval($files[0]->price);
               } else {
                   $price = $aup_fix_points;
               }        
           }    
            
           if (!$download_in_parts){
               $can_download = JDHelper::setAUPPointsDownload($user->id, $files[0]->title, $files[0]->id, $price, $profile);
           
               if ($params->get('user_can_download_file_when_zero_points') && $user->id){
                   $can_download = true;
               }
           } else {
               $can_download = true;
           }        
        }    
        
        // Plugin support 
        // Load external plugins
        PluginHelper::importPlugin('jdownloads');
        $results = Factory::getApplication()->triggerEvent('onBeforeDownloadIsSendJD', array(&$files, &$can_download, $user_rules, $download_in_parts));
        
        if (!$can_download){
            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_BACKEND_SET_AUP_FE_MESSAGE_NO_DOWNLOAD'), 'notice');
            $app->redirect(Route::_($current_link));
        } else {
            // Run download            
            if (!$download_in_parts){
                // Send at first e-mail
                if ($params->get('send_mailto_option') == '1' && $files){
                    JDHelper::sendMailDownload($files);               
                }

                // Give uploader AUP points when is set on
                if ($params->get('use_alphauserpoints')){
                    if ($params->get('use_alphauserpoints_with_price_field')){
                        JDHelper::setAUPPointsDownloaderToUploaderPrice($files);
                    } else {    
                        JDHelper::setAUPPointsDownloaderToUploader($files);
                    }
                      
                }

                // Write data in log 
                if ($params->get('activate_download_log')){
                    JDHelper::updateLog($type = 1, $files, '');  
                }                
            
                // Update downloads hits
                if (count($files) > 1){
                    $db->setQuery('UPDATE #__jdownloads_files SET downloads=downloads+1 WHERE id IN ('.$files_list.')'); 
                    $db->execute();    
                } else {
                    $db->setQuery("UPDATE #__jdownloads_files SET downloads=downloads+1 WHERE id = '".$files[0]->id."'");
                    $db->execute();
                }
            }
                
            // Get filesize
            if (!$extern){
                if (!file_exists($filename)) { 
                    $app->enqueueMessage(Text::_('COM_JDOWNLOADS_FILE_NOT_FOUND').': '.basename($filename), 'notice');
                    $app->redirect(Route::_($current_link, false));
                } else {
                    $size = filesize($filename);
                }    
            } else {   
                 $size = JDHelper::getUrlFilesize($filename);
            }
            
            // If url go to other website - open it in a new browser window
            if ($extern_site){
                echo "<script>document.location.href='$filename';</script>\n";  
                exit;   
            }    
            
            // If set the option for direct link to the file
            if (!$params->get('use_php_script_for_download')){
                
                $root = str_replace('\\', '/', $_SERVER["DOCUMENT_ROOT"]);
                $root = rtrim($root, "/");               
                $host = $_SERVER["HTTP_HOST"];                
                
                // Alternate when symlink are used (like "Strato")
                $joomla_host = URI::root();
                $joomla_root = JPATH_ROOT.'/';
                $joomla_root = str_replace('\\', '/', $joomla_root);
                
                if (strpos($filename_direct, $root) !== false ){
                    $filename_direct = str_replace($root, $host, $filename_direct);
                } else {
                    $filename_direct = str_replace($joomla_root, $joomla_host, $filename_direct);
                }   
                    
                if (strpos($filename_direct, 'http://') === false && strpos($filename_direct, 'https://') === false && strpos($filename_direct, 'ftp://') === false){
                    //$filename_direct = str_replace('//', '/', $filename_direct);
                    $filename_direct = 'http://'.$filename_direct;
                }
                
                $app->redirect($filename_direct);

            } else {    
                $only_filename = basename($filename);
                $extension = JDHelper::getFileExtension($only_filename);
                if ($extern){
                    $mime_type = JDHelper::getMimeTypeRemote($filename);
                } else {
                    $mime_type = JDHelper::getMimeTyp($extension);
                }
                
                // Check for protocol and set the appropriate headers
                $use_ssl  = false;
                $uri      = Uri::getInstance(Uri::current());
                $protocol = $uri->getScheme();
                if ($protocol == 'https'){ 
                    $use_ssl = true;
                }
                
                $open_in_browser = false;
                if (in_array($extension, $view_types)){
                    // view file in browser
                    $open_in_browser = true;
                }                    
                
                clearstatcache();
                
                // Do clean up the output buffer
                while (ob_get_level() > 0)
                    @ob_end_clean();                     
               
                if ($extern){                

                    // needed for MS IE - otherwise content disposition is not used?
                    if (ini_get('zlib.output_compression')){
                        ini_set('zlib.output_compression', 'Off');
                    }
                    
                    header("Cache-Control: public, must-revalidate");
                    header('Cache-Control: pre-check=0, post-check=0, max-age=0');
                    header("Expires: 0"); 
                    header("Content-Description: File Transfer");
                    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
                    header("Content-Type: " . $mime_type);
                    header("Content-Length: ".(string)$size);
                    if (!$open_in_browser){
                        header('Content-Disposition: attachment; filename="'.$only_filename.'"');
                    } else {
                      // view file in browser
                      header('Content-Disposition: inline; filename="'.$only_filename.'"');
                    }   
                    header("Content-Transfer-Encoding: binary\n");
                    // redirect to category when it is set the time
                    if (intval($params->get('redirect_after_download')) > 0){ 
                        header( "refresh:".$params->get('redirect_after_download')."; url=".$current_link );
                    }    
                    
                    // set_time_limit doesn't work in safe mode
                    if (!ini_get('safe_mode')){ 
                        @set_time_limit(0);
                    }
                    @readfile($filename);
                    flush();
                    exit;
                    
                } else {    
                    
                         $object = new JDownloader;
                         $object->set_byfile($filename);              // Type: Download from a file
                         $object->set_filename($only_filename);       // Set the file basename
                         $object->set_filesize($size);                // Set the file basename
                         $object->set_mime($mime_type);               // Set the mime type
                         $object->set_speed($transfer_speed);         // Set download speed 
                         $object->set_refresh($current_link, (int)$params->get('redirect_after_download')); // Redirect to category when it is set the time in configuration
                         $object->use_resume      = true;             // Set the value for using Resume Mode
                         $object->use_ssl         = $use_ssl;         // Set support for SSL
                         $object->open_in_browser = $open_in_browser; // Set whether the file shall be opened in browser window
                         $object->use_autoexit    = true;             // Set the value for auto exit  ('false' worked not really with extern file?)
                         $object->download();                         // Run the download
                         flush();
                         exit;
               }    
           }
        }    
    }    
}
?>