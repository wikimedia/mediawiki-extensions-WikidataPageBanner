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
	public static $wpbFunctionsClass = "WikidataPageBannerFunctions";

	/**
	 * Holds an array of valid parameters for PAGEBANNER hook.
	 */
	private static $allowedParameters = array(
		'pgname',
		'tooltip',
		'toc',
		'bottomtoc',
		'origin',
		'icon-*',
		'extraClass'
	);

	/**
	 * Expands icons for rendering via template
	 *
	 * @param array[] $icons of options for IconWidget
	 * @return array[]
	 */
	protected static function expandIconTemplateOptions( array $icons ) {
		foreach ( $icons as $key => $iconData ) {
			$widget = new OOUI\IconWidget( $iconData );
			$iconData['html'] = $widget->toString();
			$icons[$key] = $iconData;
		}

		return $icons;
	}

	/**
	 * GetSkinTemplateOutputPageBeforeExec
	 * Modifies the template to add the banner html for rendering by the skin. Note not
	 * all skins render the prebodyhtml template variable so in some skins this will have no impact
	 * whatsoever.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateOutputPageBeforeExec
	 *
	 * @param Skin &$skin
	 * @param SkinTemplate &$tpl
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$tpl ) {
		$config = WikidataPageBannerFunctions::getWPBConfig();
		$blacklist = $config->get( 'WPBSkinBlacklist' );

		$banner = $skin->getOutput()->getProperty( 'articlebanner' );
		if ( !in_array( $skin->getSkinName(), $blacklist ) ) {
			$tpl->set( 'prebodyhtml', $banner . $tpl->get( 'prebodyhtml', '' ) );
		}

		return true;
	}

	/**
	 * WikidataPageBanner::addBanner Generates banner from given options and adds it and its styles
	 * to Output Page. If no options defined through {{PAGEBANNER}}, tries to add a wikidata banner
	 * or a default one.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin Skin object being rendered
	 * @return  bool
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		// if images are disabled on Minerva skin, then do nothing
		if ( class_exists( 'MobileContext' )
				&& MobileContext::singleton()->shouldDisplayMobileView()
				&& MobileContext::singleton()->imagesDisabled() === true
			) {
			return true;
		}
		$config = WikidataPageBannerFunctions::getWPBConfig();
		$title = $out->getTitle();
		$isDiff = $out->getRequest()->getVal( 'diff' );
		$wpbFunctionsClass = self::$wpbFunctionsClass;

		// if banner-options are set and not a diff page, add banner anyway
		if ( $out->getProperty( 'wpb-banner-options' ) !== null && !$isDiff ) {
			$params = $out->getProperty( 'wpb-banner-options' );
			$bannername = $params['name'];
			if ( isset( $params['icons'] ) ){
				$out->enableOOUI();
				$params['icons'] = self::expandIconTemplateOptions( $params['icons'] );
			}
			$banner = $wpbFunctionsClass::getBannerHtml( $bannername, $params );
			// attempt to get WikidataBanner
			if ( $banner === null ) {
				$bannername = $wpbFunctionsClass::getWikidataBanner( $title );
				$banner = $wpbFunctionsClass::getBannerHtml( $bannername, $params );
			}
			// only add banner and styling if valid banner generated
			if ( $banner !== null ) {
				if ( isset( $params['toc'] ) ) {
					$out->addModuleStyles( 'ext.WikidataPageBanner.toc.styles' );
				}
				$wpbFunctionsClass::insertBannerIntoOutputPage( $out, $banner );

				// FIXME: This is currently only needed to support testing
				$out->setProperty( 'articlebanner-name', $bannername );
			}
		} elseif (
			$title->isKnown() &&
			$out->isArticle() &&
			$config->get( 'WPBEnableDefaultBanner' ) &&
			!$isDiff
		) {
			// if the page uses no 'PAGEBANNER' invocation and if article page, insert default banner
			$ns = $title->getNamespace();
			// banner only on specified namespaces (but all if true), and not Main Page of wiki
			if ( $wpbFunctionsClass::validateNamespace( $ns ) && !$title->isMainPage() ) {
				// first try to obtain bannername from Wikidata
				$bannername = $wpbFunctionsClass::getWikidataBanner( $title );
				if ( $bannername === null ) {
					// if Wikidata banner not found, set bannername to default banner
					$bannername = $config->get( 'WPBImage' );
				}
				// add title to template parameters
				$paramsForBannerTemplate = array( 'title' => $title );
				$banner = $wpbFunctionsClass::
					getBannerHtml( $bannername, $paramsForBannerTemplate );
				// only add banner and styling if valid banner generated
				if ( $banner !== null ) {
					$wpbFunctionsClass::insertBannerIntoOutputPage( $out, $banner );

					// set articlebanner property on OutputPage
					// FIXME: This is currently only needed to support testing
					$out->setProperty( 'articlebanner-name', $bannername );
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
		if ( $pOut->getExtensionData( 'wpb-banner-options' ) != null ) {
			$options = $pOut->getExtensionData( 'wpb-banner-options' );

			// if toc parameter set and toc enabled, remove original classes and add banner class
			if ( isset( $options['enable-toc'] ) && $pOut->getTOCEnabled() ) {
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
	 * Validates a given array of parameters against a set of allowed parameters and adds a
	 * warning message with a list of unknown parameters and a tracking category, if there are any.
	 *
	 * @param array $args Array of parameters to check
	 * @param Parser $parser ParserOutput object to add the warning message
	 * @return Status A Status object, if there are invalid/unknown parameters,
	 *	they will be added as warnings.
	 */
	public static function addBadParserFunctionArgsWarning( array $args, Parser $parser ) {
		global $wgLang;

		$badParams = array();
		$allowedParams = array_flip( self::$allowedParameters );
		foreach ( $args as $param => $value ) {
			// manually check for icons, they can have any name with the "icon-" prefix
			if ( !isset( $allowedParams[$param] ) && substr( $param, 0, 5 ) !== 'icon-' ) {
				$badParams[] = $param;
			}
		}

		if ( $badParams ) {
			// if there are unknown parameters, add a tracking category
			$parser->addTrackingCategory( 'wikidatapagebanner-invalid-arguments-cat' );

			// this message will be visible when the page preview button is used, but not when the page is
			// saved. It contains a list of unknown parameters.
			$parser->getOutput()->addWarning(
				wfMessage( 'wikidatapagebanner-invalid-arguments', $wgLang->commaList( $badParams ) )
					->title( $parser->getTitle() )
					->inContentLanguage()
					->text()
			);
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
		$config = $wpbFunctionsClass::getWPBConfig();

		if ( $wpbFunctionsClass::validateNamespace( $ns ) && !$title->isMainPage() ) {
			// check for unknown parameters used in the parser hook and add a warning if there is any
			self::addBadParserFunctionArgsWarning( $argumentsFromParserFunction, $parser );

			// set title and tooltip attribute to default title
			// convert title to preferred language variant as done in core Parser.php
			$paramsForBannerTemplate['tooltip'] = $parser->getConverterLanguage()
				->convert( $title->getText() );
			$paramsForBannerTemplate['title'] = $parser->getConverterLanguage()
				->convert( $title->getText() );
			if ( isset( $argumentsFromParserFunction['pgname'] ) ) {
				// set tooltip attribute to  parameter 'pgname', if set
				$paramsForBannerTemplate['tooltip'] = $argumentsFromParserFunction['pgname'];
				// set title attribute to 'pgname' if set
				$paramsForBannerTemplate['title'] = $argumentsFromParserFunction['pgname'];
			}
			// set extra CSS classes added with extraClass attribute
			$wpbFunctionsClass::addCssClasses( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
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
			// add custom or wikidata banner properties to page_props table if a valid banner exists
			// in, checking for custom banner first, then wikidata banner
			if ( $bannerTitle !== null ) {
				$parser->getOutput()->setExtensionData( 'wpb-banner-options', $paramsForBannerTemplate );
				$parser->fetchFileAndTitle( $bannerTitle );
				$parser->getOutput()->setProperty( 'wpb_banner', $bannerTitle->getText() );
				$parser->getOutput()->setProperty( 'wpb_banner_focus_x',
						$paramsForBannerTemplate['data-pos-x'] );
				$parser->getOutput()->setProperty( 'wpb_banner_focus_y',
						$paramsForBannerTemplate['data-pos-y'] );
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
	 * @param array[] &$modules
	 * @param ResourceLoader &$rl
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
