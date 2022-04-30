<?php

if (!function_exists('arrayPath')) {
	/**
	 * Fetches a value from an array using a path string, eg 'some/setting/here'.
	 * @param $array
	 * @param $path
	 * @param null $defaultValue
	 * @return mixed|null
	 */
	function arrayPath($array, $path, $defaultValue = null)	{
		$pathArray = explode('/', $path);

		$config = $array;

		for ($i = 0; $i < count($pathArray); $i++) {
			$part = $pathArray[$i];

			if (! isset($config[$part])) {
				return $defaultValue;
			}

			if ($i == count($pathArray) - 1) {
				return $config[$part];
			}

			$config = $config[$part];
		}

		return $defaultValue;
	}
}

if (!function_exists('arrayPathExists')) {
	/**
	 * Fetches a value from an array using a path string, eg 'some/setting/here'.
	 *
	 * @param $array
	 * @param $path
	 *
	 * @return boolean
	 */
	function arrayPathExists($array, $path) {
		$pathArray = explode('/', $path);

		$config = $array;

		for($i = 0; $i < count($pathArray); $i++) {
			$part = $pathArray[$i];

			if(!isset($config[$part])) {
				return false;
			}

			if($i == count($pathArray) - 1) {
				return true;
			}

			$config = $config[$part];
		}

		return false;
	}
}

if (!function_exists('trailingslashit')) {
	function trailingslashit( $string ) {
		return untrailingslashit( $string ) . '/';
	}
}

if (!function_exists('untrailingslashit')) {
	function untrailingslashit($string) {
		return rtrim($string, '/\\');
	}
}