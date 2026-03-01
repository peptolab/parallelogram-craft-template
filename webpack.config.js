const path = require('path');
const { EsbuildPlugin } = require('esbuild-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    mode: 'production',
    entry: {
        app: [
            './asset/js/core/App.js',
            './asset/js/app.js',
            './asset/css/app.scss'
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
            new EsbuildPlugin({
                target: 'es2018',
            }),
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '../css/[name].css',
        })
    ]
};