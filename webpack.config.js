const path = require('path');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';

    return {
        entry: ['./src/index.js', 'bootstrap'],
        output: {
            filename: 'bundle.js',
            path: path.resolve(__dirname, 'dist')
        },
        devtool: isProduction ? 'source-map' : 'eval-source-map',
        // other configurations...
    };
};