<?php

namespace ILAB\Greedo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerCommand extends GreedoCommand {
	protected static $defaultName = 'composer';

	protected function configure() {
		$this->setDescription("Executes Composer commands.");
		$this->addArgument('command to execute', InputArgument::REQUIRED | InputArgument::IS_ARRAY, "The command to execute.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!function_exists('pcntl_exec')) {
			$output->writeln("<error>PHP extension 'pcntl' is not installed.</error>");
			return Command::FAILURE;
		}

		$result = $this->loadConfig($output, false);
		if ($result !== Command::SUCCESS) {
			return $result;
		}



		$name = arrayPath($this->config, 'name');
		if (empty(arrayPath($this->config, 'services/php', null))) {
			$output->writeln("<error>No PHP service configured for $name</error>");
			return Command::FAILURE;
		}

		$buildDir = trailingslashit($this->rootDir).'docker/'.$name.'/';
		if (!file_exists($buildDir)) {
			$output->writeln("<error>Docker directory does not exist.</error>");
			return Command::FAILURE;
		}
		chdir($buildDir);

		$publicDir = arrayPath($this->config, 'services/php/public_dir');
		$publicPath = untrailingslashit('/srv/www/'.$publicDir);

		$dockerPs = `docker ps --all --no-trunc --format='{{json .}}'`;
		$dockerPsLines = explode("\n", $dockerPs);
		$instanceID = null;
		foreach($dockerPsLines as $dockerPsLine) {
			if (!empty($dockerPsLine)) {
				$entry = json_decode($dockerPsLine, true);
				if (arrayPath($entry, 'Names') === "{$name}_php") {
					$instanceID = arrayPath($entry, 'ID');
					break;
				}
			}
		}

		if (empty($instanceID)) {
			$output->writeln("<error>No PHP service running for $name</error>");
			return Command::FAILURE;
		}

		$command = $input->getArgument('command to execute');
		$docker = rtrim(`which docker`);
		if (empty($docker)) {
			$output->writeln("<error>Docker not found.</error>");
			return Command::FAILURE;
		}
		pcntl_exec($docker, array_merge(["exec", "-it", $instanceID, "composer", "--working-dir=$publicPath"], $command));

		return Command::SUCCESS;
	}
}