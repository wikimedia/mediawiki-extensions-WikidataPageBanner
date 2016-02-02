<?php

/**
 * @covers WikidataPageBannerFunctions
 *
 * @group WikidataPageBanner
 *
 * @license GPL-2.0
 * @author Sébastien Santoro <dereckson@espace-win.org>
 */
class WikidataPageBannerFunctionsTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers WikidataPageBannerFunctions::getImageUrl
	 */
	public function testGetImageUrl() {
		$this->assertNull( WikidataPageBannerFunctions::getImageUrl( "not-existing-image-file.jpg" ) );
	}

}
