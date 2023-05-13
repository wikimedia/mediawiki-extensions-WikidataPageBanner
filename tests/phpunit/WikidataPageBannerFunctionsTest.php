<?php

use MediaWiki\Extension\WikidataPageBanner\Banner;
use MediaWiki\MediaWikiServices;

/**
 * @covers \MediaWiki\Extension\WikidataPageBanner\Banner
 *
 * @group WikidataPageBanner
 *
 * @license GPL-2.0-only
 * @author Sébastien Santoro <dereckson@espace-win.org>
 */
class WikidataPageBannerFunctionsTest extends PHPUnit\Framework\TestCase {

	/**
	 * @covers \MediaWiki\Extension\WikidataPageBanner\Banner::getImageUrl
	 */
	public function testGetImageUrl() {
		$this->assertNull( Banner::getImageUrl( "not-existing-image-file.jpg" ) );
	}

	/**
	 * @covers \MediaWiki\Extension\WikidataPageBanner\Banner::getBannerHtml()
	 * @dataProvider provideGetBannerHtml
	 * @param string $bannerFilename
	 * @param bool $fileNeeded
	 * @param mixed[] $options
	 * @param string|null $expectedHtml
	 */
	public function testGetBannerHtml( $bannerFilename, $fileNeeded, $options, $expectedHtml ) {
		// Rely on InstantCommons for test files.
		if ( $fileNeeded && !MediaWikiServices::getInstance()->getRepoGroup()->findFile( $bannerFilename ) ) {
			$this->markTestSkipped( '"' . $bannerFilename . '" not found? Instant commons disabled?' );
		}
		$bannerHtml = Banner::getBannerHtml( $bannerFilename, $options );
		if ( $bannerHtml === null ) {
			$this->assertSame( $expectedHtml, $bannerHtml );
		} else {
			$this->assertStringMatchesFormat( $expectedHtml, $bannerHtml );
		}
	}

	public static function provideGetBannerHtml() {
		return [
			'file-not-found' => [
				'Not-existing-image-file.jpg',
				false,
				[],
				null,
			],
			'links to banner image' => [
				'Test.jpg',
				true,
				[],
				'%a<a class="image" dir="ltr" title="" href="%a/File:Test.jpg">%a',
			],
			'links to local wiki page' => [
				'Test.jpg',
				true,
				[ 'link' => 'Foo' ],
				'%a<a class="image" dir="ltr" title="" href="%a/Foo">%a',
			],
			'links to remote URL' => [
				'Test.jpg',
				true,
				[ 'link' => 'https://example.org/foo' ],
				'%a<a class="image" dir="ltr" title="" href="https://example.org/foo">%a',
			],
			'no link' => [
				'Test.jpg',
				true,
				[ 'link' => '' ],
				'%a<a class="image" dir="ltr" title="">%a',
			],
		];
	}
}
