<?php
namespace pdyn\filesystem\fileuploader;

/**
 * A FileUploader child class that handles image uploads.
 */
class ImageUploader extends \pdyn\filesystem\fileuploader\FileUploader {
	/** @var array Default restrictions for image-only uploads */
	protected $restrictions = [
		'mediatype' => ['image'],
		'extension' => ['jpeg', 'jpg', 'gif', 'png', 'tiff', 'bmp', 'tif'],
	];
}
