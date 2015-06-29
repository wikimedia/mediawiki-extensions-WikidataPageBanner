<?php
class WikidataPageBanner {

	/**
	 * WikidataPageBanner::addDefaultBanner
	 * This method is only for rendering a default banner, in case no
	 * user-defined banner is added using the 'PAGEBANNER' parser function, to
	 * the article. Otherwise, it only sets the 'articlebanner' property on
	 * OutputPage if a banner has been added
	 *
	 * @param Article $article
	 * @return bool
	 */
	public static function addDefaultBanner( $article ) {
		global $wgPBImage, $wgBannerNamespaces;
		$title = $article->getTitle();
		$out = $article->getContext()->getOutput();
		// if the page does not exist, or can not be viewed, return
		if ( !$title->isKnown() ) {
			return true;
		} elseif ( $article->getParserOutput()->getProperty( 'articlebanner' ) == null ) {
			$ns = $title->getNamespace();
			// banner only on specified namespaces, and not Main Page of wiki
			if ( in_array( $ns, $wgBannerNamespaces )
				&& !$title->isMainPage() ) {
				// if the page uses no 'PAGEBANNER' invocation, insert default banner or
				// WikidataBanner, first try to obtain bannername from Wikidata
				$bannername = static::getWikidataBanner( $title );
				if ( $bannername === null ) {
					// if Wikidata banner not found, set bannername to default banner
					$bannername = $wgPBImage;
				}
				// add title to template parameters
				$paramsForBannerTemplate = array( 'title' => $title );
				$banner = static::getBannerHtml( $bannername, $paramsForBannerTemplate );
				// add banner only if one found with given banner name
				if ( $banner !== null ) {
					// add the banner to output page if a valid one found
					// only set articlebanner property on OutputPage
					$out->addHtml( $banner );
					$out->setProperty( 'articlebanner', $banner );
				}
			}
		} else {
			// set 'articlebanner' property on OutputPage
			$out->setProperty(
				'articlebanner',
				$article->getParserOutput()->getProperty( 'articlebanner' )
			);
		}
		if ( $article->getParserOutput()->getProperty( 'bannertoc' ) && $out->isTOCEnabled() ) {
			$out->addJsConfigVars( 'wgWPBToc', true );
			$out->addModules( 'ext.WikidataPageBanner.toc' );
		}
		return true;
	}

	/**
	 * WikidataPageBanner::loadModules
	 *
	 * @param $out OutputPage
	 * @param $parserOutput ParserOutput
	 * @return  bool
	 */
	public static function loadModules( $out, $parserOutput ) {
		global $wgBannerNamespaces;
		$pageTitle = $out->getPageTitle();
		$title = Title::newFromText( $pageTitle );
		if ( isset( $title ) ) {
			$ns = $title->getNamespace();
			if ( in_array( $ns, $wgBannerNamespaces ) ) {
				// add banner style on allowed namespaces, so that banners are visible even on
				// preview
				$out->addModuleStyles( 'ext.WikidataPageBanner' );
			}
		}
	}

	/**
	 * WikidataPageBanner::addCustomBanner
	 * Parser function hooked to 'PAGEBANNER' magic word, to expand and load banner.
	 *
	 * @param  $parser Parser
	 * @param  $bannername Name of custom banner
	 * @return output
	 */
	public static function addCustomBanner( $parser, $bannername ) {
		global $wgBannerNamespaces;
		// @var array to get arguments passed to {{PAGEBANNER}} function
		$argumentsFromParserFunction = array();
		// @var array to hold parameters to be passed to banner template
		$paramsForBannerTemplate = array();
		// skip parser function name and bannername in arguments
		$argumentsFromParserFunction = array_slice( func_get_args(), 2 );
		// Convert $argumentsFromParserFunction into an associative array
		$argumentsFromParserFunction = self::extractOptions( $argumentsFromParserFunction );
		// if given banner does not exist, return
		$banner = '';
		$title = $parser->getTitle();
		$ns = $title->getNamespace();
		if ( in_array( $ns, $wgBannerNamespaces ) && !$title->isMainPage() ) {
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
			WikidataPageBannerFunctions::addToc( $parser->getOutput(),
					$argumentsFromParserFunction );
			$banner = static::getBannerHtml( $bannername, $paramsForBannerTemplate );
			// if given banner does not exist, return
			if ( $banner === null ) {
				return array( '', 'noparse' => true, 'isHTML' => true );
			}
			// Set 'articlebanner' property for future reference
			$parser->getOutput()->setProperty( 'articlebanner', $banner );
		}
		return array( $banner, 'noparse' => true, 'isHTML' => true );
	}

	/**
	 * WikidataPageBanner::getBannerUrl
	 * Return the full url of the banner image, stored on the wiki, given the
	 * image name. Additionally, if a width parameter is specified, it creates
	 * and returns url of an image of specified width.
	 *
	 * @param  string $filename Filename of the banner image
	 * @return string|null Full url of the banner image on the wiki or null
	 */
	public static function getBannerUrl( $filename, $bannerwidth = null ) {
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
		elseif ( filter_var( $bannerwidth, FILTER_VALIDATE_INT, $options ) !== false ) {
			// return null if querying for a width greater than original width,
			// so that when srcset is generated from this, urls are not repeated
			if ( $bannerwidth > $file->getWidth() ) {
				return null;
			}
			$mto = $file->transform( array( 'width' => $bannerwidth ) );
			$url = wfExpandUrl( $mto->getUrl(), PROTO_CURRENT );
			return $url;
		} else {
			// return image without transforming, if width not valid
			return $file->getFullUrl();
		}
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
		global $wgStandardSizes, $wgArticlePath;
		$urls = static::getStandardSizeUrls( $bannername );
		$banner = null;
		/** @var String srcset attribute for <img> element of banner image */
		$srcset = "";
		// if a valid bannername given, set banner
		if ( !empty( $urls ) ) {
			// @var int index variable
			$i = 0;
			foreach ( $urls as $url ) {
				$size = $wgStandardSizes[$i];
				// add url with width and a comma if not adding the last url
				if ( $i < count( $urls ) ) {
					$srcset .= "$url {$size}w,";
				}
				$i++;
			}
			// only use the largest size url as a hi-res banner url, as a mix
			// of 'w' and 'x' causes issues in chrome
			$url = $urls[$i-1];
			$srcset .= "$url 2x";
			$bannerurl = $urls[0];
			$bannerfile = str_replace( "$1", "File:$bannername", $wgArticlePath );
			$templateParser = new TemplateParser( __DIR__ . '/../templates' );
			$options['bannerfile'] =  $bannerfile;
			$options['banner'] = $bannerurl;
			$options['srcset'] = $srcset;
			$banner = $templateParser->processTemplate(
					'banner',
					$options
				);
		}
		return $banner;
	}

	/**
	 * WikidataPageBanner::onParserFirstCallInit
	 * Hooks the parser function addCustomBanner to the magic word 'PAGEBANNER'
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'PAGEBANNER', 'WikidataPageBanner::addCustomBanner', SFH_NO_HASH );
		return true;
	}

	/**
	 * WikidataPageBanner::getStandardSizeUrls
	 * returns an array of urls of standard image sizes defined by $wgStandardSizes
	 *
	 * @param  String $filename Name of Image file
	 * @return array
	 */
	public static function getStandardSizeUrls( $filename ) {
		global $wgStandardSizes;
		$urlSet = array();
		foreach ( $wgStandardSizes as $size ) {
			$url = static::getBannerUrl( $filename, $size );
			if ( $url != null ) {
				$urlSet[] = $url;
			}
			$prevurl = $url;
		}
		return $urlSet;
	}

	/**
	 * Fetches banner from wikidata for the specified page
	 *
	 * @param   Title $title Title of the page
	 * @return  String|null file name of the banner from wikitata [description]
	 * or null if none found
	 */
	public static function getWikidataBanner( $title ) {
		global $wgBannerProperty;
		$banner = null;
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
			/** @var Wikibase\DataModel\Entity\Item $item */
			if ( $itemId != null ) {
				$item = $entityLookup->getEntity( $itemId );
				$statements = $item->getStatements()->getByPropertyId(
						new Wikibase\DataModel\Entity\PropertyId(
							$wgBannerProperty
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
}
