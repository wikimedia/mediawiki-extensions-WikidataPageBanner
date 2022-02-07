<?php

namespace MediaWiki\Extension\WikidataPageBanner;

use Config;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use OutputPage;
use PageImages\PageImages;
use Parser;
use Sanitizer;
use Skin;
use TemplateParser;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * This class contains helper functions which are used by hooks in WikidataPageBanner
 * to render the banner
 */
class WikidataPageBannerFunctions {
	/**
	 * Set bannertoc variable on parser output object
	 *
	 * @param array &$paramsForBannerTemplate banner parameters array
	 * @param array $options options from parser function
	 */
	public static function addToc( &$paramsForBannerTemplate, $options ) {
		if ( isset( $options['toc'] ) && $options['toc'] === 'yes' ) {
			$paramsForBannerTemplate['enable-toc'] = true;
		}
	}

	/**
	 * Render icons using OOJS-UI for icons which are set in arguments
	 *
	 * @param array &$paramsForBannerTemplate Parameters defined for banner template
	 * @param array $argumentsFromParserFunction Arguments passed to {{PAGEBANNER}} function
	 */
	public static function addIcons( &$paramsForBannerTemplate, $argumentsFromParserFunction ) {
		$iconsToAdd = [];

		// check all parameters and look for one's starting with icon-
		// The old format of icons=star,unesco would not generate any icons
		foreach ( $argumentsFromParserFunction as $key => $value ) {
			// found a valid icon parameter, so process it
			if ( substr( $key, 0, 5 ) === 'icon-' ) {
				// extract iconname after 'icon-' til the end of key
				$iconname = substr( $key, 5 );
				if ( !isset( $iconname ) || !isset( $value ) ) {
					continue;
				}

				$iconName = Sanitizer::escapeClass( $iconname );
				$iconUrl = Title::newFromText( $value );
				$iconTitleText = $iconName;
				$finalIcon = [ 'url' => '#' ];
				// reference article for icons provided and is valid, then add its link
				if ( $iconUrl ) {
					$finalIcon['url'] = $iconUrl->getLocalURL();
					// set icon title to title of referring article
					$iconTitleText = $iconUrl->getText();
				}
				$finalIcon['icon'] = $iconName;
				$finalIcon['title'] = $iconTitleText;
				$iconsToAdd[] = $finalIcon;
			}
		}

		// only set hasIcons to true if parser function gives some non-empty icon names
		if ( $iconsToAdd ) {
			$paramsForBannerTemplate['hasIcons'] = true;
			$paramsForBannerTemplate['icons'] = $iconsToAdd;
		}
	}

	/**
	 * Sets focus parameter on banner templates to shift focus on banner when cropped
	 *
	 * @param array &$paramsForBannerTemplate Parameters defined for banner template
	 * @param array $argumentsFromParserFunction Arguments passed to {{PAGEBANNER}} function
	 */
	public static function addFocus( &$paramsForBannerTemplate, $argumentsFromParserFunction ) {
		// default centering would be 0, and -1 would represent extreme left and extreme top
		// Allowed values for each coordinate is between 0 and 1
		// If no value has been specified these are set to null
		$paramsForBannerTemplate['data-pos-x'] = 0;
		$paramsForBannerTemplate['data-pos-y'] = 0;
		$paramsForBannerTemplate['hasPosition'] = false;

		if ( isset( $argumentsFromParserFunction['origin'] ) ) {
			// split the origin into x and y coordinates
			$coords = explode( ',', $argumentsFromParserFunction['origin'] );
			if ( count( $coords ) === 2 ) {
				$paramsForBannerTemplate['hasPosition'] = true;
				$positionx = $coords[0];
				$positiony = $coords[1];
				// TODO:Add a js module to use the data-pos values being set below to fine tune the
				// position of the banner to emulate a coordinate system.
				if ( filter_var( $positionx, FILTER_VALIDATE_FLOAT ) !== false ) {
					if ( $positionx >= -1 && $positionx <= 1 ) {
						$paramsForBannerTemplate['data-pos-x'] = $positionx;
						if ( $positionx <= -0.25 ) {
							// these are classes to be added in case js is disabled
							$paramsForBannerTemplate['originx'] = 'wpb-left';
						} elseif ( $positionx >= 0.25 ) {
							$paramsForBannerTemplate['originx'] = 'wpb-right';
						}
					}
				}
				if ( filter_var( $positiony, FILTER_VALIDATE_FLOAT ) !== false ) {
					if ( $positiony >= -1 && $positiony <= 1 ) {
						$paramsForBannerTemplate['data-pos-y'] = $positiony;
					}
				}
			}
		}
	}

	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value
	 *
	 * @param Parser $parser
	 * @param string[] $options
	 * @return array $results
	 */
	public static function extractOptions( Parser $parser, array $options ) {
		$results = [];
		$langConv = MediaWikiServices::getInstance()->getLanguageConverterFactory()
			->getLanguageConverter( $parser->getTargetLanguage() );

		foreach ( $options as $option ) {
			$pair = explode( '=', $option, 2 );
			if ( count( $pair ) == 2 ) {
				$name = trim( $pair[0] );
				// convert value to preferred language variant as
				// done in core Parser.php
				$value = $langConv->convert( trim( $pair[1] ) );
				$results[$name] = $value;
			}
		}

		return $results;
	}

	/**
	 * WikidataPageBanner::getBannerHtml
	 * Returns the html code for the pagebanner
	 *
	 * @param string $bannername FileName of banner image
	 * @param array $options additional parameters passed to template
	 * @return string|null Html code of the banner or null if invalid bannername
	 */
	public static function getBannerHtml( $bannername, $options = [] ) {
		$config = self::getWPBConfig();
		$urls = static::getStandardSizeUrls( $bannername );
		$banner = null;
		/** @var string srcset attribute for <img> element of banner image */
		$srcset = [];

		// if a valid bannername given, set banner
		if ( !empty( $urls ) ) {
			// @var int index variable
			$i = 0;
			foreach ( $urls as $url ) {
				$size = $config->get( 'WPBStandardSizes' );
				$size = $size[$i];
				// add url with width and a comma if not adding the last url
				if ( $i < count( $urls ) ) {
					$srcset[] = "$url {$size}w";
				}
				$i++;
			}
			// create full src set from individual urls, separated by comma
			$srcset = implode( ',', $srcset );
			// use largest image url as src attribute
			$bannerurl = $urls[count( $urls ) - 1];
			$bannerfile = Title::newFromText( "File:$bannername" );
			$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $bannerfile );
			// don't auto generate banner if image is not landscape, see bug report T131424
			$fileWidth = $file->getWidth();
			$fileHeight = $file->getHeight();
			if ( !empty( $options['isAutomatic'] ) && $fileWidth < 1.5 * $fileHeight ) {
				return null;
			}
			// Get the URL of the link. Can be an internal or external link, or none. Defaults to the image's page.
			if ( isset( $options['link'] ) ) {
				$href = $options['link'] === '' ? false : Skin::makeInternalOrExternalUrl( $options['link'] );
			} else {
				$href = $bannerfile->getLocalURL();
			}
			$templateParser = new TemplateParser( __DIR__ . '/../templates' );
			$options['href'] = $href;
			$options['banner'] = $bannerurl;
			$options['srcset'] = $srcset;
			$options['maxWidth'] = $fileWidth;
			// Provide information to the logic-less template about whether it is a panorama or not.
			$options['isPanorama'] = $fileWidth > ( $fileHeight * 2 );
			$options['isHeadingOverrideEnabled'] = $config->get( 'WPBEnableHeadingOverride' );
			$banner = $templateParser->processTemplate(
				'banner',
				$options
			);
		}

		return $banner;
	}

	/**
	 * WikidataPageBanner::getImageUrl
	 * Return the full url of the banner image, stored on the wiki, given the
	 * image name. Additionally, if a width parameter is specified, it creates
	 * and returns url of an image of specified width.
	 *
	 * @param string $filename Filename of the banner image
	 * @param int|null $imagewidth
	 * @return string|null Full url of the banner image on the wiki or null
	 */
	public static function getImageUrl( $filename, $imagewidth = null ) {
		// make title object from image name
		$title = Title::makeTitleSafe( NS_FILE, $filename );
		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );
		$options = [
			'options' => [ 'min_range' => 0, 'max_range' => 3000 ]
		];
		// if file not found, return null
		if ( $file === false ) {
			return null;
		} elseif ( filter_var( $imagewidth, FILTER_VALIDATE_INT, $options ) !== false ) {
			// validate $bannerwidth to be a width within 3000
			$mto = $file->transform( [ 'width' => $imagewidth ] );
			return wfExpandUrl( $mto->getUrl(), PROTO_CURRENT );
		} else {
			// return image without transforming, if width not valid
			return $file->getFullUrl();
		}
	}

	/**
	 * WikidataPageBanner::getStandardSizeUrls
	 * returns an array of urls of standard image sizes defined by $wgWPBStandardSizes
	 *
	 * @param string $filename Name of Image file
	 * @return array
	 */
	public static function getStandardSizeUrls( $filename ) {
		$urlSet = [];

		foreach ( self::getWPBConfig()->get( 'WPBStandardSizes' ) as $size ) {
			$url = static::getImageUrl( $filename, $size );
			// prevent duplication in urlSet
			if ( $url !== null && !in_array( $url, $urlSet, true ) ) {
				$urlSet[] = $url;
			}
		}

		return $urlSet;
	}

	/**
	 * Fetches a banner for a given title when none has been specified by an editor
	 *
	 * @param Title $title Title of the page
	 * @return string|null file name of a suitable automatic banner or null if none found
	 */
	public static function getAutomaticBanner( $title ) {
		$config = self::getWPBConfig();
		$bannername = static::getWikidataBanner( $title );

		if ( $bannername === null ) {
			$bannername = static::getPageImagesBanner( $title );
		}
		if ( $bannername === null ) {
			// if Wikidata banner not found, set bannername to default banner
			$bannername = $config->get( 'WPBImage' );
		}
		return $bannername;
	}

	/**
	 * Fetches banner from PageImages
	 *
	 * @param Title $title Title of the page
	 * @return string|null file name of the banner found via page images
	 * or null if none found
	 */
	public static function getPageImagesBanner( $title ) {
		$config = self::getWPBConfig();

		if (
			$config->get( 'WPBEnablePageImagesBanners' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'PageImages' )
		) {
			$pi = PageImages::getPageImage( $title );
			// getPageImage returns false if no page image.
			if ( $pi ) {
				return $pi->getTitle()->getDBkey();
			}
		}

		return null;
	}

	/**
	 * WikidataPageBanner::getWikidataBanner Fetches banner from wikidata for the specified page
	 *
	 * @param Title $title Title of the page
	 * @return string|null file name of the banner from wikidata
	 * or null if none found
	 */
	public static function getWikidataBanner( $title ) {
		$banner = null;
		$wpbBannerProperty = self::getWPBConfig()->get( 'WPBBannerProperty' );
		if ( empty( $wpbBannerProperty ) ) {
			return null;
		}
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			return null;
		}

		$entityIdLookup = WikibaseClient::getEntityIdLookup();
		$itemId = $entityIdLookup->getEntityIdForTitle( $title );
		// check if this page has an associated item page
		if ( $itemId !== null ) {
			$entityLookup = WikibaseClient::getEntityLookup();
			$item = $entityLookup->getEntity( $itemId );
			if ( !( $item instanceof Item ) ) {
				// Sometimes EntityIdLookup is not consistent/ up to date with repo
				return null;
			}
			$statements = $item->getStatements()->getByPropertyId(
				new NumericPropertyId( $wpbBannerProperty )
			)->getBestStatements();
			if ( !$statements->isEmpty() ) {
				$statements = $statements->toArray();
				$snak = $statements[0]->getMainSnak();
				if ( $snak instanceof PropertyValueSnak ) {
					$banner = $snak->getDataValue()->getValue();
				}
			}
		}

		return $banner;
	}

	/**
	 * @param Skin $skin
	 * @return bool
	 */
	public static function isSkinDisabled( $skin ) {
		$skinName = $skin->getSkinName();
		$config = $skin->getConfig();
		$skinDisabled = (array)$config->get( 'WPBSkinDisabled' );
		return in_array( $skinName, $skinDisabled );
	}

	/**
	 * Insert banner HTML into the page as a page property.
	 * Suppress primary page title if configured.
	 *
	 * @param OutputPage $out to inject banner into
	 * @param string $html of banner to insert
	 */
	public static function setOutputPageProperties( $out, $html ) {
		$config = self::getWPBConfig();

		if ( $config->get( 'WPBEnableHeadingOverride' )
			&& !self::isSkinDisabled( $out->getSkin() ) ) {
			$htmlTitle = $out->getHTMLTitle();
			// hide primary title
			$out->setPageTitle( '' );
			// set html title again, because above call also empties the <title> tag
			$out->setHTMLTitle( $htmlTitle );
		}
		// set articlebanner property on OutputPage for getSkinTemplateOutputPageBeforeExec hook
		$out->setProperty( 'articlebanner', $html );

		// Add common resources
		$out->addModuleStyles( 'ext.WikidataPageBanner' );
		$out->addModuleStyles( 'ext.WikidataPageBanner.print.styles' );
		$out->addModules( 'ext.WikidataPageBanner.positionBanner' );
	}

	/**
	 * Returns a new or cached config object for WikidataPageBanner extension.
	 *
	 * @return Config
	 */
	public static function getWPBConfig() {
		return MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'wikidatapagebanner' );
	}

	/**
	 * Adds banner custom CSS classes according to extraClass parameter
	 *
	 * @param array &$paramsForBannerTemplate Parameters defined for banner template
	 * @param array $argumentsFromParserFunction Arguments passed to {{PAGEBANNER}} function
	 */
	public static function addCssClasses( &$paramsForBannerTemplate,
			$argumentsFromParserFunction ) {
		$paramsForBannerTemplate['extraClass'] = '';
		if ( isset( $argumentsFromParserFunction['extraClass'] ) ) {
			$classes = explode( ' ', $argumentsFromParserFunction['extraClass'] );
			foreach ( $classes as $class ) {
				$paramsForBannerTemplate['extraClass'] .= ' ' . Sanitizer::escapeClass( $class );
			}
		}
	}

	/**
	 * Check if the namespace should have a banner on it by default.
	 * $wgWPBNamespaces can be an array of namespaces, or true, in which case it applies to all
	 * namespaces. If it's true, certain namespaces can be disabled with $wgWPBDisabledNamespaces.
	 *
	 * @param int $ns Namespace of page
	 * @return bool
	 */
	public static function validateNamespace( $ns ) {
		$config = self::getWPBConfig();
		if ( $config->get( 'WPBNamespaces' ) === true ) {
			return !in_array( $ns, $config->get( 'WPBDisabledNamespaces' ) );
		} else {
			return in_array( $ns, $config->get( 'WPBNamespaces' ) );
		}
	}
}
