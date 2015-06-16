<?php
/**
 * This class contains helper functions which are used by hooks in WikidataPageBanner
 * to render the banner
 */
class WikidataPageBannerFunctions {
	/**
	 * Set bannertoc variable on parser output object
	 * @param ParserOutput $parserOutput ParserOutput object
	 * @param array $options options from parser function
	 */
	public static function addToc( $parserOutput, $options ) {
		if ( isset( $options['toc'] ) && $options['toc'] == 'yes' ) {
			$parserOutput->setProperty( 'bannertoc', true );
		}
	}
}
