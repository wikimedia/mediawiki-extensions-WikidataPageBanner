<?php
class WikidataPageBanner {
	/**
	 * WikidataPageBanner::addBanner Generates banner from given options and adds it and its styles
	 * to Output Page
	 *
	 * @param $out OutputPage
	 * @param $skin Skin Object
	 * @return  bool
	 */
	public static function addBanner( $out, $skin ) {
		global $wgPBImage, $wgBannerNamespaces;
		$title = $out->getTitle();
		// if banner-options are set, add banner anyway
		if ( $out->getProperty( 'wpb-banner-options' ) !== null ) {
			$params = $out->getProperty( 'wpb-banner-options' );
			$bannername = $params['name'];
			OOUI\Theme::setSingleton( new OOUI\MediaWikiTheme );
			$out->enableOOUI();
			$banner = static::getBannerHtml( $bannername, $params );
			// attempt to get WikidataBanner
			if ( $banner === null ) {
				$bannername = static::getWikidataBanner( $title );
				$banner = static::getBannerHtml( $bannername, $params );
			}
			// only add banner and styling if valid banner generated
			if ( $banner !== null ) {
				$out->addModuleStyles( 'ext.WikidataPageBanner' );
				if ( isset( $params['toc'] ) ) {
					$out->addModuleStyles( 'ext.WikidataPageBanner.toc.styles' );
				}
				$out->prependHtml( $banner );
				// hide primary title
				$out->setPageTitle( '' );
				$out->setHTMLTitle( $out->getTitle() );
				// set articlebanner property on OutputPage
				$out->setProperty( 'articlebanner', $banner );
			}
		}
		// if the page uses no 'PAGEBANNER' invocation and if article page, insert default banner
		elseif ( $title->isKnown() && $out->isArticle() ) {
			$ns = $title->getNamespace();
			// banner only on specified namespaces, and not Main Page of wiki
			if ( in_array( $ns, $wgBannerNamespaces )
				&& !$title->isMainPage() ) {
				// first try to obtain bannername from Wikidata
				$bannername = static::getWikidataBanner( $title );
				if ( $bannername === null ) {
					// if Wikidata banner not found, set bannername to default banner
					$bannername = $wgPBImage;
				}
				// add title to template parameters
				$paramsForBannerTemplate = array( 'title' => $title );
				$banner = static::getBannerHtml( $bannername, $paramsForBannerTemplate );
				// only add banner and styling if valid banner generated
				if ( $banner !== null ) {
					$out->addModuleStyles( 'ext.WikidataPageBanner' );
					$out->prependHtml( $banner );
					// hide primary title
					$out->setPageTitle( '' );
					$out->setHTMLTitle( $out->getTitle() );
					// set articlebanner property on OutputPage
					$out->setProperty( 'articlebanner', $banner );
				}
			}
		}
		return true;
	}

	/**
	 * WikidataPageBanner::onOutputPageParserOutput add banner parameters from ParserOutput to
	 * Output page
	 * @param  OutputPage $out
	 * @param  ParserOutput $pOut
	 */
	public static function onOutputPageParserOutput( $out, $pOut ) {
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
				$out->enableTOC( false );
			}
			$out->setProperty( 'wpb-banner-options', $options );
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
			WikidataPageBannerFunctions::addToc( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
			WikidataPageBannerFunctions::addIcons( $paramsForBannerTemplate,
					$argumentsFromParserFunction );
			$paramsForBannerTemplate['name'] = $bannername;
			// Set 'wpb-banner-options' property for generating banner later
			$parser->getOutput()->setProperty( 'wpb-banner-options', $paramsForBannerTemplate );
		}
		return array( '', 'noparse' => true, 'isHTML' => true );
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
	 * WikidataPageBanner::getBannerHtml
	 * Returns the html code for the pagebanner
	 *
	 * @param string $bannername FileName of banner image
	 * @param array  $options additional parameters passed to template
	 * @return string|null Html code of the banner or null if invalid bannername
	 */
	public static function getBannerHtml( $bannername, $options = array() ) {
		global $wgStandardSizes;
		$urls = static::getStandardSizeUrls( $bannername );
		$banner = null;
		/** @var String srcset attribute for <img> element of banner image */
		$srcset = array();
		// if a valid bannername given, set banner
		if ( !empty( $urls ) ) {
			// @var int index variable
			$i = 0;
			foreach ( $urls as $url ) {
				$size = $wgStandardSizes[$i];
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
			$url = static::getImageUrl( $filename, $size );
			// prevent duplication in urlSet
			if ( $url !== null && !in_array( $url, $urlSet, true ) ) {
				$urlSet[] = $url;
			}
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
