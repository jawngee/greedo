<?php

namespace ILAB\Greedo\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends GreedoCommand {
	protected static $defaultName = 'start';

	protected function configure() {
		$this->setDescription("Starts a Greedo PHP site.");
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
			switch(pcntl_fork()) {
				case 0:
					$caddy = rtrim(`which caddy`);
					pcntl_exec($caddy, ["start"]);
					exit(0);
				default:
					break;
			}
		}

		$docker = rtrim(`which docker`);
		pcntl_exec($docker, ["compose", "up", "-d"]);

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
		}

		if(strpos($hostFile, $domains[0]) === false) {
			$hostFile .= "\n#GREEDO {$this->config['name']}\n";
			foreach($domains as $domain) {
				$hostFile .= "127.0.0.1 $domain\n";
			}
			$hostFile .= "#ENDGREEDO {$this->config['name']}\n";
		}

		file_put_contents('/etc/hosts', $hostFile);
	}
}