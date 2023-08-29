const path = require('path');
const { ProvidePlugin, DefinePlugin } = require('webpack');
const FileManagerPlugin = require('filemanager-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const dist = process.env.DIST;
const { GitRevisionPlugin } = require('git-revision-webpack-plugin');
const gitRevisionPlugin = new GitRevisionPlugin();

module.exports = {
  mode: 'production',
  entry: {
    config: './assets/raw/plugin/index.tsx',
    bi: './assets/raw/bi/index.tsx',
  },
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: 'ts-loader',
      },
      {
        test: /\.(sa|sc|c)ss$/,
        use: ['style-loader', 'css-loader', 'sass-loader'],
      },
      {
        test: /\.(gif|png|jpe?g|svg)$/i,
        type: 'asset/resource',
        generator: {
          filename: 'images/flags/[contenthash][ext][query]',
        },
      },
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/,
        type: 'asset/resource',
        generator: {
          filename: 'fonts/[contenthash][ext][query]',
        },
      },
    ],
  },

  plugins: [
    new ProvidePlugin({
      process: 'process/browser',
    }),
    new DefinePlugin({
      VERSION: JSON.stringify(gitRevisionPlugin.version()),
    }),

    new FileManagerPlugin({
      events: {
        onEnd: {
          copy: [
            {
              source: path.resolve(__dirname, './node_modules/aesirx-bi-app/public/assets/images/'),
              destination: path.resolve(
                __dirname,
                `${dist}/images/`
              ),
            },
            {
              source: path.resolve(__dirname, './node_modules/aesirx-bi-app/public/assets/data/'),
              destination: path.resolve(__dirname, `${dist}/data/`),
            },
          ],
        },
      },
    }),

    new WebpackAssetsManifest({
      entrypoints: true,
    }),
  ],

  output: {
    filename: 'bi/[contenthash].js',
    clean: true,
  },

  optimization: {
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          compress: {
            drop_console: true,
          },
          format: {
            comments: false,
          },
        },
        extractComments: false,
      }),
    ],
    splitChunks: {
      chunks: 'all',
    },
  },
  resolve: {
    alias: {
      react$: require.resolve(path.resolve(__dirname, './node_modules/react')),
    },
    extensions: ['.tsx', '.ts', '.js'],
    fallback: { 'process/browser': require.resolve('process/browser') },
  },
};
