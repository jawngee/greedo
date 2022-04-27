<?php

namespace ILAB\Greedo\Commands;

use duncan3dc\Laravel\BladeInstance;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends GreedoCommand {
	protected static $defaultName = 'start';

	protected function configure() {
		$this->setDescription("Starts a Greedo PHP site.");

		$this->addOption("root-db-user", null, InputOption::VALUE_OPTIONAL, "The root database user.", null);
		$this->addOption("root-db-password", null, InputOption::VALUE_OPTIONAL, "The root database password.", null);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$result = $this->loadConfig($output);
		if ($result !== Command::SUCCESS) {
			return $result;
		}

		$this->updateHosts();

		if (arrayPath($this->config, 'db/create', false)) {
			if (!$this->createDatabase($input, $output)) {
				return Command::FAILURE;
			}
		}

		$data = [
			'name' => $this->config['name'],
			'domain' => $this->config['domain'],
			'public_dir' => $this->rootDir . $this->config['public_dir'],
			'app_dir' => $this->rootDir . $this->config['app_dir'],
			'upload_limit' => arrayPath($this->config, 'upload_limit', 32),
			'fpm_user' => arrayPath($this->config, 'fpm/user'),
			'fpm_group' => arrayPath($this->config, 'fpm/group'),
			'php_flags' => arrayPath($this->config, 'php/flags', []),
			'php_values' => arrayPath($this->config, 'php/values', []),
		];

		$phpVer = arrayPath($this->config, 'php/version', '7.4');
		$blade = new BladeInstance($this->greedoDir . 'views/', $this->greedoDir . 'cache/');

		$nginx = $blade->render('nginx-conf', $data);
		file_put_contents("/etc/nginx/sites-enabled/{$this->config['domain']}.conf", $nginx);

		$fpm = $blade->render('fpm-conf', $data);
		file_put_contents("/etc/php/{$phpVer}/fpm/pool.d/{$this->config['name']}.conf", $fpm);

		`service php{$phpVer}-fpm restart`;
		`service nginx restart`;

		return Command::SUCCESS;
	}

	protected function updateHosts(): void {
		$hostFile = file_get_contents('/etc/hosts');
		if(strpos($hostFile, $this->config['domain']) !== false) {
			$hostFile = rtrim(preg_replace('/#GREEDO\s+' . $this->config['name'] . '(?:.*)#ENDGREEDO\s+' . $this->config['name'] . '/ms', '', $hostFile))."\n";
		}

		if(strpos($hostFile, $this->config['domain']) !== false) {
			$hostFile = preg_replace('/#GREEDO\s+' . $this->config['name'] . '(?:.*)#ENDGREEDO\s+' . $this->config['name'] . '/ms', '', $hostFile);
		}

		if(strpos($hostFile, $this->config['domain']) === false) {
			$hostFile .= "\n#GREEDO {$this->config['name']}\n";
			$hostFile .= "127.0.0.1 {$this->config['domain']}\n";
			$hostFile .= "#ENDGREEDO {$this->config['name']}\n";
		}

		file_put_contents('/etc/hosts', $hostFile);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return bool
	 */
	protected function createDatabase(InputInterface $input, OutputInterface $output): bool {
		$name = arrayPath($this->config, 'db/name', null);
		$user = arrayPath($this->config, 'db/user', null);
		$pass = arrayPath($this->config, 'db/password', null);
		$rootUser = arrayPath($this->config, 'db/root/user', null) ?? $input->getOption('root-db-user');
		$rootPass = arrayPath($this->config, 'db/root/password', null) ?? $input->getOption('root-db-password');
		$driver = arrayPath($this->config, 'db/driver', 'mysql');

		if(!$name || !$user || !$pass) {
			$output->writeln("<error>Database configuration is missing.</error>");
			return false;
		}

		if(!$rootPass || !$rootUser) {
			$output->writeln("<error>Root database credentials are missing.</error>");
			return false;
		}

		if($driver === 'mysql') {
			`mysql --user=$rootUser --password=$rootPass -e 'create database if not exists $name'`;
			`mysql --user=$rootUser --password=$rootPass -e "grant all privileges on {$name}.* to '{$user}'@'localhost' identified by '{$pass}'"`;
		} else {
			$output->writeln("<error>Unsupported database driver: $driver</error>");
			return false;
		}

		return true;
}
}