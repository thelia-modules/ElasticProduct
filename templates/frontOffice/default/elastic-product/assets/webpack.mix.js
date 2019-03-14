const webpack = require("webpack");
let mix = require("laravel-mix");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.version();

mix.setPublicPath("dist");

mix.js("src/js/app.js", "dist/js");

mix.sass("src/scss/app.scss", "dist/css").options({
    processCssUrls: false,
    postCss: [
        require("postcss-custom-properties"),
        require("autoprefixer")({
            browsers: ["> 0.5%", "last 3 versions"],
            cascade: false
        })
    ]
});

mix.webpackConfig({
    module: {
        rules: [
            {
                test: /\.scss/,
                loader: "import-glob"
            }
        ]
    }
});

mix.disableNotifications();
