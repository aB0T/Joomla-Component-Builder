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
use Joomla\CMS\HTML\HTMLHelper as Html;

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Libraryfiles Form Field class for the Componentbuilder component
 */
class JFormFieldLibraryfiles extends JFormFieldList
{
	/**
	 * The libraryfiles field type.
	 *
	 * @var        string
	 */
	public $type = 'libraryfiles';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return    array    An array of Html options.
	 */
	protected function getOptions()
	{
		// get the input from url
		$jinput = JFactory::getApplication()->input;
		// get the library id
		$id = $jinput->getInt('id', 0);
		// get custom the files
		$files = ComponentbuilderHelper::getLibraryFiles($id);
		// set the default
		$options[] = JHtml::_('select.option', '', JText::_('COM_COMPONENTBUILDER_NO_FILES_LINKED'));
		// now check if there are files in the folder
		if (ComponentbuilderHelper::checkArray($files))
		{
			$options = array();
			foreach ($files as $file => $name)
			{
				$options[] = JHtml::_('select.option', $file, $name);
			}
		}
		return $options;
	}
}
