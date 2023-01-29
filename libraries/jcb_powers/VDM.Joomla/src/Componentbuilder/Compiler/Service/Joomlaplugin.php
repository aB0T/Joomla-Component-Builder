<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    4th September, 2022
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Service;


use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\Data as JoomlaPluginData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\Builder as JoomlaPluginBuilder;


/**
 * Joomla Plugin Service Provider
 * 
 * @since 3.2.0
 */
class Joomlaplugin implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 * @since 3.2.0
	 */
	public function register(Container $container)
	{
		$container->alias(JoomlaPluginData::class, 'Joomlaplugin.Data')
			->share('Joomlaplugin.Data', [$this, 'getJoomlaPluginData'], true);

		$container->alias(JoomlaPluginBuilder::class, 'Joomlaplugin.Builder')
			->share('Joomlaplugin.Builder', [$this, 'getJoomlaPluginBuilder'], true);
	}

	/**
	 * Get the Joomla Plugin Data
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  JoomlaPluginData
	 * @since 3.2.0
	 */
	public function getJoomlaPluginData(Container $container): JoomlaPluginData
	{
		return new JoomlaPluginData(
			$container->get('Config'),
			$container->get('Customcode'),
			$container->get('Customcode.Gui'),
			$container->get('Placeholder'),
			$container->get('Language'),
			$container->get('Field'),
			$container->get('Field.Name'),
			$container->get('Model.Filesfolders')
		);
	}

	/**
	 * Get the Joomla Plugin Builder
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  JoomlaPluginBuilder
	 * @since 3.2.0
	 */
	public function getJoomlaPluginBuilder(Container $container): JoomlaPluginBuilder
	{
		return new JoomlaPluginBuilder(
			$container->get('Joomlaplugin.Data'),
			$container->get('Component'),
			$container->get('Config'),
			$container->get('Registry'),
			$container->get('Customcode.Dispenser'),
			$container->get('Event'),
			$container->get('Utilities.Counter'),
			$container->get('Utilities.Folder'),
			$container->get('Utilities.File'),
			$container->get('Utilities.Files')
		);
	}

}

