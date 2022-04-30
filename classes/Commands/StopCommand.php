<?php

namespace ILAB\Greedo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends GreedoCommand {
	protected static $defaultName = 'stop';

	protected function configure() {
		$this->setDescription("Stops a currently running Greedo PHP site.");
		$this->addOption("kill", null, InputOption::VALUE_NONE, "Stops and removes running containers.");
		$this->addOption("delete-db", null, InputOption::VALUE_NONE, "Removes the database .");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$result = $this->loadConfig($output);
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

		$dockerFile = $buildDir.'docker-compose.yml';
		if (!file_exists($dockerFile)) {
			$output->writeln("<error>Could not find docker compose file.  Try running 'greedo build' first.</error>");
			return Command::FAILURE;
		}

		$this->updateHosts();

		$caddyFile = $buildDir.'Caddyfile';
		if (file_exists($caddyFile)) {
			$output->writeln("<info>Stopping Caddy server...</info>");
			switch(pcntl_fork()) {
				case 0:
					$caddy = rtrim(`which caddy`);
					pcntl_exec($caddy, ["stop"]);
					exit(0);
				default:
					break;
			}
		}

		$docker = rtrim(`which docker`);
		if (empty($docker)) {
			$output->writeln("<error>Could not find docker executable.</error>");
			return Command::FAILURE;
		}

		if ($input->getOption('kill')) {
			`$docker compose down`;
			if ($input->getOption('delete-db')) {
				$name = arrayPath($this->config, 'name');
				$pathParts = explode(DIRECTORY_SEPARATOR, trim($buildDir, DIRECTORY_SEPARATOR));
				$currentDir = array_pop($pathParts);
				`$docker volume rm {$currentDir}_{$name}-db-volume`;
			}
		} else {
			`$docker compose stop`;
		}

		return Command::SUCCESS;
	}

	protected function updateHosts(): void {
		$name = arrayPath($this->config, 'name');
		$domains = arrayPath($this->config, 'domains', []);
		if (count($domains) === 0) {
			return;
		}

		$hostFile = file_get_contents('/etc/hosts');
		if(strpos($hostFile, $domains[0]) !== false) {
			$hostFile = rtrim(preg_replace('/#GREEDO\s+' . $name . '(?:.*)#ENDGREEDO\s+' . $name . '/ms', '', $hostFile))."\n";
			file_put_contents('/etc/hosts', $hostFile);
		}
	}
}