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
 
namespace JDownloads\Component\JDownloads\Site\View\Download;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Event\Event;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use JLoader;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;

/**
 * HTML Downloads View class for the jDownloads component
 */
class HtmlView extends BaseHtmlView
{
	protected $item;
	protected $params;
	protected $state;
	protected $user;
    protected $user_rules;

	public function display($tpl = null)
	{
		// Initialise variables.
		$app		= Factory::getApplication();
        $params     = $app->getParams();
		$user		= Factory::getUser();
		$userId		= $user->get('id');

        $document = Factory::getDocument();
        
        // get jD User group settings and limitations
        $this->user_rules = JDHelper::getUserRules();
        
        // Get the needed layout data - type = 5 for a 'download details' layout            
        $this->layout = JDHelper::getLayout(5);
        
        // Add JavaScript Frameworks
        HTMLHelper::_('bootstrap.framework');
        
        // Add jQuery Framework
        HTMLHelper::_('jquery.framework');

        // Load optional RTL Bootstrap CSS
        if ($this->layout->uses_bootstrap){
            HTMLHelper::_('bootstrap.loadCss', true, $this->document->direction);
        }

        // Load optional w3css framework
        if ($this->layout->uses_w3css){
            $w3_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/w3.css';
            if (File::exists($w3_css_path)){
                $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/css/w3.css", "text/css", null, array() );
            }
        }

		$this->item		 = $this->get('Item');
		$this->state	 = $this->get('State');
		$this->user		 = $user;
        
        // upload icon handling
        $this->view_upload_button = false;
        
        if ($this->user_rules->uploads_view_upload_icon){
            // we must here check whether the user has the permissions to create new downloads 
            // this can be defined in the components permissions but also in any category
            // but the upload icon is only viewed when in the user groups settings is also activated the: 'display add/upload icon' option
                
            // 1. check the component permissions
            if (!$user->authorise('core.create', 'com_jdownloads')){
                // 2. not global permissions so we must check now every category (for a lot of categories can this be very slow)
                $this->authorised_cats = JDHelper::getAuthorisedJDCategories('core.create', $user);
                if (count($this->authorised_cats) > 0){
                    $this->view_upload_button = true;
                }
            } else {
                $this->view_upload_button = true;
            }        
        }

        $this->ipad_user = false;
                                     
        // check whether we have an ipad/iphone user for flowplayer aso...
        if ((bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') || (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')){        
            $this->ipad_user = true;
        }
            
        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
        
        // add all needed cripts and css files
        
        $document->addScript(URI::base().'components/com_jdownloads/assets/js/jdownloads.js');
        
        if ($params->get('view_ratings')){
            $document->addScript(URI::base().'components/com_jdownloads/assets/rating/js/ajaxvote.js');
        }
        
        // loadscript for flowplayer
        if ($params->get('flowplayer_use')){
            $document->addScript(URI::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.12.min.js');
            // load also the ipad plugin when required
            if ($this->ipad_user){
                $document->addScript(URI::base().'components/com_jdownloads/assets/flowplayer/flowplayer.ipad-3.2.12.min.js');
            }
        }    
		
        $document->addScriptDeclaration('var live_site = "'.URI::base().'";');
        
        $document->addScriptDeclaration('function openWindow (url) {
                fenster = window.open(url, "_blank", "width=550, height=480, STATUS=YES, DIRECTORIES=NO, MENUBAR=NO, SCROLLBARS=YES, RESIZABLE=NO");
                fenster.focus();
                }');
        
        if ($params->get('use_lightbox_function')){
            $document->addScript(URI::base().'components/com_jdownloads/assets/lightbox/src/js/lightbox.js');
            $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/lightbox/src/css/lightbox.css", 'text/css', null, array() );
        }
            
        $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/css/jdownloads_buttons.css", "text/css", null, array() ); 

        if ($params->get('load_frontend_css')){
            $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/css/jdownloads_fe.css", "text/css", null, array() );
			$currentLanguage = Factory::getLanguage();
            $isRTL = $currentLanguage->get('rtl');
            if ($isRTL) {
                $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/css/jdownloads_fe_rtl.css", "text/css", null, array() );
			}
        } else {
            if ($params->get('own_css_file')){
                $own_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/'.$params->get('own_css_file');
                if (File::exists($own_css_path)){
                    $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/css/".$params->get('own_css_file'), "text/css", null, array() );
                }
            }
        }         
        if ($params->get('view_ratings')){
            $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/rating/css/ajaxvote.css", "text/css", null, array() );         
        }

        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (File::exists($custom_css_path)){
            $document->addStyleSheet( URI::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }   
        
        $this->jd_image_path = JPATH_ROOT  . '/images/jdownloads';
        
        // Create a shortcut for $item.
		$item = $this->item;
        $item->tagLayout = new LayoutHelper('joomla.content.tags');

		// Add router helpers.
		$item->slug			= $item->alias ? ($item->id.':'.$item->alias) : $item->id;
		$item->catslug		= $item->category_alias ? ($item->catid.':'.$item->category_alias) : $item->catid;
		$item->parent_slug	= $item->category_alias ? ($item->parent_id.':'.$item->parent_alias) : $item->parent_id;

		// TODO: Change based on shownoauth
		$item->readmore_link = Route::_(RouteHelper::getDownloadRoute($item->slug, $item->catslug));

		// Merge download params. If this is single-download view, menu params override download params
		// Otherwise, download params override menu item params
		$this->params	= $this->state->get('params');
		$active	= $app->getMenu()->getActive();
		$temp	= clone ($this->params);

		// Check to see which parameters should take priority
		if ($active) {
			$currentLink = $active->link;
			// If the current view is the active item and an download view for this download, then the menu item params take priority
			if (strpos($currentLink, 'view=download') && (strpos($currentLink, '&id='.(string) $item->id))) {
				// $item->params are the download params, $temp are the menu item params
				// Merge so that the menu item params take priority
				$item->params->merge($temp);
				// Load layout from active query (in case it is an alternative menu item)
				if (isset($active->query['layout'])) {
					$this->setLayout($active->query['layout']);
				}
			}
			else {
				// Current view is not a single download, so the download params take priority here
				// Merge the menu item params with the download params so that the download params take priority
				$temp->merge($item->params);
				$item->params = $temp;
			}
		}
		else {
			// Merge so that download params take priority
			$temp->merge($item->params);
			$item->params = $temp;
			// Check for alternative layouts (since we are not in a single-download menu item)
			// Single-download menu item layout takes priority over alt layout for an download
			if ($menu_layout = $item->params->get('download_layout')) {
				$this->setLayout($menu_layout);
			}
		}

		$offset = $this->state->get('list.offset');

		// Check the view access to the download (the model has already computed the values).
		if ($item->params->get('access-view') != true && ($item->params->get('show_noauth') != true &&  $user->get('guest') ) ) {
			$return = base64_encode(URI::getInstance());
            $login_url_with_return = Route::_('index.php?option=com_users&view=login&return=' . $return);
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
            $app->redirect($login_url_with_return, 403);
		}

        $item->tags = new TagsHelper;
        $item->tags->getItemTags('com_jdownloads.download', $this->item->id);
        
        if (Associations::isEnabled() && $item->params->get('show_associations')){
            $item->associations = AssociationHelper::displayAssociations($item->id);
        }
        
        // Required for some content plugins which needed a field named text
        if ($item->description_long != ''){
            $item->text = $item->description_long;
            $long_used = true;
        } else {
            $item->text = $item->description;
            $long_used = false;
        }
        
        // Process the content plugins.
        PluginHelper::importPlugin('content');

        // This is the event to get the content plugins the possibility to modify the Download data. Also required to get Joomla Fields when used in jD.
        if ($params->get('activate_general_plugin_support')) {
            $app->triggerEvent('onContentPrepare', ['com_jdownloads.download', &$item, &$params, $offset]);
        }
        
		$item->event = new \stdClass();
        
        $results = $app->triggerEvent('onContentAfterTitle', array('com_jdownloads.download', &$item, &$this->params, $offset));
        $item->event->afterDisplayTitle = trim(implode("\n", $results));

        $results = $app->triggerEvent('onContentBeforeDisplay', array('com_jdownloads.download', &$item, &$this->params, $offset));
        $item->event->beforeDisplayContent = trim(implode("\n", $results));

        $results = $app->triggerEvent('onContentAfterDisplay', array('com_jdownloads.download', &$item, &$this->params, $offset));
        $item->event->afterDisplayContent = trim(implode("\n", $results));
        
		// we use a little trick to get always the changes from content plugins 
        if ($long_used){
            if ($item->text != $item->description_long){
                $item->description_long = $item->text; 
            }
        } else {
            if ($item->text != $item->description){
                $item->description = $item->text; 
            }            
        }    
        
        // Increment the views counter of the download
        $model = $this->getModel();
		$model->view();

		//Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx') ?? '');
		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
        $app	= Factory::getApplication();
		$params   = $app->getParams();
        $menus	= $app->getMenu();
		$pathway = $app->getPathway();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_JDOWNLOADS_DOWNLOADS'));
		}

		$title = $this->params->get('page_title', '');

        if (isset($menu->query['catid'])){
		    $id = (int) @$menu->query['catid'];
        } else {
            $id = 0;
        }    

        // if the menu item does not concern this download
        if ($menu && ($menu->query['option'] != 'com_jdownloads' || $menu->query['view'] != 'download' || $id != $this->item->id))
        {
            // If this is not a single download menu item, set the page title to the download title
            if ($this->item->title) {
                $title = $this->item->title;
                if ($this->item->release){
                    $title .= ' '.$this->item->release;
            	}
            }
            
            $path = array(array('title' => $this->item->title, 'link' => ''));
            
            $category = CategoriesHelper::getInstance('Download')->get($this->item->catid);
            while ($category && ($menu->query['option'] != 'com_jdownloads' || $menu->query['view'] == 'download' || $id != $category->id) && $category->id > 1)
            {
                $path[] = array('title' => $category->title, 'link' => RouteHelper::getCategoryRoute($category->id, true));
                $category = $category->getParent();
            }
            $path = array_reverse($path);
            foreach($path as $item)
            {
                if ($item['title'] !== 'ROOT'){
                    $pathway->addItem($item['title'], $item['link']);
                }
            }
        }        
        
		// Check for empty title and add site name if param is set
		if (empty($title)) {
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		if (empty($title)) {
			$title = $this->item->title;
            if ($this->item->release){
                $title .= ' '.$this->item->release;
            }
		}
		$this->document->setTitle($title);

		if ($this->item->metadesc)
		{
			$this->document->setDescription($this->item->metadesc);
		}
		elseif (!$this->item->metadesc && $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
        
        // use the Downloads description when the metadesc is still empty
        if (empty($this->item->metadesc)) 
        {
            $metadescription = strip_tags($this->item->description); 
            
            if (strlen($metadescription) >= 150)
            { 
               $metadescshort = substr($metadescription, 0, strpos($metadescription," ",150))." ...";  
            } 
            else
            {
               $metadescshort = $metadescription;
            }
            $this->document->setDescription($metadescshort);
        }            
        
		if ($this->item->metakey)
		{
			$this->document->setMetadata('keywords', $this->item->metakey);
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
