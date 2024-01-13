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
 
namespace JDownloads\Component\JDownloads\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\IpHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;

/**
 * jDownloads Component Download Model
 *
 */
class DownloadModel extends ItemModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $_context = 'com_jdownloads.download';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('site');
        
		// Load state from the request.
        $pk = $app->input->getInt('id');
		$this->setState('download.id', $pk);

		$offset = $app->input->getUInt('limitstart');
		$this->setState('list.offset', $offset);

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);

		$user = Factory::getUser();
		if ((!$user->authorise('core.edit.state', 'com_jdownloads')) &&  (!$user->authorise('core.edit', 'com_jdownloads')) && (!$user->authorise('core.edit.own', 'com_jdownloads'))){
			$this->setState('filter.published', 1);
		}
        
        $this->setState('filter.access', true);
        
        $this->setState('filter.user_access', true);

		$this->setState('filter.language', Multilanguage::isEnabled());
	}

	/**
	 * Method to get a download data.
	 *
	 * @param	integer	The id of the download.
	 *
	 * @return	mixed	Menu item data object on success, false on failure.
	 */
	public function getItem($pk = null, $plugin = false)
	{
        $app       = Factory::getApplication();
        $params   = $app->getParams('com_jdownloads');
        
        // Get the current user for authorisation checks
        $user    = Factory::getUser();
        $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;
        $userId  = $user->get('id');
        $groups  = implode (',', $user->getAuthorisedViewLevels());
        
        // Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('download.id');

		if ($this->_item === null) {
			$this->_item = array();
		}

		if (!isset($this->_item[$pk])) {

			try {
				$db = $this->getDbo();
				$query = $db->getQuery(true);

				$query->select($this->getState(
					'item.select', 'a.id, a.asset_id, a.title, a.alias, a.description, a.description_long, a.file_pic, a.images, a.price, a.release, ' .
					'a.file_language, a.system, a.license, a.url_license, a.license_agree, a.size, a.url_download, a.preview_filename, a.other_file_id, a.md5_value, a.sha1_value, ' .
                    'a.extern_file, a.extern_site, a.mirror_1, a.mirror_2, a.extern_site_mirror_1, a.extern_site_mirror_2, a.url_home, a.author, a.url_author, a.created_mail, a.submitted_by, ' .
                    'a.changelog, a.password, a.password_md5, a.views, a.update_active, a.featured, a.published, ' .
                    // If badcats is not null, this means that the download is inside an unpublished category
					// In this case, the state is set to 0 to indicate Unpublished (even if the download state is Published)
					'CASE WHEN badcats.id is null THEN a.published ELSE 0 END AS state, ' .
					'a.catid, a.created, a.created_by, a.file_date, ' .
				    // use created if modified is 0
				    // 'CASE WHEN a.modified = 0 THEN a.created ELSE a.modified END, ' .
                    'a.modified, ' .					
                    'a.publish_up, a.publish_down, a.modified_by, a.checked_out, a.checked_out_time,  ' .
					'a.ordering, a.metakey, a.metadesc, a.robots, a.access, a.user_access, a.downloads, a.language'
					)
				);
				$query->from('#__jdownloads_files AS a');

                // Join on files table.
                $query->select('aa.url_download AS filename_from_other_download');
                $query->join('LEFT', '#__jdownloads_files AS aa on aa.id = a.other_file_id');
				
                // Join on category table.
				$query->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access, c.cat_dir AS category_cat_dir, c.cat_dir_parent AS category_cat_dir_parent, c.published AS category_published')
				    ->innerJoin('#__jdownloads_categories AS c on c.id = a.catid')
                    ->where('c.published > 0');

				// Join on user table.
				if ($params->get('use_real_user_name_in_frontend')){
                    $query->select('u.name AS creator');
                } else {
                    $query->select('u.username AS creator');
                }    
				$query->join('LEFT', '#__users AS u on u.id = a.created_by');
                
                if ($params->get('use_real_user_name_in_frontend')){
                    $query->select('u2.name AS modifier');
                } else {
                    $query->select('u2.username AS modifier');
                } 
                $query->join('LEFT', '#__users AS u2 on u2.id = a.modified_by');                

                if ($params->get('use_real_user_name_in_frontend')){
                    $query->select('u3.name AS user_access_name');
                } else {
                    $query->select('u3.username AS user_access_name');
                } 
                $query->join('LEFT', '#__users AS u3 on u3.id = a.user_access');

				// Join on contact table
				$subQuery = $db->getQuery(true);
				$subQuery->select('contact.user_id, MAX(contact.id) AS id, contact.language');
				$subQuery->from('#__contact_details AS contact');
				$subQuery->where('contact.published = 1');
				$subQuery->group('contact.user_id, contact.language');
				$query->select('contact.id as contactid' );
				$query->join('LEFT', '(' . $subQuery . ') AS contact ON contact.user_id = a.created_by');

                // Join on license table.
                $query->select('l.title AS license_title, l.url AS license_url, l.description AS license_text, l.id as lid');
                $query->join('LEFT', '#__jdownloads_licenses AS l on l.id = a.license');
                
                // Join on ratings table.
                $query->select('ROUND(r.rating_sum / r.rating_count, 0) AS rating, r.rating_count as rating_count, r.rating_sum as rating_sum');
                $query->join('LEFT', '#__jdownloads_ratings AS r on r.file_id = a.id');

				// Filter by language
				if ($this->getState('filter.language'))
				{
					$query->where('a.language in ('.$db->quote(Factory::getLanguage()->getTag()).','.$db->quote('*').')');
				}

				// Join over the categories to get parent category titles
				$query->select('parent.title as parent_title, parent.id as parent_id, parent.alias as parent_alias');
				$query->join('LEFT', '#__jdownloads_categories as parent ON parent.id = c.parent_id');

				$query->where('a.id = ' . (int) $pk);

				// Filter by start and end dates.
                $nowDate = Factory::getDate()->toSql();

				if (!$user->authorise('core.edit.state', 'com_jdownloads.download.' . $pk)
                    && !$user->authorise('core.edit', 'com_jdownloads.download.' . $pk)
                )
                {
                    // Filter by start and end dates.
                    $nowDate = Factory::getDate()->toSql();

                    $query->extendWhere(
                        'AND',
                        [
                            $db->quoteName('a.publish_up') . ' IS NULL',
                            $db->quoteName('a.publish_up') . ' <= :publishUp',
                        ],
                        'OR'
                    )
                        ->extendWhere(
                            'AND',
                            [
                                $db->quoteName('a.publish_down') . ' IS NULL',
                                $db->quoteName('a.publish_down') . ' >= :publishDown',
                            ],
                            'OR'
                        )
                        ->bind([':publishUp', ':publishDown'], $nowDate);
                }
                
                
                

				// Join to check for category published state in parent categories up the tree
				// If all categories are published, badcats.id will be null, and we just use the download state
				$subquery = ' (SELECT cat.id as id FROM #__jdownloads_categories AS cat JOIN #__jdownloads_categories AS parent ';
				$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
				$subquery .= 'WHERE parent.published <= 0 GROUP BY cat.id)';
				$query->join('LEFT OUTER', $subquery . ' AS badcats ON badcats.id = c.id');

                // Filter by access level and by user_access field (when used). 
                if ($this->getState('filter.access')){
                    if ($this->getState('filter.user_access')){
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
                            $query->where('a.access IN ('.$groups.')');
                            $query->where('c.access IN ('.$groups.')');
                        }
                    } else {
                        $query->where('a.access IN ('.$groups.')');
                        $query->where('c.access IN ('.$groups.')');
                    } 
                }

				// Filter by published state.
				$published = $this->getState('filter.published');

				if (is_numeric($published)) {
					$query->where('(a.published = ' . (int) $published.')');
				}

				$db->setQuery($query);

                $item = $db->loadObject();

                if (!$item && $plugin === true){
                    return $item;
                }
                
                if (empty($item)){
                    throw new \Exception(Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_FOUND'), 404);
                }

                // Check for published state if filter set.
                if ((is_numeric($published)) && ($item->published != $published)) {
                    throw new \Exception(Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_FOUND'), 404);
                }
                    
                
				$item->params = clone $params;
                
                // Check the permission for the view
                $access = $this->getState('filter.access');
                
                if ($access){
                        $item->params->set('access-view', true);
                } else {
                    // If no access filter is set, the layout takes some responsibility for display of limited information.
                    if ($item->catid == 0 || $item->category_access === null) {
                        $item->params->set('access-view', in_array($item->access, $groups));
                    } else {
                        $item->params->set('access-view', in_array($item->access, $groups) && in_array($item->category_access, $groups));
                    }
                }
                
                // Get custom fields data (jcfields)
                $item->jcfields = FieldsHelper::getFields('com_jdownloads.download', $item, true);

                // Compute the asset access permissions.
                $asset    = 'com_jdownloads.download.'.$item->id;

                // Check at first the 'download' permission.
                if ($user->authorise('download', $asset)) {
                    $item->params->set('access-download', true);
                }

                // Technically guest could edit a download, but lets not check that to improve performance a little.
                if (!$user->get('guest')) {
                                        
					// Check general edit permission first.
                    if ($user->authorise('core.edit', $asset)) {
                        $item->params->set('access-edit', true);
					}
					// Now check if edit.own is available.
                    elseif (!empty($userId) && $user->authorise('core.edit.own', $asset)) {
						// Check for a valid user and that they are the owner.
                        if ($userId == $item->created_by) {
                            $item->params->set('access-edit', true);
						}
					}
                    
                    // Check general delete permission
                    if ($user->authorise('core.delete', $asset)) {
                        $item->params->set('access-delete', true);
					}
				}

				$this->_item[$pk] = $item;
			}
			catch (\Exception $e)
			{
				if ($e->getCode() == 404) {
					// Need to go thru the error handler to allow Redirect to work.
					throw $e;
				} else {
					$this->setError($e);
					$this->_item[$pk] = false;
				}
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * Increment the views counter for the download
	 *
	 * @param	int		Optional primary key of the download to increment.
	 *
	 * @return	boolean	True if successful; false otherwise and internal error set.
	 */
	public function view($pk = 0)
	{
        $jinput = Factory::getApplication()->input;    
        $viewcount = $jinput->get('viewcount', 1, 'int');

        if ($viewcount)
        {
            // Initialise variables.
            $pk = (!empty($pk)) ? $pk : (int) $this->getState('download.id');
            $db = $this->getDbo();

            $db->setQuery(
                    'UPDATE #__jdownloads_files' .
                    ' SET views = views + 1' .
                    ' WHERE id = '.(int) $pk
            );

            if (!$db->execute()) {
                    $this->setError($db->getErrorMsg());
                    return false;
            }
        }
        return true;
	}

    public function storeVote($pk = 0, $rate = 0)
    {
        if ( $rate >= 1 && $rate <= 5 && $pk > 0 )
        {
            $userIP = $_SERVER['REMOTE_ADDR'];
            $db = $this->getDbo();

            $db->setQuery(
                    'SELECT *' .
                    ' FROM #__jdownloads_ratings' .
                    ' WHERE file_id = '.(int) $pk
            );

            $rating = $db->loadObject();

            if (!$rating)
            {
                // There are no ratings yet, so lets insert our rating
                $db->setQuery(
                        'INSERT INTO #__jdownloads_ratings ( file_id, lastip, rating_sum, rating_count )' .
                        ' VALUES ( '.(int) $pk.', '.$db->Quote($userIP).', '.(int) $rate.', 1 )'
                );

                if (!$db->execute()) {
                        $this->setError($db->getErrorMsg());
                        return false;
                }
            } else {
                if ($userIP != ($rating->lastip))
                {
                    $db->setQuery(
                            'UPDATE #__jdownloads_ratings' .
                            ' SET rating_count = rating_count + 1, rating_sum = rating_sum + '.(int) $rate.', lastip = '.$db->Quote($userIP) .
                            ' WHERE file_id = '.(int) $pk
                    );
                    if (!$db->execute()) {
                            $this->setError($db->getErrorMsg());
                            return false;
                    }
                } else {
                    return false;
                }
            }
            return true;
        }
        Factory::getApplication()->enqueueMessage( Text::sprintf('COM_JDOWNLOADS_INVALID_RATING', $rate), "JModelDownload::storeVote($rate)", 'warning');
        return false;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   4.0.0
     * @throws  \Exception
     */
    public function getTable($name = 'Download', $prefix = 'Administrator', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }
    
    /**
     * Method to check-out a row for editing.
     *
     * @param   integer  $pk  The numeric id of the primary key.
     *
     * @return  boolean  False on failure or error, true otherwise.
     *
     * @since   1.6
     */
    public function checkout($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Only attempt to check the row in if it exists.
        if ($pk)
        {
            // Get an instance of the row to checkout.
            $table = $this->getTable();

            if (!$table->load($pk))
            {
                if ($table->getError() === false)
                {
                    // There was no error returned, but false indicates that the row did not exist in the db, so probably previously deleted.
                    $this->setError(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
                }
                else
                {
                    $this->setError($table->getError());
                }

                return false;
            }

            // If there is no checked_out or checked_out_time field, just return true.
            if (!$table->hasField('checked_out') || !$table->hasField('checked_out_time'))
            {
                return true;
            }

            $user            = Factory::getUser();
            $checkedOutField = $table->getColumnAlias('checked_out');

            // Check if this is the user having previously checked out the row.
            if ($table->$checkedOutField > 0 && $table->$checkedOutField != $user->get('id'))
            {
                $this->setError(Text::_('JLIB_APPLICATION_ERROR_CHECKOUT_USER_MISMATCH'));

                return false;
            }

            // Attempt to check the row out.
            if (!$table->checkOut($user->get('id'), $pk))
            {
                $this->setError($table->getError());

                return false;
            }
        }

        return true;
    }
        
    /**
     * Method to checkin a row.
     *
     * @param   integer  $pk  The numeric id of the primary key.
     *
     * @return  boolean  False on failure or error, true otherwise.
     *
     * @since   3.2
     * @throws  \RuntimeException
     */
    public function checkin($pk = null)
    {
        // Only attempt to check the row in if it exists.
        if ($pk)
        {
            $user = Factory::getUser();

            // Get an instance of the row to checkin.
            $table = $this->getTable();

            if (!$table->load($pk))
            {
                throw new \RuntimeException($table->getError());
            }

            // Check if this is the user has previously checked out the row.
            if (!is_null($table->checked_out) && $table->checked_out != $user->get('id') && !$user->authorise('core.admin', 'com_checkin'))
            {
                throw new \RuntimeException($table->getError());
            }

            // Attempt to check the row in.
            if (!$table->checkIn($pk))
            {
                throw new \RuntimeException($table->getError());
            }
        }

        return true;
    }    
    
}
