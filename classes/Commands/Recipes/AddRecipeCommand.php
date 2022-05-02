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

class AddRecipeCommand extends GreedoCommand {
	protected static $defaultName = 'recipes:add';

	protected function configure() {
		$this->setDescription("Saves the current greedo.yml file as a recipe that cen be used later.");
		$this->addArgument('recipe', InputArgument::REQUIRED, "The name of the recipe to save.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$result = $this->loadConfig($output, false);
		if ($result !== Command::SUCCESS) {
			return $result;
		}

		$recipe = $input->getArgument('recipe');
		$recipesDir = trailingslashit(trailingslashit($_SERVER['HOME']).'/.greedo/recipes/');
		if (!file_exists($recipesDir)) {
			mkdir($recipesDir, 0755, true);
		}

		copy($this->rootDir.'/greedo.yml', $recipesDir.$recipe.'.yml');

		return Command::SUCCESS;
	}
}