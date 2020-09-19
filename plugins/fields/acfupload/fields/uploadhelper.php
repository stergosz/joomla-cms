<?php

/**
 * @package         Advanced Custom Fields
 * @version         1.2.0-RC2 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.path');

/**
 *  Upload Helper class mainly used by the FileUpload field
 */
class ACFUploadHelper
{
	/**
	 * Upload file
	 *
	 * @param	array	$file			The request file as posted by form
	 * @param	string	$upload_folder	The upload folder where the file must be uploaded
	 * @param	bool	$randomize		If is set to true, the filename will get a random prefix
	 * @param	bool	$allow_unsafe	Allow the upload of unsafe files. See JFilterInput::isSafeFile() method.
	 *
	 * @return	mixed	String on success, Null on failure
	 */
	public static function upload($file, $upload_folder, $randomize = false, $allow_unsafe = false)
	{
		// Make sure we have a valid file array
		if (!isset($file['name']) || !isset($file['tmp_name']))
		{
			return;
		}

		// Sanitize filename
		$filename = \JFile::makeSafe($file['name']);

		// Replace spaces with underscore
		$filename = str_replace(' ', '_', $filename);

		// Add a random prefix to filename to prevent replacing existing files accidentally
		if ($randomize)
		{
			self::randomizeFilename($filename);
		}

		$filename = \JPath::clean(JPATH_ROOT . '/' . $upload_folder . '/' . $filename);

		// In case the filename exists, append copy_X suffix to filename
		self::makeFilenameUnique($filename);

        if (!\JFile::upload($file['tmp_name'], $filename, false, $allow_unsafe))
        {
			return;
		}

		return [
			'file' => pathinfo($filename, PATHINFO_BASENAME),
			'url'  => self::absURL($filename)
		];
	}

	/**
	 * Generates a unique filename in case the give name already exists by appending copy_X suffix to filename.
	 *
	 * @param  strimg $name
	 *
	 * @return void
	 */
	private static function makeFilenameUnique(&$name)
	{
		$dir = pathinfo($name, PATHINFO_DIRNAME);
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		$actual_name = pathinfo($name, PATHINFO_FILENAME);
		
		$original_name = $actual_name;

		$i = 1;

		while(\JFile::exists($dir . '/' . $actual_name . '.' . $ext))
		{           
			$actual_name = (string) $original_name . '_copy_' . $i;
			$name = $dir . '/' . $actual_name . '.' . $ext;
			$i++;
		}
	}

	/**
	 * Delete an uploaded file
	 *
	 * @param string $filename	The filename
	 * @param string $upload_folder	The uploaded folder
	 *
	 * @return bool
	 */
	public static function deleteFile($filename, $upload_folder)
	{
		if (empty($filename) || empty($upload_folder))
		{
			return;
		}

		$file = \JPath::clean(JPATH_ROOT . '/' . $upload_folder . '/' . $filename);

		if (!\JFile::exists($file))
		{
			return;
		}

		return JFile::delete($file);
	}

	/**
	 * Add a random prefix to filename
	 *
	 * @param  string $filename
	 *
	 * @return void
	 */
	public static function randomizeFilename(&$filename)
	{
		$filename = substr(str_shuffle(md5(time())), 0, 10) . '_' . $filename;
	}

	/**
	 * Checks whether a filename type is in an allowed list
	 *
	 * @param	mixed	$allowed_types	Array or a comma separated list of allowed file types. Eg: .jpg, .png, .gif
	 * @param	string	$file_path		The filename path to check
	 *
	 * @return	bool
	 */
	public static function isInAllowedTypes($allowed_types, $file_path)
	{
		// If empty assume, all files types are accepted
		if (empty($allowed_types))
		{
			return true;
		}

		if (is_string($allowed_types))
		{
			$allowed_types = explode(',', $allowed_types);
		}

		// Remove null and empty properties
		$allowed_types = array_filter($allowed_types);

		if (!$file_extension = \JFile::getExt($file_path))
		{
			return false;
		}

		foreach ($allowed_types as $allowed_extension)
		{
			if (strpos($allowed_extension, $file_extension) !== false)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Return absolute full URL of a path
	 *
	 * @param	string	$path
	 *
	 * @return	string
	 */
	public static function absURL($path)
	{
		$path = str_replace([JPATH_SITE, JPATH_ROOT, \JURI::root()], '', $path);
		$path = \JPath::clean($path);

		// Convert Windows Path to Unix
		$path = str_replace('\\','/',$path);

		$path = ltrim($path, '/');
		$path = \JURI::root() . $path;

		return $path;
	}


	/**
	 * Make sure the folder does exist and it's writable. If the folder doesn't exist, it will try to create it.
	 *
	 * @param  string $path
	 *
	 * @return bool
	 */
	public static function folderExistsAndWritable($path)
	{
		if (!\JFolder::exists($path))
		{
			if (!\JFolder::create($path))
			{
				return false;
			}

			// New folder created. Let's protect it.
			self::writeHtaccessFile($path);
			self::writeIndexHtmlFile($path);
		}

		// Make sure the folder is writable
		return @is_writable($path);
	}

	/**
	 * Add an .htaccess file to the folder in order to disable PHP engine entirely 
	 *
	 * @param  string $path	The path where to write the file
	 *
	 * @return void
	 */
	public static function writeHtaccessFile($path)
	{
		$content = '
			# Turn off all options we don\'t need.
			Options None
			Options +FollowSymLinks

			# Disable the PHP engine entirely.
			<IfModule mod_php5.c>
				php_flag engine off
			</IfModule>

			# Block direct PHP access
			<Files *.php>
				deny from all
			</Files>
		';

		\JFile::write($path . '/.htaccess', $content);
	}

	/**
	 * Creates an empty index.html file to prevent directory listing 
	 *
	 * @param  string $path	The path where to write the file
	 *
	 * @return void
	 */
	public static function writeIndexHtmlFile($path)
	{
		\JFile::write($path . '/index.html', '<!DOCTYPE html><title></title>');	
	}

	/**
	 * Strip .tmp extension from a filename
	 *
	 * @param  string $path
	 *
	 * @return string
	 */
	public static function removeTmpSuffix($path)
	{
		if (strpos($path, '.tmp') === false)
		{
			return $path;
		}

		$new_path = \JFile::stripExt($path);

		// Rename filename
		if (\JFile::move($path, $new_path))
		{
			return $new_path;
		}

		return false;
	}

	/**
	 * Help method to encrypt and descypt sensitive data
	 *
	 * @return object
	 */
	public static function getCrypt()
	{
		$privateKey = md5(\JFactory::getConfig()->get('secret'));

		// Build the JCryptKey object.
		$key = new \JCryptKey('simple', $privateKey, $privateKey);

		// Setup the JCrypt object.
		return new \JCrypt(new \JCryptCipherSimple, $key);
	}

	/**
	 * Get a human readable file size in PHP:
	 * Credits to: http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
	 *
	 * @param integer	$filename	The file size in bytes
	 * @param int 		$decimals
	 *
	 * @return string	The human readable string
	 */
	public static function humanFilesize($bytes, $decimals = 2)
	{
		$size = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
		$factor = floor((strlen($bytes) - 1) / 3);

		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
	}
}