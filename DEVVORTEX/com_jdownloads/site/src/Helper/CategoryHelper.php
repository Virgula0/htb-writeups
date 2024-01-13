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

namespace JDownloads\Component\JDownloads\Site\Helper; 

\defined('_JEXEC') or die;

use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;


/**
 * jDownloads Component Category Tree
 *
 * @static
 */
class CategoryHelper extends CategoriesHelper
{
	public function __construct($options = array())
	{
		if (!is_array($options)){
            $options = array();
        }
        $options['table'] = '#__jdownloads_files';
        $options['extension'] = 'com_jdownloads';
        
		parent::__construct($options);
	}
    
    public function getExtension()
    {
        return 'com_jdownloads';
    }
}
