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

		$output = MockWikidataPageBannerOptions::addCustomBanner( $parser, 'Banner1' );
		$this->assertEquals( $output[0]['title'], 'BannerWithOptions',
			'pgname must be set to title' );
		$this->assertEquals( $output[0]['tooltip'], 'BannerWithOptions',
			'tooltip must be set to title' );

		$output = MockWikidataPageBannerOptions::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2' );
		$this->assertEquals( $output[0]['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $output[0]['tooltip'], 'Banner2',
			'tooltip must be set to pgname' );

		$output = MockWikidataPageBannerOptions::addCustomBanner( $parser, 'Banner1',
			'pgname=Banner2', 'tooltip=hovertext' );
		$this->assertEquals( $output[0]['title'], 'Banner2',
			'pgname must be set' );
		$this->assertEquals( $output[0]['tooltip'], 'hovertext',
			'pgname must be set' );
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
