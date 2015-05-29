module.exports = function ( grunt ) {
  grunt.loadNpmTasks( 'grunt-banana-checker' );
  grunt.loadNpmTasks( 'grunt-contrib-jshint' );

  grunt.initConfig( {
    jshint: {
      options: {
        jshintrc: true
      },
      all: [
        'Gruntfile.js',
        'resources',
      ],
    },
    banana: {
      all: 'i18n/'
    },
  } );

  grunt.registerTask( 'test', [ 'jshint', 'banana' ] );
  grunt.registerTask( 'default', 'test' );
};
