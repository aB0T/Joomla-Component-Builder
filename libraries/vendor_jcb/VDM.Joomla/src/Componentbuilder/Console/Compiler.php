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

namespace VDM\Joomla\Componentbuilder\Console;


use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VDM\Joomla\Componentbuilder\Compiler\Factory as JCB;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Joomla\Abstraction\Console;


/**
 * Compiler Command (CLI infrastructure for the compiler).
 * 
 * Provides:
 * - Consistent compiler CLI options (GUI parity)
 * - Component GUID resolution (inline, JSON, file, env fallback)
 * - Compiler option normalization into Joomla input (underscore keys)
 * - Optional bundle options support (JSON or @file)
 * - Strict allowed-value validation for known radio/list fields
 * - Flushes both MessageBus messages and Joomla application message queue to terminal
 * - Safe exception handling and stable exit codes
 * 
 * @since  5.1.4
 */
final class Compiler extends Console
{
	/**
	 * The component option (com_example).
	 *
	 * @var   string
	 * @since 5.1.4
	 */
	protected const COMPONENT_OPTION = 'com_componentbuilder';

	/**
	 * Environment variable for a single component GUID.
	 *
	 * @var   string
	 * @since 5.1.4
	 */
	protected const ENV_COMPONENT = 'JCB_COMPILE_COMPONENT';

	/**
	 * Environment variable for components list (CSV/newlines/JSON).
	 *
	 * @var   string
	 * @since 5.1.4
	 */
	protected const ENV_COMPONENTS = 'JCB_COMPILE_COMPONENTS';

	/**
	 * Environment variable for components file path.
	 *
	 * @var   string
	 * @since 5.1.4
	 */
	protected const ENV_COMPONENTS_FILE = 'JCB_COMPILE_COMPONENTS_FILE';

	/**
	 * Environment variable for options bundle (JSON or @file).
	 *
	 * @var   string
	 * @since 5.1.4
	 */
	protected const ENV_OPTIONS = 'JCB_COMPILER_OPTIONS';

	/**
	 * Per-option environment prefix (e.g. JCB_BACKUP=1).
	 *
	 * @var   string
	 * @since 5.1.4
	 */
	protected const ENV_PREFIX = 'JCB_';

	/**
	 * The SymfonyStyle IO helper (HUMAN OUTPUT -> STDERR).
	 *
	 * @var   SymfonyStyle
	 * @since 5.1.4
	 */
	protected SymfonyStyle $io;

	/**
	 * STDOUT stream (MACHINE OUTPUT).
	 *
	 * @var   OutputInterface
	 * @since 5.1.4
	 */
	protected OutputInterface $stdout;

	/**
	 * STDERR stream (HUMAN OUTPUT).
	 *
	 * @var   OutputInterface
	 * @since 5.1.4
	 */
	protected OutputInterface $stderr;

	/**
	 * The messages.
	 *
	 * @var   array<int, string>
	 * @since 5.1.4
	 */
	protected array $messages = [];

	/**
	 * Collected machine-output paths.
	 *
	 * @var   array<int, string>
	 * @since 5.1.4
	 */
	protected array $outputPaths = [];

	/**
	 * Command constructor.
	 *
	 * @param  string  $name  The full command name (e.g. component:compile)
	 *
	 * @since  5.1.4
	 */
	public function __construct(string $name)
	{
		if ($name === '')
		{
			throw new \InvalidArgumentException('Command name may not be empty.');
		}

		// Component context for CLI execution
		Helper::setOption(static::COMPONENT_OPTION);

		// Load administrator language file for backend
		$lang = Factory::getApplication()->getLanguage();
		$lang->load(static::COMPONENT_OPTION, JPATH_ADMINISTRATOR);

		parent::__construct($name);

		// Keeps reflection-based tooling consistent
		static::$defaultName = $name;
	}

	/**
	 * Initialize common Joomla CLI context and IO helper.
	 *
	 * IMPORTANT:
	 * - STDOUT = machine output (pipe-safe)
	 * - STDERR = human messages
	 *
	 * @param   InputInterface   $input
	 * @param   OutputInterface  $output
	 *
	 * @return  void
	 * @since   5.1.4
	 */
	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		$this->stdout ??= $output;
		$this->stderr ??= $output->getErrorOutput();

		// SymfonyStyle must NEVER write to STDOUT
		$this->io ??= new SymfonyStyle($input, $this->stderr);
	}

	/**
	 * Configure the command.
	 *
	 * NOTE:
	 * - Component selection is required, but may be provided via multiple mechanisms.
	 * - All compiler options are optional; omitted options imply GLOBAL behavior downstream.
	 *
	 * @return void
	 * @since  5.1.4
	 */
	protected function configure(): void
	{
		$this->setDescription('Compile a component using the JCB compiler via CLI.');

		$this->setHelp(
<<<'EOF'
Compile a component using the JCB compiler via CLI.

Component inputs (at least one is required):
  --component / -c         Single component GUID
  --components             CSV/newline/JSON list of component GUIDs
  --components-file        Path to file containing components (CSV/newlines/JSON)
  --components=@/path      Shorthand file syntax via --components option

Options bundle:
  --options / -o           JSON object or @/path/to/file (merged; explicit CLI flags override)

Environment fallbacks:
  JCB_COMPILE_COMPONENT
  JCB_COMPILE_COMPONENTS
  JCB_COMPILE_COMPONENTS_FILE
  JCB_COMPILER_OPTIONS

Per-option environment variables:
  JCB_BACKUP
  JCB_REPOSITORY
  JCB_ADD_PLACEHOLDERS
  JCB_DEBUG_LINE_NR
  JCB_MINIFY
  JCB_POWERS
  JCB_JOOMLA_VERSION
  JCB_SHOW_ADVANCED_OPTIONS
  JCB_POWERS_REPOSITORY
  JCB_INDENTATION_VALUE
  JCB_ADD_BUILD_DATE
  JCB_BUILD_DATE

Notes:
  - Options are injected into Joomla input using underscore keys (e.g. add_placeholders).
  - If an option is not provided, it is not set (GLOBAL behavior is handled downstream).
EOF
		);

		$this->addOption(
			'component',
			'c',
			InputOption::VALUE_OPTIONAL,
			'Single component GUID. ENV fallback: ' . static::ENV_COMPONENT
		);

		$this->addOption(
			'components',
			null,
			InputOption::VALUE_OPTIONAL,
			'Components list as CSV/newlines/JSON. Supports @/path/to/file. ENV fallback: ' . static::ENV_COMPONENTS
		);

		$this->addOption(
			'components-file',
			null,
			InputOption::VALUE_OPTIONAL,
			'Path to file containing components (CSV/newlines/JSON). ENV fallback: ' . static::ENV_COMPONENTS_FILE
		);

		// GUI parity options
		$this->addSharedOptions();
	}

	/**
	 * Register shared CLI options derived from the Builder dynamic form.
	 *
	 * @param  array<string>  $exclude  A list of option keys to exclude.
	 *
	 * @return void
	 * @since  5.1.4
	 */
	protected function addSharedOptions(): void
	{
		$this->addOption('backup', null, InputOption::VALUE_OPTIONAL,
			'Add compiled package to backup and sales server. Values: 1=yes, 0=no.'
		);

		$this->addOption('repository', null, InputOption::VALUE_OPTIONAL,
			'Move compiled component to local repository folder. Values: 1=yes, 0=no.'
		);

		$this->addOption('add-placeholders', null, InputOption::VALUE_OPTIONAL,
			'Insert custom code placeholders. Values: 2=global, 1=yes, 0=no.'
		);

		$this->addOption('debug-line-nr', null, InputOption::VALUE_OPTIONAL,
			'Add compiler debug line numbers. Values: 2=global, 1=yes, 0=no.'
		);

		$this->addOption('minify', null, InputOption::VALUE_OPTIONAL,
			'Minify JavaScript output. Values: 2=global, 1=yes, 0=no.'
		);

		$this->addOption('powers', null, InputOption::VALUE_OPTIONAL,
			'Add powers linked to the component. Values: 2=global, 1=yes, 0=no.'
		);

		$this->addOption('joomla-version', null, InputOption::VALUE_OPTIONAL,
			'Target Joomla version. Allowed: 3, 4, 5, 6.'
		);

		$this->addOption('show-advanced-options', null, InputOption::VALUE_OPTIONAL,
			'Enable advanced compiler options. Values: 1=yes, 0=no.'
		);

		$this->addOption('powers-repository', null, InputOption::VALUE_OPTIONAL,
			'Activate Super Powers repository sync. Values: 2=global, 1=yes, 0=no.'
		);

		$this->addOption('indentation-value', null, InputOption::VALUE_OPTIONAL,
			'Indentation style. Values: 1=tab, 2=two spaces, 4=four spaces.'
		);

		$this->addOption('add-build-date', null, InputOption::VALUE_OPTIONAL,
			'Build date mode. Values: 1=default, 2=manual, 3=component.'
		);

		$this->addOption('build-date', null, InputOption::VALUE_OPTIONAL,
			'Manual build date (YYYY-MM-DD). Used when --add-build-date=2.'
		);

		// Options bundle (JSON or @file)
		$this->addOption(
			'options',
			'o',
			InputOption::VALUE_OPTIONAL,
			'Compiler options as JSON or @/path/to/file (merged; explicit CLI flags override). ENV fallback: ' . static::ENV_OPTIONS
		);
	}

	/**
	 * Execute wrapper with consistent exception handling.
	 *
	 * @param   InputInterface   $input
	 * @param   OutputInterface  $output
	 *
	 * @return  int
	 * @since   5.1.4
	 */
	final protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		try
		{
			$this->initialize($input, $output);
			$status = (int) $this->doExecuteAction($input);
		}
		catch (\InvalidArgumentException $e)
		{
			$this->io->error($e->getMessage());
			return 1;
		}
		catch (\Throwable $e)
		{
			$this->io->error('An unexpected error occurred.');
			$this->io->writeln($e->getMessage());
			return 2;
		}

		// Flush human messages
		$appOut = $this->renderApplicationMessages();
		$busOut = $this->renderMessageBus();

		if (!$busOut && !$appOut && $status === 0)
		{
			$this->io->success('Task completed with no additional messages.');
		}

		// Emit MACHINE output LAST (STDOUT)
		if ($status === 0)
		{
			// $this->emitMachineOutput();
		}

		return (int) $status;
	}

	/**
	 * Action-specific compiler logic.
	 *
	 * @param   InputInterface   $input
	 *
	 * @return  int
	 * @since   5.1.4
	 */
	protected function doExecuteAction(InputInterface $input): int
	{
		$components = $this->resolveComponents($input);

		if ($components === [])
		{
			throw new \InvalidArgumentException(
				'No component GUID(s) provided. Use --component, --components, --components-file, or environment variables.'
			);
		}

		LayoutHelper::$defaultBasePath =
			JPATH_ADMINISTRATOR . '/components/com_componentbuilder/layouts';

		$this->normalizeCompilerOptions($input);

		$appInput = $this->getApplication()->getInput();
		$status   = 0;

		foreach ($components as $componentGuid)
		{
			$component = is_numeric($componentGuid)
				? $componentGuid
				: JCB::_('Data.Item')->table('joomla_component')->value($componentGuid);

			if (!is_numeric($component))
			{
				$this->io->error('Component GUID "' . $componentGuid . '" not found.');
				$status = 1;
				continue;
			}

			$appInput->post->set('component_id', $component);

			$this->io->section('Compile Request');
			$this->io->definitionList(['Component' => $componentGuid]);

			if (!JCB::_('Compiler')->run())
			{
				$this->io->error('Compiler failed');
				$status = 1;
				JCB::unset();
				continue;
			}

			$message = LayoutHelper::render('jcbbuildersuccessmessagecli');
			$message = JCB::_('Placeholder')->update(
				$message,
				JCB::_('Compiler.Builder.Content.One')->allActive()
			);

			$this->messages[] = $message;

			$this->collectCompilerPaths();

			JCB::unset();
		}

		return $status;
	}

	/**
	 * Collect compiled file paths from the compiler.
	 *
	 * @return  void
	 * @since   5.1.4
	 */
	protected function collectCompilerPaths(): void
	{
		$component = JCB::_('FilePaths')->get('component');
		$modules = JCB::_('FilePaths')->get('modules');
		$plugins = JCB::_('FilePaths')->get('plugins');

		if (!empty($component))
		{
			$this->outputPaths[] = $component;
		}

		if (!empty($modules))
		{
			foreach($modules as $module)
			{
				$this->outputPaths[] = $module;
			}
		}

		if (!empty($plugins))
		{
			foreach($plugins as $plugin)
			{
				$this->outputPaths[] = $plugin;
			}
		}
	}

	/**
	 * Emit machine-readable output to STDOUT.
	 *
	 * Default format:
	 * - One path per line (Unix-native)
	 *
	 * @return  void
	 * @since   5.1.4
	 */
	protected function emitMachineOutput(): void
	{
		if ($this->outputPaths === [])
		{
			return;
		}

		$paths = array_values(array_unique($this->outputPaths));

		foreach ($paths as $path)
		{
			$this->stdout->writeln($path);
		}
	}

	/**
	 * Resolve component GUID(s) from CLI / ENV / file / JSON.
	 *
	 * Supports:
	 * - --component GUID
	 * - --components CSV/newlines/JSON
	 * - --components @/path/to/file
	 * - --components-file /path/to/file
	 * - ENV fallbacks
	 *
	 * @param   InputInterface  $input
	 *
	 * @return  array<int, string>
	 * @since   5.1.4
	 */
	protected function resolveComponents(InputInterface $input): array
	{
		$single = (string) ($input->getOption('component') ?? '');
		if ($single === '')
		{
			$single = (string) getenv(static::ENV_COMPONENT);
		}

		$list = (string) ($input->getOption('components') ?? '');
		if ($list === '')
		{
			$list = (string) getenv(static::ENV_COMPONENTS);
		}

		$file = (string) ($input->getOption('components-file') ?? '');
		if ($file === '')
		{
			$file = (string) getenv(static::ENV_COMPONENTS_FILE);
		}

		$values = [];

		if ($single !== '')
		{
			$values[] = $single;
		}

		// @file shorthand on --components
		if ($list !== '' && str_starts_with($list, '@'))
		{
			$file = substr($list, 1);
			$list = '';
		}

		if ($list !== '')
		{
			$values = array_merge($values, $this->parseStringList($list));
		}

		if ($file !== '')
		{
			$contents = $this->readFileContents($file, 'components-file');
			$values   = array_merge($values, $this->parseStringList($contents));
		}

		return $this->normalizeGuidList($values);
	}

	/**
	 * Normalize compiler options into Joomla input (underscore keys).
	 *
	 * Resolution order:
	 * 1. Per-option ENV variables (JCB_<OPTION>)
	 * 2. Options bundle (--options or ENV JCB_COMPILER_OPTIONS)
	 * 3. Explicit CLI flags (kebab-case)
	 *
	 * GLOBAL semantics:
	 * - If a value is not resolved, it is NOT set.
	 *
	 * @param   InputInterface  $input
	 *
	 * @return  void
	 * @since   5.1.4
	 */
	protected function normalizeCompilerOptions(InputInterface $input): void
	{
		$appInput = $this->getApplication()->getInput();

		$allowed = [
			'backup'                => ['0', '1'],
			'repository'            => ['0', '1'],
			'add_placeholders'      => ['0', '1', '2'],
			'debug_line_nr'         => ['0', '1', '2'],
			'minify'                => ['0', '1', '2'],
			'powers'                => ['0', '1', '2'],
			'joomla_version'        => ['3', '4', '5', '6'],
			'show_advanced_options' => ['0', '1'],
			'powers_repository'     => ['0', '1', '2'],
			'indentation_value'     => ['1', '2', '4'],
			'add_build_date'        => ['1', '2', '3'],
			'build_date'            => null, // string date; downstream decides
		];

		$resolved = [];

		// 1) Per-option ENV fallback
		foreach ($allowed as $uKey => $_)
		{
			if (!isset($resolved[$uKey]))
			{
				$envName = static::ENV_PREFIX . strtoupper($uKey);
				$envVal  = getenv($envName);

				if ($envVal !== false && $envVal !== '')
				{
					$resolved[$uKey] = (string) $envVal;
				}
			}
		}

		// 2) Bundle JSON (string or @file)
		$bundle = (string) ($input->getOption('options') ?? '');
		if ($bundle === '')
		{
			$bundle = (string) getenv(static::ENV_OPTIONS);
		}

		if ($bundle !== '')
		{
			if (str_starts_with($bundle, '@'))
			{
				$bundle = $this->readFileContents(substr($bundle, 1), 'options');
			}

			$data = json_decode($bundle, true);

			if (!is_array($data))
			{
				throw new \InvalidArgumentException('Invalid compiler options JSON (bundle).');
			}

			foreach ($data as $key => $value)
			{
				$key = $this->normalizeOptionKey((string) $key);
				if (array_key_exists($key, $allowed))
				{
					$resolved[$key] = (string) $value;
				}
			}
		}

		// 3) Explicit CLI flags override bundle
		foreach ($allowed as $uKey => $_)
		{
			$cliKey = str_replace('_', '-', $uKey);
			$val = $input->getOption($cliKey);

			if ($val !== null)
			{
				$resolved[$uKey] = (string) $val;
			}
		}

		// Validate + inject
		foreach ($resolved as $uKey => $val)
		{
			$permitted = $allowed[$uKey];

			if (is_array($permitted) && !in_array($val, $permitted, true))
			{
				throw new \InvalidArgumentException(
					sprintf('Invalid value "%s" for option "%s".', $val, $uKey)
				);
			}

			$appInput->post->set($uKey, $val);
		}
	}

	/**
	 * Render local message queue to CLI output.
	 *
	 * @return bool
	 * @since  5.1.4
	 */
	protected function renderMessageBus(): bool
	{
		if ($this->messages === [])
		{
			return false;
		}

		foreach ($this->messages as $message)
		{
			$this->io->success($message);
		}

		return true;
	}

	/**
	 * Render Joomla application message queue to CLI output.
	 *
	 * @return bool
	 * @since  5.1.4
	 */
	protected function renderApplicationMessages(): bool
	{
		$queue = $this->getApplication()->getMessageQueue();

		if (!$queue)
		{
			return false;
		}

		foreach ($queue as $message)
		{
			$type = $message['type'] ?? 'info';
			$text = $message['message'] ?? '';

			if ($type === 'error')
			{
				$this->io->error($text);
			}
			elseif ($type === 'warning')
			{
				$this->io->warning($text);
			}
			else
			{
				$this->io->writeln($text);
			}
		}

		return true;
	}

	/**
	 * Normalize list string (CSV/newlines/JSON).
	 *
	 * @param   string  $raw
	 *
	 * @return  array
	 * @since   5.1.4
	 */
	protected function parseStringList(string $raw): array
	{
		$raw = trim($raw);

		if ($raw === '')
		{
			return [];
		}

		$decoded = json_decode($raw, true);

		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
		{
			// allow { "components": [...] } or plain list
			if (array_keys($decoded) !== range(0, count($decoded) - 1))
			{
				foreach (['components', 'items'] as $k)
				{
					if (isset($decoded[$k]) && is_array($decoded[$k]))
					{
						return $decoded[$k];
					}
				}

				return array_values($decoded);
			}

			return $decoded;
		}

		$raw = str_replace(["\r\n", "\r"], "\n", $raw);

		if (str_contains($raw, ','))
		{
			return explode(',', $raw);
		}

		return explode("\n", $raw);
	}

	/**
	 * Read file contents safely.
	 *
	 * @param   string  $path
	 * @param   string  $optionName
	 *
	 * @return  string
	 * @since   5.1.4
	 */
	protected function readFileContents(string $path, string $optionName): string
	{
		$path = trim($path);

		if ($path === '')
		{
			throw new \InvalidArgumentException("The --{$optionName} value is empty.");
		}

		if (!is_file($path) || !is_readable($path))
		{
			throw new \InvalidArgumentException("Unable to read file for --{$optionName}: {$path}");
		}

		$contents = file_get_contents($path);

		if ($contents === false)
		{
			throw new \InvalidArgumentException("Failed to read file for --{$optionName}: {$path}");
		}

		return $contents;
	}

	/**
	 * Normalize string(GUID) list: trim, drop empties, de-duplicate.
	 *
	 * @param   array  $values
	 *
	 * @return  array<int, string>
	 * @since   5.1.4
	 */
	protected function normalizeGuidList(array $values): array
	{
		$out = [];

		foreach ($values as $value)
		{
			$value = trim((string) $value);

			if ($value !== '' && GuidHelper::valid($value))
			{
				$out[] = $value;
			}
		}

		return array_values(array_unique($out));
	}

	/**
	 * Normalize an option key to underscore + lowercase.
	 *
	 * @param   string  $key
	 *
	 * @return  string
	 * @since   5.1.4
	 */
	protected function normalizeOptionKey(string $key): string
	{
		$key = trim($key);

		return strtolower(str_replace('-', '_', $key));
	}
}

