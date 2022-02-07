<?php

use MediaWiki\Extension\WikidataPageBanner\WikidataPageBannerFunctions;

/**
 * Mock class for WikidataPageBanner
 */
class MockWikidataPageBannerFunctions extends WikidataPageBannerFunctions {

	/**
	 * @param string $bannername
	 * @param array $options
	 * @return string|null
	 */
	public static function getBannerHtml( $bannername, $options = [] ) {
		if ( $bannername == 'NoBanner' ) {
			return null;
		}
		return "Banner";
	}

	/**
	 * @param string $title
	 * @return string|null
	 */
	public static function getPageImagesBanner( $title ) {
		if ( in_array( $title, [ 'WikidataBanner', 'PageWithPageImageBanner' ] ) ) {
			return "PageImagesBanner";
		} else {
			return null;
		}
	}

	/**
	 * @param string $title
	 * @return string|null
	 */
	public static function getWikidataBanner( $title ) {
		if ( in_array( $title, [ 'WikidataBanner', 'PageWithoutCustomBanner',
			'PageWithCustomBanner', 'PageWithInvalidCustomBanner' ] ) ) {
			return "WikidataBanner";
		} else {
			return null;
		}
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
