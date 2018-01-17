<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2017 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Parser;

use LightnCandy\LightnCandy;

class View_Handlebars extends \View
{
	// default extension used by this template engine
	public $extension = 'hbs';

	protected function process_file($file_override = false)
	{
		// get the template filename
		$file = $file_override ?: $this->file_name;

		// compiled template path
		$path = rtrim(\Config::get('parser.View_Handlebars.compile_dir', APPPATH.'tmp'.DS.'handlebars'),DS).DS;

		// construct the compiled filename
		$compiled = md5($file);
		$compiled = $path.substr($compiled, 0, 1).DS.substr($compiled, 1, 1).DS.substr($compiled, 2).'.'.$this->extension;

		// do we need to compile?
		if ( ! is_file($compiled) or filemtime($file) > filemtime($compiled) or \Config::get('parser.View_Handlebars.force_compile', true))
		{
			// make sure the directory exists
			if  ( ! is_dir($compiled_path = dirname($compiled)))
			{
				\File::create_dir(APPPATH, substr($compiled_path, strlen(APPPATH)));
			}

			// write the compiled code
			file_put_contents($compiled, '<?php ' . LightnCandy::compile(
				file_get_contents($file),
				array(
					'partialresolver' => function($cx, $name) { return \Finder::search('views', $name, '.'.$this->extension, false, false); }
				) + \Config::get('parser.View_Handlebars.environment', array())
			));
		}

		// fetch the compiled template and render it
		try
		{
			$result = include($compiled);
			$result = $result($this->get_data());
		}
		catch (\Exception $e)
		{
			// Delete the output buffer & re-throw the exception
			ob_end_clean();
			throw $e;
		}

		$this->unsanitize($data);
		return $result;
	}
}
