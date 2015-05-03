<?php

namespace Debug;

abstract class Debug {

	public static $projectRoot;

	protected static $callDepth = 1;
	protected static $isatty = null;
	protected static $objectHash = [];


	public static function show($data/*, $data2, ...*/) {
		if (is_null(self::$isatty)) {
			self::$isatty = posix_isatty(STDOUT);
		}

		self::$objectHash = [];

		$args = func_get_args();

		$locationString = self::getLocation(self::$callDepth);

		if (! self::$isatty) {
			echo "<pre>";
			echo "<strong>$locationString</strong>\n";
			foreach ($args as $arg) {
				echo htmlentities(self::export($arg))."\n";
			}

			echo "</pre><!--\n ---- ";
			echo $locationString."\n\n";
			foreach ($args as $arg) {
				echo self::export($arg)."\n";
			}
			echo "\n-->";
		} else {
			echo "\033[47;30m\n".$locationString."\033[0;37;40m\n";
			foreach ($args as $arg) {
				$str = self::export($arg, null);
				echo "\n$str\n";
			}
			echo "\033[0m\n";
		}
	}

	protected static function export($arg, $key, $indentLevel = 0) {
		$indent = str_repeat('  ', $indentLevel);
		$text = $indent.(! is_null($key) ? "$key: " : '');
		$comma = '';

		if (is_object($arg)) {
			$hash = spl_object_hash($arg);
			$obj = new \ReflectionObject($arg);

			if (array_key_exists($hash, self::$objectHash)) {
				$id = self::$objectHash[$hash];
				$print = false;
			} else {
				$id = count(self::$objectHash);
				$print = true;
				self::$objectHash[$hash] = $id;
			}

			$name = $obj->getName();
			$partials = explode('\\', trim($name, '\\'));
			$last = array_pop($partials);
			$name = implode('\\', $partials).'\\'."\033[36m$last\033[0;40m";
			//$name = preg_replace('/[\\\\](.{1})(.*?)[\\\\]/is', '\\\\$1\\\\', $name);
			$props = $obj->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED);

			$text .= "{ // $name (&$id)\n";
			if ($print) {
				$i = 0;
				$last = count($props) - 1;

				foreach ($props as $prop) {
					$prop->setAccessible(true);
					$value = $prop->getValue($arg);
					$text .= self::export($value, $prop->getName(), $indentLevel + 1).($i == $last ? '' : ','). "\n";
					$i++;
				}
			}

			$text .= $indent.'}';
		} else if (is_array($arg)) {
			$text .= '['."\n";
			$i = 0;
			$last = count($arg) - 1;
			foreach ($arg as $key => $value) {
				$text .= self::export($value, $key, $indentLevel + 1).($i == $last ? '' : ',')."\n";
				$i++;
			}
			$text .= $indent.']';
		} else {
			if (is_string($arg)) {
				if (self::$isatty) {
					$arg = "\033[33m$arg\033[0;40m";
				}
				$arg = '"'.$arg.'"';
			} else if (is_numeric($arg)) {
				$arg = "\033[36m$arg\033[0;40m";
			} else if (is_bool($arg) || is_null($arg)) {
				$string = ($arg === true ? 'TRUE' : ($arg === false ? 'FALSE' : 'NULL'));
				$arg = "\033[32m$string\033[0;40m";
			}
			$text .= $arg;
		}
		return $text;
	}

	protected static function export2($arg) {
		return @var_export($arg, true);
	}

	public static function setCallDepth($callDepth) {
		self::$callDepth = $callDepth;
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