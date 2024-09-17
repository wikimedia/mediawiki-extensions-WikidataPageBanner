( function () {
	var $wpbBannerImageContainer = $( '.wpb-topbanner' ),
		$img = $( 'img.wpb-banner-image' );

	/**
	 * Calculate the required container margin for a given image and offset.
	 * This is a generalized function for both left and top margins, but becuase
	 * in the vertical direction the values are inverted,
	 * it's necessary to know which is being caculated (i.e. with isVert).
	 *
	 * @param {boolean} isVert Whether the margin being calculated is left (false) or top (true).
	 * @param {number} image The image dimension.
	 * @param {number} container The container dimension.
	 * @param {number} offset The offset, between -1 and 1.
	 * @return {number} The calculated left or top margin.
	 */
	function getMargin( isVert, image, container, offset ) {
		// The max that the image can be moved without leaving blank space.
		var max = image - container;
		// Move the origin to the top or left of the space,
		// and turn it into a value between 0 and 1 (instead of between 1 and -1).
		var offsetRatio = ( ( isVert ? 1 : -1 ) * offset + 1 ) / 2;
		// Calculate the offset to the origin center of the image.
		var offsetToCenter = image * offsetRatio;
		// Subtract half the container dimension to get the offset to edge of banner.
		var margin = offsetToCenter - ( container / 2 );
		// Constrain the offset to be within the allowed bounds.
		var finalMargin = margin;
		if ( margin > max ) {
			finalMargin = max;
		} else if ( margin < 0 ) {
			// The minimum margin is 0px.
			finalMargin = 0;
		}
		return finalMargin;
	}

	function positionBanner( $container ) {
		/**
		 * Javascript to fine tune position of banner according to position coordinates.
		 */
		// extract position parameters
		var $wpbBannerImage = $container.find( '.wpb-banner-image' ),
			totalOffsetX = 0,
			totalOffsetY = 0,
			containerWidth = $container.width(),
			containerHeight = $container.height(),
			centerX = $wpbBannerImage.data( 'pos-x' ),
			centerY = $wpbBannerImage.data( 'pos-y' );

		// Safari has a bug where when you use width: 100%; height:auto,
		// it may return a fractional size for the image.
		// While this doesn't overflow the container,
		// it does mess up the bannerImgHeight > containerHeight comparison below, so floor()
		var bannerImgHeight = Math.floor( $wpbBannerImage.height() );
		var bannerImgWidth = Math.floor( $wpbBannerImage.width() );

		// reset translations applied by css
		$wpbBannerImage.css( {
			transform: 'translate(0)',
			MozTransform: 'translate(0)',
			WebkitTransform: 'translate(0)',
			msTransform: 'translate(0)',
			'margin-left': 0,
			'margin-top': 0
		} );

		// Adjust vertical focus
		if ( bannerImgHeight > containerHeight && centerY !== undefined ) {
			totalOffsetY = getMargin(
				false, bannerImgHeight, containerHeight, centerY
			);
		}

		// Adjust horizontal focus
		if ( bannerImgWidth > containerWidth ) {
			if ( centerX === undefined && $container.hasClass( 'wpb-banner-image-panorama' ) ) {
				// adjust panoramas
				centerX = -0.25;
				centerY = 0;
			}

			// Handle editor specified coordinates
			if ( centerX !== undefined ) {
				totalOffsetX = getMargin(
					true, bannerImgWidth, containerWidth, centerX
				);
			}
		} else if ( bannerImgHeight > containerHeight && centerY === undefined ) {
			// We are likely to be using a stretched portait photo
			// so if none defined default to -10%
			totalOffsetY = containerWidth / 10;
		}
		// shift the banner horizontally and vertically by the offsets calculated above
		$wpbBannerImage.css( {
			'margin-top': -totalOffsetY,
			'margin-left': -totalOffsetX
		} );
	}
	$( window ).on( 'resize', mw.util.debounce(
		100,
		function () {
			positionBanner( $wpbBannerImageContainer );
		}
	) );
	// set focus after image has loaded
	$img.on( 'load', function () {
		positionBanner( $wpbBannerImageContainer );
		$wpbBannerImageContainer.addClass( 'wpb-positioned-banner' );
	} );
	// Image might be cached
	if ( $img.length && $img[ 0 ].complete ) {
		positionBanner( $wpbBannerImageContainer );
		$wpbBannerImageContainer.addClass( 'wpb-positioned-banner' );
	}
	// Expose interface for testing.
	mw.wpb = {
		positionBanner: positionBanner
	};
}() );
