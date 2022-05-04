<?php

namespace ILAB\Greedo\Commands;

use duncan3dc\Laravel\BladeInstance;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class BuildCommand extends GreedoCommand {
	protected static $defaultName = 'build';

	protected function configure() {
		$this->setDescription("Builds a greedo file into docker files.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$result = $this->loadConfig($output, false);
		if ($result !== Command::SUCCESS) {
			return $result;
		}

		$io = new SymfonyStyle($input, $output);

		$name = arrayPath($this->config, 'name');
		$domains = arrayPath($this->config, 'domains');

		if (!file_exists('/tmp/greedo-cache/')) {
			mkdir('/tmp/greedo-cache/', 0755, true);
		}
		$blade = new BladeInstance($this->greedoDir . 'views/', '/tmp/greedo-cache/');

		$hasDB = !empty(arrayPath($this->config, 'services/db', null));
		$dbImage = null;
		$dbName = null;
		$dbUser = null;
		$dbPassword = null;
		if ($hasDB) {
			$dbServer = arrayPath($this->config, 'services/db/server');
			$dbImage = arrayPath($this->config, 'services/db/image', $dbServer.':latest');
			$dbName = arrayPath($this->config, 'services/db/name');
			$dbUser = arrayPath($this->config, 'services/db/user');
			$dbPassword = arrayPath($this->config, 'services/db/password');
			$hasDB = !empty($dbImage) && !empty($dbName) && !empty($dbUser) && !empty($dbPassword);
		}

		$hasRedis = isset($this->config['services']['redis']);
		$redisVersion = arrayPath($this->config, 'services/redis/version', 6);
		$redisPort = arrayPath($this->config, 'services/redis/port');

		$hasMemcached = isset($this->config['services']['memcached']);
		$memcachedMemory = arrayPath($this->config, 'services/memcached/memory', '64');
		$memcachedPort = arrayPath($this->config, 'services/memcached/port');

		$hasMailhog = isset($this->config['services']['mailhog']);
		$mailhogPort = arrayPath($this->config, 'services/mailhog/port');

		$hasPHP = !empty(arrayPath($this->config, 'services/php', null));
		$appDir = arrayPath($this->config, 'services/php/app_dir');

		$hasCron = !empty(arrayPath($this->config, 'services/cron', null));
		$cronJobs = arrayPath($this->config, 'services/cron/jobs', []);
		$dockerDefs = arrayPath($this->config, 'docker');

		$publicDir = arrayPath($this->config, 'services/php/public_dir');
		$uploadLimit = arrayPath($this->config, 'services/php/upload_limit', '32');
		$publicPort = arrayPath($this->config, 'services/php/port', 8000);
		$phpVer = arrayPath($this->config, 'services/php/version');
		$phpFlags = arrayPath($this->config, 'services/php/ini/flags', []);
		$phpValues = arrayPath($this->config, 'services/php/ini/values', []);
		$extensions = arrayPath($this->config, 'services/php/extensions', []);
		$xdebug = arrayPath($this->config, 'services/php/xdebug', false);
		$installComposer = arrayPath($this->config, 'services/php/composer', false);
		$installWPCLI = arrayPath($this->config, 'services/php/wpcli', false);
		$mounts = arrayPath($this->config, 'services/php/mount', []);
		$installMysqlClient = arrayPath($this->config, 'services/php/mysql_client', false);

		if ($xdebug) {
			$extensions[] = 'xdebug';
		}

		if ($hasPHP) {
			$publicPath = untrailingslashit('/srv/www/'.$publicDir);
		}

		$data = [
			'domains' => $domains,
			'publicPort' => $publicPort,
			'name' => $name,
			'dbServer' => $dbServer,
			'dbImage' => $dbImage,
			'dbName' => $dbName,
			'dbUser' => $dbUser,
			'dbPassword' => $dbPassword,
			'appDir' => $appDir,
			'publicPath' => $publicPath,
			'hasDB' => $hasDB,
			'hasRedis' => $hasRedis,
			'redisPort' => $redisPort,
			'redisVersion' => $redisVersion,
			'hasPHP' => $hasPHP,
			'hasMemcached' => $hasMemcached,
			'memcachedMemory' => $memcachedMemory,
			'memcachedPort' => $memcachedPort,
			'hasMailhog' => $hasMailhog,
			'mailhogPort' => $mailhogPort,
			'uploadLimit' => $uploadLimit,
			'extensions' => $extensions,
			'phpVer' => $phpVer,
			'phpFlags' => $phpFlags,
			'phpValues' => $phpValues,
			'mounts' => $mounts,
			'installComposer' => $installComposer,
			'installWPCLI' => $installWPCLI,
			'installMysqlClient' => $installMysqlClient,
			'hasCron' => $hasCron,
			'cronJobs' => $cronJobs,
		];

		$buildDir = trailingslashit($this->rootDir).'docker/';
		if (!file_exists($buildDir)) {
			mkdir($buildDir, 0755, true);
		}

		if (arrayPath($this->config, 'proxy') === 'caddy') {
			$output->write("<info>Generating Caddyfile ... </info>");
			$caddy = $blade->render('caddyfile', $data);
			file_put_contents($this->rootDir."Caddyfile", $caddy);
			$output->writeln("<options=bold>Done</>");
		}

		$output->write("<info>Generating docker-compose.yml ... </info>");
		$compose = $blade->render('docker-compose', $data);
		if (!empty($dockerDefs)) {
			$builtCompose = Yaml::parse($compose);
			$dockerServices = arrayPath($dockerDefs, 'services', []);
			foreach($dockerServices as $service => $def) {
				$builtCompose['services'][$service] = $def;
			}

			$dockerVolumes = arrayPath($dockerDefs, 'volumes', []);
			foreach($dockerVolumes as $volume => $def) {
				$builtCompose['volumes'][$volume] = $def;
			}

			$dockerNetworks = arrayPath($dockerDefs, 'networks', []);
			foreach($dockerNetworks as $network => $def) {
				$builtCompose['networks'][$network] = $def;
			}

			$compose = Yaml::dump($builtCompose, PHP_INT_MAX, 2);
		}
		file_put_contents($this->rootDir."docker-compose.yml", $compose);
		$output->writeln("<options=bold>Done</>");

		$output->write("<info>Generating PHP-FPM Dockerfile ... </info>");
		$fpmDocker = $blade->render('phpfpm-dockerfile', $data);
		file_put_contents($buildDir."phpfpm-Dockerfile", $fpmDocker);
		$output->writeln("<options=bold>Done</>");

		$output->write("<info>Generating NGINX config ... </info>");
		$nginxConfigDir = $buildDir . 'conf/nginx/';
		if (!file_exists($nginxConfigDir)) {
			mkdir($nginxConfigDir, 0755, true);
		}
		$nginx = $blade->render('nginx-conf', $data);
		file_put_contents($nginxConfigDir."default.conf", $nginx);
		$output->writeln("<options=bold>Done</>");


		$output->write("<info>Generating PHP-FPM config ... </info>");
		$fpmConfigDir = $buildDir . 'conf/php-fpm/';
		if (!file_exists($fpmConfigDir)) {
			mkdir($fpmConfigDir, 0755, true);
		}
		$fpm = $blade->render('fpm-conf', $data);
		file_put_contents($fpmConfigDir."www.conf", $fpm);
		$output->writeln("<options=bold>Done</>");

		if ($hasCron) {
			$output->write("<info>Generating cron config ... </info>");
			$cronConfigDir = $buildDir . 'cron/';
			if (!file_exists($cronConfigDir)) {
				mkdir($cronConfigDir, 0755, true);
			}
			file_put_contents($cronConfigDir."crontab.txt", implode("\n", $cronJobs));

			$cronEntry = $blade->render('cron-entry-sh', $data);
			file_put_contents($cronConfigDir.'entry.sh', $cronEntry);

			$cronDocker = $blade->render('cron-dockerfile', $data);
			file_put_contents($buildDir."cron-Dockerfile", $cronDocker);

			$output->writeln("<options=bold>Done</>");
		}

		if ($hasPHP) {
			if ($io->confirm("Do you want to rebuild the PHP-FPM Docker image?", false)) {
				$docker = rtrim(`which docker`);
				if (empty($docker)) {
					$output->writeln("<error>Docker not found.</error>");
					return Command::FAILURE;
				}

				$process = new Process([$docker, "compose", "build", "{$name}_php", "--no-cache"]);
				$process->setTimeout(PHP_INT_MAX);
				$process->run(function($type, $buffer) use ($output) {
					$output->write($buffer);
				});
				$output->writeln('');
//				echo `$docker compose build {$name}_php --no-cache`;
			}
		}

		if ($hasCron) {
			if ($io->confirm("Do you want to rebuild the cron Docker image?", false)) {
				$docker = rtrim(`which docker`);
				if (empty($docker)) {
					$output->writeln("<error>Docker not found.</error>");
					return Command::FAILURE;
				}

				$process = new Process([$docker, "compose", "build", "{$name}_cron", "--no-cache"]);
				$process->setTimeout(PHP_INT_MAX);
				$process->run(function($type, $buffer) use ($output) {
					$output->write($buffer);
				});
				$output->writeln('');
//				echo `$docker compose build {$name}_cron --no-cache`;
			}
		}

		return Command::SUCCESS;
	}
}