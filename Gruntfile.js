'use strict';
module.exports = function(grunt) {
    var pkg = grunt.file.readJSON('package.json');

    grunt.initConfig({
        // setting folder templates
        dirs: {
            css: 'assets/css'
        },

        // Generate POT files.
        makepot: {
            target: {
                options: {
                    exclude: ['build/.*'],
                    domainPath: '/languages/', // Where to save the POT file.
                    potFilename: pkg.slug + '.pot', // Name of the POT file.
                    type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
                    potHeaders: {
                        'report-msgid-bugs-to': 'support@wedevs.com',
                        'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
                    }
                }
            }
        },

        // Clean up build directory
        clean: {
            main: ['build/']
        },

        // Copy the plugin into the build directory
        copy: {
            main: {
                src: [
                    '**',
                    '!node_modules/**',
                    '!build/**',
                    '!bin/**',
                    '!.git/**',
                    '!Gruntfile.js',
                    '!package.json',
                    '!debug.log',
                    '!phpunit.xml',
                    '!.gitignore',
                    '!.gitmodules',
                    '!npm-debug.log',
                    '!plugin-deploy.sh',
                    '!assets/less/**',
                    '!tests/**',
                    '!**/Gruntfile.js',
                    '!**/package.json',
                    '!**/README.md',
                    '!**/*~'
                ],
                dest: 'build/'
            }
        },

        //Compress build directory into <name>.zip and <name>-<version>.zip
        compress: {
            main: {
                options: {
                    mode: 'zip',
                    archive: './build/'+ pkg.slug + '-v' + pkg.version + '.zip'
                },
                expand: true,
                cwd: 'build/',
                src: ['**/*'],
                dest: pkg.slug
            }
        },

        replace: {
            version_no: {
                src: pkg.slug + '.php',
                overwrite: true,
                replacements: [{
                        from: /Version:\s*(.*)/,
                        to: 'Version: ' + pkg.version
                }]
            }
        }

    });

    // Load NPM tasks to be used here
    grunt.loadNpmTasks( 'grunt-wp-i18n' );
    grunt.loadNpmTasks( 'grunt-contrib-clean' );
    grunt.loadNpmTasks( 'grunt-contrib-copy' );
    grunt.loadNpmTasks( 'grunt-contrib-compress' );
    grunt.loadNpmTasks( 'grunt-text-replace' );

    grunt.registerTask( 'release', [
        'replace',
        'makepot',
        'clean',
        'copy',
        'compress'
    ]);

    grunt.registerTask( 'zip', [
        'clean',
        'copy',
        'compress'
    ])
};