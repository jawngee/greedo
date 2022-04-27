<?php

namespace ILAB\Greedo\Commands;

use duncan3dc\Laravel\BladeInstance;
use NorthernLights\HostsFileParser\HostsFile;
use NorthernLights\HostsFileParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class StopCommand extends GreedoCommand {
	protected static $defaultName = 'stop';

	protected function configure() {
		$this->setDescription("Stops a running Greedo PHP site.");

		$this->addOption("destroy", null, InputOption::VALUE_NONE, "Destroys the database.", null);
		$this->addOption("root-db-user", null, InputOption::VALUE_OPTIONAL, "The root database user.", null);
		$this->addOption("root-db-password", null, InputOption::VALUE_OPTIONAL, "The root database password.", null);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$result = $this->loadConfig($output);
		if ($result !== Command::SUCCESS) {
			return $result;
		}

		if (file_exists("/etc/nginx/sites-enabled/{$this->config['domain']}.conf")) {
			unlink("/etc/nginx/sites-enabled/{$this->config['domain']}.conf");
		}

		$phpVer = arrayPath($this->config, 'php/version', '7.4');
		if (file_exists("/etc/php/{$phpVer}/fpm/pool.d/{$this->config['name']}.conf")) {
			unlink("/etc/php/{$phpVer}/fpm/pool.d/{$this->config['name']}.conf");
		}

		`service php{$phpVer}-fpm restart`;
		`service nginx restart`;

		if ($input->getOption('destroy') !== false) {
			if (!$this->destroyDatabase($input, $output)) {
				return Command::FAILURE;
			}
		}

		$this->updateHosts();

		return Command::SUCCESS;
	}

	/**
	 */
	protected function updateHosts(): void {
		$hostFile = file_get_contents('/etc/hosts');
		if(strpos($hostFile, $this->config['domain']) !== false) {
			$hostFile = rtrim(preg_replace('/#GREEDO\s+' . $this->config['name'] . '(?:.*)#ENDGREEDO\s+' . $this->config['name'] . '/ms', '', $hostFile))."\n";
		}

		file_put_contents('/etc/hosts', $hostFile);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return bool
	 */
	protected function destroyDatabase(InputInterface $input, OutputInterface $output): bool {
		$name = arrayPath($this->config, 'db/name', null);
		$rootUser = arrayPath($this->config, 'db/root/user', null) ?? $input->getOption('root-db-user');
		$rootPass = arrayPath($this->config, 'db/root/password', null) ?? $input->getOption('root-db-password');
		$driver = arrayPath($this->config, 'db/driver', 'mysql');

		if(!$name) {
			$output->writeln("<error>Database configuration is missing.</error>");
			return false;
		}

		if(!$rootPass || !$rootUser) {
			$output->writeln("<error>Root database credentials are missing.</error>");
			return false;
		}

		if($driver === 'mysql') {
			`mysql --user=$rootUser --password=$rootPass -e 'drop database $name'`;
		} else {
			$output->writeln("<error>Unsupported database driver: $driver</error>");
			return false;
		}

		return true;
}
}