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
 
namespace JDownloads\Component\JDownloads\Site\View\Category;

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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Router\Route;
use JLoader;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;
 
/**
 * HTML View class for the jDownloads component
 *
 */
class HtmlView extends BaseHtmlView
{
	protected $state;
	protected $items;
	protected $category;
    protected $categories;
	protected $children;
	protected $pagination;

	protected $lead_items = array();
	protected $intro_items = array();
	protected $link_items = array();
	protected $columns = 1;

	public function display($tpl = null)
	{
		
        $document = Factory::getDocument();
        
        $app   = Factory::getApplication();
        $user  = Factory::getUser();
        
        $jd_user_settings = JDHelper::getUserRules();
        
        // This output is a little complicated as we need layouts from three sources
        
        // Get the needed layout data - type = 4 for a 'category' layout            
        $layouts['category'] = JDHelper::getLayout(4);
        // Get the needed layout data - type = 8 for a 'sub categories' layout with pagination!           
        $layouts['subcategory'] = JDHelper::getLayout(8);
        // Get the needed layout data - type = 2 for a 'files' layout            
        $layouts['files'] = JDHelper::getLayout(2);
        
        // Add JavaScript Frameworks
        HTMLHelper::_('bootstrap.framework');
        
        // Add jQuery Framework
        HTMLHelper::_('jquery.framework');

        // Load optional RTL Bootstrap CSS
        if ($layouts['category']->uses_bootstrap || $layouts['subcategory']->uses_bootstrap || $layouts['files']->uses_bootstrap){
            HTMLHelper::_('bootstrap.loadCss', true, $this->document->direction);
        }

        // Load optional w3css framework
        if ($layouts['category']->uses_w3css || $layouts['subcategory']->uses_w3css || $layouts['files']->uses_w3css){
            $w3_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/w3.css';
            if (File::exists($w3_css_path)){
                $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/w3.css", 'text/css', null, array() );                
            }
        }
        
        // Get some data from the models
		$state		= $this->get('State');
		$params		= $state->params;
		$items		= $this->get('Items');      // get the category downloads
		$category	= $this->get('Category');   // get the selected category data
        $children	= $this->get('Children');   // get the categories sub categories
		$parent		= $this->get('Parent');     // get the categories parent categories
		$pagination = $this->get('Pagination'); // get the downloads pagination ($pagination->total is the amount of Downloads from the current category)
        
        // Upload icon handling
        $this->view_upload_button = false;
        
        if ($jd_user_settings->uploads_view_upload_icon){
            // We must here check whether the user has the permissions to create new downloads. 
            // This can be defined in the components permissions but also in any category
            // But the upload icon is only viewed when in the user groups settings is also activated the: 'display add/upload icon' option
                
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
        
        // Check whether we have an ipad/iphone user for flowplayer aso...
        if ((bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') || (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')){        
            $this->ipad_user = true;
        }        

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors));
		}
        
        // Add all needed cripts and css files
        $document = Factory::getDocument();
        
        $document->addScript(Uri::base().'components/com_jdownloads/assets/js/jdownloads.js');
        
        if ($params->get('view_ratings')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/rating/js/ajaxvote.js');
        }
        
        // Loadscript for flowplayer
        if ($params->get('flowplayer_use')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.12.min.js');
            // Load also the ipad plugin when required
             if ($this->ipad_user){
                $document->addScript(Uri::base().'components/com_jdownloads/assets/flowplayer/flowplayer.ipad-3.2.12.min.js');
            }
        }             
        
        $document->addScriptDeclaration('live_site = "'.Uri::base().'";');
        
        $document->addScriptDeclaration('function openWindow (url) {
                fenster = window.open(url, "_blank", "width=550, height=480, STATUS=YES, DIRECTORIES=NO, MENUBAR=NO, SCROLLBARS=YES, RESIZABLE=NO");
                fenster.focus();
                }');
        
        if ($params->get('use_lightbox_function')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/lightbox/src/js/lightbox.js');
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/lightbox/src/css/lightbox.css", 'text/css', null, array() );
        }
        
        // Required only for subcategories pagination
        if ($params->get('use_pagination_subcategories')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/pagination/jdpagination.js');
            $document->addScript(Uri::base().'components/com_jdownloads/assets/pagination/jdpagination_2.js');
        }

        $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_buttons.css", "text/css", null, array() ); 
        
        if ($params->get('load_frontend_css')){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_fe.css", "text/css", null, array() );
			$currentLanguage = Factory::getLanguage();
            $isRTL = $currentLanguage->get('rtl');
            if ($isRTL) {
                $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_fe_rtl.css", "text/css", null, array() );
				}
        } else {
            if ($params->get('own_css_file')){
                $own_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/'.$params->get('own_css_file');
                if (File::exists($own_css_path)){
                    $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/".$params->get('own_css_file'), "text/css", null, array() );
                }
            }
        } 
        
        if ($params->get('view_ratings')){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/rating/css/ajaxvote.css", "text/css", null, array() );         
        }

        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (File::exists($custom_css_path)){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }   
        
        $this->jd_image_path = JPATH_ROOT  . '/images/jdownloads';        

		if ($category == false){
			// It seems that we have a not public visible category so we redirect to the login page
            if ($user->get('guest')){
                $return = base64_encode(Uri::getInstance());
                $login_url_with_return = Route::_('index.php?option=com_users&view=login&return=' . $return);
                $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
                $app->redirect($login_url_with_return, 403);
            } else {
				$app->enqueueMessage( Text::_('COM_JDOWNLOADS_CATEGORY_NOT_FOUND'), 'error');
                return false;
			}
        }
        
		if ($parent == false){ 
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_CATEGORY_PARENT_NOT_FOUND'), 'error');
            return false;
		}

		// Setup the category parameters.
		$cparams = $category->getParams();
		$category->params = clone($params);
		$category->params->merge($cparams);
        
        $category->tags = new TagsHelper;
        $category->tags->getItemTags('com_jdownloads.category', $category->id);        

		// Check whether category access level allows access.
		$user	= Factory::getUser();
		$groups	= $user->getAuthorisedViewLevels();
		if (!in_array($category->access, $groups)) {
			$app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            return false;
		}

		// Compute the download slugs and prepare text (runs content plugins).
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = &$items[$i];
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

            //$item->parent_slug = $item->parent_alias ? ($item->parent_id . ':' . $item->parent_alias) : $item->parent_id;

			// No link for ROOT category
			if ($item->parent_alias === 'root') {
				$item->parent_slug = null;
			}

            // Process the content plugins.
            PluginHelper::importPlugin('content');

            // Required for some content plugins which needed a field named id and text
            $item->text = $item->description;
            
            // This is the event to get the content plugins the possibility to modify the Download data. Also required to get Joomla Fields when used in jD.
            if ($params->get('activate_general_plugin_support')) {
                $app->triggerEvent('onContentPrepare', ['com_jdownloads.download', &$item, &$params, 0]);
            }
            
            $item->description = $item->text;             
            
            $item->event = new \stdClass();
            
            $results = $app->triggerEvent('onContentAfterTitle', array('com_jdownloads.download', &$item, &$item->params, 0));
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentBeforeDisplay', array('com_jdownloads.download', &$item, &$item->params, 0));
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentAfterDisplay', array('com_jdownloads.download', &$item,&$item->params, 0));
            $item->event->afterDisplayContent = trim(implode("\n", $results));			

        }

		// Check for layout override only if this is not the active menu item
		// If it is the active menu item, then the view and category id will match
		$active	= $app->getMenu()->getActive();
		if ((!$active) || ((strpos($active->link, 'view=category') === false) || (strpos($active->link, '&catid=' . (string) $category->id) === false))) {
			// Get the layout from the merged category params
			if ($layout = $category->params->get('category_layout')) {
				$this->setLayout($layout);
			}
		}
		// At this point, we are in a menu item, so we don't override the layout
		elseif (isset($active->query['layout'])) {
			// We need to set the layout from the query in case this is an alternative menu item (with an alternative layout)
			$this->setLayout($active->query['layout']);
		}

		$children = array($category->id => $children);

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx') ?? '');

		$this->maxLevelcat      = $params->get('maxLevelcat', -1) < 0 ? PHP_INT_MAX : $params->get('maxLevelcat', PHP_INT_MAX);
        
        $this->params           = &$params;
        $this->state            = &$state;
        $this->items            = &$items;
        $this->category         = &$category;
        $this->children         = &$children;
        $this->parent           = &$parent;
        $this->pagination       = &$pagination;
        $this->user             = &$user;
        $this->layouts          = &$layouts;
        $this->jd_user_settings = &$jd_user_settings;
        
		$this->prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function prepareDocument()
	{
        $app		= Factory::getApplication();
        $params     = $app->getParams();
		$menus		= $app->getMenu();
		$pathway	= $app->getPathway();
		$title		= null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu) {
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', Text::_('COM_JDOWNLOADS_DOWNLOADS'));
		}
        
        $title = $this->params->get('page_title', '');

		if (isset($menu->query['catid'])){
            $id = (int) @$menu->query['catid'];
        } else {
            $id = 0;
        }  

		if ($menu && ($menu->query['option'] != 'com_jdownloads' || $menu->query['view'] != 'category' || $id != $this->category->id)) {
			
            // If this is not a single category menu item, set the page title to the category title
            if ($this->category->title) {
                $title = $this->category->title;
            }
            
            $path = array(array('title' => $this->category->title, 'link' => ''));
			$category = $this->category->getParent();

			while (($menu->query['option'] != 'com_jdownloads' || $menu->query['view'] == 'download' || $id != $category->id) && (int)$category->id > 1){
				$path[] = array('title' => $category->title, 'link' => RouteHelper::getCategoryRoute($category->id, true));
				$category = $category->getParent();
			}

			$path = array_reverse($path);

			foreach ($path as $item){
				$pathway->addItem($item['title'], $item['link']);
			}
		}

		if (empty($title)){
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->category->metadesc){
			$this->document->setDescription($this->category->metadesc);
		}
		elseif (!$this->category->metadesc && $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->category->metakey){
			$this->document->setMetadata('keywords', $this->category->metakey);
		}
		elseif (!$this->category->metakey && $this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

        // Use at first settings from download - alternate from jD configuration
        if ($this->category->robots){
            $this->document->setMetadata('robots', $this->category->robots);    
        } 
        elseif ($params->get('robots')){
            // Use settings from jD-config
            $this->document->setMetadata('robots', $params->get('robots'));    
        } else {
            // Is not defined in item or jd-config - so we use the global config setting
            $this->document->setMetadata( 'robots' , $app->getCfg('robots' ));
        }
	}
}
