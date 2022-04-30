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
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends GreedoCommand {
	static $phpExtensions = [
		'amqp' => ['7.4', '8.0', '8.1'],
		'apcu' => ['7.4', '8.0', '8.1'],
		'apcu_bc' => ['7.4'],
		'ast' => ['7.4', '8.0', '8.1'],
		'bcmath' => ['7.4', '8.0', '8.1'],
		'blackfire' => ['7.4', '8.0', '8.1'],
		'bz2' => ['7.4', '8.0', '8.1'],
		'calendar' => ['7.4', '8.0', '8.1'],
		'cmark' => ['7.4'],
		'csv' => ['7.4', '8.0', '8.1'],
		'dba' => ['7.4', '8.0', '8.1'],
		'decimal' => ['7.4', '8.0', '8.1'],
		'ds' => ['7.4', '8.0', '8.1'],
		'enchant' => ['7.4', '8.0', '8.1'],
		'ev' => ['7.4', '8.0', '8.1'],
		'event' => ['7.4', '8.0', '8.1'],
		'excimer' => ['7.4', '8.0', '8.1'],
		'exif' => ['7.4', '8.0', '8.1'],
		'ffi' => ['7.4', '8.0', '8.1'],
		'gd' => ['7.4', '8.0', '8.1'],
		'gearman' => ['7.4', '8.0'],
		'geoip' => ['7.4'],
		'geos' => ['7.4'],
		'geospatial' => ['7.4', '8.0', '8.1'],
		'gettext' => ['7.4', '8.0', '8.1'],
		'gmagick' => ['7.4', '8.0', '8.1'],
		'gmp' => ['7.4', '8.0', '8.1'],
		'gnupg' => ['7.4', '8.0', '8.1'],
		'grpc' => ['7.4', '8.0', '8.1'],
		'http' => ['7.4', '8.0', '8.1'],
		'igbinary' => ['7.4', '8.0', '8.1'],
		'imagick' => ['7.4', '8.0', '8.1'],
		'imap' => ['7.4', '8.0', '8.1'],
		'inotify' => ['7.4', '8.0', '8.1'],
		'intl' => ['7.4', '8.0', '8.1'],
		'ioncube_loader' => ['7.4'],
		'jsmin' => ['7.4'],
		'json_post' => ['7.4', '8.0', '8.1'],
		'ldap' => ['7.4', '8.0', '8.1'],
		'luasandbox' => ['7.4', '8.0', '8.1'],
		'lzf' => ['7.4', '8.0', '8.1'],
		'mailparse' => ['7.4', '8.0', '8.1'],
		'maxminddb' => ['7.4', '8.0', '8.1'],
		'mcrypt' => ['7.4', '8.0', '8.1'],
		'memcache' => ['7.4', '8.0', '8.1'],
		'memcached' => ['7.4', '8.0', '8.1'],
		'memprof' => ['7.4', '8.0', '8.1'],
		'mongodb' => ['7.4', '8.0', '8.1'],
		'mosquitto' => ['7.4', '8.0', '8.1'],
		'msgpack' => ['7.4', '8.0', '8.1'],
		'mysqli' => ['7.4', '8.0', '8.1'],
		'oauth' => ['7.4', '8.0', '8.1'],
		'oci8' => ['7.4', '8.0', '8.1'],
		'odbc' => ['7.4', '8.0', '8.1'],
		'opcache' => ['7.4', '8.0', '8.1'],
		'opencensus' => ['7.4', '8.0', '8.1'],
		'openswoole' => ['7.4', '8.0', '8.1'],
		'parallel' => ['7.4'],
		'parle' => ['7.4', '8.0', '8.1'],
		'pcntl' => ['7.4', '8.0', '8.1'],
		'pcov' => ['7.4', '8.0', '8.1'],
		'pdo_dblib' => ['7.4', '8.0', '8.1'],
		'pdo_firebird' => ['7.4', '8.0', '8.1'],
		'pdo_mysql' => ['7.4', '8.0', '8.1'],
		'pdo_oci' => ['7.4', '8.0', '8.1'],
		'pdo_odbc' => ['7.4', '8.0', '8.1'],
		'pdo_pgsql' => ['7.4', '8.0', '8.1'],
		'pdo_sqlsrv' => ['7.4', '8.0', '8.1'],
		'pgsql' => ['7.4', '8.0', '8.1'],
		'propro' => ['7.4'],
		'protobuf' => ['7.4', '8.0'],
		'pspell' => ['7.4', '8.0', '8.1'],
		'raphf' => ['7.4', '8.0', '8.1'],
		'rdkafka' => ['7.4', '8.0', '8.1'],
		'redis' => ['7.4', '8.0', '8.1'],
		'seaslog' => ['7.4', '8.0', '8.1'],
		'shmop' => ['7.4', '8.0', '8.1'],
		'smbclient' => ['7.4', '8.0', '8.1'],
		'snmp' => ['7.4', '8.0', '8.1'],
		'snuffleupagus' => ['7.4', '8.0', '8.1'],
		'soap' => ['7.4', '8.0', '8.1'],
		'sockets' => ['7.4', '8.0', '8.1'],
		'solr' => ['7.4', '8.0'],
		'sourceguardian' => ['7.4', '8.0'],
		'spx' => ['7.4', '8.0', '8.1'],
		'sqlsrv' => ['7.4', '8.0', '8.1'],
		'ssh2' => ['7.4', '8.0', '8.1'],
		'stomp' => ['7.4', '8.0', '8.1'],
		'swoole' => ['7.4', '8.0', '8.1'],
		'sysvmsg' => ['7.4', '8.0', '8.1'],
		'sysvsem' => ['7.4', '8.0', '8.1'],
		'sysvshm' => ['7.4', '8.0', '8.1'],
		'tensor' => ['7.4', '8.0'],
		'tidy' => ['7.4', '8.0', '8.1'],
		'timezonedb' => ['7.4', '8.0', '8.1'],
		'uopz' => ['7.4', '8.0', '8.1'],
		'uploadprogress' => ['7.4', '8.0', '8.1'],
		'uuid' => ['7.4', '8.0', '8.1'],
		'vips' => ['7.4', '8.0', '8.1'],
		'xdebug' => ['7.4', '8.0', '8.1'],
		'xhprof' => ['7.4', '8.0', '8.1'],
		'xlswriter' => ['7.4', '8.0', '8.1'],
		'xmldiff' => ['7.4', '8.0', '8.1'],
		'xmlrpc' => ['7.4', '8.0', '8.1'],
		'xsl' => ['7.4', '8.0', '8.1'],
		'yac' => ['7.4', '8.0', '8.1'],
		'yaml' => ['7.4', '8.0', '8.1'],
		'yar' => ['7.4', '8.0', '8.1'],
		'zephir_parser' => ['7.4', '8.0', '8.1'],
		'zip' => ['7.4', '8.0', '8.1'],
		'zookeeper' => ['7.4', '8.0', '8.1'],
		'zstd' => ['7.4', '8.0', '8.1'],
	];


	protected static $defaultName = 'init';

	protected function configure() {
		$this->setDescription("Initialize a Greedo config file.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$io = new SymfonyStyle($input, $output);

		$io->section("Project Info");

		$projectName = $io->ask("Project name");
		$projectDomain = $io->ask("Project domain");
		$useCaddy = $io->confirm("Are you using Caddy as a reverse proxy?");

		$services = [];

		$io->section("Redis Service");
		if ($io->confirm("Do you want to enable redis?")) {
			$redisVersion = $io->choice("Redis version", [5, 6, 7]);
			$redisPort = $io->ask("Redis public port");
			$services['redis'] = [
				'version' => $redisVersion,
			];

			if (!empty($redisPort)) {
				$services['redis']['port'] = $redisPort;
			}
		}

		$io->section("Memcache Service");
		if ($io->confirm("Do you want to enable memcache?")) {
			$memcacheSize = intval($io->ask("Memcache cache size", 64));
			$memcachePort = intval($io->ask("Memcache public port"));
			$services['memcached'] = [
				'mem' => $memcacheSize,
			];

			if (!empty($memcachePort)) {
				$services['memcached']['port'] = $memcachePort;
			}
		}

		$io->section("Database Service");
		if ($io->confirm("Do you want to enable the database?")) {
			$dbType = $io->choice("Database type", ['mariadb', 'mysql', 'postgres'], "mariadb");
			$dbName = $io->ask("Database name");
			$dbUser = $io->ask("Database user name");
			$dbPassword = $io->ask("Database password");
			$dbPort = null;
			if ($io->confirm("Do you want to expose the database post?")) {
				$dbPort = intval($io->ask("Database port"));
			}

			$services['db'] = [
				'server' => $dbType,
				'name' => $dbName,
				'user' => $dbUser,
				'password' => $dbPassword,
			];

			if (!empty($dbPort)) {
				$services['db']['port'] = $dbPort;
			}
		}

		$io->section("PHP Service");
		if ($io->confirm("Do you want to enable PHP-FPM?")) {
			$phpVer = $io->choice("PHP Version", ["7.4", "8.0", "8.1"]);
			$appDir = trailingslashit($io->ask("Application directory"));
			if (strpos($appDir, DIRECTORY_SEPARATOR) !== 0) {
				if (strpos($appDir, './') !== 0) {
					$appDir = './'.$appDir;
				}
			}
			$publicDir = $io->ask("Public directory (relative to application directory)");
			$uploadLimit = intval($io->ask("Upload limit (in MB)", 32));
			$port = intval($io->ask("Public port", 8081));
			$xdebug = $io->confirm("Do you want to enable XDebug?");
			$composer = $io->confirm("Do you want to install Composer?");
			$wpcli = $io->confirm("Do you want to install WP-CLI?");
			$extensions = [];

			if ($io->confirm("Do you want to define PHP extensions?")) {
				$extensionMode = $io->choice("How do you want to define what PHP extensions to use?", ["One by one", "Comma separated list", "Standard WordPress", "Standard Laravel"]);
				if ($extensionMode === 'One by one') {
					$extensions = [];
					$selectableExtensions = [];
					foreach(self::$phpExtensions as $ext => $versions) {
						if(in_array($phpVer, $versions)) {
							$selectableExtensions[] = $ext;
						}
					}

					foreach($selectableExtensions as $extension) {
						if($io->confirm($extension)) {
							$extensions[] = $extension;
						}
					}
				} else if ($extensionMode === 'Standard WordPress') {
					$extensions = [
						'xmlrpc',
						'zip',
						'gd',
						'bcmath',
						'opcache',
						'imagick',
					];
				} else if ($extensionMode === 'Standard Laravel') {
					$extensions = [
						'pdo',
						'zip',
						'gd',
						'bcmath',
						'opcache',
						'exif',
						'imagick',
					];
				} else {
					$extensionCSV = $io->ask("PHP extensions to install (separated by comma)");
					$extensions = explode(",", $extensionCSV);
					array_walk($extensions, function (&$item) {
						$item = trim($item);
					});
				}
			}

			$services['php'] = [
				'version' => $phpVer,
				'app_dir' => $appDir,
				'public_dir' => $publicDir,
				'upload_limit' => $uploadLimit,
				'port' => intval($port),
				'xdebug' => $xdebug,
				'composer' => $composer,
				'wpcli' => $wpcli,
				'extensions' => $extensions,
				'ini' => [
					'flags' => [],
					'values' => [],
				]
			];

			if (isset($services['redis'])) {
				if (!in_array('redis', $services['php']['extensions'])) {
					$services['php']['extensions'][] = 'redis';
				}
			}

			if (isset($services['memcache'])) {
				if (!in_array('memcached', $services['php']['extensions'])) {
					$services['php']['extensions'][] = 'memcached';
				}
			}

			if (isset($services['db'])) {
				$server = arrayPath($services, 'db/server');
				if ($server === 'postgres') {
					if (!in_array('pgsql', $services['php']['extensions'])) {
						$services['php']['extensions'][] = 'pgsql';
					}

					if (in_array('pdo', $services['php']['extensions'])) {
						if (!in_array('pdo_pgsql', $services['php']['extensions'])) {
							$services['php']['extensions'][] = 'pdo_pgsql';
						}
					}
				} else {
					if (!in_array('mysqli', $services['php']['extensions'])) {
						$services['php']['extensions'][] = 'mysqli';
					}

					if (in_array('pdo', $services['php']['extensions'])) {
						if (!in_array('pdo_mysql', $services['php']['extensions'])) {
							$services['php']['extensions'][] = 'pdo_mysql';
						}
					}
				}
			}

			$io->section("Mailhog");
			if ($io->confirm("Do you want to enable mailhog?")) {
				$mailhogPort = intval($io->ask("Mailhog public port"));
				if (empty($mailhogPort)) {
					$services['mailhog'] = true;
				} else {
					$services['mailhog'] = [
						'port' => $mailhogPort
					];
				}
			}
		}

		$config = [
			'version' => 2,
			'name' => $projectName,
			'domains' => [
				$projectDomain
			],
			'proxy' => $useCaddy ? 'caddy' : 'none',
			'services' => $services
		];

		file_put_contents($this->rootDir.'greedo.yml', Yaml::dump($config, PHP_INT_MAX));

		return Command::SUCCESS;
	}
}