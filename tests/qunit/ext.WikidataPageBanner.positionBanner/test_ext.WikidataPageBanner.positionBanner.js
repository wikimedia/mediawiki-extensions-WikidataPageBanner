( function ( mw, $ ) {
	QUnit.module( 'ext.WikidataPageBanner.positionBanner', QUnit.newMwEnvironment() );
	QUnit.test( 'testFocus', function ( assert ) {
		assert.expect( 10 );
		this.$wpbBannerImageContainer = $( '<div/>', {
			width: 600,
			height: 300
		} );
		this.$wpbBannerImage = $( '<img/>', {
			class: 'wpb-banner-image wpb-banner-image-panorama',
			width: 900,
			height: 500
		} );
		this.$wpbBannerImageContainer.append( this.$wpbBannerImage );
		// set test focus points
		this.$wpbBannerImage.data( 'pos-x', 0 );
		this.$wpbBannerImage.data( 'pos-y', 0 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-150px',
			'Banner left should shift -150px for focus' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '-100px',
			'Banner top should shift -100px for focus' );

		// set test focus points
		// this case tests the case where origin is ignored because a positive margin would appear
		this.$wpbBannerImage.data( 'pos-x', -1 );
		this.$wpbBannerImage.data( 'pos-y', -1 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '0px',
			'Banner left should not leave positive margin for focus' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '0px',
			'Banner top should not leave positive margin for focus' );

		// set test focus points
		this.$wpbBannerImage.data( 'pos-x', 0.5 );
		this.$wpbBannerImage.data( 'pos-y', undefined );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-300px',
			'Banner left should shift -300px for focus' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '0px',
			'Banner top should not leave positive margin for focus' );

		this.$wpbBannerImageContainer = $( '<div/>', {
			width: 1500,
			height: 300
		} );
		this.$wpbBannerImage = $( '<img/>', {
			class: 'wpb-banner-image',
			width: 1900,
			height: 400
		} );
		this.$wpbBannerImageContainer.append( this.$wpbBannerImage );
		// set test focus points
		// position in vertical direction is ignored after a limit because of too much negative
		// margin
		this.$wpbBannerImage.data( 'pos-x', 0 );
		this.$wpbBannerImage.data( 'pos-y', 1 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-200px',
			'Banner left should shift -390px for focus' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '-100px',
			'Banner top should shift -100px only for to not leave too much negative margin' );

		// set test focus points
		// Position in vertical direction is ignored because of positive margin
		this.$wpbBannerImage.data( 'pos-x', 0.2 );
		this.$wpbBannerImage.data( 'pos-y', -1 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-390px',
			'Banner left should shift -240px for focus' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '0px',
			'Banner top should not leave positive margin for focus' );

	} );
}( mediaWiki, jQuery ) );
