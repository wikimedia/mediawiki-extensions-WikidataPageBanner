<?php

use MediaWiki\Extension\WikidataPageBanner\WikidataPageBanner;

/**
 * @group WikidataPageBanner
 * @group Database
 */
class BannerOptionsTest extends MediaWikiIntegrationTestCase {

	public function addDBData() {
		try {
			if ( !Title::newFromText( 'BannerWithOptions', NS_MAIN )->exists() ) {
				$this->insertPage( 'BannerWithOptions', 'Some Text' );
			}
		} catch ( Exception $e ) {
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->addDBData();
		$this->setMwGlobals( 'wgWPBEnablePageImagesBanners', false );
	}

	/**
	 * Test for covering parameters passed to {{PAGEBANNER}} function
	 * @covers \MediaWiki\Extension\WikidataPageBanner\WikidataPageBanner::addCustomBanner
	 */
	public function testBannerOptions() {
		// store a mock class name in $wpbFunctionsClass static variable so that hooks call mock
		// functions through this variable when performing tests
		WikidataPageBanner::$wpbFunctionsClass = MockWikidataPageBannerOptions::class;
		$parser = $this->createParser( 'BannerWithOptions', NS_MAIN );

		WikidataPageBanner::addCustomBanner( $parser, 'Banner1' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( 'BannerWithOptions', $bannerparams['title'],
			'pgname must be set to title' );
		$this->assertEquals( 'BannerWithOptions', $bannerparams['tooltip'],
			'tooltip must be set to title' );
		$this->assertEquals( [], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( 'Banner2', $bannerparams['title'],
			'pgname must be set' );
		$this->assertEquals( 'Banner2', $bannerparams['tooltip'],
			'tooltip must be set to pgname' );
		$this->assertEquals( [], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'tooltip=hovertext' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( 'Banner2', $bannerparams['title'],
			'pgname must be set' );
		$this->assertEquals( 'hovertext', $bannerparams['tooltip'],
			'pgname must be set' );
		$this->assertEquals( [], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'icon-unesco=', 'icon-star=Main Page' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( 'Banner2', $bannerparams['title'],
			'pgname must be set' );
		$this->assertEquals( 'unesco', $bannerparams['icons'][0]['title'],
			'unesco icon must be set' );
		$this->assertEquals( '#', $bannerparams['icons'][0]['url'],
			'iconurl must be a default #' );
		$this->assertEquals( 'Main Page', $bannerparams['icons'][1]['title'],
			'star icon must be set' );
		$this->assertStringContainsString( 'Main_Page', $bannerparams['icons'][1]['url'],
			'iconurl must be a valid main page url' );
		$this->assertEquals( [], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3,0.2' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( 'wpb-right', $bannerparams['originx'],
			'classname for position must be set' );
		$this->assertEquals( 0.3, $bannerparams['data-pos-x'],
			'data-pos-x must be set' );
		$this->assertEquals( 0.2, $bannerparams['data-pos-y'],
			'data-pos-x must be set' );
		$this->assertTrue( $bannerparams['hasPosition'],
			'when data-pos set this is true' );
		$this->assertEquals( [], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertSame( 0, $bannerparams['data-pos-x'],
			'data-pos must default to 0' );
		$this->assertSame( 0, $bannerparams['data-pos-y'],
			'data-pos-x must default to 0' );
		$this->assertEquals( [], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3', 'test=testparam' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertSame( 0, $bannerparams['data-pos-x'],
			'data-pos must default to 0' );
		$this->assertSame( 0, $bannerparams['data-pos-y'],
			'data-pos-x must default to 0' );
		$this->assertEquals( [
			'Following arguments used in PAGEBANNER are invalid or unknown: test'
		], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3', 'test=testparam', 'test2' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertSame( 0, $bannerparams['data-pos-x'],
			'data-pos must default to 0' );
		$this->assertSame( 0, $bannerparams['data-pos-y'],
			'data-pos-x must default to 0' );
		$this->assertFalse( $bannerparams['hasPosition'],
			'when no data-pos-x or y specified this is false' );
		$this->assertEquals( [
			'Following arguments used in PAGEBANNER are invalid or unknown: test'
		], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'toc=yes', 'test=testparam', 'test2' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertTrue( $bannerparams['enable-toc'],
			'toc must default to yes' );
		$this->assertEquals( [
			'Following arguments used in PAGEBANNER are invalid or unknown: test'
		], $pOut->getWarnings() );

		$pOut->setExtensionData( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'link=' );
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertSame( '', $bannerparams['link'], 'Empty link is possible.' );
	}

	/**
	 * Helper function for self::testBannerOptions.
	 * @param string $title
	 * @param int $namespace
	 * @return Parser Parser object associated with test pages
	 */
	protected function createParser( $title, $namespace ) {
		$parser = $this->createMock( Parser::class );

		$parserOutput = new ParserOutput();
		$parser->expects( $this->any() )->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$curTitle = Title::newFromText( $title, $namespace );
		$parser->expects( $this->any() )->method( 'getTitle' )
			->will( $this->returnValue( $curTitle ) );
		$language = Language::factory( 'en' );
		$parser->expects( $this->any() )->method( 'getTargetLanguage' )
			->will( $this->returnValue( $language ) );

		return $parser;
	}

}
