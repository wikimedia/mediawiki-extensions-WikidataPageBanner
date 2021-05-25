<?php

use MediaWiki\MediaWikiServices;

class WikidataPageBanner {

	/**
	 * Singleton instance for helper class functions
	 * This variable holds the class name for helper functions and is used to make calls to those
	 * functions
	 * Note that this variable is also used by tests to store a mock classname of helper functions
	 * in it externally
	 * @var string
	 */
	public static $wpbFunctionsClass = "WikidataPageBannerFunctions";

	/**
	 * Holds an array of valid parameters for PAGEBANNER hook.
	 */
	private static $allowedParameters = [
		'pgname',
		'tooltip',
		'toc',
		'bottomtoc',
		'origin',
		'icon-*',
		'extraClass',
		'link',
	];

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
	 * Checks if the skin should output the wikidata banner before the
	 * site subtitle, in which case it should use the sitenotice container.
	 * @param Skin $skin
	 * @return bool
	 */
	private static function isSiteNoticeSkin( $skin ) {
		$currentSkin = $skin->getSkinName();
		$skins = $skin->getConfig()->get( 'WPBDisplaySubtitleAfterBannerSkins' );
		return array_search( $currentSkin, $skins ) !== false;
	}

	/**
	 * Determine whether a banner should be shown on the given page.
	 * @param Title $title
	 * @return bool
	 */
	private static function isBannerPermitted( Title $title ) {
		$config = WikidataPageBannerFunctions::getWPBConfig();
		$ns = $title->getNamespace();
		$enabledMainPage = $title->isMainPage() ? $config->get( 'WPBEnableMainPage' ) : true;
		return self::$wpbFunctionsClass::validateNamespace( $ns ) && $enabledMainPage;
	}

	/**
	 * Modifies the template to add the banner html for rendering by the skin to the subtitle
	 * if a banner exists and the skin is configured via WPBDisplaySubtitleAfterBannerSkins;
	 * Any existing subtitle is made part of the banner and the subtitle is reset.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBeforeHTML
	 *
	 * @param OutputPage $out
	 * @return bool inidicating whether it was added or not
	 */
	public static function addBannerToSkinOutput( $out ) {
		$skin = $out->getSkin();
		$isSkinDisabled = self::$wpbFunctionsClass::isSkinDisabled( $skin );

		// If the skin is using SiteNoticeAfter abort.
		if ( $isSkinDisabled || self::isSiteNoticeSkin( $skin ) ) {
			return false;
		}
		$banner = $out->getProperty( 'articlebanner' );
		if ( $banner ) {
			// Insert banner
			$out->addSubtitle( $banner );
		}

		return true;
	}

	/**
	 * Add banner to skins which output banners into the site notice area.
	 * @param string|bool &$siteNotice of the page.
	 * @param Skin $skin being used.
	 */
	public static function onSiteNoticeAfter( &$siteNotice, Skin $skin ) {
		if ( !self::$wpbFunctionsClass::isSkinDisabled( $skin ) &&
			self::isSiteNoticeSkin( $skin )
		) {
			$out = $skin->getOutput();
			$banner = $out->getProperty( 'articlebanner' );

			if ( $siteNotice ) {
				$siteNotice .= $banner;
			} else {
				$siteNotice = $banner;
			}
			return;
		}
	}

	/**
	 * WikidataPageBanner::addBanner Generates banner from given options and adds it and its styles
	 * to Output Page. If no options defined through {{PAGEBANNER}}, tries to add a wikidata banner
	 * or an image as defined by the PageImages extension or a default one
	 * dependent on how extension is configured.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin Skin object being rendered
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$config = WikidataPageBannerFunctions::getWPBConfig();
		$title = $out->getTitle();
		$isDiff = $out->getRequest()->getCheck( 'diff' );
		$wpbFunctionsClass = self::$wpbFunctionsClass;

		// if banner-options are set and not a diff page, add banner anyway
		if ( $out->getProperty( 'wpb-banner-options' ) !== null && !$isDiff ) {
			$params = $out->getProperty( 'wpb-banner-options' );
			$bannername = $params['name'];
			if ( isset( $params['icons'] ) ) {
				$out->enableOOUI();
				$params['icons'] = self::expandIconTemplateOptions( $params['icons'] );
			}
			$banner = $wpbFunctionsClass::getBannerHtml( $bannername, $params );
			// attempt to get an automatic banner
			if ( $banner === null ) {
				$params['isAutomatic'] = true;
				$bannername = $wpbFunctionsClass::getAutomaticBanner( $title );
				$banner = $wpbFunctionsClass::getBannerHtml( $bannername, $params );
			}
			// only add banner and styling if valid banner generated
			if ( $banner !== null ) {
				if ( isset( $params['toc'] ) ) {
					$out->addModuleStyles( 'ext.WikidataPageBanner.toc.styles' );
				}
				$wpbFunctionsClass::setOutputPageProperties( $out, $banner );

				// FIXME: This is currently only needed to support testing
				$out->setProperty( 'articlebanner-name', $bannername );
			}
		} elseif (
			$title->isKnown() &&
			$out->isArticle() &&
			self::isBannerPermitted( $title ) &&
			$config->get( 'WPBEnableDefaultBanner' ) &&
			!$isDiff
		) {
			// if the page uses no 'PAGEBANNER' invocation and if article page, insert default banner
			// first try to obtain bannername from Wikidata
			$bannername = $wpbFunctionsClass::getAutomaticBanner( $title );
			// add title and whether the banner is auto generated to template parameters
			$paramsForBannerTemplate = [ 'title' => $title, 'isAutomatic' => true ];
			$banner = $wpbFunctionsClass::getBannerHtml( $bannername, $paramsForBannerTemplate );
			// only add banner and styling if valid banner generated
			if ( $banner !== null ) {
				$wpbFunctionsClass::setOutputPageProperties( $out, $banner );

				// set articlebanner property on OutputPage
				// FIXME: This is currently only needed to support testing
				$out->setProperty( 'articlebanner-name', $bannername );
			}
		}
		self::addBannerToSkinOutput( $out );

		return true;
	}

	/**
	 * WikidataPageBanner::onOutputPageParserOutput add banner parameters from ParserOutput to
	 * Output page
	 *
	 * @param OutputPage $out
	 * @param ParserOutput $pOut
	 */
	public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $pOut ) {
		if ( $pOut->getExtensionData( 'wpb-banner-options' ) !== null ) {
			$options = $pOut->getExtensionData( 'wpb-banner-options' );

			// if toc parameter set, then remove original classes and add banner class
			if ( isset( $options['enable-toc'] ) ) {
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
				// Remove default TOC
				$pOut->setText( preg_replace(
					'#' . preg_quote( Parser::TOC_START, '#' ) . '.*?' . preg_quote( Parser::TOC_END, '#' ) . '#s',
					'',
					$pOut->getRawText()
				) );
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
	 */
	public static function addBadParserFunctionArgsWarning( array $args, Parser $parser ) {
		$badParams = [];
		$allowedParams = array_flip( self::$allowedParameters );
		foreach ( $args as $param => $value ) {
			// manually check for icons, they can have any name with the "icon-" prefix
			if ( !isset( $allowedParams[$param] ) && substr( $param, 0, 5 ) !== 'icon-' ) {
				$badParams[] = $param;
			}
		}

		if ( $badParams ) {
			$contLang = MediaWikiServices::getInstance()->getContentLanguage();
			// if there are unknown parameters, add a tracking category
			$parser->addTrackingCategory( 'wikidatapagebanner-invalid-arguments-cat' );

			// this message will be visible when the page preview button is used, but not when the page is
			// saved. It contains a list of unknown parameters.
			$parser->getOutput()->addWarning(
				wfMessage( 'wikidatapagebanner-invalid-arguments', $contLang->commaList( $badParams ) )
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
	 * @param string ...$args
	 */
	public static function addCustomBanner( Parser $parser, $bannername, ...$args ) {
		// @var array to hold parameters to be passed to banner template
		$paramsForBannerTemplate = [];
		// Convert $argumentsFromParserFunction into an associative array
		$wpbFunctionsClass = self::$wpbFunctionsClass;
		$argumentsFromParserFunction = $wpbFunctionsClass::extractOptions( $parser, $args );
		// if given banner does not exist, return
		$title = $parser->getTitle();

		if ( self::isBannerPermitted( $title ) ) {
			// check for unknown parameters used in the parser hook and add a warning if there is any
			self::addBadParserFunctionArgsWarning( $argumentsFromParserFunction, $parser );

			// set title and tooltip attribute to default title
			// convert title to preferred language variant as done in core Parser.php
			$langConv = MediaWikiServices::getInstance()->getLanguageConverterFactory()
				->getLanguageConverter( $parser->getTargetLanguage() );
			$paramsForBannerTemplate['tooltip'] = $langConv->convert( $title->getText() );
			$paramsForBannerTemplate['title'] = $langConv->convert( $title->getText() );
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
			// Add link attribute, to change the target of the banner link.
			if ( isset( $argumentsFromParserFunction['link'] ) ) {
				$paramsForBannerTemplate['link'] = $argumentsFromParserFunction['link'];
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
			$bannerTitle = null;
			if ( $wpbFunctionsClass::getImageUrl( $paramsForBannerTemplate['name'] ) !== null ) {
				$bannerTitle = Title::makeTitleSafe( NS_FILE, $paramsForBannerTemplate['name'] );
			} else {
				$fallbackBanner = $wpbFunctionsClass::getAutomaticBanner( $title );
				if ( $wpbFunctionsClass::getImageUrl( $fallbackBanner ) !== null ) {
					$bannerTitle = Title::makeTitleSafe( NS_FILE, $fallbackBanner );
				}
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
		$parser->setFunctionHook(
			'PAGEBANNER', 'WikidataPageBanner::addCustomBanner', Parser::SFH_NO_HASH
		);
		return true;
	}

}
