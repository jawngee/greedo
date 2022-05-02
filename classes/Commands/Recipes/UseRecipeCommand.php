<?php

namespace ILAB\Greedo\Commands\Recipes;

use duncan3dc\Laravel\BladeInstance;
use ILAB\Greedo\Commands\GreedoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class UseRecipeCommand extends GreedoCommand {
	protected static $defaultName = 'recipes:use';

	protected function configure() {
		$this->setDescription("Applies a recipe in the current directory.");
		$this->addArgument('recipe', InputArgument::REQUIRED, "The name of the recipe to use.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$recipe = $input->getArgument('recipe');

		$recipesDir = trailingslashit(trailingslashit($_SERVER['HOME']).'/.greedo/recipes/');
		if (!file_exists($recipesDir)) {
			$output->writeln("<error>No recipes directory found.</error>");
			return Command::FAILURE;
		}

		$recipePath = $recipesDir.$recipe.'.yml';
		if (!file_exists($recipePath)) {
			$output->writeln("<error>Recipe not found.</error>");
			return Command::FAILURE;
		}

		$config = Yaml::parseFile($recipePath);

		$io = new SymfonyStyle($input, $output);

		$io->section("Project Info");
		$config['name'] = $io->ask("Project name");
		$config['domains'] = [$io->ask("Project domain")];

		foreach($config['services'] as $key => $service) {
			$port = arrayPath($service, 'port');
			if (!empty($port)) {
				$serviceName = (in_array($key, ['db', 'php'])) ? strtoupper($key) : ucfirst($key);
				$config['services'][$key]['port'] = $io->ask("$serviceName public port", $port);
			}
		}

		$output->write("<info>Saving greedo file ... <info>");
		file_put_contents($this->rootDir.'greedo.yml', Yaml::dump($config, PHP_INT_MAX));
		$output->writeln("<info>Done</info>");
		$output->writeln("<options=bold>Done</>");

		return Command::SUCCESS;
	}
}