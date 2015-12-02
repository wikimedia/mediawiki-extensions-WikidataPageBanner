<?php
/**
 * @group WikidataPageBanner
 */

/**
 * Test for checking compatibility with imagesDisabled option in MobileFrontendExtension
 */
class BannerMFTest extends MediaWikiTestCase {
	/**
	 * Stores the original value for disableImages cookie
	 * @var bool $oldDisableImages;
	 */
	private $oldDisableImages;

	/**
	 * Stores the original value for forcedMobileView
	 * @var bool $oldForceMobile;
	 */
	private $oldForceMobileView;

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
			$mobileContext = MobileContext::singleton();
			$this->oldDisableImages = $mobileContext->imagesDisabled();
			$this->oldForceMobileView = $mobileContext->getForceMobileView();
			$mobileContext->setDisableImagesCookie( true );
			$mobileContext->setForceMobileView( true );
		}
		$this->addDBData();
		$this->setMwGlobals( 'wgWPBImage', "DefaultBanner" );
		$this->setMwGlobals( 'wgWPBEnableDefaultBanner', true );
	}

	protected function tearDown() {
		if ( class_exists( 'MobileContext' ) ) {
			$mobileContext = MobileContext::singleton();
			$mobileContext->setDisableImagesCookie( $this->oldDisableImages );
			$mobileContext->setForceMobileView( $this->oldForceMobileView );
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
			WikidataPageBanner::addBanner( $out, $skin );
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
}
