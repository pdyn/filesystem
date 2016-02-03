<?php
/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @copyright 2010 onwards James McQuillan (http://pdyn.net)
 * @author James McQuillan <james@pdyn.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pdyn\filesystem;

/**
 * A collection of filesystem utilities.
 */
class FilesystemUtils {
	/**
	 * Determine the mime-type of a file.
	 *
	 * @param string $filename The full, absolute path to a file.
	 * @return string The mime type.
	 */
	public static function get_mime_type($filename) {
		if (extension_loaded('fileinfo')) {
			try {
				$finfo = new \finfo(FILEINFO_MIME_TYPE);
				return $finfo->file($filename);
			} catch (\Exception $e) {
				// Try next option...
			}
		}

		if (function_exists('mime_content_type')) {
			try {
				return mime_content_type($filename);
			} catch (\Exception $e) {
				// Try next option...
			}
		}

		$mime = \pdyn\filesystem\Mimetype::ext2mime(static::get_ext($filename));

		// Strip out encoding, if present.
		if (mb_strpos($mime, ';') !== false) {
			$mime = explode(';', $mime);
			$mime = $mime[0];
		}
		return $mime;
	}

	/**
	 * This helps us create normalized filenames.
	 *
	 * As the point of this function is to alter the input, the result should not be used to refer to the original, but instead
	 * used as a normalized name for storing new files.
	 *
	 * @param string $input The input filename.
	 * @return string Normalized filename.
	 */
	public static function normalize_filename($input) {
		if (empty($input)) {
			return false;
		}

		$pathinfo = pathinfo($input);

		// Normalize extension.
		$ext = mb_strtolower($pathinfo['extension']);
		if ($ext === 'jpeg') {
			$ext = 'jpg';
		}

		// Rebuild filename.
		$filename = $pathinfo['filename'].'.'.$ext;

		// Sanitize.
		$filename = static::sanitize_filename($filename);

		return $filename;
	}

	/**
	 * Sanitize a filename.
	 *
	 * Specifically, this removes directory traversal via ./ and ../
	 *
	 * @param string $i The input value.
	 * @param boolean $allow_subdirs Whether to allow forward directory traversal (i.e. subdirectories).
	 * @return string The sanitized string.
	 */
	public static function sanitize_filename($i, $allow_subdirs = false) {
		$i = trim($i);
		$replacements = ['../', './'];
		if ($allow_subdirs === false) {
			$replacements[] = '/';
		}
		if ($i{0} === '/') {
			$i = substr($i, 1);
		}
		return str_replace($replacements, '', $i);
	}

	/**
	 * Create a complete directory structure in a given path.
	 *
	 * @param array $folders An array representing the structure to be created. Keys are folder names, values are arrays of
	 *                       subfolders (with keys being names and values being subfolders, etc)
	 * @param string $base_dir The directory to create the structure in. Must include trailing slash.
	 */
	public static function create_directory_structure($folders, $base_dir) {
		if (!empty($folders) && is_array($folders)) {
			foreach ($folders as $name => $subfolders) {
				if (!file_exists($base_dir.$name.'/')) {
					mkdir($base_dir.$name.'/');
				}
				touch($base_dir.$name.'/index.html');
				if (!empty($subfolders) && is_array($subfolders)) {
					static::create_directory_structure($subfolders, $base_dir.$name.'/');
				}
			}
		}
	}

	/**
	 * Delete a folder and all subfolders.
	 *
	 * @param string $path The absolute path to the folder to delete.
	 * @return bool Success/Failure.
	 */
	public static function rrmdir($path) {
		if (is_file($path)) {
			return unlink($path);
		} elseif (is_dir($path)) {
			$dir_members = scandir($path);
			foreach ($dir_members as $member) {
				if ($member !== '.' && $member !== '..') {
					static::rrmdir($path.'/'.$member);
				}
			}
			return @rmdir($path);
		}
	}

	/**
	 * Unzip a zip file into a directory.
	 *
	 * @param string $file The absolute path to a zip file.
	 * @param string $destination The absolute path to the directory to unzip to file into.
	 * @return bool Success/Failure.
	 */
	public static function unzip($file, $destination) {
		if (!file_exists($file)) {
			return false;
		}

		if (!file_exists($destination)) {
			mkdir($destination);
		}

		if (class_exists('ZipArchive')) {
			$result = static::unzip_using_ziparchive($file, $destination);
			if ($result === true) {
				return true;
			}
		} else {
			throw new \Exception('No zip support available', 500);
		}
	}

	/**
	 * Unzip a file into a directory using the ZipArchive class.
	 *
	 * @param string $file The absolute path to a zip file.
	 * @param string $destination The absolute path to the directory to unzip to file into.
	 * @return bool Success/Failure.
	 */
	public static function unzip_using_ziparchive($file, $destination) {
		$za = new \ZipArchive;
		$zares = $za->open($file);
		if ($zares === true) {
			$za->extractTo($destination);
			$za->close();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the general file type from a mime type.
	 *
	 * This returns the beginning part (before the slash) of a mime type, giving a more general category of the file.
	 *
	 * @param string $mime The mime type.
	 * @return string The general file type.
	 */
	public static function get_file_type_from_mime($mime) {
		$validtypes = ['application', 'audio', 'image', 'message', 'multipart', 'text', 'video'];

		if (mb_stripos($mime, '/') === false) {
			// Malformed mime type.
			return false;
		}

		$iparts = explode('/', $mime);
		if (count($iparts) !== 2) {
			// Malformed mime type.
			return false;
		}

		return (in_array($iparts[0], $validtypes, true)) ? $iparts[0] : false;
	}

	/**
	 * Get the mime type of a file using only the filename.
	 *
	 * This will return the mime type of a file using the files extension. It will not check if the file exists, open the file,
	 * analyze the file, etc. Therefore, understand that the returned mime type may not reflect what is actually in the file.
	 *
	 * @param string $fn The filename.
	 * @return string The mime type.
	 */
	public static function get_mime_from_filename($fn) {
		return \pdyn\filesystem\Mimetype::ext2mime(static::get_ext($fn));
	}

	/**
	 * Get the file extension of a filename.
	 *
	 * @param string $filename The filename.
	 * @return string The extension.
	 */
	public static function get_ext($filename) {
		return mb_substr(mb_strrchr($filename, '.'), 1);
	}

	/**
	 * Convert bytes into a human-readable format.
	 *
	 * For example: $bytes = 1024 would return 1KB, $bytes = 1048576 would return 1MB, etc.
	 *
	 * @param int $bytes The number of bytes to convert.
	 * @return string The human readable representation of the bytes.
	 */
	public static function make_human_readable_filesize($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		$num_units = count($units);
		$size = $bytes;
		foreach ($units as $i => $unit) {
			if ($size < 1024 || (($i + 1) === $num_units)) {
				return round($size, 2).$unit;
			}
			$size = $size / 1024;
		}
		return $size;
	}

	/**
	 * Get the disk usage of a given directory.
	 *
	 * @param string $dir The absolute path to the directory to get the disk usage of.
	 * @return int The disk usage of the directory in bytes.
	 */
	public static function dirsize($dir, $createifmissing = false) {
		$dirsize = 0;
		if (!file_exists($dir)) {
			if ($createifmissing === true) {
				mkdir($dir);
				return 0;
			}
			throw new \Exception('Directory does not exist!', 400);
		}

		$dir_info = scandir($dir);

		if ($dir{(mb_strlen($dir) - 1)} !== '/') {
			$dir .= '/';
		}

		clearstatcache();
		foreach ($dir_info as $i => $member) {
			if ($member === '.' || $member === '..') {
				continue;
			}
			$abs_member = $dir.$member;
			if (is_file($abs_member)) {
				$dirsize += filesize($abs_member);
			} elseif (is_dir($abs_member)) {
				$dirsize += static::dirsize($abs_member.'/');
			}
		}
		return $dirsize;
	}
}
