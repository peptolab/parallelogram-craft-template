const path = require('path');
const TerserJSPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    mode: 'production',
    entry: {
        xpace: [
            './asset/js/core/App.js',
            './asset/js/xpace.js',
            './asset/css/xpace.scss'
        ]
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'web/cms/js'),
        chunkFilename: '[name].js',
    },
    resolve: {
        alias: {
            '@components': path.resolve(__dirname, 'asset/js/components'),
            '@core': path.resolve(__dirname, 'asset/js/core'),
        },
        conditionNames: ['development', 'import', 'module', 'default']
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        cacheDirectory: true,
                        presets: [
                            ['@babel/preset-env', {
                                targets: '> 0.25%, not dead'
                            }]
                        ],
                        plugins: [
                            ['@babel/transform-runtime']
                        ]
                    }
                }
            }, {
                test: /\.(scss)$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            url: false
                        }
                    },
                    'postcss-loader',
                    {
                        loader: 'sass-loader',
                        options: {
                            api: 'modern',
                            sassOptions: {
                                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
                                includePaths: ['node_modules'],
                                quietDeps: true
                            }
                        }
                    }
                ]
            }
        ]
    },
    optimization: {
        chunkIds: 'named',
        minimize: true,
        sideEffects: true,
        minimizer: [
            new TerserJSPlugin({
                terserOptions: {
                    compress: {
                        drop_console: false,
                    },
                },
                extractComments: false,
            }),
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '../css/[name].css',
        })
    ]
};
