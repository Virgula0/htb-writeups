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
 
namespace JDownloads\Component\JDownloads\Site\Controller;

\defined('_JEXEC') or die;

setlocale(LC_ALL, 'C.UTF-8', 'C');

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Session\Session;

/**
*       
 */
class ReportController extends FormController
{
	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param	string	$name	The model name. Optional.
	 * @param	string	$prefix	The class prefix. Optional.
	 * @param	array	$config	Configuration array for model. Optional.
	 *
	 * @return	object	The model.
	 *
	 */
	public function getModel($name = 'form', $prefix = '', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Get the return URL.
	 *
	 * If a "return" variable has been passed in the request
	 *
	 * @return	string	The return URL.
	 */
	protected function getReturnPage()
	{
		$return = $this->input->get('return', null, 'base64');

		if (empty($return) || !Uri::isInternal(base64_decode($return))) {
			return URI::base();
		}
		else {
			return base64_decode($return);
		}
	}


	/**
	 * Method to send the report form data to the defined e-mail addresses
	 *
	 */
	public function send()
	{
	    // Check for request forgeries.
        Session::checkToken('request') or jexit(Text::_('JINVALID_TOKEN'));

        $model = $this->getModel('Report');
        if ($model->send()) {
            $type = 'message';
        } else {
            $type = 'error';
        }

        $msg = $model->getError();
        $this->setRedirect(Route::_('index.php?option=com_jdownloads'), $msg, $type);
    }

    /**
     * Method to cancel a report form.
     *
     */
    public function cancel($key = null)
    {
        // Check for request forgeries.
        Session::checkToken('request') or jexit(Text::_('JINVALID_TOKEN'));
        $this->setRedirect(Route::_('index.php?option=com_jdownloads'));
    }

}