<?php
class WikidataPageBanner {
	/**
	 * WikidataPageBanner::viewBanner
	 *
	 * @param Article $article
	 * @return bool
	 */
	public static function viewBanner( $article ) {
		global $wgPBImageUrl, $wgBannerNamespaces;
		$bannerurl = $wgPBImageUrl;
		$title = $article->getTitle();
		$ns = $title->getNamespace();
		// banner only on specified namespaces, and not Main Page of wiki
		if ( in_array( $ns, $wgBannerNamespaces ) && !$title->isMainPage() ) {
			$banner = Html::openElement( 'div', array( 'class' => 'noprint' ) ) .
			Html::openElement( 'div', array( 'class' => 'ext-wpb-pagebanner',
									'style' => "background-image:url($bannerurl);"
								)
							) .
			Html::openElement( 'div', array( 'class' => 'topbanner' ) ) .
			Html::element( 'div',
				array( 'class' => 'name' ),
				$title
			) .
			Html::element( 'div',
				array( 'class' => 'iconbox' )
			) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' ) . "\n";
			$out = $article->getContext()->getOutput();
			$out->addHtml( $banner );
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
		$title = $out->getTitle();
		if ( in_array( $ns, $wgBannerNamespaces ) && !$title->isMainPage() ) {
			// Setup banner styling, only if specified namespaces, and not Main Page of wiki
			$out->addModuleStyles( 'ext.WikidataPageBanner' );
		}
	}
}
