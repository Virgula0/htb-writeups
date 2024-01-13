<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JDownloads\Component\JDownloads\Administrator\Service\HTML;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use Joomla\Registry\Registry;

/**
 * JDownloads Component HTML Helper
 *
 * @since  4.0.0
 */
class Icon
{
	/**
	 * The application
	 *
	 * @var    CMSApplication
	 *
	 * @since  4.0.0
	 */
	private $application;

	/**
	 * Service constructor
	 *
	 * @param   CMSApplication  $application  The application
	 *
	 * @since   4.0.0
	 */
	public function __construct(CMSApplication $application)
	{
		$this->application = $application;
	}

	/**
	 * Method to generate a link to the create item page for the given category
	 *
	 * @param   object    $category  The category information
	 * @param   Registry  $params    The item parameters
	 * @param   array     $attribs   Optional attributes for the link
	 * @param   boolean   $legacy    True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string  The HTML markup for the create item link
	 *
	 * @since  4.0.0
	 */
	public function create($category, $params, $attribs = array(), $legacy = false)
	{
		$uri = Uri::getInstance();

		$url = 'index.php?option=com_jdownloads&task=download.add&return=' . base64_encode($uri) . '&a_id=0&catid=' . $category->id;

		$text = '';

        if ($params->get('show_icons')) {
			$text .= '<span class="icon-plus icon-fw" aria-hidden="true"></span>';
		}

		$text .= Text::_('COM_CONTENT_NEW_ARTICLE');

		// Add the button classes to the attribs array
        if (isset($attribs['class'])) {
			$attribs['class'] .= ' btn btn-primary';
        } else {
			$attribs['class'] = 'btn btn-primary';
		}

		$button = HTMLHelper::_('link', Route::_($url), $text, $attribs);

		return $button;
	}

	/**
	 * Display an edit icon for the download.
	 *
	 * This icon will not display in a popup window.
	 * Edit access checks must be performed in the calling code.
	 *
	 * @param   object    $download  The download information
	 * @param   Registry  $params   The item parameters
	 * @param   array     $attribs  Optional attributes for the link
	 * @param   boolean   $legacy   True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string	The HTML for the download edit icon.
	 *
	 * @since  4.0.0
	 */
	public function edit($download, $params, $attribs = array(), $legacy = false)
	{
		$app = Factory::getApplication();
        $user = $app->getIdentity();
		$uri  = Uri::getInstance();

		// Ignore if in a popup window.
        if ($params && $params->get('popup')) {
            return '';
		}

		// Ignore if the state is negative (trashed).
		if (!in_array($download->state, [Workflow::CONDITION_UNPUBLISHED, Workflow::CONDITION_PUBLISHED])){
			return;
		}

		// Show checked_out icon if the download is checked out by a different user
		if (
			property_exists($download, 'checked_out')
			&& property_exists($download, 'checked_out_time')
			&& !is_null($download->checked_out)
			&& $download->checked_out != $user->get('id')
		){
			$checkoutUser = $app->getIdentity($download->checked_out);
			$date         = HTMLHelper::_('date', $download->checked_out_time);
			$tooltip      = Text::sprintf('COM_CONTENT_CHECKED_OUT_BY', $checkoutUser->name)
				. ' <br> ' . $date;

			$text = LayoutHelper::render('joomla.content.icons.edit_lock', array('download' => $download, 'tooltip' => $tooltip, 'legacy' => $legacy));

			$attribs['aria-describedby'] = 'editarticle-' . (int) $download->id;
			$output = HTMLHelper::_('link', '#', $text, $attribs);

			return $output;
		}

		$contentUrl = RouteHelper::getDownloadRoute($download->slug, $download->catid, $download->language);
		$url        = $contentUrl . '&task=download.edit&a_id=' . $download->id . '&return=' . base64_encode($uri);

		if ($download->state == Workflow::CONDITION_UNPUBLISHED){
			$tooltip = Text::_('COM_CONTENT_EDIT_UNPUBLISHED_ARTICLE');
		} else {
			$tooltip = Text::_('COM_CONTENT_EDIT_PUBLISHED_ARTICLE');
		}

		$text = LayoutHelper::render('joomla.content.icons.edit', array('download' => $download, 'tooltip' => $tooltip, 'legacy' => $legacy));

		$attribs['aria-describedby'] = 'editarticle-' . (int) $download->id;
		$output = HTMLHelper::_('link', Route::_($url), $text, $attribs);

		return $output;
	}

	/**
	 * Method to generate a link to print an download
	 *
	 * @param   Registry  $params  The item parameters
	 * @param   boolean   $legacy  True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string  The HTML markup for the popup link
	 *
	 * @since  4.0.0
	 */
	public function print_screen($params, $legacy = false)
	{
		$text = LayoutHelper::render('joomla.content.icons.print_screen', array('params' => $params, 'legacy' => $legacy));

		return '<button type="button" onclick="window.print();return false;">' . $text . '</button>';
	}
}
