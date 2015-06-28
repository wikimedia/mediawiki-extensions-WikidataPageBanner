<?php
/**
 * @group WikidataPageBanner
 */

/**
 * Test for validating options passed to {{PAGEBANNER}} function
 * Mock class for WikidataPageBannerOptions
 */
class MockWikidataPageBannerOptions extends WikidataPageBanner {
	public static function getBannerHtml( $bannername, $options = array() ) {
		return $options;
	}
}

class BannerOptionsTest extends MediaWikiTestCase {
	public function addDBData() {
		try {
			if ( !Title::newFromText( 'BannerWithOptions', NS_MAIN )->exists() ) {
				$this->insertPage( 'BannerWithOptions', 'Some Text' );
			}
		} catch ( Exception $e ) {
			$this->exceptionFromAddDBData = $e;
		}
	}

	protected function setUp() {
		parent::setUp();
		$this->addDBData();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * Test for covering parameters passed to {{PAGEBANNER}} function
	 * @covers addCustomBanner(...)
	 */
	public function testBannerOptions() {
		$parser = $this->createParser( 'BannerWithOptions', NS_MAIN );

		MockWikidataPageBannerOptions::addCustomBanner( $parser, 'Banner1' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'BannerWithOptions',
			'pgname must be set to title' );
		$this->assertEquals( $bannerparams['tooltip'], 'BannerWithOptions',
			'tooltip must be set to title' );

		$pOut->setProperty( 'wpb-banner-options', null );
		MockWikidataPageBannerOptions::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['tooltip'], 'Banner2',
			'tooltip must be set to pgname' );

		$pOut->setProperty( 'wpb-banner-options', null );
		MockWikidataPageBannerOptions::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'tooltip=hovertext' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['tooltip'], 'hovertext',
			'pgname must be set' );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = MockWikidataPageBannerOptions::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'icons=unesco,star' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['icons'][0]['icon']->getTitle(), 'unesco',
			'unesco icon must be set' );
		$this->assertEquals( $bannerparams['icons'][1]['icon']->getTitle(), 'star',
			'star icon must be set' );
	}

	/**
	 * Helper function for testCustomBanner
	 * @return  Parser Parser object associated with test pages
	 */
	protected function createParser( $title, $namespace ) {
		$parser = $this->getMock( 'Parser' );
		$parserOutput = new ParserOutput();
		$parser->expects( $this->any() )->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );
		$curTitle = Title::newFromText( $title, $namespace );
		$parser->expects( $this->any() )->method( 'getTitle' )
			->will( $this->returnValue( $curTitle ) );
		return $parser;
	}
}
