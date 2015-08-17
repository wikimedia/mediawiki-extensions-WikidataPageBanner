<?php
class WikidataPageBanner {
	/**
	 * Singleton instance for helper class functions
	 * This variable holds the class name for helper functions and is used to make calls to those
	 * functions
	 * Note that this variable is also used by tests to store a mock classname of helper functions
	 * in it externally
	 * @var string Classname of WikidataPageBannerFunctions
	 */
	static $wpbFunctionsClass = "WikidataPageBannerFunctions";

	/**
	 * WikidataPageBanner::addBanner Generates banner from given options and adds it and its styles
	 * to Output Page. If no options defined through {{PAGEBANNER}}, tries to add a wikidata banner
	 * or a default one.
	 *
	 * @param OutputPage $out
	 * @return  bool
	 */
	public static function addBanner( OutputPage $out ) {
		global $wgWPBImage, $wgWPBNamespaces, $wgWPBEnableDefaultBanner;
		$title = $out->getTitle();
		// if banner-options are set, add banner anyway
		if ( $out->getProperty( 'wpb-banner-options' ) !== null ) {
			$params = $out->getProperty( 'wpb-banner-options' );
			$bannername = $params['name'];
			$out->enableOOUI();
			$wpbFunctionsClass = self::$wpbFunctionsClass;
			$banner = $wpbFunctionsClass::getBannerHtml( $bannername, $params );
			// attempt to get WikidataBanner
			if ( $banner === null ) {
				$bannername = $wpbFunctionsClass::getWikidataBanner( $title );
				$banner = $wpbFunctionsClass::getBannerHtml( $bannername, $params );
			}
			// only add banner and styling if valid banner generated
			if ( $banner !== null ) {
				$out->addModuleStyles( 'ext.WikidataPageBanner' );
				$out->addModules( 'ext.WikidataPageBanner.positionBanner' );
				if ( isset( $params['toc'] ) ) {
					$out->addModuleStyles( 'ext.WikidataPageBanner.toc.styles' );
				}
				$out->prependHtml( $banner );
				// hide primary title
				$out->setPageTitle( '' );
				$out->setHTMLTitle( $out->getTitle() );
				// set articlebanner property on OutputPage
				// FIXME: This is currently only needed to support testing
				$out->setProperty( 'articlebanner', $bannername );
			}
		}
		// if the page uses no 'PAGEBANNER' invocation and if article page, insert default banner
		elseif ( $title->isKnown() && $out->isArticle() && $wgWPBEnableDefaultBanner ) {
			$ns = $title->getNamespace();
			// banner only on specified namespaces, and not Main Page of wiki
			if ( in_array( $ns, $wgWPBNamespaces )
				&& !$title->isMainPage() ) {
				$wpbFunctionsClass = self::$wpbFunctionsClass;
				// first try to obtain bannername from Wikidata
				$bannername = $wpbFunctionsClass::getWikidataBanner( $title );
				if ( $bannername === null ) {
					// if Wikidata banner not found, set bannername to default banner
					$bannername = $wgWPBImage;
				}
				// add title to template parameters
				$paramsForBannerTemplate = array( 'title' => $title );
				$banner = $wpbFunctionsClass::
					getBannerHtml( $bannername, $paramsForBannerTemplate );
				// only add banner and styling if valid banner generated
				if ( $banner !== null ) {
					$out->addModuleStyles( 'ext.WikidataPageBanner' );
					$out->addModules( 'ext.WikidataPageBanner.positionBanner' );
					$out->prependHtml( $banner );
					// hide primary title
					$out->setPageTitle( '' );
					$out->setHTMLTitle( $out->getTitle() );
					// set articlebanner property on OutputPage
					// FIXME: This is currently only needed to support testing
					$out->setProperty( 'articlebanner', $bannername );
				}
			}
		}
		return true;
	}

	/**
	 * WikidataPageBanner::onOutputPageParserOutput add banner parameters from ParserOutput to
	 * Output page
	 *
	 * @param  OutputPage $out
	 * @param  ParserOutput $pOut
	 */
	public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $pOut ) {
		if ( $pOut->getProperty( 'wpb-banner-options' ) != null ) {
			$options = $pOut->getProperty( 'wpb-banner-options' );
			// if toc parameter set and toc enabled, remove original classes and add banner class
			if ( isset( $options['toc'] ) && $pOut->getTOCEnabled() ) {
				$options['toc'] = $pOut->getTOCHTML();
				// replace id and class of toc with blank
				// FIXME! This code is hacky, until core has better handling of toc contents
				// See https://phabricator.wikimedia.org/T105520
				if ( strpos( $options['toc'], 'id="toc"' ) !== false ) {
					$options['toc'] = str_replace( 'id="toc"', '', $options['toc'] );
				}
				if ( strpos( $options['toc'], 'class="toc"' ) !== false ) {
					$options['toc'] = str_replace( 'class="toc"', '', $options['toc'] );
				}
				// disable default TOC
				$out->enableTOC( false );
			}
			// set banner properties as an OutputPage property
			$out->setProperty( 'wpb-banner-options', $options );
		}
	}

	/**
	 * WikidataPageBanner::addCustomBanner
	 * Parser function hooked to 'PAGEBANNER' magic word, to define a custom banner and options to
	 * customize banner such as icons,horizontal TOC,etc. The method does not return any content but
	 * sets the banner parameters in ParserOutput object for use at a later stage to generate banner
	 *
	 * @param Parser $parser
	 * @param string $bannername Name of custom banner
	 */
	public static function addCustomBanner( Parser $parser, $bannername ) {
		global $wgWPBNamespaces;
		// @var array to hold parameters to be passed to banner template
		$paramsForBannerTemplate = array();
		// skip parser function name and bannername in arguments
		$argumentsFromParserFunction = array_slice( func_get_args(), 2 );
		// Convert $argumentsFromParserFunction into an associative array
		$wpbFunctionsClass = self::$wpbFunctionsClass;
		$argumentsFromParserFunction = $wpbFunctionsClass::
			extractOptions( $argumentsFromParserFunction );
		// if given banner does not exist, return
		$title = $parser->getTitle();
		$ns = $title->getNamespace();
		if ( in_array( $ns, $wgWPBNamespaces ) && !$title->isMainPage() ) {
			// set title and tooltip attribute to default title
			$paramsForBannerTemplate['tooltip'] = $title->getText();
			$paramsForBannerTemplate['title'] = $title->getText();
			if ( isset( $argumentsFromParserFunction['pgname'] ) ) {
				// set tooltip attribute to  parameter 'pgname', if set
				$paramsForBannerTemplate['tooltip'] = $argumentsFromParserFunction['pgname'];
				// set title attribute to 'pgname' if set
				$paramsForBannerTemplate['title'] = $argumentsFromParserFunction['pgname'];
			}
			// set tooltip attribute to  parameter 'tooltip', if set, which takes highest preference
			if ( isset( $argumentsFromParserFunction['tooltip'] ) ) {
				$paramsForBannerTemplate['tooltip'] = $argumentsFromParserFunction['tooltip'];
			}
			// set 'bottomtoc' parameter to allow TOC completely below the banner
			if ( isset( $argumentsFromParserFunction['bottomtoc'] ) &&
					$argumentsFromParserFunction['bottomtoc'] === 'yes' ) {
				$paramsForBannerTemplate['bottomtoc'] = true;
			}
			WikidataPageBannerFunctions::addToc( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
			WikidataPageBannerFunctions::addIcons( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
			WikidataPageBannerFunctions::addFocus( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
			$paramsForBannerTemplate['name'] = $bannername;
			// Set 'wpb-banner-options' property for generating banner later
			$parser->getOutput()->setProperty( 'wpb-banner-options', $paramsForBannerTemplate );

			// add the valid banner to image links
			// @FIXME:Since bannernames which are to be added are generated here, getBannerHtml can
			// be cleaned to only accept a valid title object pointing to a banner file
			// Default banner is not added to imagelinks as that is the property of this extension
			// and is uniform across all pages
			$wikidataBanner = $wpbFunctionsClass::getWikidataBanner( $title );
			$bannerTitle = null;
			if ( $wpbFunctionsClass::getImageUrl( $paramsForBannerTemplate['name'] ) !== null ) {
				$bannerTitle = Title::makeTitleSafe( NS_FILE, $paramsForBannerTemplate['name'] );
			} elseif ( $wpbFunctionsClass::getImageUrl( $wikidataBanner ) !== null ) {
				$bannerTitle = Title::makeTitleSafe( NS_FILE, $wikidataBanner );
			}
			if ( $bannerTitle !== null ) {
				$parser->fetchFileAndTitle( $bannerTitle );
			}
		}
	}

	/**
	 * WikidataPageBanner::onParserFirstCallInit
	 * Hooks the parser function addCustomBanner to the magic word 'PAGEBANNER'
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'PAGEBANNER', 'WikidataPageBanner::addCustomBanner', SFH_NO_HASH );
		return true;
	}

	/*
	 * UnitTestsList hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param array $files
	 * @return bool
	 */
	public static function onUnitTestsList( &$files ) {
		// traverse test/phpunit/ directory and add test files
		$it = new RecursiveDirectoryIterator( __DIR__ . '/../tests/phpunit' );
		$it = new RecursiveIteratorIterator( $it );
		foreach ( $it as $path => $file ) {
			if ( substr( $path, -8 ) === 'Test.php' ) {
				$files[] = $path;
			}
		}
		return true;
	}

	/**
	 * Register QUnit tests.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @param array $files
	 * @return bool
	 */
	public static function onResourceLoaderTestModules( &$modules, &$rl ) {
		$boilerplate = array(
			'localBasePath' => __DIR__ . '/../tests/qunit/',
			'remoteExtPath' => 'WikidataPageBanner/tests/qunit',
			'targets' => array( 'desktop', 'mobile' ),
		);

		$modules['qunit']['ext.WikidataPageBanner.positionBanner.test'] = $boilerplate + array(
			'scripts' => array(
				'ext.WikidataPageBanner.positionBanner/test_ext.WikidataPageBanner.positionBanner.js',
			),
			'dependencies' => array( 'ext.WikidataPageBanner.positionBanner' ),
		);
	}
}
