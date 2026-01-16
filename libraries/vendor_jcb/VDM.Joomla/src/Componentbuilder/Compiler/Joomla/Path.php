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

namespace VDM\Joomla\Componentbuilder\Compiler\Joomla;


use VDM\Joomla\Componentbuilder\Compiler\Placeholder;


/**
 * Core Namespace (path) Resolver.
 * 
 * Responsible for identifying and resolving canonical Joomla core namespace
 * paths for components, modules, and plugins.
 * 
 * This class maintains an immutable set of compiler-safe core namespace
 * definitions and provides fast, read-only detection of whether a fully
 * qualified namespace belongs to a known core extension scope.
 * 
 * Namespace definitions intentionally use fragmented placeholder tokens to
 * prevent premature resolution during the JCB compilation phase. All
 * placeholder substitution is applied lazily at read time via the injected
 * Placeholder service, ensuring canonical definitions remain unchanged.
 * 
 * Plugin namespace paths use a deferred ":" delimiter to represent an
 * unresolved plugin group boundary. The group name is unknown at definition
 * time and is resolved dynamically during namespace inspection.
 * 
 * This class is optimized for repeated hot-path lookups and performs only
 * minimal string operations with no mutation, caching, or allocation side
 * effects.
 * 
 * @since 5.1.4
 */
final class Path
{
	/**
	 * The Placeholder Class.
	 *
	 * @var   Placeholder
	 * @since 5.1.4
	 */
	private Placeholder $placeholder;

	/**
	 * Canonical core namespace path definitions.
	 *
	 * Provides deferred, compiler-safe namespace patterns for Joomla core
	 * extension types (components, modules, plugins).
	 *
	 * Placeholder tokens are intentionally fragmented to prevent premature
	 * resolution during the JCB compilation phase and are resolved only at
	 * their designated stage.
	 *
	 * Plugin paths intentionally use a ":" delimiter to represent a deferred
	 * plugin group boundary. The actual group name is unknown at this stage
	 * and is resolved downstream via explode-based routing logic.
	 *
	 * @var   array<string, string>
	 * @since 5.1.4
	 */
	private array $coreNamespace = [
		'admin' => '[' . '[' . '[Namespace' . 'Prefix]' . ']' . ']\Component\[' . '[' . '[Component' . 'Namespace]' . ']' . ']\Administrator',
		'site' => '[' . '[' . '[Namespace' . 'Prefix]' . ']' . ']\Component\[' . '[' . '[Component' . 'Namespace]' . ']' . ']\Site',
// TODO: target the module and plugin also...
//		'mod_admin' => '[' . '[' . '[Namespace' . 'Prefix]' . ']' . ']\Module\[' . '[' . '[Component' . 'Namespace]' . ']' . ']\Administrator',
//		'mod_site' => '[' . '[' . '[Namespace' . 'Prefix]' . ']' . ']\Module\[' . '[' . '[Component' . 'Namespace]' . ']' . ']\Site',
//		'plugin' => '[' . '[' . '[Namespace' . 'Prefix]' . ']' . ']\Plugin\:\[' . '[' . '[Component' . 'Namespace]' . ']' . ']',
	];

	/**
	 * Constructor.
	 *
	 * @param Placeholder   $placeholder   The Placeholder Class.
	 *
	 * @since 5.1.4
	 */
	public function __construct(Placeholder $placeholder)
	{
		$this->placeholder = $placeholder;
	}

	/**
	 * Detect whether a fully qualified namespace belongs to a core namespace.
	 *
	 * Performs a fast, allocation-free lookup to determine whether the given
	 * namespace matches one of the canonical core namespace prefixes.
	 *
	 * Component and module namespaces are matched via strict prefix checks.
	 * Plugin namespaces support a deferred group segment (after ":") which
	 * is matched as a namespace segment anywhere after the plugin base.
	 *
	 * Returns the matching core path key on success, or null on failure.
	 *
	 * @param   string  $namespace  Fully qualified namespace.
	 *
	 * @return  string|null  Core namespace key on match, null otherwise.
	 *
	 * @since   5.1.4
	 */
	public function core(string $namespace): ?string
	{
		if ($namespace === '')
		{
			return null;
		}

		foreach ($this->get() as $key => $corePath)
		{
			// Plugin path (contains deferred group delimiter)
			if (($colonPos = strpos($corePath, ':')) !== false)
			{
				$base  = substr($corePath, 0, $colonPos);
				$component = substr($corePath, $colonPos + 1);

				// Must begin with plugin base
				if (strncmp($namespace, $base, strlen($base)) !== 0)
				{
					continue;
				}

				if (strpos($namespace, $component) !== false)
				{
					return $key;
				}

				continue;
			}

			// Component / module (pure prefix match)
			if (strncmp($namespace, $corePath, strlen($corePath)) === 0)
			{
				return $key;
			}
		}

		return null;
	}

	/**
	 * Retrieve core namespace paths with placeholders resolved for the current context.
	 *
	 * Applies placeholder updates to the requested path definitions at read time
	 * without mutating the internally stored canonical core paths.
	 *
	 * When no key is provided, all core paths are returned with placeholder
	 * transformations applied to each value.
	 *
	 * When a key is provided, only the resolved namespace path for that key
	 * is returned.
	 *
	 * @param   string|null  $key  Optional path key (e.g. "com_admin", "plugin").
	 *
	 * @return  array<string, string>|string|null
	 *          Resolved path map when no key is provided.
	 *          Resolved namespace path string when a key is provided and exists.
	 *          Null when a key is provided but no matching path exists.
	 *
	 * @since   5.1.4
	 */
	public function get(?string $key = null)
	{
		if ($key === null)
		{
			$resolved = [];

			foreach ($this->coreNamespace as $name => $path)
			{
				$resolved[$name] = $this->placeholder->update_($path);
			}

			return $resolved;
		}

		if (!array_key_exists($key, $this->coreNamespace))
		{
			return null;
		}

		return $this->placeholder->update_($this->coreNamespace[$key]);
	}
}

