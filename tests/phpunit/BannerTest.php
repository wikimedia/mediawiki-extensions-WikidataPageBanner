<?php

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\WikidataPageBanner\Hooks;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Skin\Skin;
use MediaWiki\Title\Title;

/**
 * @group WikidataPageBanner
 * @group Database
 */
class BannerTest extends MediaWikiIntegrationTestCase {

	/**
	 * Set of pages.
	 * array follows the pattern:
	 * array( 0 => TestPageName, 1 => Namespace, 2 => customBannerValue, 3 => expected articlebanner
	 * property
	 */
	private const TEST_PAGES_FOR_DEFAULT_BANNER = [
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
	 * @see MediaWikiIntegrationTestCase::addDBData()
	 */
	public function addDBData() {
		try {
			foreach ( self::TEST_PAGES_FOR_DEFAULT_BANNER as $page ) {
				if ( !Title::newFromText( $page[0], $page[1] )->exists() ) {
					$this->insertPage( $page[0], 'Some Text' );
				}
			}
		} catch ( Exception $e ) {
		}
	}

	private function newHooks() {
		return new Hooks(
			$this->getServiceContainer()->getLanguageConverterFactory()
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValues( [
			'WPBImage' => 'DefaultBanner',
			'WPBEnableDefaultBanner' => true,
			'WPBEnablePageImagesBanners' => true,
		] );
		$this->addDBData();
	}

	/**
	 * @dataProvider provideTestDefaultBanner
	 * @covers \MediaWiki\Extension\WikidataPageBanner\Hooks::onBeforePageDisplay
	 * @param string $title of page banner being generated on
	 * @param number $ns namespace of title
	 * @param string $customBanner parameter given to PAGEBANNER magic word
	 * @param string $expected the name of the banner we output.
	 */
	public function testDefaultBanner( $title, $ns, $customBanner, $expected ) {
		$out = $this->createPage( $title, $ns, $customBanner );
		// store a mock object in $wpbFunctionsClass static variable so that hooks call mock functions
		// through this variable when performing tests
		Hooks::$wpbBannerClass = MockBanner::class;
		$skin = $this->createMock( Skin::class );
		$skin->expects( $this->any() )->method( 'getSkinName' )
			->willReturn( "vector" );
		$wikidataPageBanner = $this->newHooks();
		$wikidataPageBanner->onBeforePageDisplay( $out, $skin );
		$this->assertEquals( $expected, $out->getProperty( 'articlebanner-name' ),
			'articlebanner-name property must only be set when a valid banner is added' );
	}

	/**
	 * @covers \MediaWiki\Extension\WikidataPageBanner\Hooks::addCustomBanner
	 */
	public function testCustomBanner() {
		$parser = $this->createParser( 'PageWithCustomBanner', NS_MAIN );
		// store a mock class name in $wpbFunctionsClass static variable so that hooks call mock
		// functions through this variable when performing tests
		Hooks::$wpbBannerClass = "MockBanner";
		$wikidataPageBanner = $this->newHooks();
		$wikidataPageBanner->addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( 'Banner', $bannerparams['name'],
			'banner parameters must be set on valid namespaces' );

		$parser = $this->createParser( 'PageInTalkNamespace', NS_TALK );
		Hooks::$wpbBannerClass = MockBanner::class;
		$wikidataPageBanner->addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertNull( $bannerparams,
			'bannerparameters property should be null for not-allowed namespaces' );

		$parser = $this->createParser( 'NoWikidataBanner', NS_TALK );
		Hooks::$wpbBannerClass = MockBanner::class;
		$wikidataPageBanner->addCustomBanner( $parser, 'NoBanner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertNull( $bannerparams,
			'bannerparameters property should be null for invalid Wikidata banner' );

		$this->overrideConfigValue( 'WPBNamespaces', true );
		$parser = $this->createParser( 'PageWithCustomBanner', NS_TALK );
		Hooks::$wpbBannerClass = MockBanner::class;
		$wikidataPageBanner->addCustomBanner( $parser, 'Banner' );
		$pOut = $parser->getOutput();
		$bannerparams = $pOut->getExtensionData( 'wpb-banner-options' );
		$this->assertEquals( 'Banner', $bannerparams['name'],
			'banner parameters must be set on valid namespaces' );
		$this->overrideConfigValue( 'WPBNamespaces', [ 0 ] );

		// Test $wgWPBEnableMainPage.
		$parser = $this->createParser( 'Main_Page', NS_MAIN );
		Hooks::$wpbBannerClass = MockBanner::class;
		// Not enabled.
		$this->overrideConfigValue( 'WPBEnableMainPage', false );
		$wikidataPageBanner->addCustomBanner( $parser, 'Banner' );
		$bannerparams = $parser->getOutput()->getExtensionData( 'wpb-banner-options' );
		$this->assertNull( $bannerparams, 'bannerparams must not be set on the Main Page' );
		// Enabled.
		$this->overrideConfigValue( 'WPBEnableMainPage', true );
		$wikidataPageBanner->addCustomBanner( $parser, 'Banner' );
		$bannerparams = $parser->getOutput()->getExtensionData( 'wpb-banner-options' );
		$this->assertIsArray( $bannerparams, 'bannerparams is set on the Main Page' );
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
		$parser = $this->createMock( Parser::class );

		$parserOutput = new ParserOutput();
		$parser->expects( $this->any() )->method( 'getOutput' )
			->willReturn( $parserOutput );

		$curTitle = Title::newFromText( $title, $namespace );
		$parser->expects( $this->any() )->method( 'getTitle' )
			->willReturn( $curTitle );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$parser->expects( $this->any() )->method( 'getTargetLanguage' )
			->willReturn( $language );

		return $parser;
	}

	/**
	 * Data Provider for testDefaultBanner
	 */
	public static function provideTestDefaultBanner() {
		return self::TEST_PAGES_FOR_DEFAULT_BANNER;
	}

}
