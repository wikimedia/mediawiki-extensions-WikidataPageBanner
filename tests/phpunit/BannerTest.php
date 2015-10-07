<?php
/**
 * @group WikidataPageBanner
 */

/**
 * Mock class for WikidataPageBanner
 */
class MockWikidataPageBannerFunctions extends WikidataPageBannerFunctions {
	public static function getBannerHtml( $bannername, $options = array() ) {
		if ( $bannername == 'NoBanner' ) {
			return null;
		}
		return "Banner";
	}
	public static function getWikidataBanner( $title ) {
		if ( $title == 'NoWikidataBanner' ) {
			return null;
		}
		return "WikidataBanner";
	}
}

class BannerTest extends MediaWikiTestCase {
	/**
	 * Set of pages.
	 * array follows the pattern:
	 * array( 0 => TestPageName, 1 => Namespace, 2 => customBannerValue, 3 => expected articlebanner
	 * property
	 */
	protected $testPagesForDefaultBanner = array(
			array( 'PageWithoutCustomBanner', NS_MAIN, false, "WikidataBanner" ),
			array( 'PageWithCustomBanner', NS_MAIN, "CustomBanner", "CustomBanner" ),
			array( 'PageInFileNamespace', NS_FILE, false, null ),
			array( 'NoWikidataBanner', NS_MAIN, false, "DefaultBanner" ),
			array( 'PageWithInvalidCustomBanner', NS_MAIN, "NoBanner", "WikidataBanner" )
		);

	/**
	 * Add test pages to database
	 * @see MediaWikiTestCase::addDBData()
	 */
	public function addDBData() {
		try {
			foreach ( $this->testPagesForDefaultBanner as $page ) {
				if ( !Title::newFromText( $page[0], $page[1] )->exists() ) {
					$this->insertPage( $page[0], 'Some Text' );
				}
			}
		} catch ( Exception $e ) {
		}

	}
	protected function setUp() {
		parent::setUp();
		$this->setMwGlobals( 'wgWPBImage', "DefaultBanner" );
		$this->setMwGlobals( 'wgWPBEnableDefaultBanner', true );
		$this->addDBData();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * @dataProvider provideTestDefaultBanner
	 * @covers addDefaultBanner(...)
	 */
	public function testDefaultBanner( $title, $ns, $customBanner, $expected ) {
		$out = $this->createPage( $title, $ns, $customBanner );
		// store a mock object in $wpbFunctionsClass static variable so that hooks call mock functions
		// through this variable when performing tests
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		$skin = $this->getMock( "Skin" );
		$skin->expects( $this->any() )->method( 'getSkinName' )
			->will( $this->returnValue( "vector" ) );
		WikidataPageBanner::addBanner( $out, $skin );
		$this->assertEquals( $out->getProperty( 'articlebanner-name' ), $expected,
			'articlebanner-name property must only be set when a valid banner is added' );
	}

	/**
	 * @covers addCustomBanner(...)
	 */
	public function testCustomBanner() {
		$parser = $this->createParser( 'PageWithCustomBanner', NS_MAIN );
		// store a mock class name in $wpbFunctionsClass static variable so that hooks call mock
		// functions through this variable when performing tests
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		WikidataPageBanner::addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['name'], 'Banner',
			'banner parameters must be set on valid namespaces' );

		$parser = $this->createParser( 'PageInTalkNamespace', NS_TALK );
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		WikidataPageBanner::addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getProperty( 'wpb-banner-options' );
		$this->assertFalse( $bannerparams, 'Banner',
			'bannerparameters property should be null for not-allowed namespaces' );
	}

	/**
	 * Helper function for testDefaultBanner
	 * @param string $title
	 * @param int $namespace
	 * @param string $customBanner
	 * @return OutputPage
	 */
	protected function createPage( $title, $namespace, $customBanner ) {
		$context = new RequestContext();
		$curTitle = Title::newFromText( $title, $namespace );
		$context->setTitle( $curTitle );
		$out = $context->getOutput();
		$out->setTitle( $curTitle );
		$out->setPageTitle( $title );
		$out->setArticleFlag( true );
		if ( $customBanner ) {
			$out->setProperty( 'wpb-banner-options', array( 'name' => $customBanner ) );
		}
		return $out;
	}

	/**
	 * Helper function for testCustomBanner
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

	/**
	 * Data Provider for testDefaultBanner
	 */
	public function provideTestDefaultBanner() {
		return $this->testPagesForDefaultBanner;
	}
}
