<?php

namespace Debug;

abstract class Debug {

	public static $projectRoot;

	protected static $callDepth = 1;

	public static function show($data/*, $data2, ...*/) {
		$args = func_get_args();

		$locationString = self::getLocation(self::$callDepth);

		echo "<pre>";
		echo "<strong>$locationString</strong>\n";
		foreach ($args as $arg) {
			echo htmlentities(@var_export($arg, true))."\n";
		}

		echo "</pre><!--\n ---- ";
		echo $locationString."\n\n\n\n";
		foreach ($args as $arg) {
			echo @var_export($arg)."\n";
		}
		echo "\n-->";
	}

	public static function setCallDepth($callDepth) {
		self::$callDepth = $callDepth;
	}

	public static function quit() {

		self::setCallDepth(3);
		call_user_func_array(['\\Debug\\Debug', 'show'], func_get_args());

		exit;
	}

	public static function getLocation($stackLevel = 0) {
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
}