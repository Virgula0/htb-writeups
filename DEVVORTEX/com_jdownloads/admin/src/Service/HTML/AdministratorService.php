<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_jdownloads
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JDownloads\Component\JDownloads\Administrator\Service\HTML;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
                                                                                                              
/**
 * JDownloads HTML helper
 *
 * @since  3.0
 */
class AdministratorService
{
	/**
	 * Render the list of associated items (add the support for the Joomla Language Associations)
	 *
	 * @param   integer  $fileid  The download item id
	 *
	 * @return  string  The language HTML
	 *
	 * @throws  Exception
	 */
	public static function association($fileid)
	{
		// Defaults
		$html = '';

		// Get the associations
		if ($associations = Associations::getAssociations('com_jdownloads', '#__jdownloads_files', 'com_jdownloads.item', $fileid, 'id', 'alias', ''))
		{
			foreach ($associations as $tag => $associated)
			{
				$associations[$tag] = (int) $associated->id;
			}

			// Get the associated menu items
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('c.*')
				->select('l.sef as lang_sef')
				->select('l.lang_code')
				->from('#__jdownloads_files as c')
				->select('cat.title as category_title')
				->join('LEFT', '#__jdownloads_categories as cat ON cat.id = c.catid')
				->where('c.id IN (' . implode(',', array_values($associations)) . ')')
				->where('c.id != ' . $fileid)
				->join('LEFT', '#__languages as l ON c.language = l.lang_code')
				->select('l.image')
				->select('l.title as language_title');
			$db->setQuery($query);

			try
			{
				$items = $db->loadObjectList('id');
			}
			catch (RuntimeException $e)
			{
				throw new Exception($e->getMessage(), 500, $e);
			}

			if ($items)
			{
				$languages = LanguageHelper::getContentLanguages(array(0, 1));
                $content_languages = array_column($languages, 'lang_code');

                foreach ($items as &$item)
                {
                    if (in_array($item->lang_code, $content_languages))
                    {
                        $text     = $item->lang_code;
                        $url      = Route::_('index.php?option=com_jdownloads&task=download.edit&id=' . (int) $item->id);
                        $tooltip  = '<strong>' . htmlspecialchars($item->language_title, ENT_QUOTES, 'UTF-8') . '</strong><br>'
                            . htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');
                        $classes  = 'badge bg-secondary';

                        $item->link = '<a href="' . $url . '" class="' . $classes . '">' . $text . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $item->id . '">' . $tooltip . '</div>';
                    }
                    else
                    {
                        // Display warning if Content Language is trashed or deleted
                        Factory::getApplication()->enqueueMessage(Text::sprintf('JGLOBAL_ASSOCIATIONS_CONTENTLANGUAGE_WARNING', $item->lang_code), 'warning');
                    }
                }
			}

			$html = LayoutHelper::render('joomla.content.associations', $items);
		}

		return $html;
	}
    
    /**
     * Render the list of associated category items
     *
     * @param   integer  $catid      Category identifier to search its associations
     * @param   string   $extension  Category Extension
     *
     * @return  string   The language HTML
     *
     * @since   3.2
     * @throws  Exception
     */
    public static function catAssociation($catid, $extension = '')
    {
        // Defaults
        $html = '';

        // Get the associations
        if ($associations = JDownloadsHelper::getCatAssociations($catid, $extension))
        {
            $associations = ArrayHelper::toInteger($associations);

            // Get the associated categories
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('c.id, c.title')
                ->select('l.sef as lang_sef')
                ->select('l.lang_code')
                ->from('#__jdownloads_categories as c')
                ->where('c.id IN (' . implode(',', array_values($associations)) . ')')
                ->where('c.id != ' . $catid)
                ->join('LEFT', '#__languages as l ON c.language=l.lang_code')
                ->select('l.image')
                ->select('l.title as language_title');
            $db->setQuery($query);

            try
            {
                $items = $db->loadObjectList('id');
            }
            catch (RuntimeException $e)
            {
                throw new Exception($e->getMessage(), 500, $e);
            }

            if ($items)
            {
                
                $languages = LanguageHelper::getContentLanguages(array(0, 1));
                $content_languages = array_column($languages, 'lang_code');
                
                foreach ($items as &$item)
                {
                    if (in_array($item->lang_code, $content_languages))
                    {
                        $text     = $item->lang_code;
                        $url      = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . (int) $item->id);
                        $tooltip  = '<strong>' . htmlspecialchars($item->language_title, ENT_QUOTES, 'UTF-8') . '</strong><br>'
                            . htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');
                        $classes  = 'badge bg-secondary';

                        $item->link = '<a href="' . $url . '" class="' . $classes . '">' . $text . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $catid . '-' . (int) $item->id . '">' . $tooltip . '</div>';
                    }
                    else
                    {
                        // Display warning if Content Language is trashed or deleted
                        Factory::getApplication()->enqueueMessage(Text::sprintf('JGLOBAL_ASSOCIATIONS_CONTENTLANGUAGE_WARNING', $item->lang_code), 'warning');
                    }
            
                }
            }

            $html = LayoutHelper::render('joomla.content.associations', $items);
        }

        return $html;
    }

}
