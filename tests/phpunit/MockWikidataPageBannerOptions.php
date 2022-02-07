<?php

use MediaWiki\Extension\WikidataPageBanner\WikidataPageBannerFunctions;

/**
 * Test for validating options passed to {{PAGEBANNER}} function
 * Mock class for WikidataPageBannerOptions
 */
class MockWikidataPageBannerOptions extends WikidataPageBannerFunctions {

	/**
	 * @param string $bannername
	 * @param array $options
	 * @return string|null
	 */
	public static function getBannerHtml( $bannername, $options = [] ) {
		return $options;
	}

	/**
	 * @param string $filename
	 * @param int|null $imagewidth
	 * @return string|null
	 */
	public static function getImageUrl( $filename, $imagewidth = null ) {
		if ( $filename == 'NoWikidataBanner' || $filename == 'NoBanner' || $filename === null ) {
			return null;
		}

		return "BannerUrl";
	}

}
