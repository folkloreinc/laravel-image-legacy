const path = require('path');
const webpack = require('webpack');
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

const contextPath = path.join(__dirname, './js/src');
const outputPath = path.join(__dirname, './js/dist');
const publicPath = '/';

module.exports = () => ({

    context: contextPath,

    entry: './index',

    output: {
        path: outputPath,
        publicPath,
        filename: 'image.js',
        jsonpFunction: 'flklrJsonp',
        libraryTarget: 'umd',
        library: 'LaravelImage',
    },

    module: {
        rules: [
            {
                test: /\.jsx?$/,
                loader: 'babel-loader',
                exclude: /node_modules/,
                options: {
                    forceEnv: 'dist',
                    cacheDirectory: true,
                },
            },
        ],
    },

    plugins: [
        new webpack.DefinePlugin({
            'process.env.NODE_ENV': JSON.stringify('production'),
            __DEV__: JSON.stringify(false),
        }),
        new webpack.optimize.ModuleConcatenationPlugin(),
        new UglifyJSPlugin({
            uglifyOptions: {
                beautify: false,
                sourceMap: true,
                mangle: {
                    keep_fnames: true,
                },
                compress: {
                    warnings: false,
                },
                comments: false,
            },
        }),
        new webpack.SourceMapDevToolPlugin({
            filename: '[file].map',
            exclude: [/vendor\//],
        }),
    ],

    resolve: {
        extensions: ['.js', '.es6'],
        modules: [
            path.join(__dirname, './node_modules'),
            path.join(__dirname, './web_modules'),
            path.join(__dirname, './bower_components'),
        ],
    },

    stats: {
        colors: true,
        modules: true,
        reasons: true,
        modulesSort: 'size',
        children: true,
        chunks: true,
        chunkModules: true,
        chunkOrigins: true,
        chunksSort: 'size',
    },

    performance: {
        maxAssetSize: 100000,
        maxEntrypointSize: 300000,
    },

    cache: true,
    watch: false,
});
