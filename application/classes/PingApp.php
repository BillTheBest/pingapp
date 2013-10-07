<?php defined('SYSPATH') or die('No direct script access');

final class PingApp {
	
	/**
	 * SMS Service
	 * @var string
	 */
	public static $sms = FALSE;

	/**
	 * Name of the SMS provider
	 * @var string
	 */
	public static $sms_provider = NULL;
	
	/**
	 * Initializes Pingapp and Plugins
	 */
	public static function init()
	{
		/**
		 * 1. Plugin Registration Listener
		 */
		Event::instance()->listen(
			'PingApp_Plugin',
			function ($event, $params) {
				self::register($params);
			}
		);

		/**
		 * 2. Load the plugins
		 */
		self::load();


		// SMS Settings
		self::$sms = (PingApp_Settings::get('sms') == 'on') ? TRUE : FALSE;
		self::$sms_provider = PingApp_Settings::get('sms_provider');
	}

	/**
	 * Load All Plugins Into System
	 */
	public static function load()
	{
		// Load Plugins
		$results = scandir(PLUGINPATH);
		foreach ($results as $result) {
			if ($result === '.' or $result === '..') continue;

			if (is_dir(PLUGINPATH.$result))
			{
				Kohana::modules( array($result => PLUGINPATH.$result) + Kohana::modules() );
			}
		}
	}

	/**
	 * Register A Plugin
	 *
	 * @param array $params
	 */
	public static function register($params)
	{
		if (self::valid_plugin($params))
		{
			$config = Kohana::$config->load('_plugins');
			$config->set(key($params), $params[key($params)]);
		}
	}

	/**
	 * Validate Plugin Parameters
	 *
	 * @param array $params
	 * @return bool valid/invalid
	 */
	public static function valid_plugin($params)
	{
		$path = array_keys($params)[0];

		if ( ! is_array($params) )
		{
			return FALSE;
		}

		// Validate Name
		if ( ! isset($params[$path]['name']) )
		{
			Kohana::$log->add(Log::ERROR, __("':plugin' does not have 'name'", array(':plugin' => $path)));
			return FALSE;
		}

		// Validate Version
		if ( ! isset($params[$path]['version']) )
		{
			Kohana::$log->add(Log::ERROR, __("':plugin' does not have 'version'", array(':plugin' => $path)));
			return FALSE;
		}

		// Validate Services
		if ( ! isset($params[$path]['services']) OR ! is_array($params[$path]['services']) )
		{
			Kohana::$log->add(Log::ERROR, __("':plugin' does not have 'services' or 'services' is not an array", array(':plugin' => $path)));
			return FALSE;
		}

		// Validate Options
		if ( ! isset($params[$path]['options']) OR ! is_array($params[$path]['options']) )
		{
			Kohana::$log->add(Log::ERROR, __("':plugin' does not have 'options' or 'options' is not an array", array(':plugin' => $path)));
			return FALSE;
		}

		// Validate Links
		if ( ! isset($params[$path]['links']) OR ! is_array($params[$path]['links']) )
		{
			Kohana::$log->add(Log::ERROR, __("':plugin' does not have 'links' or 'links' is not an array", array(':plugin' => $path)));
			return FALSE;
		}

		return TRUE;
	}
}