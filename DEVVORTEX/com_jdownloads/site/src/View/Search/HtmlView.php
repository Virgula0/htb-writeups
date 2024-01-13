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
 
namespace JDownloads\Component\JDownloads\Site\View\Search;

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
use Joomla\Registry\Registry;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Site\Helper\SearchHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;

/**
 * HTML View class for the search page
 *
 */
class HtmlView extends BaseHtmlView
{
	public function display($tpl = null)
	{
        // Initialise some variables
		$app	= Factory::getApplication();
        $user   = Factory::getUser();
        $pathway = $app->getPathway();
        
        $this->user_rules = JDHelper::getUserRules();

		$error	 = null;
		$rows	 = null;
		$results = null;
		$total	 = 0;

		// Get some data from the model
		$areas	    = $this->get('areas');
		$state		= $this->get('state');
		$searchword = $state->get('keyword');
        $searchcat  = $state->get('searchcat');
        
		// Load the parameters. Merge Global and Menu Item params into new object
        $params = $app->getParams();
        if ($menu = $app->getMenu()->getActive()){
            $menuParams = $menu->getParams();
        } else {
            $menuParams = new Registry;
        } 
        
        // add all needed cripts and css files
        $document = Factory::getDocument();
        $document->addScript(Uri::base().'components/com_jdownloads/assets/js/jdownloads.js');
        
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
        
        $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_buttons.css", "text/css", null, array() );

        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (File::exists($custom_css_path)){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }           
        
        $document->addScriptDeclaration('var live_site = "'.Uri::base().'";');
        $document->addScriptDeclaration('function openWindow (url) {
                fenster = window.open(url, "_blank", "width=550, height=480, STATUS=YES, DIRECTORIES=NO, MENUBAR=NO, SCROLLBARS=YES, RESIZABLE=NO");
                fenster.focus();
                }');        
        
		// because the application sets a default page title, we need to get it
		// right from the menu item itself
		if (is_object($menu)) {
			$menuParams->loadString($menuParams);
			if (!$menuParams->get('page_title')) {
				$params->set('page_title',	Text::_('COM_JDOWNLOADS_SEARCH'));
			}
		}
		else {
			$params->set('page_title',	Text::_('COM_JDOWNLOADS_SEARCH'));
		}

		$title = $params->get('page_title');
		if ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		$this->document->setTitle($title);

		if ($params->get('menu-meta_description'))
		{
			$this->document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $params->get('menu-meta_keywords'));
		}

		if ($params->get('robots'))
		{
			$this->document->setMetadata('robots', $params->get('robots'));
		}
        
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
        
		// built select lists
		$orders = array();
		$orders[] = HTMLHelper::_('select.option',  'newest',   Text::_('COM_JDOWNLOADS_SEARCH_NEWEST_FIRST'));
		$orders[] = HTMLHelper::_('select.option',  'oldest',   Text::_('COM_JDOWNLOADS_SEARCH_OLDEST_FIRST'));
		$orders[] = HTMLHelper::_('select.option',  'popular',  Text::_('COM_JDOWNLOADS_SEARCH_MOST_POPULAR'));
		$orders[] = HTMLHelper::_('select.option',  'alpha',    Text::_('COM_JDOWNLOADS_SEARCH_ALPHABETICAL'));
		$orders[] = HTMLHelper::_('select.option',  'category', Text::_('COM_JDOWNLOADS_SEARCH_CATEGORY'));

		$lists = array();
		$lists['ordering'] = HTMLHelper::_('select.genericlist', $orders, 'ordering', 'class="inputbox"', 'value', 'text', $state->get('ordering'));

        // Build categories select list box
        $preload[] = HTMLHelper::_('select.option', 0, Text::_('COM_JDOWNLOADS_SELECT_CATEGORY'));
        $areas['cat_listbox'] = @array_merge($preload, $areas['cat_listbox']); 
        $this->listbox = HTMLHelper::_('select.genericlist', $areas['cat_listbox'], 'searchcat', 'class="inputbox" title="'.Text::_('COM_JDOWNLOADS_SEARCH_ONLY_IN').'" onchange=""', 'value', 'text', $searchcat ); 
        
		$searchphrases		= array();
		$searchphrases[]	= HTMLHelper::_('select.option',  'all', Text::_('COM_JDOWNLOADS_SEARCH_ALL_WORDS'));
		$searchphrases[]	= HTMLHelper::_('select.option',  'any', Text::_('COM_JDOWNLOADS_SEARCH_ANY_WORDS'));
		$searchphrases[]	= HTMLHelper::_('select.option',  'exact', Text::_('COM_JDOWNLOADS_SEARCH_EXACT_PHRASE'));
		$lists['searchphrase' ]= HTMLHelper::_('select.radiolist',  $searchphrases, 'searchphrase', '', 'value', 'text', $state->get('match'));

		//limit searchword
		$lang = Factory::getLanguage();
		$upper_limit = $lang->getUpperLimitSearchWord();
		$lower_limit = $lang->getLowerLimitSearchWord();
		if (SearchHelper::limitSearchWord($searchword)) {
			$error = Text::sprintf('COM_JDOWNLOADS_ERROR_SEARCH_MESSAGE', $lower_limit, $upper_limit);
		}

		//sanatise searchword
		if (SearchHelper::santiseSearchWord($searchword, $state->get('match'))) {
			$error = Text::_('COM_JDOWNLOADS_ERROR_IGNOREKEYWORD');
		}

		// put the filtered results back into the model
		// for next release, the checks should be done in the model perhaps...
		$state->set('keyword', $searchword);
		if ($error == null) {
			$results	= $this->get('data');
			$total		= $this->get('total');
			$pagination	= $this->get('pagination');

			for ($i=0, $count = count($results); $i < $count; $i++)
			{
				$row = &$results[$i]->text;

				if ($state->get('match') == 'exact') {
					$searchwords = array($searchword);
					$needle = $searchword;
				}
				else {
					$searchworda = preg_replace('#\xE3\x80\x80#s', ' ', $searchword);
					$searchwords = preg_split("/\s+/u", $searchworda);
 					$needle = $searchwords[0];
				}

				$row = SearchHelper::prepareSearchContent($row, $needle);
				$searchwords = array_unique($searchwords);
				$searchRegex = '#(';
				$x = 0;

				foreach ($searchwords as $k => $hlword)
				{
					$searchRegex .= ($x == 0 ? '' : '|');
					$searchRegex .= preg_quote($hlword, '#');
					$x++;
				}
				$searchRegex .= ')#iu';

				$row = preg_replace($searchRegex, '<span class="highlight">\0</span>', $row);

				$result = &$results[$i];
				if ($result->created) {
					$created = HTMLHelper::_('date', $result->created, Text::_('DATE_FORMAT_LC3'));
				}
				else {
					$created = '';
				}

				$result->text		= HTMLHelper::_('content.prepare', $result->text, '', 'com_jdownloads.search');
				$result->created	= $created;
				$result->count		= $i + 1;
			}
		}

		// Check for layout override
		$active = Factory::getApplication()->getMenu()->getActive();
		if (isset($active->query['layout'])) {
			$this->setLayout($active->query['layout']);
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx') ?? '');

		$this->pagination    = &$pagination;
        $this->results       = &$results;
        $this->lists         = &$lists;
        $this->params        = &$params;

		$this->ordering     = $state->get('ordering');
		$this->searchword   = $searchword;
		$this->origkeyword  = $state->get('origkeyword');
		$this->searchphrase = $state->get('match');
		$this->searchareas  = $areas;

		$this->total    = $total;
		$this->error    = $error;

		parent::display($tpl);
	}
}
