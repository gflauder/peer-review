const mix = require('laravel-mix');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const fs = require('fs');
const path = require('path');
const webpack = require('webpack');

mix.setPublicPath('dist');
mix.webpackConfig({
    plugins: [
        new CleanWebpackPlugin(),
        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
            'window.jQuery': 'jquery'
        })
    ]
});

mix.js([
    'src/main.js',
 /*   'src/pyritephp.js',*/
    'node_modules/selectize/dist/js/standalone/selectize.min.js',
    'node_modules/@popperjs/core/dist/umd/popper.min.js'
], 'dist/js/main.js')
    .sourceMaps()
    .version();

mix.styles([
    'src/bootstrap-feedback-left.css',
    'src/bootstrap-modal-vertical.css',
    'src/inline-labels.css',
    'src/main.css',
    'src/print.css',
    'src/pyritephp.css',
 /*   'src/sbadmin2.css',*/
], 'dist/css/main.css')
    .sourceMaps()
    .version();

mix.js('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'dist/js/bootstrap.js')
    .sass('node_modules/bootstrap/scss/bootstrap.scss', 'dist/css/bootstrap.css')
    .sourceMaps()
    .version();

mix.then(() => {
    const manifestPath = path.join(__dirname, 'dist', 'mix-manifest.json');
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf-8'));

    const updatedManifest = {};
    for (const [key, value] of Object.entries(manifest)) {
        updatedManifest[`/dist${key}`] = `/dist${value}`;
    }

    fs.writeFileSync(manifestPath, JSON.stringify(updatedManifest, null, 2));
});

mix.browserSync({
    proxy: 'http://localhost:80',
    files: [
        'dist/js/*.js',
        'dist/css/*.css',
        'templates/**/*.html',
        'src/**/*.js',
        'src/**/*.css'
    ],
    open: false,
    host: '0.0.0.0',
    port: 3000,
    ui: {
        port: 3001
    }
});