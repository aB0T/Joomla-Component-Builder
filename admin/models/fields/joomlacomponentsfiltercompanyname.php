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

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Joomlacomponentsfiltercompanyname Form Field class for the Componentbuilder component
 */
class JFormFieldJoomlacomponentsfiltercompanyname extends JFormFieldList
{
	/**
	 * The joomlacomponentsfiltercompanyname field type.
	 *
	 * @var		string
	 */
	public $type = 'joomlacomponentsfiltercompanyname';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array    An array of JHtml options.
	 */
	protected function getOptions()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('companyname'));
		$query->from($db->quoteName('#__componentbuilder_joomla_component'));
		$query->order($db->quoteName('companyname') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();
		$_filter = array();

		if ($results)
		{
			$results = array_unique($results);
			foreach ($results as $companyname)
			{
				// Now add the companyname and its text to the options array
				$_filter[] = JHtml::_('select.option', $companyname, $companyname);
			}
		}
		return $_filter;
	}
}
