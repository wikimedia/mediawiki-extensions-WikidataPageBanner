( function ( mw, $ ) {
	QUnit.module( 'ext.WikidataPageBanner.positionBanner', QUnit.newMwEnvironment() );
	QUnit.test( 'testFocus', 6, function( assert ) {
		this.$wpbBannerImageContainer = $( '<div/>', {
			width: 600,
			height: 300
		} );
		this.$wpbBannerImage = $( '<img/>', {
			class: 'wpb-banner-image',
			width: 900,
			height: 500
		} );
		this.$wpbBannerImageContainer.append( this.$wpbBannerImage );
		// set test focus points
		this.$wpbBannerImage.data( 'pos-x', 0 );
		this.$wpbBannerImage.data( 'pos-y', 0 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-150px' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '-100px' );

		// set test focus points
		this.$wpbBannerImage.data( 'pos-x', -1 );
		this.$wpbBannerImage.data( 'pos-y', -1 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '0px' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '0px' );

		// set test focus points
		this.$wpbBannerImage.data( 'pos-x', 0.5 );
		this.$wpbBannerImage.data( 'pos-y', undefined );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-300px' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '0px' );
	} );
} )( mediaWiki, jQuery );
