<?php
/**
 * This class contains helper functions which are used by hooks in WikidataPageBanner
 * to render the banner
 */
class WikidataPageBannerFunctions {
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
	 * @param  array $paramsForBannerTemplate Parameters defined for banner template
	 * @param  array $argumentsFromParserFunction Arguments passed to {{PAGEBANNER}} function
	 * @return
	 */
	public static function addIcons( &$paramsForBannerTemplate, $argumentsFromParserFunction ) {
		$iconsToAdd = array();
		if ( isset( $argumentsFromParserFunction['icons'] ) ) {
			$icons = explode( ',', $argumentsFromParserFunction['icons'] );
			foreach ( $icons as $iconname ) {
				// avoid icon generation when empty iconname
				if ( empty( $iconname ) ) {
					continue;
				}
				$iconName = Sanitizer::escapeClass( $iconname );
				$icon = new OOUI\IconWidget( array(
					'icon' => $iconName,
					'title' => $iconName
				) );
				$iconsToAdd[] = array( 'icon' => $icon );
			}
			// only set hasIcons to true if parser function gives some non-empty icon names
			if ( !empty( $iconsToAdd ) ) {
				$paramsForBannerTemplate['hasIcons'] = true;
				$paramsForBannerTemplate['icons'] = $iconsToAdd;
			}
		}
	}
}
