<?php

namespace ILAB\Greedo\Commands;

use duncan3dc\Laravel\BladeInstance;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
		if (strpos($appDir, DIRECTORY_SEPARATOR) !== 0) {
			if (strpos($appDir, './') === 0) {
				$appDir = '../../'.substr($appDir, 2);
			}
		}

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
		array_walk($mounts, function(&$item) {
			if (strpos($item, DIRECTORY_SEPARATOR) !== 0) {
				if (strpos($item, './') === 0) {
					$item = '../../'.substr($item, 2);
				}
			}
		});

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
		];

		$buildDir = trailingslashit($this->rootDir).'docker/'.$name.'/';
		if (!file_exists($buildDir)) {
			mkdir($buildDir, 0755, true);
		}

		if (arrayPath($this->config, 'proxy') === 'caddy') {
			$output->write("<info>Generating Caddyfile ... </info>");
			$caddy = $blade->render('caddyfile', $data);
			file_put_contents($buildDir."Caddyfile", $caddy);
			$output->writeln("<options=bold>Done</>");
		}

		$output->write("<info>Generating docker-compose.yml ... </info>");
		$compose = $blade->render('docker-compose', $data);
		file_put_contents($buildDir."docker-compose.yml", $compose);
		$output->writeln("<options=bold>Done</>");

		$output->write("<info>Generating PHP-FPM Dockerfile ... </info>");
		$fpmDockerDir = $buildDir . 'phpfpm/';
		if (!file_exists($fpmDockerDir)) {
			mkdir($fpmDockerDir, 0755, true);
		}
		$fpmDocker = $blade->render('phpfpm-dockerfile', $data);
		file_put_contents($fpmDockerDir."Dockerfile", $fpmDocker);
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

		if ($hasPHP) {
			if ($io->ask("Do you want to rebuild the PHP-FPM Docker image?")) {
				if (!function_exists('pcntl_exec')) {
					$output->writeln("<error>PHP extension 'pcntl' is not installed.</error>");
					return Command::FAILURE;
				}

				$docker = rtrim(`which docker`);
				if (empty($docker)) {
					$output->writeln("<error>Docker not found.</error>");
					return Command::FAILURE;
				}

				chdir($buildDir);
				pcntl_exec($docker, ["compose", "build", "--no-cache"]);
			}
		}

		return Command::SUCCESS;
	}
}