<?php

class Globals
{
	private static $data = array();

	public static function set($key, $val)
	{
		Globals::$data[$key] = $val;
	}

	public static function get($key, $fallback = null)
	{
		return (isset(Globals::$data[$key])) ? Globals::$data[$key] : $fallback;
	}

	public static function delete($key)
	{
		if (isset(Globals::$data[$key]))
		{
			unset(Globals::$data[$key]);
		}
	}

	public static function push($key, $value, $index = null)
	{
		$g = &Globals::$data[$key];

		if (!empty($g) and !is_array($g)) return;

		if (empty($g))
		{
			$g = array();
		}

		if (!is_null($index))
		{
			$g[$index] = $value;
		}
		else
		{
			$g[] = $value;
		}
	}

	public static function pop($key, $index = null)
	{
		if (!is_array(Globals::$data[$key])) return;

		if (!is_null($index))
		{
			unset(Globals::$data[$key][$index]);
		}
		else
		{
			array_pop(Globals::$data[$key]);
		}
	}

	public static function extractAll()
	{
		return Globals::$data;
	}

	public static function extract()
	{
		$data = array();
		$args = func_get_args();

		foreach ($args as $arg)
		{
			$data[$arg] = (isset(Globals::$data[$arg])) ? Globals::$data[$arg] : null;
		}

		return $data;
	}
}
