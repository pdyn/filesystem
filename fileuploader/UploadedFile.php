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

namespace pdyn\filesystem\fileuploader;

use \pdyn\base\Exception;

/**
 * Represents a file uploaded with the FileUploader suite.
 */
class UploadedFile extends \pdyn\datatype\UploadedFile {
	/** @var int The size of the file, in bytes. */
	protected $size;

	/** @var string The received mimetype of the file */
	protected $type;

	/**
	 * Constructor.
	 *
	 * @param array $filesentry A single file entry from the $_FILES global.
	 */
	public function __construct(array $filesentry) {
		$this->stored_filename = $filesentry['tmp_name'];
		$this->orig_filename = $filesentry['name'];
		$this->size = (int)$filesentry['size'];
		$receivedtype = $filesentry['type'];
		$analyzedtype = $this->get_analyzed_mimetype();
		$this->type = $analyzedtype;
	}

	/**
	 * Get the mediatype of the file.
	 *
	 * @return string The mediatype of the file.
	 */
	public function get_mediatype() {
		return \pdyn\filesystem\Mimetype::get_mediatype($this->type, $this->stored_filename);
	}

	/**
	 * Get the size, in bytes, of the file.
	 *
	 * @return int The size, in bytes, of the file.
	 */
	public function get_size() {
		return $this->size;
	}

	/**
	 * Get the mimetype of the file.
	 *
	 * @return string The mimetype of the file.
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Save the uploaded file to a specific path.
	 *
	 * @param string $savepath A full path (incl. filename) to save the uploaded file as.
	 * @return bool Success/Failure.
	 */
	public function save($savepath) {
		$result = move_uploaded_file($this->stored_filename, $savepath);
		if ($result === true) {
			$this->stored_filename = $savepath;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get object information as an array.
	 *
	 * @return array An array of object information.
	 */
	public function to_array() {
		return array(
			'origname' => $this->orig_filename,
			'size' => $this->size,
			'mime' => $this->type,
			'file' => $this->stored_filename
		);
	}
}
