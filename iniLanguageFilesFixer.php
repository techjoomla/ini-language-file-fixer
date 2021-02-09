<?php
/**
 * @package     JoomlaScripts
 * @subpackage  ini-language-files-fixer
 *
 * @author      Manoj L <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

error_reporting(0);
error_reporting(E_ALL);

/**
 * Class for .ini language files fixes
 *
 * @since  1.0.0
 */
class IniLanguageFilesFixer
{
	/**
	 * Constructor
	 *
	 * @param   string  $langFile   LangFile path
	 * @param   string  $directory  Directory path
	 */
	public function __construct($langFile, $directory)
	{
		$this->sourceFile        = $langFile;
		$this->sourceDir         = $directory;

		$this->unusedConstantsCount    = 0;
		$this->duplicateConstantsCount = 0;
	}

	/**
	 * Check and fix unused constants
	 *
	 * @param   boolean  $fixUnused  Comments out unsed constants
	 *
	 * @return void
	 */
	public function checkUnused($fixUnused)
	{
		$langFile      = $this->sourceFile;
		$langConstants = file_get_contents($langFile);
		$langConstants = explode('
', $langConstants
		);

		// Loop through all constants
		foreach ($langConstants as $lc)
		{
			// Eg: COM_ABC_CONST="Value of constant"
			$lc = explode('=', $lc);

			if (count($lc) == 2)
			{
				// If not commented
				if ($lc[0][0] != ';')
				{
					$constants[] = $lc[0];
				}
			}
		}

		// Get all files
		$directory = new \RecursiveDirectoryIterator($this->sourceDir);
		$iterator  = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

		// Check for all constants in all files
		foreach ($constants as $lc)
		{
			// @echo "\033[0;32m \n\n>>>> Checking constant: " . $lc;

			$found = 0;

			// Loop through all files
			foreach ($iterator as $file)
			{
				if ($file->isFile())
				{
					// Get file extension, name
					$fileExtension = $file->getExtension();
					$fileName      = $file->getFilename();
					$filePath      = $file->getPathname();

					// Only process php files
					if ($fileExtension == 'php' || $fileExtension == 'xml')
					{
						// @echo "\033[0;32m \n\n>>>> Checking file: " . $filePath;
						$searchResult = $this->langConstantExistsInFile($lc, $filePath);

						if ($searchResult === 1)
						{
							$found = 1;

							break;
						}
					}
				}
			}

			if ($found === 0)
			{
				echo "\033[0;31m \n>>>> Unused constant found: " . $lc;
				$unusedConstants[] = $lc;
				$this->unusedConstantsCount++;
			}
		}

		// @echo "Total " . $this->unusedConstantsCount . " contants found!";
		// @print_r($unusedConstants);

		if ($fixUnused)
		{
			$langConstants = file_get_contents($langFile);
			$langConstants = explode('
', $langConstants
			);

			foreach ($langConstants as $lc)
			{
				$lc = explode('=', $lc);

				if (count($lc) == 2)
				{
					// Comment out unused
					if (in_array($lc[0], $unusedConstants))
					{
						$lc[0] = ";" . $lc[0];
					}
				}

				$finalLangConstants[] = $lc;
			}

			// @print_r($finalLangConstants);

			foreach ($finalLangConstants as $lc)
			{
				if (count($lc) == 2)
				{
					$lc = implode('=', $lc);
				}
				else
				{
					$lc = $lc[0];
				}

				$finalLangConstants2[] = $lc;
			}

			// @print_r($finalLangConstants2);

			$finalLangFileContents = implode('
', $finalLangConstants2
			);

			// @print_r($finalLangFileContents);

			$langFile = str_replace('.ini', '_fixed_unused.ini', $langFile);
			file_put_contents($langFile, $finalLangFileContents);
		}
	}

	/**
	 * Check and fix duplicates constants
	 *
	 * @param   boolean  $fixDuplicates  Comments out duplicates constants
	 *
	 * @return void
	 */
	public function checkDuplicates($fixDuplicates = false)
	{
		$langFile      = $this->sourceFile;
		$langConstants = file_get_contents($langFile);
		$langConstants = explode('
', $langConstants
		);

		$duplicateLangConstants = array();

		foreach ($langConstants as $lc)
		{
			$lc = explode('=', $lc);

			if (count($lc) == 2)
			{
				if ($lc[0][0] != ';')
				{
					$duplicateLangConstants[] = $lc[0];
				}
			}
		}

		$duplicateLangConstants = (array_count_values($duplicateLangConstants));
		$count = 0;

		foreach ($duplicateLangConstants as $key => $val)
		{
			if ($val === 1)
			{
				unset($duplicateLangConstants[$key]);
			}
			else
			{
				$count += $val - 1;
			}
		}

		echo "\nTotal duplicate CONSTANTS are - " . count($duplicateLangConstants);
		echo "\nTotal duplicate CONSTANTS lines that can be reduced are - " . $count;
		echo "\n";
		print_r($duplicateLangConstants);

		$duplicateLangConstantValues = array();

		foreach ($langConstants as $lc)
		{
			$lc = explode('=', $lc);

			if (count($lc) == 2)
			{
				if ($lc[0][0] != ';')
				{
					$duplicateLangConstantValues[] = $lc[1];
				}
			}
		}

		// @print_r($duplicateLangConstantValues);
		$duplicateLangConstantValues = (array_count_values($duplicateLangConstantValues));
		$count = 0;

		foreach ($duplicateLangConstantValues as $key => $val)
		{
			if ($val === 1)
			{
				unset($duplicateLangConstantValues[$key]);
			}
			else
			{
				$count += $val - 1;
			}
		}

		echo "\nTotal duplicate VALUES are - " . count($duplicateLangConstantValues);
		echo "\nTotal duplicate VALUES lines that can be reduced are - " . $count;
		echo "\n";

		// @print_r($duplicateLangConstantValues);
		echo $json = json_encode($duplicateLangConstantValues, JSON_PRETTY_PRINT);
	}

	/**
	 * Reads a file and searches for constant
	 *
	 * @param   string  $constant  The constant
	 * @param   string  $file      The path to the file
	 *
	 * @return    boolean
	 */
	protected function langConstantExistsInFile($constant, $file)
	{
		$content = (array) file($file, FILE_SKIP_EMPTY_LINES);

		// Get the constants to look for
		foreach ($content as $line_num => $line)
		{
			// @echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . " -=> ";
			// @echo $i . '-' ; var_dump($line);

			// Search for constant
			$pos_1 = mb_strpos($line, trim($constant), 0, 'utf-8');

			// Check the position of the words
			if ($pos_1 !== false)
			{
				unset($content);

				return 1;
			}
		}

		unset($content);

		return 0;
	}
}

/**
 * Print usage instructions
 *
 * @return  void
 */
function printUsageInstructions()
{
	echo "\n\033[0;32m
	Usage: \n
	php iniLanguageFilesFixer.php check-unused /path/to/valid/lang/file /path/to/valid/source/directory
	\n [or] \n
	php iniLanguageFilesFixer.php fix-unused /path/to/valid/lang/file /path/to/valid/source/directory
	\n [or] \n
	php iniLanguageFilesFixer.php check-duplicates /path/to/valid/lang/file /path/to/valid/source/directory\n";
}

// @print_r($argv);

if (!isset ($argv[1]))
{
	printUsageInstructions();
	exit;
}

// Check for command
if (isset ($argv[1])
	&& ($argv[1] !== 'check-unused'
	&& $argv[1] !== 'fix-unused'
	&& $argv[1] !== 'check-duplicates'))
{
	echo "\033[0;31m Provide valid command check-duplicates or fix-unused or check-duplicates";
	printUsageInstructions();
	exit;
}

// Check for if .ini file path is given
if (isset($argv[2]) && !strpos($argv[2], '.ini'))
{
	echo "\033[0;31m Provide valid .ini path";
	printUsageInstructions();
	exit;
}

// Check for directory is provided
if (isset($argv[3]) && !is_dir($argv[3]))
{
	echo "\033[0;31m Provide valid source path";
	printUsageInstructions();
	exit;
}
else
{
	// Saves the start time and memory usage.
	$startTime = microtime(1);
	$startMem  = memory_get_usage();

	$iniLanguageFilesFixer = new IniLanguageFilesFixer($argv[2], $argv[3]);

	if ($argv[1] == 'check-unused')
	{
		$iniLanguageFilesFixer->checkUnused($fixUnused = false);
	}
	elseif ($argv[1] == 'fix-unused')
	{
		$iniLanguageFilesFixer->checkUnused($fixUnused = true);
	}
	elseif ($argv[1] == 'check-duplicates')
	{
		$iniLanguageFilesFixer->checkDuplicates();
	}

	// Saves the start time and memory usage.
	$endTime = microtime(1);
	$endMem  = memory_get_usage();

	$timeTaken = (float) $endTime - (float) $startTime;

	echo "\n\n\033[1;33m------------------------------------------------";
	echo "\n\033[0;33m***Summary***";

	// @echo "\n\033[0;33mCompleted checking/fixing language file duplicates";

	if ($argv[1] == 'check-unused')
	{
		echo "\n\033[0;33m" . $iniLanguageFilesFixer->unusedConstantsCount . " unused contants found!";
	}
	elseif ($argv[1] == 'fix-unused')
	{
		echo "\n\033[0;33m" . $iniLanguageFilesFixer->unusedConstantsCount . " unused contants commented in lang. file and new updated lang. file created";
	}

	// @echo "\n\033[0;33mProcessed  " . $iniLanguageFilesFixer->processedCount . " files";
	// @echo "\n\033[0;33mUpdated    " . $iniLanguageFilesFixer->updatedLinesCount . " lines";

	echo "\n\033[0;33mTime taken " . round($timeTaken, 2) . " seconds";
	echo "\n\033[1;33m------------------------------------------------";
	echo "\n\033[1;37m \n";
}
