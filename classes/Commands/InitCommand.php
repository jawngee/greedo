<?php

namespace ILAB\Greedo\Commands;

use duncan3dc\Laravel\BladeInstance;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends GreedoCommand {
	protected static $defaultName = 'init';

	protected function configure() {
		$this->setDescription("Initialize a Greedo config file.");
	}

	protected function askQuestion(QuestionHelper $questions, InputInterface $input, OutputInterface $output, string $question, string $default = null, $required = true) {
		while (true) {
			$question = new Question($question, $default);
			$result = trim($questions->ask($input, $output, $question));
			if (!empty($result)) {
				return $result;
			}

			if (!$required) {
				return $default;
			}
		}
	}

	protected function askConfirmation(QuestionHelper $questions, InputInterface $input, OutputInterface $output, string $question, bool $default = false) {
		$question = new ConfirmationQuestion($question, $default);
		return $questions->ask($input, $output, $question);
	}

	protected function askMultipleChoice(QuestionHelper $questions, InputInterface $input, OutputInterface $output, string $question, array $choices) {
		while (true) {
			$question = new ChoiceQuestion($question, $choices, 0);
			$result = trim($questions->ask($input, $output, $question));
			if (!empty($result)) {
				return $result;
			}
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$questions = $this->getHelper('question');

		$projectName = $this->askQuestion($questions, $input, $output, "Project name: ");
		$projectDomain = $this->askQuestion($questions, $input, $output, "Project domain: ");

		$publicDir = $this->askQuestion($questions, $input, $output, "Public directory: ");
		$appDir = $this->askQuestion($questions, $input, $output, "App directory: ", $publicDir);
		$phpVer = $this->askMultipleChoice($questions, $input, $output, "PHP version: ", [
			"7.4", "8.1"
		]);

		while(true) {
			$uploadLimit = $this->askQuestion($questions, $input, $output, "Upload limit: ", 32);
			if (filter_var($uploadLimit, FILTER_VALIDATE_INT)) {
				break;
			}

			$output->writeln("<error>Please enter a number.</error>");
		}

		$fpmUser = $this->askQuestion($questions, $input, $output, "FPM user: ", null, false);
		$fpmGroup = $this->askQuestion($questions, $input, $output, "FPM group: ", null, false);

		$createDatabase = $this->askConfirmation($questions, $input, $output, "Create database? ");
		$database = $this->askQuestion($questions, $input, $output, "Database name: ", null, true);
		$databaseUser = $this->askQuestion($questions, $input, $output, "Database user: ", null, true);
		$databasePassword = $this->askQuestion($questions, $input, $output, "Database password: ", null, true);


		$config = [
			'name' => $projectName,
			'domain' => $projectDomain,
			'public_dir' => $publicDir,
			'app_dir' => $appDir,
			'php' => [
				'version' => $phpVer,
				'flags' => [
					'log_errors' => 'On',
					'display_errors' => 'On',
				],
				'values' => []
			],
			'upload_limit' => $uploadLimit,
			'fpm' => [
				'user' => $fpmUser,
				'group' => $fpmGroup,
			],
			'db' => [
				'create' => $createDatabase,
				'driver' => 'mysql',
				'name' => $database,
				'user' => $databaseUser,
				'password' => $databasePassword
			]
		];


		file_put_contents($this->rootDir.'greedo.yml', Yaml::dump($config, 8, 2));

		return Command::SUCCESS;
	}
}