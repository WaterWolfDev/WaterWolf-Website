const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    entry: [
        './frontend/less/style.less'
    ],
    plugins: [
        new MiniCssExtractPlugin()
    ],
    output: {
        path: path.resolve(__dirname, './web/static/dist')
    },
    devServer: {
        static: path.resolve(__dirname, './web/static/dist'),
        port: 8080,
        hot: true
    },

    module: {
        rules: [
            {
                test: /\.less$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'less-loader'
                ],
            },
            {
                test: /\.css$/,
                exclude: /node_modules/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                ],
            },
        ]
    },
}
