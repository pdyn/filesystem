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

namespace pdyn\filesystem\tests;

/**
 * Test that our mimetypes library is sane.
 *
 * @group pdyn
 * @group pdyn_filesystem
 * @codeCoverageIgnore
 */
class MimetypeTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test the mimetypes can be imported.
	 */
	public function test_import() {
		$mimemap = \pdyn\filesystem\Mimetype::get_mime_map();
		$this->assertNotEmpty($mimemap);
		$this->assertInternalType('array', $mimemap);
	}
}
