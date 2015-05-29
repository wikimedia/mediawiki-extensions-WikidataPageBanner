<?php
/**
 * @group WikidataPageBanner
 */

/**
 * Mock class for WikidataPageBanner
 */
class MockWikidataPageBanner extends WikidataPageBanner {
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
		return "Banner";
	}
}

class BannerTest extends MediaWikiTestCase {
	protected $exceptionFromAddDBData;
	/**
	 * Set of pages.
	 * array follows the pattern:
	 * array( 0 => TestPageName, 1 => Namespace, 2 => isCustomBanner, 3 => expected articlebanner
	 * property
	 */
	protected $testPagesForDefaultBanner = array(
			array( 'PageWithoutCustomBanner', NS_MAIN, false, "Banner" ),
			array( 'PageWithCustomBanner', NS_MAIN, true, "Banner" ),
			array( 'PageInTalkNamespace', NS_TALK, false, null ),
			array( 'NoWikidataBanner', NS_MAIN, false, "Banner" )
		);
	/**
	 * Set of pages.
	 * array follows the pattern:
	 * array( 0 => TestPageName, 1 => Namespace, 2 => expected output of parser function
	 */
	protected $testPagesForCustomBanner = array(
			array( 'PageWithCustomBanner', NS_MAIN, "Banner" ),
			array( 'PageInTalkNamespace', NS_TALK, '' ),
			array( 'NoBanner', NS_MAIN, '' )
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
	 * @dataProvider provideTestDefaultBanner
	 * @covers addDefaultBanner(...)
	 */
	public function testDefaultBanner( $title, $ns, $isCustomBanner, $expected ) {
		$article = $this->createPage( $title, $ns, $isCustomBanner );
		MockWikidataPageBanner::addDefaultBanner( $article );
		$out = $article->getContext()->getOutput();
		$this->assertEquals( $out->getProperty( 'articlebanner' ), $expected,
			'articlebanner property must only be set when a valid banner is added' );
	}

	/**
	 * @dataProvider provideTestCustomBanner
	 * @covers addCustomBanner(...)
	 */
	public function testCustomBanner( $title, $ns, $expected ) {
		$parser = $this->createParser( $title, $ns );
		$output = MockWikidataPageBanner::addCustomBanner( $parser, $title );
		$this->assertEquals( $output[0], $expected,
			'articlebanner property must only be set when a valid banner is added' );
	}

	/**
	 * Helper function for testDefaultBanner
	 * @return  Article Article object representing test pages
	 */
	protected function createPage( $title, $namespace, $isCustomBanner ) {
		$context = new RequestContext();
		$curTitle = Title::newFromText( $title, $namespace );
		$context->setTitle( $curTitle );
		$article = Article::newFromTitle( $curTitle, $context );
		$parserOutput = new ParserOutput();
		$article->mParserOutput = $parserOutput;
		if ( $isCustomBanner ) {
			$parserOutput->setProperty( 'articlebanner', "Banner" );
		}
		return $article;
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

	/**
	 * Data Provider for testDefaultBanner
	 */
	public function provideTestDefaultBanner() {
		return $this->testPagesForDefaultBanner;
	}

	/**
	 * Data Provider for testDefaultBanner
	 */
	public function provideTestCustomBanner() {
		return $this->testPagesForCustomBanner;
	}
}
