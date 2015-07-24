<?php
/**
 * Wikidata PageBanner Extension
 *
 * For more info see http://mediawiki.org/wiki/Extension:WikidataPageBanner
 * @author Sumit Asthana, 2015
 * @license GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'WikidataPageBanner',
	'namemsg'        => "WikidataPageBanner",
	'description'    => "Render banners on wikivoyage",
	'descriptionmsg' => 'Display pagewide banners on wikivoyage',
	'author'         => array( 'Sumit Asthana' ),
	'version'        => '0.0.1',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:WikidataPageBanner',
	'license-name'   => 'GPL-2.0+',
);

/**
 * $wgPBImage - default pagebanner image file, use only filename, do not prefix 'File:',
 * e.g. $wgPBImage = 'Foo.jpg'
 */
$wgPBImage = "";
/** $wgBannerNamespace - Namespaces on which to display banner */
$wgBannerNamespaces = array( NS_MAIN );
/** $wgStandardSizes - Array of standard predefined screen widths in increasing order */
$wgStandardSizes = array( 320, 640, 1280, 2560 );
/** $wgBannerProperty - Banner property on wikidata which holds commons media file */
$wgBannerProperty = "";

/* Setup */
// autoloader
$wgAutoloadClasses['WikidataPageBanner'] = __DIR__ . '/includes/WikidataPageBanner.hooks.php';
$wgAutoloadClasses['WikidataPageBannerFunctions'] =
	__DIR__ . '/includes/WikidataPageBanner.functions.php';

// Register files
$wgMessagesDirs['WikidataPageBanner'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['WikidataPageBannerMagic'] =
	__DIR__ . '/WikidataPageBanner.i18n.magic.php';

// Register hooks
// Hook to inject banner code
$wgHooks['BeforePageDisplay'][] = 'WikidataPageBanner::addBanner';
// hook to pass banner data from ParserOutput to OutputPage
$wgHooks['OutputPageParserOutput'][] = 'WikidataPageBanner::onOutputPageParserOutput';
$wgHooks['ParserFirstCallInit'][] = 'WikidataPageBanner::onParserFirstCallInit';
$wgHooks['UnitTestsList'][] = 'WikidataPageBanner::onUnitTestsList';

// include WikidataPageBanner class file
require_once __DIR__ . "/resources/Resources.php";
