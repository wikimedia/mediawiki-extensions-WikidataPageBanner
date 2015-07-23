<?php
/**
 * Definition of WikidataPageBanner's ResourceLoader modules.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$wgResourceModules['ext.WikidataPageBanner'] = array(
	'styles' => array(
		'ext.WikidataPageBanner.styles/ext.WikidataPageBanner.less',
	),
	'skinStyles' => array(
		'minerva' => 'ext.WikidataPageBanner.styles/ext.WikidataPageBanner.minerva.less'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'WikidataPageBanner/resources',
	'targets' => array( 'desktop', 'mobile' ),
	'position' => 'top'
);

$wgResourceModules['ext.WikidataPageBanner.toc.styles'] = array(
	'styles' => array(
		'ext.WikidataPageBanner.toc.styles/ext.WikidataPageBanner.toc.less',
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'WikidataPageBanner/resources',
	'skinStyles' => array(
		'minerva' => 'ext.WikidataPageBanner.toc.styles/ext.WikidataPageBanner.toc.minerva.less'
	),
	'targets' => array( 'desktop', 'mobile' ),
	'position' => 'top'
);
