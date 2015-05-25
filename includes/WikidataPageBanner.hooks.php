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
		} else if ( $article->getParserOutput()->getProperty( 'articlebanner' ) == null ) {
			// if the page uses no 'PAGEBANNER' invocation, insert default banner,
			// only set articlebanner property on OutputPage
			$bannerurl = self::getBannerUrl( $wgPBImage );
			$ns = $title->getNamespace();
			// banner only on specified namespaces, and not Main Page of wiki
			// if no image exists with given bannerurl, no banner will be added
			if ( in_array( $ns, $wgBannerNamespaces ) && !$title->isMainPage()
				&& $bannerurl !== null ) {
				$banner = self::getBannerHtml( $bannerurl, $title );
				$out->addHtml( $banner );
				$out->setProperty( 'articlebanner', $banner );
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
		$fileurl = self::getBannerUrl( $bannername );
		// if given banner does not exist, return
		if ( $fileurl === null ) {
			return array( '', 'noparse' => true, 'isHTML' => true );
		}
		$banner = '';
		$title = $parser->getTitle();
		$ns = $title->getNamespace();
		if ( in_array( $ns, $wgBannerNamespaces ) && !$title->isMainPage() ) {
			$banner = self::getBannerHtml( $fileurl, $title );
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
	 * @return string Full url of the banner image on the wiki
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
		else if ( filter_var( $bannerwidth, FILTER_VALIDATE_INT, $options ) !== FALSE ) {
			$mto = $file->transform( array( 'width' => $width ) );
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
	 * @param string $bannerurl Url of the image in the banner
	 * @param string $title Title to display on banner
	 * @return string Html code of the banner
	 * TODO:Move this banner html code to a template.
	 */
	public static function getBannerHtml( $bannerurl, $title ) {
		$templateParser = new TemplateParser( __DIR__ . '/../templates' );
		$banner = $templateParser->processTemplate(
				'banner',
				array(
					'banner' => $bannerurl,
					'title' => $title
				)
			);
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
}
