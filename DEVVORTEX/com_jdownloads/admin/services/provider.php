<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_jdownloads
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Association\AssociationExtensionInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Administrator\Helper\AssociationsHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;

require_once JPATH_SITE.'/administrator/components/com_jdownloads/src/Helper/AssociationsHelper.php';
require_once JPATH_SITE.'/administrator/components/com_jdownloads/src/Helper/associations.php';
require_once JPATH_SITE.'/components/com_jdownloads/src/Helper/AssociationHelper.php';
require_once JPATH_SITE.'/administrator/components/com_jdownloads/src/Extension/JDownloadsComponent.php';


/**
 * The jDownloads service provider.
 *
 * @since  4.0
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		$container->set(AssociationExtensionInterface::class, new AssociationsHelper);

		//$container->registerServiceProvider(new CategoryFactory('\\JDownloads\\Component\\JDownloads'));
		$container->registerServiceProvider(new MVCFactory('\\JDownloads\\Component\\JDownloads'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\JDownloads\\Component\\JDownloads'));
		$container->registerServiceProvider(new RouterFactory('\\JDownloads\\Component\\JDownloads'));

		$container->set(
			ComponentInterface::class,
			function (Container $container)
			{
				$component = new JDownloadsComponent($container->get(ComponentDispatcherFactoryInterface::class));

				$component->setRegistry($container->get(Registry::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				//$component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
				$component->setAssociationExtension($container->get(AssociationExtensionInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
