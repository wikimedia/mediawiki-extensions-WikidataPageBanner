( function () {
	QUnit.module( 'ext.WikidataPageBanner.positionBanner', QUnit.newMwEnvironment() );
	QUnit.test( 'testFocus', function ( assert ) {
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

		// Origin = 0,0
		this.$wpbBannerImage.data( 'pos-x', 0 );
		this.$wpbBannerImage.data( 'pos-y', 0 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-150px',
			'Banner left should shift -150px for focus' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '-100px',
			'Banner top should shift -100px for focus' );

		// Origin = -1,-1
		this.$wpbBannerImage.data( 'pos-x', -1 );
		this.$wpbBannerImage.data( 'pos-y', -1 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '0px',
			'Banner left should not have a margin for origin -1,-1' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '-200px',
			'Banner top should be 200px down for origin -1,-1' );

		// Origin = 0.5, undefined
		this.$wpbBannerImage.data( 'pos-x', 0.5 );
		this.$wpbBannerImage.removeData( 'pos-y' );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-300px',
			'Banner left should shift -300px for focus' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '0px',
			'Banner top should default to 0px if no Y offset provided' );

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

		// Origin = 0,1
		this.$wpbBannerImage.data( 'pos-x', 0 );
		this.$wpbBannerImage.data( 'pos-y', 1 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-200px',
			'Banner left should shift -200px with origin 0,1' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '0px',
			'Banner top should shift 0px with origin 0,1' );

		// Origin = 0.2,-1
		this.$wpbBannerImage.data( 'pos-x', 0.2 );
		this.$wpbBannerImage.data( 'pos-y', -1 );
		mw.wpb.positionBanner( this.$wpbBannerImageContainer );
		assert.equal( this.$wpbBannerImage.css( 'margin-left' ), '-390px',
			'Banner left should shift -390px with origin 0.2,-1' );
		assert.equal( this.$wpbBannerImage.css( 'margin-top' ), '-100px',
			'Banner top should shift -100px with origin 0.2,-1' );

	} );
}() );
