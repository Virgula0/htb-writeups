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

namespace JDownloads\Component\JDownloads\Administrator\Model;
 
\defined( '_JEXEC' ) or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

class UploadModel extends AdminModel
{

	function __construct()
	{
		parent::__construct();
	}
	
	function upload($fieldName)
	{
        // Check the file extension is ok */
        $fileName = $_FILES[$fieldName]['name'];

		// This is the name of the field in the html form, filedata is the default name for swfupload
		// So we will leave it as that
		$fieldName = 'Filedata';
 
		// Any errors the server registered on uploading
		$fileTemp = $_FILES[$fieldName]['tmp_name'];
        $uploadPath  = JPATH_SITE.'/jdownloads/'.$fileName ;
 
		if(!File::upload($fileTemp, $uploadPath, false, true)) 
		{
			echo Text::_( 'COM_JDOWNLOADS_UPLOAD_ERROR_MOVING_FILE' );
			return;
		}
		else
		{
			exit(0);
		}
	}
}
?>