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
		$this->assertEquals( array(), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['tooltip'], 'Banner2',
			'tooltip must be set to pgname' );
		$this->assertEquals( array(), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'tooltip=hovertext' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['tooltip'], 'hovertext',
			'pgname must be set' );
		$this->assertEquals( array(), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'icon-unesco=', 'icon-star=Main Page' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $bannerparams['icons'][0]['title'], 'unesco',
			'unesco icon must be set' );
		$this->assertEquals( '#', $bannerparams['icons'][0]['url'],
			'iconurl must be a default #' );
		$this->assertEquals( $bannerparams['icons'][1]['title'], 'Main Page',
			'star icon must be set' );
		$this->assertContains( 'Main_Page', $bannerparams['icons'][1]['url'],
			'iconurl must be a valid main page url' );
		$this->assertEquals( array(), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3,0.2' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['originx'], 'wpb-right',
			'classname for position must be set' );
		$this->assertEquals( $bannerparams['data-pos-x'], 0.3,
			'data-pos-x must be set' );
		$this->assertEquals( $bannerparams['data-pos-y'], 0.2,
			'data-pos-x must be set' );
		$this->assertEquals( array(), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['data-pos-x'], 0,
			'data-pos must default to 0' );
		$this->assertEquals( $bannerparams['data-pos-y'], 0,
			'data-pos-x must default to 0' );
		$this->assertEquals( array(), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3', 'test=testparam' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['data-pos-x'], 0,
			'data-pos must default to 0' );
		$this->assertEquals( $bannerparams['data-pos-y'], 0,
			'data-pos-x must default to 0' );
		$this->assertEquals( array(
			'Following arguments used in PAGEBANNER are invalid or unknown: test' ), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'origin=0.3', 'test=testparam', 'test2' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['data-pos-x'], 0,
			'data-pos must default to 0' );
		$this->assertEquals( $bannerparams['data-pos-y'], 0,
			'data-pos-x must default to 0' );
		$this->assertEquals( array(
			'Following arguments used in PAGEBANNER are invalid or unknown: test' ), $pOut->getWarnings() );

		$pOut->setProperty( 'wpb-banner-options', null );
		$output = WikidataPageBanner::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'toc=yes', 'test=testparam', 'test2' );
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['toc'], true,
			'toc must default to yes' );
		$this->assertEquals( array(
			'Following arguments used in PAGEBANNER are invalid or unknown: test' ), $pOut->getWarnings() );
	}

	/**
	 * Helper function for self::testBannerOptions.
	 * @param string $title
	 * @param int $namespace
	 * @return Parser Parser object associated with test pages
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
