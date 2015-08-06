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
	 * @param array $paramsForBannerTemplate Parameters defined for banner template
	 * @param array $argumentsFromParserFunction Arguments passed to {{PAGEBANNER}} function
	 */
	public static function addIcons( &$paramsForBannerTemplate, $argumentsFromParserFunction ) {
		$iconsToAdd = array();
		if ( isset( $argumentsFromParserFunction['icons'] ) ) {
			$icons = explode( ',', $argumentsFromParserFunction['icons'] );
			foreach ( $icons as $iconname ) {
				// avoid icon generation when empty iconname
				// @FIXME don't use empty here
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
			if ( $iconsToAdd ) {
				$paramsForBannerTemplate['hasIcons'] = true;
				$paramsForBannerTemplate['icons'] = $iconsToAdd;
			}
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
}
