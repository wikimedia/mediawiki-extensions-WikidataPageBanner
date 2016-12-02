<?php
/**
 * @group WikidataPageBanner
 */

/**
 * Test for checking compatibility with imagesDisabled option in MobileFrontendExtension
 */
class BannerMFTest extends MediaWikiTestCase {
	/**
	 * Stores the original value for MobileContext
	 * @var bool $oldDisableImages;
	 */
	private static $oldMobileContext = null;

	/**
	 * Add test pages to database
	 * @see MediaWikiTestCase::addDBData()
	 */
	public function addDBData() {
		try {
			if ( !Title::newFromText( 'PageWithoutCustomBanner', NS_MAIN )->exists() ) {
				$this->insertPage( 'PageWithoutCustomBanner', 'Some Text' );
			}
		} catch ( Exception $e ) {
		}
	}

	protected function setUp() {
		parent::setUp();
		if ( class_exists( 'MobileContext' ) ) {
			self::$oldMobileContext = MobileContext::singleton();
			$mobileContext = $this->makeContext();
			$mobileContext->setForceMobileView( true );
			MobileContext::setInstance( $mobileContext );
			// set protected disableImages property to true, so that we can simulate images disabled
			$reflectionClass = new ReflectionClass( 'MobileContext' );
			$reflectionProperty = $reflectionClass->getProperty( 'disableImages' );
			$reflectionProperty->setAccessible( true );
			$reflectionProperty->setValue( $mobileContext, true );
		}
		$this->addDBData();
		$this->setMwGlobals( 'wgWPBImage', "DefaultBanner" );
		$this->setMwGlobals( 'wgWPBEnableDefaultBanner', true );
	}

	protected function tearDown() {
		if ( class_exists( 'MobileContext' ) ) {
			// restore old mobile context class
			MobileContext::setInstance( self::$oldMobileContext );
		}
		parent::tearDown();
	}

	/**
	 * Test banner addition on disabling images in MobileFrontend
	 */
	public function testMFBannerWithImageDisabled() {
		if ( class_exists( 'MobileContext' ) ) {
			$skin = $this->getMock( "Skin" );
			$out = $this->createPage( 'PageWithoutCustomBanner', NS_MAIN );
			WikidataPageBanner::onBeforePageDisplay( $out, $skin );
			$this->assertNull( $out->getProperty( 'articlebanner-name' ) );
		}
	}

	/**
	 * Helper function for testDefaultBanner
	 * @param string $title
	 * @param int $namespace
	 * @return OutputPage
	 */
	protected function createPage( $title, $namespace ) {
		$context = new RequestContext();
		$curTitle = Title::newFromText( $title, $namespace );
		$context->setTitle( $curTitle );
		$out = $context->getOutput();
		$out->setTitle( $curTitle );
		$out->setPageTitle( $title );
		$out->setArticleFlag( true );
		return $out;
	}

	/**
	 * @param string $url
	 * @param array $cookies
	 * @return MobileContext
	 */
	private function makeContext( $url = '/', $cookies = [] ) {
		$request = new FauxRequest( [] );
		$request->setRequestURL( $url );
		$request->setCookies( $cookies, '' );

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setOutput( new OutputPage( $context ) );
		$instance = unserialize( 'O:13:"MobileContext":0:{}' );
		$instance->setContext( $context );
		return $instance;
	}
}
