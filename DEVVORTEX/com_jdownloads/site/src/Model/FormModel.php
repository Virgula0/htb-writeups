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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;


/**
 * jDownloads Component Download Model
 *
 */
class FormModel extends \JDownloads\Component\JDownloads\Administrator\Model\DownloadModel
{
    
    public $typeAlias = 'com_jdownloads.download';
    
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->input->getInt('a_id');
		$this->setState('download.id', $pk);

		$this->setState('download.catid', $app->input->get('catid', 0, 'int'));

		$return = $app->input->get('return', '', 'base64');
        $this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params	= $app->getParams();
		$this->setState('params', $params);

		$this->setState('layout', $app->input->getString('layout'));
	}

	/**
	 * Method to get download data.
	 *
	 * @param	integer	The id of the download.
	 *
	 * @return	mixed	Content item data object on success, false on failure.
	 */
	public function getItem($itemId = null)
	{
		// Initialise variables.
		$itemId = (int) (!empty($itemId)) ? $itemId : $this->getState('download.id');

		// Get a row instance.
		$table = $this->getTable();

		// Attempt to load the row.
		$return = $table->load($itemId);

		// Check for a table object error.
		if ($return === false && $table->getError()) {
			$this->setError($table->getError());
			return false;
		}

		$properties = $table->getProperties(1);
		$value = ArrayHelper::toObject($properties, CMSObject::class);

		// Convert attrib field to Registry.
		$value->params = new Registry();
		
		// Compute selected asset permissions.
		$user	= Factory::getUser();
		$userId	= $user->get('id');
		$asset	= 'com_jdownloads.download.'.$value->id;

		// Check general edit permission first.
		if ($user->authorise('core.edit', $asset)) {
			$value->params->set('access-edit', true);
		} elseif (!empty($userId) && $user->authorise('core.edit.own', $asset)) {
		    // Now check if edit.own is available.
            // Check for a valid user and that they are the owner.
			if ($userId == $value->created_by) {
				$value->params->set('access-edit', true);
			}
		}
        
        // Check general delete permission
        if ($user->authorise('core.delete', $asset)) {
            $value->params->set('access-delete', true);
        }                
   
		// Check edit state permission.
		if ($itemId) {
			// Existing item
			$value->params->set('access-change', $user->authorise('core.edit.state', $asset));
		} else {
			// New item.
			$catId = (int) $this->getState('download.catid');

			if ($catId) {
				$value->params->set('access-create', $user->authorise('core.create', 'com_jdownloads.category.'.$catId));
                $value->params->set('access-change', $user->authorise('core.edit.state', 'com_jdownloads.category.'.$catId));
				$value->catid = $catId;
			} else {
				$value->params->set('access-create', $user->authorise('core.create', 'com_jdownloads'));
                $value->params->set('access-change', $user->authorise('core.edit.state', 'com_jdownloads'));
			}
		}
        
        if ($itemId){
            $value->tags = new TagsHelper;
            $value->tags->getTagIds($value->id, 'com_jdownloads.download');
            //$value->metadata['tags'] = $value->tags;
        }        

		return $value;
	}

	/**
	 * Get the return URL.
	 *
	 * @return	string	The return URL.
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page', ''));
	}
    
    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     *
     * @since   3.2
     */
    public function save($data, $auto = false, $import = false, $restore_in_progress = false)
    {
        // Associations are not edited in frontend ATM so we have to inherit them
        if (Associations::isEnabled() && !empty($data['id'])
            && $associations = AssociationHelper::getAssociations($data['id'], 'download', 'com_jdownloads.item')) 
        {
            foreach ($associations as $tag => $associated) {
                $associations[$tag] = (int) $associated->id;
            }

            $data['associations'] = $associations;
        }

        if (!Multilanguage::isEnabled()) {
            $data['language'] = '*';
        }

        return parent::save($data);
    }    
    
    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A Form object on success, false on failure
     *
     * @since   1.6
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = parent::getForm($data, $loadData);

        if (empty($form)) {
            return false;
        }

        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        // On edit Download, we get ID of Download from download.id state, but on save, we use data from input
        $id = (int) $this->getState('download.id', $app->input->getInt('a_id'));

        // Existing record. We can't edit the category in frontend if not edit.state.
        // @TODO: Elements with the disabled attribute are not submitted - 
        if ($id > 0 && !$user->authorise('core.edit.state', 'com_jdownloads.download.' . $id)) {
            $form->setFieldAttribute('catid', 'readonly', 'true');
            $form->setFieldAttribute('catid', 'required', 'false');
            $form->setFieldAttribute('catid', 'filter', 'unset');
        }

        // Prevent messing with Download language and category when editing existing Download with associations
        if ($this->getState('download.id') && Associations::isEnabled()) {
            $associations = AssociationHelper::getAssociations($id, 'download', 'com_jdownloads.item');

            // Make fields read only
            if (!empty($associations)) {
                $form->setFieldAttribute('language', 'readonly', 'true');
                $form->setFieldAttribute('language', 'filter', 'unset');
                $form->setFieldAttribute('catid', 'readonly', 'true');
                $form->setFieldAttribute('catid', 'required', 'false');
                $form->setFieldAttribute('catid', 'filter', 'unset');
                
            }
        }
        
        return $form;
    }
    
    /**
     * Allows preprocessing of the JForm object.
     *
     * @param   Form    $form   The form object
     * @param   array   $data   The data to be merged into the form object
     * @param   string  $group  The plugin group to be executed
     *
     * @return  void
     *
     * @since   3.7.0
     */
    protected function preprocessForm(Form $form, $data, $group = 'jdownload')
    {
        $params = $this->getState()->get('params');

        if ($params && $params->get('enable_category') == 1 && $params->get('catid')) {
            $form->setFieldAttribute('catid', 'default', $params->get('catid'));
            $form->setFieldAttribute('catid', 'readonly', 'true');

            if (Multilanguage::isEnabled()) {
                $categoryId = (int) $params->get('catid');

                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select($db->quoteName('language'))
                    ->from($db->quoteName('#__jdownloads_categories'))
                    ->where($db->quoteName('id') . ' = :categoryId')
                    ->bind(':categoryId', $categoryId, ParameterType::INTEGER);
                $db->setQuery($query);

                $result = $db->loadResult();

                if ($result != '*') {
                    $form->setFieldAttribute('language', 'readonly', 'true');
                    $form->setFieldAttribute('language', 'default', $result);
                }
            }
        }

        if (!Multilanguage::isEnabled()) {
            $form->setFieldAttribute('language', 'type', 'hidden');
            $form->setFieldAttribute('language', 'default', '*');
        }

        parent::preprocessForm($form, $data, $group);
    }
    
}
