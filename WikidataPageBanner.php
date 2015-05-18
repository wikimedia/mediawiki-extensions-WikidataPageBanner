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
 * Options:
 *
 * $wgPBImage - default pagebanner image file, use only filename, do not prefix 'File:',
 * e.g. $wgPBImage = 'Foo.jpg'
 * $wgBannerNamespace - Namespaces on which to display banner
 * $wgStandardSizes - Array of standard predefined screen widths in increasing order
 */
$wgPBImage = "";
$wgBannerNamespaces = array( NS_MAIN );
$wgStandardSizes = array( 320, 640, 1280, 2560 );

/* Setup */
// autoloader
$wgAutoloadClasses['WikidataPageBanner'] = __DIR__ . '/includes/WikidataPageBanner.hooks.php';

// Register files
$wgMessagesDirs['WikidataPageBanner'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['WikidataPageBannerAlias'] = __DIR__ . '/WikidataPageBanner.i18n.alias.php';
$wgExtensionMessagesFiles['WikidataPageBannerMagic'] = __DIR__ . '/WikidataPageBanner.i18n.magic.php';

// Register hooks
// Hook to inject banner code
$wgHooks['ArticleViewHeader'][] = 'WikidataPageBanner::addDefaultBanner';
// Load Banner modules, styles
$wgHooks['BeforePageDisplay'][] = 'WikidataPageBanner::loadModules';
$wgHooks['ParserFirstCallInit'][] = 'WikidataPageBanner::onParserFirstCallInit';

// include WikidataPageBanner class file
require_once __DIR__ . "/includes/WikidataPageBanner.hooks.php";
require_once __DIR__ . "/resources/Resources.php";


