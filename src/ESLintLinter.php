<?php

/**
 * Lints JavaScript and JSX files using ESLint
 */
final class ESLintLinter extends ArcanistExternalLinter {

	const ESLINT_WARNING = '1';
	const ESLINT_ERROR   = '2';

	private $flags = [];

	public function getInfoName() {
		return 'ESLint';
	}

	public function getInfoURI() {
		return 'https://eslint.org/';
	}

	public function getInfoDescription() {
		return pht('The pluggable linting utility for JavaScript and JSX');
	}

	public function getLinterName() {
		return 'ESLINT';
	}

	public function getLinterConfigurationName() {
		return 'eslint';
	}

	public function getDefaultBinary() {
		return 'eslint';
	}

	public function getVersion() {
		list($err, $stdout, $stderr) = exec_manual('%C -v', $this->getExecutableCommand());

		$matches = [];
		if (preg_match('/^v(\d\.\d\.\d)$/', $stdout, $matches)) {
			return $matches[1];
		} else {
			return false;
		}
	}

	protected function getMandatoryFlags() {
		return [
			'--format=json',
			'--no-color',
		];
	}

	protected function getDefaultFlags() {
		return $this->flags;
	}

	public function getLinterConfigurationOptions() {
		$options = [
			'eslint.config' => [
				'type' => 'optional string',
				'help' => pht('Use configuration from this file or shareable config. (https://eslint.org/docs/user-guide/command-line-interface#-c---config)'),
			],
			'eslint.env'    => [
				'type' => 'optional string',
				'help' => pht('Specify environments. To specify multiple environments, separate them using commas. (https://eslint.org/docs/user-guide/command-line-interface#--env)'),
			],
		];
		return $options + parent::getLinterConfigurationOptions();
	}

	public function setLinterConfigurationValue($key, $value) {
		switch ($key) {
			case 'eslint.config':
				$this->flags[] = '--config ' . $value;
				return;
			case 'eslint.env':
				$this->flags[] = '--env ' . $value;
				return;
		}
		return parent::setLinterConfigurationValue($key, $value);
	}

	public function getInstallInstructions() {
		return pht(
			'run `%s` to install eslint globally, or `%s` to add it to your project.',
			'npm install --global eslint',
			'npm install --save-dev eslint'
		);
	}

	protected function canCustomizeLintSeverities() {
		return false;
	}

	protected function parseLinterOutput($path, $err, $stderr, $stdout = "{}") {

		$json     = json_decode($stdout, true);
		$messages = [];
		
		if (empty($json)) {
			return [];
		}

		foreach ($json as $file) {
			foreach ($file['messages'] as $offense) {
				// skip lint failures on ignored files
				if (strpos($offense['message'], 'ignore') > 0) {
					continue;
				}

				$message = new ArcanistLintMessage();
				$message->setPath($file['filePath']);
				$message->setSeverity($this->mapSeverity($offense['severity']));
				$message->setName(isset($offense['ruleId']) ? $offense['ruleId'] : 'n/a');
				$message->setDescription($offense['message']);

				if (isset($offense['line'])) {
					$message->setLine($offense['line']);
				}

				if (isset($offense['column'])) {
					$message->setChar($offense['column']);
				}

				$source = array_key_exists('source', $offense) ? $offense['source'] : $file['filePath'];
				$message->setCode(substr($source, 0, 128));
				$messages[] = $message;
			}
		}

		return $messages;
	}

	private function mapSeverity($eslintSeverity) {
		switch ($eslintSeverity) {
			case '0':
			case '1':
				return ArcanistLintSeverity::SEVERITY_WARNING;
			case '2':
			default:
				return ArcanistLintSeverity::SEVERITY_ERROR;
		}
	}

	/**
	 * @note override to prevent --config option being wrapped in quotes
	 *
	 * @param array $paths
	 *
	 * @return array
	 */
	protected function buildFutures(array $paths) {
		$executable = $this->getExecutableCommand();

		$bin = csprintf('%C %C', $executable, implode(' ', $this->getCommandFlags()));

		$futures = array();
		foreach ($paths as $path) {
			$disk_path = $this->getEngine()->getFilePathOnDisk($path);
			$path_argument = $this->getPathArgumentForLinterFuture($disk_path);
			$future = new ExecFuture('%C %C', $bin, $path_argument);

			$future->setCWD($this->getProjectRoot());
			$futures[$path] = $future;
		}

		return $futures;
	}
}
