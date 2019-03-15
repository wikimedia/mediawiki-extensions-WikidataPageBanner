<?php

/**
 * Mock class for WikidataPageBanner
 */
class MockWikidataPageBannerFunctions extends WikidataPageBannerFunctions {

	public static function getBannerHtml( $bannername, $options = [] ) {
		if ( $bannername == 'NoBanner' ) {
			return null;
		}
		return "Banner";
	}

	public static function getPageImagesBanner( $title ) {
		if ( in_array( $title, [ 'WikidataBanner', 'PageWithPageImageBanner' ] ) ) {
			return "PageImagesBanner";
		} else {
			return null;
		}
	}

	public static function getWikidataBanner( $title ) {
		if ( in_array( $title, [ 'WikidataBanner', 'PageWithoutCustomBanner',
			'PageWithCustomBanner', 'PageWithInvalidCustomBanner' ] ) ) {
			return "WikidataBanner";
		} else {
			return null;
		}
	}

	public static function getImageUrl( $filename, $imagewidth = null ) {
		if ( $filename == 'NoWikidataBanner' || $filename == 'NoBanner' || $filename === null ) {
			return null;
		}

		return "BannerUrl";
	}

}

/**
 * @group WikidataPageBanner
 * @group Database
 */
class BannerTest extends MediaWikiTestCase {

	/**
	 * Set of pages.
	 * array follows the pattern:
	 * array( 0 => TestPageName, 1 => Namespace, 2 => customBannerValue, 3 => expected articlebanner
	 * property
	 */
	protected $testPagesForDefaultBanner = [
			[ 'PageWithoutCustomBanner', NS_MAIN, false, "WikidataBanner" ],
			[ 'PageWithCustomBanner', NS_MAIN, "CustomBanner", "CustomBanner" ],
			[ 'PageInFileNamespace', NS_FILE, false, null ],
			[ 'NoWikidataBanner', NS_MAIN, false, "DefaultBanner" ],
			[ 'PageWithInvalidCustomBanner', NS_MAIN, "NoBanner", "WikidataBanner" ],
			[ 'PageWithPageImageBanner', NS_MAIN, false, "PageImagesBanner" ],
			[ 'PageWithPageImageBanner', NS_MAIN, "NoBanner", "PageImagesBanner" ],
		];

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
		$this->setMwGlobals( 'wgWPBEnablePageImagesBanners', true );
		$this->addDBData();
	}

	/**
	 * @dataProvider provideTestDefaultBanner
	 * @covers WikidataPageBanner::onBeforePageDisplay
	 * @param string $title of page banner being generated on
	 * @param number $ns namespace of title
	 * @param string $customBanner parameter given to PAGEBANNER magic word
	 * @param string $expected the name of the banner we output.
	 */
	public function testDefaultBanner( $title, $ns, $customBanner, $expected ) {
		$out = $this->createPage( $title, $ns, $customBanner );
		// store a mock object in $wpbFunctionsClass static variable so that hooks call mock functions
		// through this variable when performing tests
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		$skin = $this->getMock( Skin::class );
		$skin->expects( $this->any() )->method( 'getSkinName' )
			->will( $this->returnValue( "vector" ) );
		WikidataPageBanner::onBeforePageDisplay( $out, $skin );
		$this->assertEquals( $expected, $out->getProperty( 'articlebanner-name' ),
			'articlebanner-name property must only be set when a valid banner is added' );
	}

	/**
	 * @covers WikidataPageBanner::addCustomBanner
	 */
	public function testCustomBanner() {
		$parser = $this->createParser( 'PageWithCustomBanner', NS_MAIN );
		// store a mock class name in $wpbFunctionsClass static variable so that hooks call mock
		// functions through this variable when performing tests
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		WikidataPageBanner::addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['name'], 'Banner',
			'banner parameters must be set on valid namespaces' );

		$parser = $this->createParser( 'PageInTalkNamespace', NS_TALK );
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		WikidataPageBanner::addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertNull( $bannerparams,
			'bannerparameters property should be null for not-allowed namespaces' );

		$parser = $this->createParser( 'NoWikidataBanner', NS_TALK );
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		WikidataPageBanner::addCustomBanner( $parser, 'NoBanner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertNull( $bannerparams,
			'bannerparameters property should be null for invalid Wikidata banner' );

		$this->setMwGlobals( 'wgWPBNamespaces', true );
		$parser = $this->createParser( 'PageWithCustomBanner', NS_TALK );
		WikidataPageBanner::$wpbFunctionsClass = "MockWikidataPageBannerFunctions";
		WikidataPageBanner::addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( $bannerparams['name'], 'Banner',
			'banner parameters must be set on valid namespaces' );
		$this->setMwGlobals( 'wgWPBNamespaces', [ 0 ] );
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
			$out->setProperty( 'wpb-banner-options', [ 'name' => $customBanner ] );
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
		$parser = $this->getMock( Parser::class );

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

	/**
	 * Data Provider for testDefaultBanner
	 */
	public function provideTestDefaultBanner() {
		return $this->testPagesForDefaultBanner;
	}

}
