<?php

namespace ILAB\Greedo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class GreedoCommand extends Command {
	protected $rootDir = null;
	protected $greedoDir = null;
	protected $config = null;

	public function __construct(string $name = null, string $rootDir = null, string $greedoDir = null) {
		parent::__construct($name);

		$this->rootDir = trailingslashit($rootDir);
		$this->greedoDir = trailingslashit($greedoDir);
	}

	protected function loadConfig(OutputInterface $output, $rootRequired = true) {
		$greedoFile = $this->rootDir . 'greedo.yml';
		if (!file_exists($greedoFile)) {
			$output->writeln("<error>Greedo file not found at $greedoFile</error>");
			return Command::FAILURE;
		}

		if ($rootRequired) {
			$userinfo = posix_getpwuid(posix_geteuid());
			if ($userinfo['name'] !== 'root') {
				$output->writeln("<error>You must run this command as root.</error>");
				return Command::FAILURE;
			}
		}

		$this->config = Yaml::parseFile($greedoFile);
		if (arrayPath($this->config, 'version') !== 2) {
			$output->writeln("<error>Invalid Greedo version.</error>");
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}