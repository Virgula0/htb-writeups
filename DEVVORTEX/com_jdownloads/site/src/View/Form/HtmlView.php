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
 
namespace JDownloads\Component\JDownloads\Site\View\Form;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Event\Event;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use JLoader;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;

/**
 * HTML Download View class for the jDownloads component
 *
 */
class HtmlView extends BaseHtmlView
{
	protected $form;
	protected $item;
	protected $return_page = '';
	protected $state;
    protected $pageclass_sfx = '';
    protected $params = null;
    protected $user = null;
    protected $jd_custom_fields = null;

	public function display($tpl = null)
	{
		// Initialise variables.
		$app		= Factory::getApplication();
		$user       = $app->getIdentity();
        $jinput     = Factory::getApplication()->input;
        
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/jdownloads_fe.css');
		$currentLanguage = Factory::getLanguage();
        $isRTL = $currentLanguage->get('rtl');
        if ($isRTL) {
        $document->addStyleSheet('components/com_jdownloads/assets/css/jdownloads_fe_rtl.css');
		}
        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (File::exists($custom_css_path)){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }           
        
        $document->addScript(Uri::base().'components/com_jdownloads/assets/js/jdownloads.js');
        
        $document->addScriptDeclaration('
        // dynamically add a new image file upload field when the prior generated fields is used
        function add_new_image_file(field)
        {
            // Get the number of files previously uploaded.
            var count = parseInt(document.getElementById(\'image_file_count\').value);
            var sum = parseInt(document.getElementById(\'sum_listed_images\').value);
            var max = parseInt(document.getElementById(\'max_sum_images\').value);
            
            // Get the name of the file that has just been uploaded.
            var file_name = document.getElementById("file_upload_thumb["+count+"]").value;
           
            // Hide the file upload control containing the information about the picture that was just uploaded.
            document.getElementById(\'new_file_row\').style.display = "none";
            document.getElementById(\'new_file_row\').id = "new_file_row["+count+"]";
           
            // Get a reference to the table containing the uploaded pictures.       
            var table = document.getElementById(\'files_table\');
           
            // Insert a new row with the file name and a delete button.
            var row = table.insertRow(table.rows.length);
            row.id = "inserted_file["+count+"]";
            var cell0 = row.insertCell(0);
            cell0.innerHTML = \'<input type="text" disabled="disabled" name="inserted_file[\'+count+\']" value="\'+file_name+\'" class="form-control valid form-control-success" size="50" aria-invalid="false" /><input type="button" name="delete[\'+count+\']" value="'.Text::_('COM_JDOWNLOADS_REMOVE').'" class="form-control valid form-control-success" aria-invalid="false" onclick="delete_inserted_image_field(this)">\';
           
            // Increment count of the number of files uploaded.
            ++count;
            if (count+sum < max){
                // Insert a new file upload control in the table.
                var row = table.insertRow(table.rows.length);
                row.id = "new_file_row";
                var cell0 = row.insertCell(0);
                cell0.innerHTML = \'<input type="file" name="file_upload_thumb[\'+count+\']" id="file_upload_thumb[\'+count+\']" class="form-control valid form-control-success" size="50" aria-invalid="false" accept="image/gif,image/jpeg,image/jpg,image/png" onchange="add_new_image_file(this)" />\';   
            }
            // Update the value of the file hidden input tag holding the count of files uploaded.
            document.getElementById(\'image_file_count\').value = count;
        }

        // user will remove the files they have previously added
        function delete_inserted_image_field(field)
        {
            // Get the field name.
            var name = field.name;
            
            // Extract the file id from the field name.
            var id = name.substr(name.indexOf(\'[\') + 1, name.indexOf(\']\') - name.indexOf(\'[\') - 1);
           
            // Hide the row displaying the uploaded file name.
            document.getElementById("inserted_file["+id+"]").style.display = "none";
               
            // Get a reference to the uploaded file control.
            var control = document.getElementById("file_upload_thumb["+id+"]");
           
            // Remove the new file control.
            control.parentNode.removeChild(control);
            
            // check that we have always a input field when we remove a other file
            var found = false;
            for (var i = 0; i <= 30; i++){
                 if (document.adminForm.elements["file_upload_thumb["+i+"]"]) {
                    found = true;
                 }
            }
            if (!found) add_new_image_file(field);
        }');         
        
		// Get model data.
		$this->state	= $this->get('State');
		$this->item		= $this->get('Item');
		$this->form		= $this->get('Form');
        
        $this->jd_custom_fields = FieldsHelper::getFields('com_jdownloads.download', $this->item->id);
        
        $catid          = $jinput->get('catid', 0, 'int');
        
        // We must get all 'allowed' category IDs 
        $this->authorised_cats = JDHelper::getAuthorisedJDCategories('core.create', $user);
        
        $user_rules  = JDHelper::getUserRules();
        $user_limits = JDHelper::getUserLimits($user_rules, 0);
        
        // Here is the place to change field attributes - defined in user groups limits
        
        if (!$user_rules->uploads_use_editor){
            $this->form->setFieldAttribute( 'description', 'type', 'textarea' );
            $this->form->setFieldAttribute( 'description', 'rows', '4' );
            $this->form->setFieldAttribute( 'description', 'cols', '60' );
            $this->form->setFieldAttribute( 'description_long', 'type', 'textarea' );
            $this->form->setFieldAttribute( 'description_long', 'rows', '6' );
            $this->form->setFieldAttribute( 'description_long', 'cols', '60' );            
            $this->form->setFieldAttribute( 'changelog', 'type', 'textarea' );
            $this->form->setFieldAttribute( 'changelog', 'rows', '4' );
            $this->form->setFieldAttribute( 'changelog', 'cols', '60' );
            $this->form->setFieldAttribute( 'custom_field_13', 'type', 'textarea' );
            $this->form->setFieldAttribute( 'custom_field_13', 'rows', '4' );
            $this->form->setFieldAttribute( 'custom_field_13', 'cols', '60' );
            $this->form->setFieldAttribute( 'custom_field_14', 'type', 'textarea' );           
            $this->form->setFieldAttribute( 'custom_field_14', 'rows', '4' );
            $this->form->setFieldAttribute( 'custom_field_14', 'cols', '60' );            
        }
        
        // Activate the 'required' state
        if ($user_rules->form_alias && $user_rules->form_alias_x)                               $this->form->setFieldAttribute( 'alias', 'required', 'true' ); 
        if ($user_rules->form_author_mail && $user_rules->form_author_mail_x)                   $this->form->setFieldAttribute( 'url_author', 'required', 'true' ); 
        if ($user_rules->form_author_name && $user_rules->form_author_name_x)                   $this->form->setFieldAttribute( 'author', 'required', 'true' ); 
        if ($user_rules->form_website && $user_rules->form_website_x)                           $this->form->setFieldAttribute( 'url_home', 'required', 'true' );
        if ($user_rules->form_changelog && $user_rules->form_changelog_x)                       $this->form->setFieldAttribute( 'changelog', 'required', 'true' ); 
        if ($user_rules->form_creation_date && $user_rules->form_creation_date_x)               $this->form->setFieldAttribute( 'created', 'required', 'true' ); 
        if ($user_rules->form_external_file && $user_rules->form_external_file_x)               $this->form->setFieldAttribute( 'extern_file', 'required', 'true' ); 
        if ($user_rules->form_license && $user_rules->form_license_x)                           $this->form->setFieldAttribute( 'license', 'required', 'true' ); 
        if ($user_rules->form_version && $user_rules->form_version_x)                           $this->form->setFieldAttribute( 'release', 'required', 'true' ); 
        if ($user_rules->form_file_date && $user_rules->form_file_date_x)                       $this->form->setFieldAttribute( 'file_date', 'required', 'true' ); 
        if ($user_rules->form_file_language && $user_rules->form_file_language_x)               $this->form->setFieldAttribute( 'file_language', 'required', 'true' ); 
        if ($user_rules->form_file_pic && $user_rules->form_file_pic_x)                         $this->form->setFieldAttribute( 'file_pic', 'required', 'true' );
        if ($user_rules->form_file_system && $user_rules->form_file_system_x)                   $this->form->setFieldAttribute( 'system', 'required', 'true' );  
        if ($user_rules->form_images && $user_rules->form_images_x)                             $this->form->setFieldAttribute( 'images', 'required', 'true' );          
        if ($user_rules->form_language && $user_rules->form_language_x)                         $this->form->setFieldAttribute( 'language', 'required', 'true' );          
        if ($user_rules->form_mirror_1 && $user_rules->form_mirror_1_x)                         $this->form->setFieldAttribute( 'mirror_1', 'required', 'true' );
        if ($user_rules->form_mirror_2 && $user_rules->form_mirror_2_x)                         $this->form->setFieldAttribute( 'mirror_2', 'required', 'true' );
        if ($user_rules->form_password && $user_rules->form_password_x)                         $this->form->setFieldAttribute( 'password', 'required', 'true' );
        if ($user_rules->form_price && $user_rules->form_price_x)                               $this->form->setFieldAttribute( 'price', 'required', 'true' );
        if ($user_rules->form_short_desc && $user_rules->form_short_desc_x)                     $this->form->setFieldAttribute( 'description', 'required', 'true' ); 
        if ($user_rules->form_long_desc && $user_rules->form_long_desc_x)                       $this->form->setFieldAttribute( 'description_long', 'required', 'true' );
        // if ($user_rules->form_created_id && $user_rules->form_created_id_x)                     $this->form->setFieldAttribute( 'created_by', 'required', 'true' );
        
        if (!$this->item->id){
            // New Download
            // Set default value for access in form when exist - use otherwise 1 for public access
            if ($user_rules->uploads_default_access_level){
                $this->form->setValue( 'access', null, (int)$user_rules->uploads_default_access_level );
            }    

            // Use this options only for 'creation' page (...why?)    
            if ($user_rules->form_select_main_file && $user_rules->form_select_main_file_x)         $this->form->setFieldAttribute( 'file_upload', 'required', 'true' );
            if ($user_rules->form_select_preview_file && $user_rules->form_select_preview_file_x)   $this->form->setFieldAttribute( 'preview_file_upload', 'required', 'true' );
        }    
        
        // User will edit a exist download so we must check the category rule 
        if ($this->item->id && !$user_rules->uploads_can_change_category){
            // Change category field to readonly 
            $this->form->setFieldAttribute( 'catid', 'readonly', 'true' );
        }
        
		$this->return_page = $this->get('ReturnPage');
        
        if (!$this->return_page){
            $current_url = '';
            
            // Seems we will create a new download about 'Add' button, so we will use the current url for return page
            if (isset($_SERVER['HTTP_REFERER'])){
            $current_url = $_SERVER['HTTP_REFERER'];
            }
            
            if ($current_url){
                $this->return_page = base64_encode($current_url);
            } else {
                $this->return_page = base64_encode(Uri::current());
            }    
        }    
        
		if (empty($this->item->id)) {
			$authorised = $user->authorise('core.create', 'com_jdownloads') || (count($this->authorised_cats));
		}
		else {
			$authorised = $this->item->params->get('access-edit');
		}

		if ($authorised !== true) {
        	if (empty($this->item->id)) {
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_FRONTEND_CREATE_NO_PERMISSIONS'), 'warning');
            } else {
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_FRONTEND_EDIT_NO_PERMISSIONS'), 'warning');
            }    
        	return false;
        } else {
            $this->view_upload_button = true;
        }

        if (isset($user_limits['upload']->sumfiles) && $user_limits['upload']->sumfiles > 0){
            $upload_limits_reached = ($user_limits['upload_remaining'] == 0);
        } else {
            $upload_limits_reached = false;
        }
        
        if ($upload_limits_reached == true) {
            $text = JDHelper::getOnlyLanguageSubstring($user_rules->upload_limit_daily_msg);
            if ($text != ''){
                $app->enqueueMessage( Text::_($text), 'notice');
            } else {
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_DAILY_UPLOAD_LIMITS_REACHED_TEXT'), 'notice');
            }
            return false;
        }
        
        $this->user_rules  = $user_rules;
        $this->user_limits = $user_limits;
        
        $this->item->tags = new TagsHelper;

        if (!empty($this->item->id))
        {
            $this->item->tags->getItemTags('com_jdownloads.download.', $this->item->id);
        }        
        
		if (!empty($this->item) && isset($this->item->id)) {
			$tmp = new \stdClass;
			$tmp->images = $this->item->images;
			$this->form->bind($tmp);
		}

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

		// Create a shortcut to the parameters.
		$params	= &$this->state->params;

		// Escape strings for HTML output
	    $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx') ?? '');
		
		$this->params	= $params;
		$this->user		= $user;

        // Check whether it is in menu settings defined only a single category (and the Download is new)
		if (!$this->item->id){
            if ($params->get('enable_category') == 1) {
			    $this->form->setFieldAttribute('catid', 'default',  $params->get('catid', 1));
			    $this->form->setFieldAttribute('catid', 'readonly', 'true');
		    } else {
                if ($catid > 1 && in_array($catid, $this->authorised_cats)){
                    // Set the current category as the default target category 
                    $this->form->setFieldAttribute('catid', 'default',  $catid);
                    $this->form->setFieldAttribute('catid', 'readonly', 'false');
                }
            }
        }
        
        // Propose current language as default when creating new Download
        if (empty($this->item->id) && Multilanguage::isEnabled()) {
            $lang = Factory::getLanguage()->getTag();
            $this->form->setFieldAttribute('language', 'default', $lang);
        }
        
        $this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
        $app       = Factory::getApplication();
        $params   = $app->getParams();
        
		$menus		= $app->getMenu();
		$pathway	= $app->getPathway();
		$title 		= null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', Text::_('COM_JDOWNLOADS_FORM_EDIT_DOWNLOAD'));
		}

		$title = $this->params->def('page_title', Text::_('COM_JDOWNLOADS_FORM_EDIT_DOWNLOAD'));
		if ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		$this->document->setTitle($title);

		$pathway = $app->getPathWay();
		$pathway->addItem($title, '');

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

        // use at first settings from download - alternate from jD configuration
        if ($this->item->robots)
        {
            $this->document->setMetadata('robots', $this->item->robots);    
        } elseif ($params->get('robots')){
            // use settings from jD-config
            $this->document->setMetadata('robots', $params->get('robots'));    
        } else {
            // is not defined in item or jd-config - so we use the global config setting
            $this->document->setMetadata( 'robots' , $app->getCfg('robots' ));
        }		

	}
}
