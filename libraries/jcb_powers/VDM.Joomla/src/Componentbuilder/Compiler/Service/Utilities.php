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
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;


/**
 * Utilities Service Provider
 * 
 * @since 3.2.0
 */
class Utilities implements ServiceProviderInterface
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
		$container->alias(Folder::class, 'Utilities.Folder')
			->share('Utilities.Folder', [$this, 'getFolder'], true);

		$container->alias(File::class, 'Utilities.File')
			->share('Utilities.File', [$this, 'getFile'], true);

		$container->alias(Counter::class, 'Utilities.Counter')
			->share('Utilities.Counter', [$this, 'getCounter'], true);

		$container->alias(Paths::class, 'Utilities.Paths')
			->share('Utilities.Paths', [$this, 'getPaths'], true);

		$container->alias(Files::class, 'Utilities.Files')
			->share('Utilities.Files', [$this, 'getFiles'], true);
	}

	/**
	 * Get the Compiler Folder
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Folder
	 * @since 3.2.0
	 */
	public function getFolder(Container $container): Folder
	{
		return new Folder(
			$container->get('Utilities.Counter'),
			$container->get('Utilities.File')
		);
	}

	/**
	 * Get the Compiler File
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  File
	 * @since 3.2.0
	 */
	public function getFile(Container $container): File
	{
		return new File(
			$container->get('Utilities.Counter')
		);
	}

	/**
	 * Get the Compiler Counter
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Counter
	 * @since 3.2.0
	 */
	public function getCounter(Container $container): Counter
	{
		return new Counter(
			$container->get('Content')
		);
	}

	/**
	 * Get the Compiler Paths
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Paths
	 * @since 3.2.0
	 */
	public function getPaths(Container $container): Paths
	{
		return new Paths(
			$container->get('Config'),
			$container->get('Component')
		);
	}

	/**
	 * Get the Compiler Files Bucket
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Files
	 * @since 3.2.0
	 */
	public function getFiles(Container $container): Files
	{
		return new Files();
	}

}

