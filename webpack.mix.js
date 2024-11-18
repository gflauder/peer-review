const mix = require('laravel-mix');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const fs = require('fs');
const path = require('path');
const webpack = require('webpack');

mix.setPublicPath('dist');
mix.webpackConfig({
    plugins: [
        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
            'window.jQuery': 'jquery'
        }),

        new CleanWebpackPlugin({
            cleanOnceBeforeBuildPatterns: ['**/*'], // This will clean all files in the dist folder
        }),
    ]
});

mix.js([
    'src/main.js',
    'src/pyritephp.js',
    'node_modules/@selectize/selectize/dist/js/selectize.js',
    'node_modules/jquery-ui/ui/widgets/sortable.js'
], 'dist/js/main.js')
    .sourceMaps()
    .version();

mix.styles([
    'src/bootstrap-feedback-left.css',
    'src/bootstrap-modal-vertical.css',
    'node_modules/@selectize/selectize/dist/css/selectize.bootstrap5.css',
    'src/inline-labels.css',
    'src/main.css',
    'src/print.css',
    'src/pyritephp.css',
    'src/style.css',
], 'dist/css/main.css')
    .sourceMaps()
    .version();

mix.sass('node_modules/bootstrap/scss/bootstrap.scss', 'dist/css/bootstrap.css')
    .sourceMaps()
    .version();

mix.copyDirectory('src/images', 'dist/images');

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