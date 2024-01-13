<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JDownloads\Component\JDownloads\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;


/**
 * The download controller for ajax requests
 *
 * @since  3.9.0
 */
class AjaxController extends BaseController
{
	/**
	 * Method to fetch associations of an download
	 *
	 * The method assumes that the following http parameters are passed in an Ajax Get request:
	 * token: the form token
	 * assocId: the id of the download whose associations are to be returned
	 * excludeLang: the association for this language is to be excluded
	 *
	 * @return  null
	 *
	 * @since  3.9.0
	 */
	public function fetchAssociations()
	{
		if (!Session::checkToken('get')){
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
		} else {
			// We need the type (category or download)
            $app = Factory::getApplication();
            $type = $app->getUserState('type', 'string');
            
            $input = $app->input;
            
			$assocId = $input->getInt('assocId', 0);

			if ($assocId == 0){
				echo new JsonResponse(null, Text::sprintf('JLIB_FORM_VALIDATE_FIELD_INVALID', 'assocId'), true);

				return;
			}

			$excludeLang = $input->get('excludeLang', '', 'STRING');

			if ($type == 'download'){
                $associations = Associations::getAssociations('com_jdownloads', '#__jdownloads_files', 'com_jdownloads.item', (int) $assocId, 'id', 'alias', '');
			    unset($associations[$excludeLang]);

			    // Add the title to each of the associated records
			    $contentTable = Table::getInstance('DownloadTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
            } else {
                $associations = Associations::getAssociations('com_jdownloads', '#__jdownloads_categories', 'com_jdownloads.category.item', (int) $assocId, 'id', 'alias', '');
                unset($associations[$excludeLang]);

                // Add the title to each of the associated records
                $contentTable = Table::getInstance('JDCategoryTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
            }

			foreach ($associations as $lang => $association){
				$contentTable->load($association->id);
				$associations[$lang]->title = $contentTable->title;
			}

			$countContentLanguages = count(LanguageHelper::getContentLanguages(array(0, 1)));

			if (count($associations) == 0){
				$message = Text::_('JGLOBAL_ASSOCIATIONS_PROPAGATE_MESSAGE_NONE');
			} elseif ($countContentLanguages > count($associations) + 2){
				$tags    = implode(', ', array_keys($associations));
				$message = Text::sprintf('JGLOBAL_ASSOCIATIONS_PROPAGATE_MESSAGE_SOME', $tags);
			} else {
				$message = Text::_('JGLOBAL_ASSOCIATIONS_PROPAGATE_MESSAGE_ALL');
			}

			echo new JsonResponse($associations, $message);
		}
	}
}
