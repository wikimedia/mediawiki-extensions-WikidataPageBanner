<?php
class WikidataPageBanner {
	/**
	 * WikidataPageBanner::viewBanner
	 *
	 * @param Article $article
	 * @return bool
	 */
	public static function viewBanner( $article ) {
		global $wgPBImageUrl;
		$bannerurl = $wgPBImageUrl;
		$title = $article->getTitle();
		$ns = $title->getNamespace();
		// banner only on main namespacem, and not Main Page of wiki
		if ( $ns == NS_MAIN && !$title->isMainPage() ) {
			$banner = Html::openElement( 'div', array( 'class' => 'noprint' ) ) .
			Html::openElement( 'div', array( 'class' => 'ext-wpb-pagebanner',
									'style' => 'background-image:url('.$bannerurl.');'
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
		$title = $out->getTitle();
		if ( $title->getNamespace() == NS_MAIN && !$title->isMainPage() ) {
			// Setup banner styling, only if main namespace, and not Main Page of wiki
			$out->addModuleStyles( 'ext.WikidataPageBanner' );
		}
	}
}
