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
 
namespace JDownloads\Component\JDownloads\Administrator\Extension;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;
use Joomla\Component\Content\Administrator\Service\HTML\Icon;
use Psr\Container\ContainerInterface;

use JDownloads\Component\JDownloads\Administrator\Service\HTML\AdministratorService;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

require_once JPATH_SITE.'/administrator/components/com_jdownloads/src/Service/HTML/AdministratorService.php';


/**
 * Component class for com_jdownloads
 *
 * @since  4.0.0
 */
class JDownloadsComponent extends MVCComponent implements
    BootableExtensionInterface, AssociationServiceInterface,
    RouterServiceInterface, TagServiceInterface
{
    use AssociationServiceTrait;
    use RouterServiceTrait;
    use HTMLRegistryAwareTrait;
    use TagServiceTrait;
 
    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function boot(ContainerInterface $container)
    {
        $this->getRegistry()->register('jdownloadsadministrator', new AdministratorService);
        //$this->getRegistry()->register('contenticon', new Icon($container->get(SiteApplication::class)));

        // The layout joomla.content.icons does need a general icon service
        //$this->getRegistry()->register('icon', $this->getRegistry()->getService('contenticon'));
    
    }
    
    /**
     * Returns a valid section for the given section. If it is not valid then null
     * is returned.
     *
     * @param   string  $section  The section to get the mapping for
     * @param   object  $item     The item
     *
     * @return  string|null  The new section
     *
     * @since   4.0.0
     */
    public function validateSection($section, $item = null)
    {
        if (Factory::getApplication()->isClient('site'))
        {
            // On the front end we need to map some sections
            switch ($section)
            {
                // Editing an Download
                case 'form':

                    // Category list view
                case 'category':
                    $section = 'download';
            }
        }

        if ($section != 'download')
        {
            // We don't know other sections
            return null;
        }

        return $section;
    }

    /**
     * Returns valid contexts
     *
     * @return  array
     *
     * @since   4.0.0
     */
    public function getContexts(): array
    {
        Factory::getLanguage()->load('com_jdownloads', JPATH_ADMINISTRATOR);

        $contexts = array(
            'com_jdownloads.download'    => Text::_('COM_JDOWNLOADS_DOWNLOAD'),
            'com_jdownloads.categories'  => Text::_('COM_JDOWNLOADS_CATEGORY')
        );

        return $contexts;
    }
    
    /**
     * Returns the table for the count items functions for the given section.
     *
     * @param   string  $section  The section
     *
     * @return  string|null
     *
     * @since   4.0.0
     */
    protected function getTableNameForSection(string $section = null)
    {
        return '#__jdownloads_files';
    }    
    
    

}
?>