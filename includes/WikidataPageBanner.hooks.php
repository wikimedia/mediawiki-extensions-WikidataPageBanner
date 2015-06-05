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
			// if no image exists with given bannerurl, no banner will be added
			if ( in_array( $ns, $wgBannerNamespaces )
				&& !$title->isMainPage() ) {
				// if the page uses no 'PAGEBANNER' invocation, insert default banner,
				// only set articlebanner property on OutputPage
				$banner = self::getBannerHtml( $title, $wgPBImage );
				if ( $banner !== null ) {
					$out->addHtml( $banner );
					$out->setProperty( 'articlebanner', $banner );
				}
			}
		} else {
			$out->setProperty(
				'articlebanner',
				$article->getParserOutput()->getProperty( 'articlebanner' )
			);
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
		if ( $out->getProperty( 'articlebanner' ) != null ) {
			// if articlebanner property is set, we need to add banner styles
			$out->addModuleStyles( 'ext.WikidataPageBanner' );
			$out->addModules( 'ext.WikidataPageBanner.loadImage' );
		}
	}

	/**
	 * WikidataPageBanner::addCustomBanner
	 * Parser function hooked to 'PAGEBANNER' magic word, to expand and load banner.
	 *
	 * @param  $parser Parser
	 * @param  $bannerurl Url of custom banner
	 * @return output
	 */
	public static function addCustomBanner( $parser, $bannername ) {
		global $wgBannerNamespaces;
		$banner = '';
		$title = $parser->getTitle();
		$ns = $title->getNamespace();
		if ( in_array( $ns, $wgBannerNamespaces ) && !$title->isMainPage() ) {
			$banner = self::getBannerHtml( $title, $bannername );
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
	 * @param string $title Title to display on banner
	 * @param string $bannername FileName of banner image
	 * @return string|null Html code of the banner or null if invalid bannername
	 */
	public static function getBannerHtml( $title, $bannername ) {
		global $wgStandardSizes, $wgArticlePath;
		$urls = self::getStandardSizeUrls( $bannername );
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
			$banner = $templateParser->processTemplate(
					'banner',
					array(
						'bannerfile' => $bannerfile,
						'banner' => $bannerurl,
						'title' => $title,
						'srcset' => $srcset
					)
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
			$url = self::getBannerUrl( $filename, $size );
			if ( $url != null ) {
				$urlSet[] = $url;
			}
			$prevurl = $url;
		}
		return $urlSet;
	}
}
