/* eslint-env node */

module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		banana: {
			all: 'i18n/'
		},
		eslint: {
			options: {
				cache: true
			},
			all: [
				'resources/**/*.js',
				'**/*.json',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		stylelint: {
			all: [
				'**/*.less',
				'!node_modules/**',
				'!vendor/**'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'banana', 'stylelint' ] );
	grunt.registerTask( 'default', 'test' );
};
