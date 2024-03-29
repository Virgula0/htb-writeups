<?php
/**
 * @package jDownloads
 * @version 4.0
 * @copyright (C) 2007 - 2022 Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 
namespace JDownloads\Component\JDownloads\Site\Model;
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;

/**
 * jDownloads Component Download Model
 *
 */
class SendModel extends ItemModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $_context = 'com_jdownloads.download';
    protected $_items;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('site');
        $jinput = Factory::getApplication()->input;
        
        $f_marked_files_id = $jinput->get('f_marked_files_id', 0, 'string');
        $f_marked_files_id = preg_replace("/[^0-9,]/", '', $f_marked_files_id);
        
        $f_cat_id = $jinput->get('f_cat_id', 0, 'integer');
        
        $marked_files_id = array();

        // Load state from the request.
        
        // Get file id
        $fileid = $jinput->getInt('id', 0);
        
        // Get cat id
        $catid = $jinput->getInt('catid', 0);
        if (!$catid){
            $catid = $f_cat_id;
        }
        
        // Get file id from the marked files - only when is used 'mass download' layout
        $marked_files_id = $jinput->get('cb_arr', 0, 'array');
        
        // Required before submitting the form if captcha is used
        if (!$fileid && !$marked_files_id && $f_marked_files_id){
            $marked_files_id = explode(',', $f_marked_files_id);
        }
        
        // Sanitize
        if ($marked_files_id){
            for ($i=0, $n=count($marked_files_id); $i<$n; $i++){
                $marked_files_id[$i] = intval($marked_files_id[$i]);
            }
        }
        
        // Set selected cat for category select box
        if ($catid){
            $selected_cat_id = $catid;
        }                

        // Is mirror file selected
        $is_mirror = $jinput->getInt('m', '0');
        
		$this->setState('download.id', $fileid);
		$this->setState('download.catid', $catid);
        $this->setState('download.mirror.id', $is_mirror);
        $this->setState('download.marked_files.id', $marked_files_id);
        
        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);        
        
		$user = Factory::getUser();
		if ((!$user->authorise('core.edit.state', 'com_jdownloads')) &&  (!$user->authorise('core.edit', 'com_jdownloads'))){
			$this->setState('filter.published', 1);
		}
		$this->setState('filter.language', Multilanguage::isEnabled());
	}

	/**
	 * Method to get the selected documents data.
	 *
	 * @param	integer	The id of the download.
	 *
	 * @return	mixed	item data object on success, false on failure.
	 */
	public function &getItems($pk = null)
	{
        $app = Factory::getApplication();
        
        // Initialise variables.
        $sum_files_volume       = 0;
        $sum_files_prices       = 0;
        $must_confirm_license   = false;
        $directlink             = false;
        
        $this->__state_set = FALSE;
        
		$pk              = $this->getState('download.id');
        $marked_files_id = $this->getState('download.marked_files.id');
        
        if (!$pk > 0){
            if (count($marked_files_id) > 1){
                $marked_files_id = implode(',', $marked_files_id);
            } else {
               $pk = $marked_files_id[0];  
            }
        } else {
            // user has clicked on download link - not checkbox used
            $directlink = true;
        }
        
        
		if ($this->_items === null) {
			$this->_items = array();
		}

		if (!isset($this->_items[$pk])) {

			try {
				$db = $this->getDbo();
				$query = $db->getQuery(true);

				$query->select($this->getState(
					'item.select', 'a.id, a.asset_id, a.title, a.alias, a.description, a.description_long, a.file_pic, a.images, a.price, a.release, ' .
					'a.file_language, a.system, a.license, a.url_license, a.license_agree, a.size, a.url_download, a.preview_filename, a.other_file_id, a.md5_value, a.sha1_value, ' .
                    'a.extern_file, a.extern_site, a.mirror_1, a.mirror_2, a.extern_site_mirror_1, a.extern_site_mirror_2, a.url_home, a.author, a.url_author, a.created_mail, a.submitted_by, ' .
                    'a.changelog, a.password_md5, a.views, a.update_active, a.published, ' .
                    // If badcats is not null, this means that the download is inside an unpublished category
					// In this case, the state is set to 0 to indicate Unpublished (even if the download state is Published)
					'CASE WHEN badcats.id is null THEN a.published ELSE 0 END AS state, ' .
					'a.catid, a.created, a.created_by, a.file_date, ' .
				    // use created if modified is 0
                    // 'CASE WHEN a.modified = 0 THEN a.created ELSE a.modified END as modified, ' .
                    'a.modified, ' .                    
					'a.publish_up, a.publish_down, a.modified_by, a.checked_out, a.checked_out_time,  ' .
					'a.ordering, a.metakey, a.metadesc, a.robots, a.access, a.downloads, a.language'
					)
				);
				$query->from('#__jdownloads_files AS a');

				// Join on category table.
				$query->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access, c.cat_dir AS category_cat_dir, c.password AS category_password');
				$query->join('LEFT', '#__jdownloads_categories AS c on c.id = a.catid');


                // Join on license table.
                $query->select('l.title AS license_title, l.url AS license_url, l.description AS license_text, l.id as lid');
                $query->join('LEFT', '#__jdownloads_licenses AS l on l.id = a.license');
                

				// Filter by language
				if ($this->getState('filter.language'))
				{
					$query->where('a.language in ('.$db->quote(Factory::getLanguage()->getTag()).','.$db->quote('*').')');
				}

				// Join over the categories to get parent category titles
				$query->select('parent.title as parent_title, parent.id as parent_id, parent.alias as parent_alias');
				$query->join('LEFT', '#__jdownloads_categories as parent ON parent.id = c.parent_id');

                if ($pk > 0){
                    $query->where('a.id = ' . (int) $pk);
                } else {    
                    $query->where('a.id IN ('.$marked_files_id.')'); 
                }    

				// Join to check for category published state in parent categories up the tree
				// If all categories are published, badcats.id will be null, and we just use the download state
				$subquery = ' (SELECT cat.id as id FROM #__jdownloads_categories AS cat JOIN #__jdownloads_categories AS parent ';
				$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
				$subquery .= 'WHERE parent.published <= 0 GROUP BY cat.id)';
				$query->join('LEFT OUTER', $subquery . ' AS badcats ON badcats.id = c.id');

				// Filter by published state.
				$published = $this->getState('filter.published');

				if (is_numeric($published)) {
					$query->where('(a.published = ' . (int) $published.')');
				}

                // get the data 
				$db->setQuery($query);
                $files = $db->loadObjectList();

				if (empty($files)) {
					return $app->enqueueMessage( Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_FOUND'), 'error');
				}

                foreach ($files as $file)
                {
				    // Check for published state if filter set.
				    if ((is_numeric($published)) && ($file->published != $published)) {
					    return $app->enqueueMessage( Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_FOUND'), 'error');
				    }

				    $file->params = clone $this->getState('params');

				    // Compute selected asset permissions.
				    $user	= Factory::getUser();
				    $userId	= $user->get('id');
				    $asset	= 'com_jdownloads.download.'.$file->id;

                    // Check at first the 'download' permission.
                    if ($user->authorise('download', $asset)) {
                        $file->params->set('access-download', true);
                    }

                    // Technically guest could edit a download, but lets not check that to improve performance a little.
                    if (!$user->get('guest')) {
                                            
					    // Check general edit permission first.
					    if ($user->authorise('core.edit', $asset)) {
						    $file->params->set('access-edit', true);
					    }
					    // Now check if edit.own is available.
					    elseif (!empty($userId) && $user->authorise('core.edit.own', $asset)) {
						    // Check for a valid user and that they are the owner.
						    if ($userId == $file->created_by) {
							    $file->params->set('access-edit', true);
						    }
					    }
				    }

				    // Compute view access permissions.
				    if ($access = $this->getState('filter.access')) {
					    // If the access filter has been set, we already know this user can view.
					    $file->params->set('access-view', true);
				    }
				    else {
					    // If no access filter is set, the layout takes some responsibility for display of limited information.
					    $user = Factory::getUser();
					    $groups = $user->getAuthorisedViewLevels();

					    if ($file->catid == 0 || $file->category_access === null) {
						    $file->params->set('access-view', in_array($file->access, $groups));
					    }
					    else {
						    $file->params->set('access-view', in_array($file->access, $groups) && in_array($file->category_access, $groups));
					    }
				    }
                    // we check some data fields and store the calculated values
                    $sum_files_volume += JDHelper::convertFileSizeToKB($file->size);
                    $sum_files_prices += (int) $file->price;
                    if ($file->license && $file->license_agree) $must_confirm_license = true;
                    
                    
                }
				// store the values for the selected files - so we can check it later
                $this->state->sum_selected_volume   = $sum_files_volume;
                $this->state->sum_files_prices      = $sum_files_prices;
                $this->state->must_confirm_license  = $must_confirm_license;
                $this->state->directlink_used       = $directlink;                                
                $this->state->sum_selected_files    = count($files);
                
                $this->_items[$pk] = $files;
			}
			catch (JException $e)
			{
				if ($e->getCode() == 404) {
					// Need to go thru the error handler to allow Redirect to work.
					return $app->enqueueMessage( $e->getMessage(), 'error');
				}
				else {
					$this->setError($e);
					$this->_items[$pk] = false;
				}
			}
		}

		return $this->_items[$pk];
	}
    
    public function getItem($pk = null)
    {
        
    }
}