<?php
/**
 * @group WikidataPageBanner
 */

/**
 * Test for validating options passed to {{PAGEBANNER}} function
 * Mock class for WikidataPageBannerOptions
 */
class MockWikidataPageBannerOptions extends WikidataPageBannerFunctions {
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
		// store a mock class name in $wpbFunctionsClass static variable so that hooks call mock
		// functions through this variable when performing tests
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerOptions";
		$parser = $this->createParser( 'BannerWithOptions', NS_MAIN );

		WikidataPageBanner::addCustomBanner( $parser, 'Banner1' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'BannerWithOptions',
			'pgname must be set to title' );
		$this->assertEquals( $bannerparams['tooltip'], 'BannerWithOptions',
			'tooltip must be set to title' );

		$pOut->setProperty( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['tooltip'], 'Banner2',
			'tooltip must be set to pgname' );

		$pOut->setProperty( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'tooltip=hovertext' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['tooltip'], 'hovertext',
			'pgname must be set' );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'icon-unesco=', 'icon-star=Main Page' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['icons'][0]['icon']->getTitle(), 'unesco',
			'unesco icon must be set' );
		$this->assertEquals( '#', $bannerparams['icons'][0]['iconurl'],
			'iconurl must be a default #' );
		$this->assertEquals( $bannerparams['icons'][1]['icon']->getTitle(), 'Main Page',
			'star icon must be set' );
		$this->assertContains( 'Main_Page', $bannerparams['icons'][1]['iconurl'],
			'iconurl must be a valid main page url' );
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
