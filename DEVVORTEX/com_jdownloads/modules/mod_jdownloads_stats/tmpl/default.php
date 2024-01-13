<?php
/**
* @version $Id: mod_jdownloads_stats.php
* @package mod_jdownloads_stats
* @copyright (C) 2018 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

// this is the default layout

defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsTop\Site\Helper\JDownloadsTopHelper;
use Joomla\CMS\Factory;

    $html = '<div class="moduletable'.$moduleclass_sfx.'">';
    
    if ($text <> ''){
        $text = str_replace('#1', '<font color="'.$color.'">'.$sumfiles.'</font>', $text);
        $text = str_replace('#2', '<font color="'.$color.'">'.$sumcats.'</font>', $text);
        $text = str_replace('#3', '<font color="'.$color.'">'.$sumdownloads.'</font>', $text);
        $text = str_replace('#4', '<font color="'.$color.'">'.$sumviews.'</font>', $text);
        $html .= '<div style="text-align:'.$alignment.'">'.$text.'</div>';
    }
    
    echo $html.'</div>'; 

?>