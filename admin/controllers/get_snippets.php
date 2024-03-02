<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    30th April, 2015
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Get_snippets Admin Controller
 */
class ComponentbuilderControllerGet_snippets extends AdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_COMPONENTBUILDER_GET_SNIPPETS';

	/**
	 * Proxy for getModel.
	 * @since    2.5
	 */
	public function getModel($name = 'Get_snippets', $prefix = 'ComponentbuilderModel', $config = [])
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	public function dashboard()
	{
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder', false));
		return;
	}

	public function openLibraries()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// redirect to the libraries
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder&view=libraries', false));
		return;
	}

	public function openSnippets()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// redirect to the snippets
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder&view=snippets', false));
		return;
	}

	public function openSiteViews()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// redirect to the site views
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder&view=site_views', false));
		return;
	}

	public function openCustomAdminViews()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// redirect to the custom admin views
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder&view=custom_admin_views', false));
		return;
	}

	public function openTemplates()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// redirect to the templates
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder&view=templates', false));
		return;
	}

	public function openLayouts()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// redirect to the layouts
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder&view=layouts', false));
		return;
	}
}
