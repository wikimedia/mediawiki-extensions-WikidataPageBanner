<?php
/**
 * This class contains helper functions which are used by hooks in WikidataPageBanner
 * to render the banner
 */
class WikidataPageBannerFunctions {
	private static $wpbConfig = null;

	/**
	 * @var string[] name of skins that do not implement 'prebodyhtml'
	 *  banners for these skin will be prepended to body content
	 */
	protected static $blacklistSkins = array( 'monobook', 'modern', 'cologneblue' );

	/**
	 * Set bannertoc variable on parser output object
	 * @param array $paramsForBannerTemplate banner parameters array
	 * @param array $options options from parser function
	 */
	public static function addToc( &$paramsForBannerTemplate, $options ) {
		if ( isset( $options['toc'] ) && $options['toc'] === 'yes' ) {
			$paramsForBannerTemplate['toc'] = true;
		}
	}

	/**
	 * Render icons using OOJS-UI for icons which are set in arguments
	 * @param array $paramsForBannerTemplate Parameters defined for banner template
	 * @param array $argumentsFromParserFunction Arguments passed to {{PAGEBANNER}} function
	 */
	public static function addIcons( &$paramsForBannerTemplate, $argumentsFromParserFunction ) {
		$iconsToAdd = array();
		// check all parameters and look for one's starting with icon-
		// The old format of icons=star,unesco would not generate any icons
		foreach ( $argumentsFromParserFunction as $key => $value ) {
			// found a valid icon parameter, so process it
			if ( substr( $key, 0, 5 ) === 'icon-' ) {
				// extract iconname after 'icon-' til the end of key
				$iconname = substr( $key, 5 );
				if ( !isset( $iconname, $value ) ) {
					continue;
				}
				$iconName = Sanitizer::escapeClass( $iconname );
				$iconUrl = Title::newFromText( $value );
				$iconTitleText = $iconName;
				$finalIcon = array( 'url' => '#' );
				// reference article for icons provided and is valid, then add its link
				if ( $iconUrl ) {
					$finalIcon['url'] = $iconUrl->getLocalUrl();
					// set icon title to title of referring article
					$iconTitleText = $iconUrl->getText();
				}
				$finalIcon['icon'] = $iconName;
				$finalIcon['title'] =  $iconTitleText;
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
	 * @param array $paramsForBannerTemplate Parameters defined for banner template
	 * @param array $argumentsFromParserFunction Arguments passed to {{PAGEBANNER}} function
	 */
	public static function addFocus( &$paramsForBannerTemplate, $argumentsFromParserFunction ) {
		// default centering would be 0, and -1 would represent extreme left and extreme top
		// Allowed values for each coordinate is between 0 and 1
		$paramsForBannerTemplate['data-pos-x'] = 0;
		$paramsForBannerTemplate['data-pos-y'] = 0;
		if ( isset( $argumentsFromParserFunction['origin'] ) ) {
			// split the origin into x and y coordinates
			$coords = explode( ',', $argumentsFromParserFunction['origin'] );
			if ( count( $coords ) === 2 ) {
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
	 * @param array string[] $options
	 * @return array $results
	 */
	public static function extractOptions( array $options ) {
		$results = array();
		foreach ( $options as $option ) {
			$pair = explode( '=', $option, 2 );
			if ( count( $pair ) == 2 ) {
				$name = trim( $pair[0] );
				$value = trim( $pair[1] );
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
	 * @param array  $options additional parameters passed to template
	 * @return string|null Html code of the banner or null if invalid bannername
	 */
	public static function getBannerHtml( $bannername, $options = array() ) {
		$config = self::getWPBConfig();
		$urls = static::getStandardSizeUrls( $bannername );
		$banner = null;
		/** @var String srcset attribute for <img> element of banner image */
		$srcset = array();
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
			$templateParser = new TemplateParser( __DIR__ . '/../templates' );
			$options['bannerfile'] = $bannerfile->getLocalUrl();
			$options['banner'] = $bannerurl;
			$options['srcset'] = $srcset;
			$file = wfFindFile( $bannerfile );
			$options['maxWidth'] = $file->getWidth();
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
	 * @param  string $filename Filename of the banner image
	 * @return string|null Full url of the banner image on the wiki or null
	 */
	public static function getImageUrl( $filename, $imagewidth = null ) {
		// make title object from image name
		$title = Title::makeTitleSafe( NS_IMAGE, $filename );
		$file = wfFindFile( $title );
		$options = array(
				'options' => array( 'min_range' => 0, 'max_range' => 3000 )
			);
		// if file not found, return null
		if ( $file == null ) {
			return null;
		}
		// validate $bannerwidth to be a width within 3000
		elseif ( filter_var( $imagewidth, FILTER_VALIDATE_INT, $options ) !== false ) {
			$mto = $file->transform( array( 'width' => $imagewidth ) );
			$url = wfExpandUrl( $mto->getUrl(), PROTO_CURRENT );
			return $url;
		} else {
			// return image without transforming, if width not valid
			return $file->getFullUrl();
		}
	}

	/**
	 * WikidataPageBanner::getStandardSizeUrls
	 * returns an array of urls of standard image sizes defined by $wgWPBStandardSizes
	 *
	 * @param  String $filename Name of Image file
	 * @return array
	 */
	public static function getStandardSizeUrls( $filename ) {
		$urlSet = array();
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
	 * WikidataPageBanner::getWikidataBanner Fetches banner from wikidata for the specified page
	 *
	 * @param   Title $title Title of the page
	 * @return  String|null file name of the banner from wikidata
	 * or null if none found
	 */
	public static function getWikidataBanner( $title ) {
		$banner = null;
		$wpbBannerProperty = self::getWPBConfig()->get( 'WPBBannerProperty' );
		if ( empty( $wpbBannerProperty ) ) {
			return null;
		}
		// Ensure Wikibase client is installed
		if ( class_exists( 'Wikibase\Client\WikibaseClient' ) ) {
			$entityIdLookup = Wikibase\Client\WikibaseClient::getDefaultInstance()
			->getStore()
			->getEntityIdLookup();
			$itemId = $entityIdLookup->getEntityIdForTitle( $title );
			// check if this page has an associated item page
			$entityLookup = Wikibase\Client\WikibaseClient::getDefaultInstance()
			->getStore()
			->getEntityLookup();
			if ( $itemId != null ) {
				$item = $entityLookup->getEntity( $itemId );
				$statements = $item->getStatements()->getByPropertyId(
						new Wikibase\DataModel\Entity\PropertyId(
							$wpbBannerProperty
						)
					)->getBestStatements();
				if ( !$statements->isEmpty() ) {
					$statements = $statements->toArray();
					$snak = $statements[0]->getMainSnak();
					if ( $snak instanceof Wikibase\DataModel\Snak\PropertyValueSnak ) {
						$banner = $snak->getDataValue()->getValue();
					}
				}
			}
		}
		return $banner;
	}

	/**
	 * Insert banner HTML into the page
	 *
	 * @param OutputPage $out to inject banner into
	 * @param string $html of banner to insert
	 */
	public static function insertBannerIntoOutputPage( $out, $html ) {
		global $wgWPBEnableHeadingOverride;

		if ( in_array( $out->getSkin()->getSkinName(), self::$blacklistSkins ) ) {
			$out->prependHtml( $banner );
		}
		if ( $wgWPBEnableHeadingOverride ) {
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
		$out->addModules( 'ext.WikidataPageBanner.positionBanner' );
	}

	/**
	 * Returns a new or cached config object for WikidataPageBanner extension.
	 *
	 * @return Config
	 */
	public static function getWPBConfig() {
		if ( self::$wpbConfig === null ) {
			self::$wpbConfig = ConfigFactory::getDefaultInstance()->makeConfig( 'wikidatapagebanner' );
		}

		return self::$wpbConfig;
	}
}
