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
        },

        // Task for cleaning dist directory
        clean: {
            dist: ['dist'],
            postbuild: ['dist/open-graph-for-woocommerce'],
        },

        // Task for copying files to dist.
        // The plugin enqueues its unminified sources directly (no build-time
        // minification), so the shipped file set equals the loaded file set:
        // every CSS/JS asset copied here is one the plugin actually enqueues.
        copy: {
            dist: {
                src: [
                    '**',
                    '!node_modules/**',
                    '!.git/**',
                    '!.gitignore',
                    '!.gitattributes',
                    '!dist/**',
                    '!build/**',
                    '!.DS_Store',
                    '!**/.DS_Store',
                    '!npm-debug.log',
                    '!package.json',
                    '!package-lock.json',
                    '!Gruntfile.js',
                    '!gruntfile.js',
                    '!phpcs.xml',
                    '!composer.json',
                    '!composer.lock',
                    '!*.zip',
                    '!readme.md',
                    '!**/*.md',
                    '!plugin-check-report.json',
                    '!.distignore',
                    '!.github/**',
                    '!bin/**',
                    '!**/*.map',
                ],
                dest: 'dist/open-graph-for-woocommerce/',
            },
        },

        // Task for creating ZIP file
        compress: {
            dist: {
                options: {
                    archive: 'dist/open-graph-for-woocommerce-<%= pkg.version %>.zip',
                },
                files: [
                    {
                        cwd: 'dist/open-graph-for-woocommerce/',
                        src: ['**'],
                        dest: 'open-graph-for-woocommerce/',
                    },
                ],
            },
        }
    });

    // Load the plugins
    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-checktextdomain');
    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-compress');

    // Register default tasks
    grunt.registerTask('default', ['checktextdomain', 'shell', 'watch']);

    // Register build task for WordPress.org distribution
    grunt.registerTask('build', [
        'clean:dist',
        'shell',
        'copy:dist',
        'compress:dist',
        'clean:postbuild'
    ]);
};
