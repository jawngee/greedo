<?php

namespace ILAB\Greedo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SSHCommand extends GreedoCommand {
	protected static $defaultName = 'ssh';

	protected function configure() {
		$this->setDescription("Opens a SSH session with the selected service.");
		$this->addArgument('service', InputArgument::REQUIRED, "The name of the service to SSH into.");
		$this->addOption("bash", null, InputOption::VALUE_NONE, "Use bash instead of sh.");

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

		$buildDir = trailingslashit($this->rootDir).'docker/'.$name.'/';
		if (!file_exists($buildDir)) {
			$output->writeln("<error>Docker directory does not exist.</error>");
			return Command::FAILURE;
		}
		chdir($buildDir);

		$dockerPs = `docker ps --all --no-trunc --format='{{json .}}'`;
		$dockerPsLines = explode("\n", $dockerPs);
		$instanceID = null;
		$service = $input->getArgument('service');
		foreach($dockerPsLines as $dockerPsLine) {
			if (!empty($dockerPsLine)) {
				$entry = json_decode($dockerPsLine, true);
				if (arrayPath($entry, 'Names') === "{$name}_{$service}") {
					$instanceID = arrayPath($entry, 'ID');
					break;
				}
			}
		}

		if (empty($instanceID)) {
			$output->writeln("<error>No '{$service}' service running for $name</error>");
			return Command::FAILURE;
		}

		$docker = rtrim(`which docker`);
		if (empty($docker)) {
			$output->writeln("<error>Docker not found.</error>");
			return Command::FAILURE;
		}
		pcntl_exec($docker, ["exec", "-it", $instanceID, $input->getOption('bash') ? "bash" : "sh"]);

		return Command::SUCCESS;
	}
}