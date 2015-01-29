<?php

namespace Debug;

abstract class Debug {

	public static $projectRoot;

	protected static $quitting = false;

	public static function show($data/*, $data2, ...*/) {
		$args = func_get_args();

		$locationString = self::getLocation(1 + self::$quitting);

		echo "<pre>";
		echo "<strong>$locationString</strong><br/>";
		echo htmlentities(@var_export($args, true));
		echo "</pre><!--\n ---- ";
		echo $locationString."\n\n\n\n";
		@var_export($args);
		echo "\n-->";

	}

	public function getLocation($stackLevel = 0) {
		$backtrace = debug_backtrace();

		if (! isset($backtrace[$stackLevel])) {
			throw new \Exception("Invalid stack level: `$stackLevel`");
		}

		$stack = $backtrace[$stackLevel];
		$file = $stack['file'];

		if (self::$projectRoot) {
			$file = ltrim(str_replace(self::$projectRoot, '', $file), '/');
		}

		return sprintf("%s:%s", $file, $stack['line']);

	}

	public static function quit($data/*, $data2, ...*/) {
		self::$quitting = 2;
		call_user_func_array([get_called_class(), 'show'], func_get_args());
		exit;
	}

}