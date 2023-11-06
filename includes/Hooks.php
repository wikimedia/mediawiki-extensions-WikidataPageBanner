<?php

namespace MediaWiki\Extension\WikidataPageBanner;

use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\SiteNoticeAfterHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\ParserOutputFlags;
use MediaWiki\Title\Title;
use Message;
use OOUI\IconWidget;
use Parser;
use Skin;

/**
 * This class implements the hookhandlers for WikidataPageBanner
 *
 * TODO: It also currently handles a lot of the actual construction of the Banner.
 * and this should be moved into Banner, WikidataBanner, BannerFactory classes
 */
class Hooks implements
	BeforePageDisplayHook,
	OutputPageParserOutputHook,
	ParserFirstCallInitHook,
	SiteNoticeAfterHook
{

	/**
	 * Singleton instance for helper class functions
	 * This variable holds the class name for helper functions and is used to make calls to those
	 * functions
	 * Note that this variable is also used by tests to store a mock classname of helper functions
	 * in it externally
	 * @var string
	 */
	public static $wpbBannerClass = Banner::class;

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

	public function __construct() {
	}

	/**
	 * Expands icons for rendering via template
	 *
	 * @param array[] $icons of options for IconWidget
	 * @return array[]
	 */
	protected function expandIconTemplateOptions( array $icons ) {
		foreach ( $icons as $key => $iconData ) {
			$widget = new IconWidget( $iconData );
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
	private function isSiteNoticeSkin( Skin $skin ) {
		$currentSkin = $skin->getSkinName();
		$skins = $skin->getConfig()->get( 'WPBDisplaySubtitleAfterBannerSkins' );
		return in_array( $currentSkin, $skins );
	}

	/**
	 * Determine whether a banner should be shown on the given page.
	 * @param Title $title
	 * @return bool
	 */
	private function isBannerPermitted( Title $title ) {
		$config = Banner::getWPBConfig();
		$ns = $title->getNamespace();
		$enabledMainPage = $title->isMainPage() ? $config->get( 'WPBEnableMainPage' ) : true;
		return self::$wpbBannerClass::validateNamespace( $ns ) && $enabledMainPage;
	}

	/**
	 * Modifies the template to add the banner html for rendering by the skin to the subtitle
	 * if a banner exists and the skin is configured via WPBDisplaySubtitleAfterBannerSkins;
	 * Any existing subtitle is made part of the banner and the subtitle is reset.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @param OutputPage $out
	 * @return bool indicating whether it was added or not
	 */
	public function addBannerToSkinOutput( OutputPage $out ) {
		$skin = $out->getSkin();
		$isSkinDisabled = self::$wpbBannerClass::isSkinDisabled( $skin );

		// If the skin is using SiteNoticeAfter abort.
		if ( $isSkinDisabled || $this->isSiteNoticeSkin( $skin ) ) {
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
	public function onSiteNoticeAfter( &$siteNotice, $skin ) {
		if ( !self::$wpbBannerClass::isSkinDisabled( $skin ) &&
			$this->isSiteNoticeSkin( $skin )
		) {
			$out = $skin->getOutput();
			$banner = $out->getProperty( 'articlebanner' );

			if ( $siteNotice ) {
				$siteNotice .= $banner;
			} else {
				$siteNotice = $banner;
			}
		}
	}

	/**
	 * Hooks::addBanner Generates banner from given options and adds it and its styles
	 * to Output Page. If no options defined through {{PAGEBANNER}}, tries to add a wikidata banner
	 * or an image as defined by the PageImages extension or a default one
	 * dependent on how extension is configured.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin Skin object being rendered
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$config = Banner::getWPBConfig();
		$title = $out->getTitle();
		$isDiff = $out->getRequest()->getCheck( 'diff' );
		$wpbBannerClass = self::$wpbBannerClass;

		// if banner-options are set and not a diff page, add banner anyway
		if ( $out->getProperty( 'wpb-banner-options' ) !== null && !$isDiff ) {
			$params = $out->getProperty( 'wpb-banner-options' );
			$bannername = $params['name'];
			if ( isset( $params['icons'] ) ) {
				$out->enableOOUI();
				$params['icons'] = $this->expandIconTemplateOptions( $params['icons'] );
			}
			$banner = $wpbBannerClass::getBannerHtml( $bannername, $params );
			// attempt to get an automatic banner
			if ( $banner === null ) {
				$params['isAutomatic'] = true;
				$bannername = $wpbBannerClass::getAutomaticBanner( $title );
				$banner = $wpbBannerClass::getBannerHtml( $bannername, $params );
			}
			// only add banner and styling if valid banner generated
			if ( $banner !== null ) {
				if ( isset( $params['data-toc'] ) ) {
					$out->addModuleStyles( 'ext.WikidataPageBanner.toc.styles' );
				}
				$wpbBannerClass::setOutputPageProperties( $out, $banner );

				// FIXME: This is currently only needed to support testing
				$out->setProperty( 'articlebanner-name', $bannername );
			}
		} elseif (
			$title->isKnown() &&
			$out->isArticle() &&
			$this->isBannerPermitted( $title ) &&
			$config->get( 'WPBEnableDefaultBanner' ) &&
			!$isDiff
		) {
			// if the page uses no 'PAGEBANNER' invocation and if article page, insert default banner
			// first try to obtain bannername from Wikidata
			$bannername = $wpbBannerClass::getAutomaticBanner( $title );
			// add title and whether the banner is auto generated to template parameters
			$paramsForBannerTemplate = [ 'title' => $title, 'isAutomatic' => true ];
			$banner = $wpbBannerClass::getBannerHtml( $bannername, $paramsForBannerTemplate );
			// only add banner and styling if valid banner generated
			if ( $banner !== null ) {
				$wpbBannerClass::setOutputPageProperties( $out, $banner );

				// set articlebanner property on OutputPage
				// FIXME: This is currently only needed to support testing
				$out->setProperty( 'articlebanner-name', $bannername );
			}
		}
		$this->addBannerToSkinOutput( $out );
	}

	/**
	 * @param ParserOutput $parserOutput
	 * @return array|null
	 */
	private function getBannerOptions( ParserOutput $parserOutput ) {
		return $parserOutput->getExtensionData( 'wpb-banner-options' );
	}

	/**
	 * Nests child sections within their parent sections.
	 * Based on code in SkinComponentTableOfContents.
	 *
	 * @param array $sections
	 * @param int $toclevel
	 * @return array
	 */
	private function getSectionsDataInternal( array $sections, int $toclevel = 1 ): array {
		$data = [];
		foreach ( $sections as $i => $section ) {
			// Child section belongs to a higher parent.
			if ( $section->tocLevel < $toclevel ) {
				return $data;
			}

			// Set all the parent sections at the current top level.
			if ( $section->tocLevel === $toclevel ) {
				$childSections = $this->getSectionsDataInternal(
					array_slice( $sections, $i + 1 ),
					$toclevel + 1
				);
				$data[] = $section->toLegacy() + [
					'array-sections' => $childSections,
				];
			}
		}
		return $data;
	}

	/**
	 * @param OutputPage $out
	 * @return array
	 */
	private function getRecursiveTocData( OutputPage $out ) {
		$tocData = $out->getTOCData();
		$sections = $this->getSectionsDataInternal(
			$tocData ? $tocData->getSections() : []
		);
		// Since the banner is outside of #mw-content-text, it
		// will be in the 'user language' (set on the root <html>
		// tag) not the 'content language'.  Record the proper
		// page language so that we can reset lang/dir in HTML.
		$title = $out->getTitle();
		// Note that OutputPage::getLanguage() returns user language
		// *not* the page/content language -- but title should always be
		// set for pages we're interested in.
		$lang = $title ? $title->getPageLanguage() : $out->getLanguage();
		return [
			'array-sections' => $sections,
			'lang' => $lang->getHtmlCode(),
			'dir' => $lang->getDir(),
		];
	}

	/**
	 * Hooks::onOutputPageParserOutput add banner parameters from ParserOutput to
	 * Output page
	 *
	 * @param OutputPage $outputPage
	 * @param ParserOutput $parserOutput
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		$options = $this->getBannerOptions( $parserOutput );
		if ( $options !== null ) {
			// if toc parameter set, then remove original classes and add banner class
			if ( isset( $options['enable-toc'] ) ) {
				$tocData = $outputPage->getTOCData();
				if ( $tocData ) {
					$options['data-toc'] = $this->getRecursiveTocData( $outputPage );
				}
				$options['msg-toc'] = $outputPage->msg( 'toc' )->text();
			}

			// set banner properties as an OutputPage property
			$outputPage->setProperty( 'wpb-banner-options', $options );
		}
	}

	/**
	 * Validates a given array of parameters against a set of allowed parameters and adds a
	 * warning message with a list of unknown parameters and a tracking category, if there are any.
	 *
	 * @param array $args Array of parameters to check
	 * @param Parser $parser ParserOutput object to add the warning message
	 */
	public function addBadParserFunctionArgsWarning( array $args, Parser $parser ) {
		$badParams = [];
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
			$parser->getOutput()->addWarningMsg(
				'wikidatapagebanner-invalid-arguments',
				Message::listParam( $badParams, 'comma' )
			);
		}
	}

	/**
	 * Hooks::addCustomBanner
	 * Parser function hooked to 'PAGEBANNER' magic word, to define a custom banner and options to
	 * customize banner such as icons,horizontal TOC,etc. The method does not return any content but
	 * sets the banner parameters in ParserOutput object for use at a later stage to generate banner
	 *
	 * @param Parser $parser
	 * @param string $bannername Name of custom banner
	 * @param string ...$args
	 */
	public function addCustomBanner( Parser $parser, $bannername, ...$args ) {
		// @var array to hold parameters to be passed to banner template
		$paramsForBannerTemplate = [];
		// Convert $argumentsFromParserFunction into an associative array
		$wpbFunctionsClass = self::$wpbBannerClass;
		$argumentsFromParserFunction = $wpbFunctionsClass::extractOptions( $parser, $args );
		// if given banner does not exist, return
		$title = $parser->getTitle();

		if ( $this->isBannerPermitted( $title ) ) {
			// check for unknown parameters used in the parser hook and add a warning if there is any
			$this->addBadParserFunctionArgsWarning( $argumentsFromParserFunction, $parser );

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
			Banner::addToc( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
			Banner::addIcons( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
			Banner::addFocus( $paramsForBannerTemplate,
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
				$parser->getOutput()->setPageProperty( 'wpb_banner', $bannerTitle->getText() );
				$parser->getOutput()->setPageProperty( 'wpb_banner_focus_x',
						// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
						(string)$paramsForBannerTemplate['data-pos-x'] );
				$parser->getOutput()->setPageProperty( 'wpb_banner_focus_y',
						// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
						(string)$paramsForBannerTemplate['data-pos-y'] );
				if ( isset( $paramsForBannerTemplate['enable-toc'] ) ) {
					$parser->getOutput()->setOutputFlag( ParserOutputFlags::NO_TOC );
				}
			}
		}
	}

	/**
	 * Hooks::onParserFirstCallInit
	 * Hooks the parser function addCustomBanner to the magic word 'PAGEBANNER'
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook(
			'PAGEBANNER', [ $this, 'addCustomBanner' ], Parser::SFH_NO_HASH
		);
		return true;
	}

}
