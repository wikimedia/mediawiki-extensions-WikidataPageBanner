<?php
/**
 * Wikidata PageBanner Extension
 *
 * For more info see http://mediawiki.org/wiki/Extension:WikidataPageBanner
 * @author Sumit Asthana, 2015
 * @license GNU General Public Licence 2.0 or later
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikidataPageBanner' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikidataPageBanner'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikidataPageBannerMagic'] =
		__DIR__ . '/WikidataPageBanner.i18n.magic.php';
	/* wfWarn(
		'Deprecated PHP entry point used for WikidataPageBanner extension. Please use wfLoadExtension'.
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the WikidataPageBanner extension requires MediaWiki 1.25+' );
}
