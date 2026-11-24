module.exports = function (grunt) {
    // Load all grunt tasks
    require('load-grunt-tasks')(grunt);

    // Project configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Task for checking text domain
        checktextdomain: {
            options: {
                text_domain: ['woo-open-graph'],
                keywords: [
                    '__:1,2d',
                    '_e:1,2d',
                    '_x:1,2c,3d',
                    'esc_html__:1,2d',
                    'esc_html_e:1,2d',
                    'esc_html_x:1,2c,3d',
                    'esc_attr__:1,2d',
                    'esc_attr_e:1,2d',
                    'esc_attr_x:1,2c,3d',
                    '_ex:1,2c,3d',
                    '_n:1,2,4d',
                    '_nx:1,2,4c,5d',
                    '_n_noop:1,2,3d',
                    '_nx_noop:1,2,3c,4d'
                ],
            },
            files: {
                src: [
                    '*.php',
                    '**/*.php',
                    '!node_modules/**',
                    '!tests/**'
                ],
                expand: true
            },
        },

        // Task for watching PHP text-domain changes
        watch: {
            php: {
                files: ['**/*.php'],
                tasks: ['checktextdomain'],
            },
        },

        shell: {
            wpcli: {
                command: 'wp i18n make-pot . languages/woo-open-graph.pot',
            }
        },

        // Task for generating POT file
        makepot: {
            target: {
                options: {
                    domainPath: '/languages',
                    exclude: ['node_modules/.*'],
                    mainFile: 'open-graph-for-woocommerce.php',
                    potFilename: 'woo-open-graph.pot',
                    potHeaders: {
                        poedit: true,
                        'x-poedit-keywordslist': true
                    },
                    type: 'wp-plugin',
                    updateTimestamp: true
                }
            }
        }
    });

    // Load the plugins
    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-checktextdomain');
    grunt.loadNpmTasks('grunt-shell');

    // Register default tasks
    grunt.registerTask('default', ['checktextdomain', 'shell', 'watch']);

    // Release zip: bin/build-release.sh (WordPress.org folder standard).
};
