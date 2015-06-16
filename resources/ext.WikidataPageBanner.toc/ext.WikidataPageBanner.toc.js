( function( mw, $ ) {
	// FIXME! - Deal with flashing content on changing toc position,
	// See https://phabricator.wikimedia.org/T103569
	// if bannertoc property is set and the window is large enough for a toc, add it to banner
	if ( mw.config.get( 'wgWPBToc' ) && $( window ).width() > 768 ) {
		$( '.toc' ).detach().removeAttr( 'id class' ).addClass( 'wpb-banner-toc' )
			.appendTo( '.topbanner-toc' );
	}
}( mediaWiki, jQuery ) );
