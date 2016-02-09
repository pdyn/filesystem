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
 * Handles file uploads made with the FileUploader.js front-end interface.
 */
class FileUploader {
	/** @var array A list of restrictions. See $this->set_restrictions for possible keys/values */
	protected $restrictions = array();

	/**
	 * Set restrictions on file uploads.
	 * @param array $restrictions An array of restrictions. Possible keys are:
	 *                                maxsize (int) A maximum size (in bytes) for the uploaded file.
	 *                                mediatype (array) An array of allowed mediatypes.
	 *                                mimetype (array) An array of allowed mimetypes.
	 *                                extension (array) An array of allowed file extensions.
	 */
	public function set_restrictions(array $restrictions) {
		$this->restrictions = array();

		foreach ($restrictions as $k => $v) {
			switch ($k) {
				case 'maxsize':
					if (is_int($v)) {
						$this->restrictions['maxsize'] = $v;
					}
					break;

				case 'mediatype':
					if (is_array($v)) {
						$this->restrictions['mediatype'] = $v;
					}
					break;

				case 'mimetype':
					if (is_array($v)) {
						$this->restrictions['mimetype'] = $v;
					}
					break;

				case 'extension':
					if (is_array($v)) {
						$this->restrictions['extension'] = $v;
					}
					break;
			}
		}
		return true;
	}

	/**
	 * Validate an UploadedFile object against the list of restrictions.
	 *
	 * @param UploadedFile $file An UploadedFile object to validate.
	 * @return bool True if successful.
	 */
	public function validate_upload(UploadedFile $file) {
		foreach ($this->restrictions as $k => $v) {
			switch ($k) {
				case 'maxsize':
					if ($v < $file->get_size()) {
						throw new Exception('Received file was too large.', Exception::ERR_BAD_REQUEST);
					}
					break;

				case 'mediatype':
					if (!in_array($file->get_mediatype(), $v)) {
						throw new Exception('Files with that extension are not allowed.', Exception::ERR_BAD_REQUEST);
					}
					break;

				case 'mimetype':
					if (!in_array($file->get_type(), $v)) {
						throw new Exception('Files with that extension are not allowed.', Exception::ERR_BAD_REQUEST);
					}
					break;

				case 'extension':
					if (!in_array($file->get_file_extension(), $v)) {
						throw new Exception('Files with that extension are not allowed.', Exception::ERR_BAD_REQUEST);
					}
					break;
			}
		}
		return true;
	}

	/**
	 * Receive an uploaded file, validate it, and save it.
	 *
	 * @param string $savedir A directory to save the file.
	 * @param string $savefilename (Optional) A filename to save the file as, if empty, a unique name will be generated.
	 * @return UploadedFile The resulting UploadedFile object.
	 */
	public function handleupload($savedir, $savefilename = null, $quotadata = array()) {
		if (!isset($_FILES['pdynfileuploader'])) {
			throw new Exception('No file received.', Exception::ERR_BAD_REQUEST);
		}

		if (empty($savedir) || !is_string($savedir)) {
			throw new Exception('Invalid save path received', Exception::ERR_BAD_REQUEST);
		}

		if (!is_writable($savedir)) {
			throw new Exception('Could not write to save path', Exception::ERR_BAD_REQUEST);
		}

		$file = new UploadedFile($_FILES['pdynfileuploader']);

		if (!empty($quotadata) && isset($quotadata['cursize']) && isset($quotadata['limit'])) {
			$newsize = (int)$quotadata['cursize'] + (int)$file->get_size();
			if ($newsize > $quotadata['limit']) {
				unlink($file->stored_filename);
				throw new Exception('Disk quota exceeded', Exception::ERR_BAD_REQUEST);
			}
		}

		if (empty($savefilename) || !is_string($savefilename)) {
			$ext = strtolower($file->get_original_extension());
			$savefilename = uniqid();
			if ($ext !== null) {
				$savefilename .= '.'.$ext;
			}
		}

		$savepath = $savedir.'/'.$savefilename;
		$file->save($savepath);

		$this->postprocess_file($file);

		return $file;
	}

	/**
	 * Overridable hook for postprocessing a saved, uploaded file.
	 *
	 * @param \pdyn\filesystem\fileuploader\UploadedFile $file The uploaded file, post-save.
	 * @return bool Success/Failure.
	 */
	protected function postprocess_file(UploadedFile $file) {
		return true;
	}
}
