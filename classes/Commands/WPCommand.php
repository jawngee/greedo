<?php

namespace ILAB\Greedo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WPCommand extends GreedoCommand {
	protected static $defaultName = 'wp';

	protected function configure() {
		$this->setDescription("Executes WP-CLI commands.");
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

		$commandInput = $input->getArgument('command to execute');
		$command = [];
		foreach($commandInput as $commandInputPart) {
			if (strpos($commandInputPart, '+') === 0) {
				$command[] = '--'.ltrim($commandInputPart, '+');
			} else {
				$command[] = $commandInputPart;
			}
		}

		$docker = rtrim(`which docker`);
		if (empty($docker)) {
			$output->writeln("<error>Docker not found.</error>");
			return Command::FAILURE;
		}

		$output->writeln("Executing: docker exec -it {$instanceID} wp --path={$publicPath} ".implode(' ', $command));

		pcntl_exec($docker, array_merge(["exec", "-it", $instanceID, "wp", "--allow-root", "--path=$publicPath"], $command));

		return Command::SUCCESS;
	}
}