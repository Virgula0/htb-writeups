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

namespace JDownloads\Component\JDownloads\Administrator\Controller; 
 
\defined( '_JEXEC' ) or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Jdownloads Component Jdownloads Controller
 *
 * @package Joomla
 * @subpackage Jdownloads
 */
class LayoutsController extends AdminController
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

	}
    
    public function install() 
    {
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=layoutinstall');        
    }

    public function cssimport() 
    {
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=cssimport');        
    }
    
    public function cssexport() 
    {
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=cssexport');        
    } 
}
?>