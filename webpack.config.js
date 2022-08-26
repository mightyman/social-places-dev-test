const Encore = require('@symfony/webpack-encore');
const VuetifyLoaderPlugin = require('vuetify-loader/lib/plugin');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

if (!Encore.isProduction()) {
    Encore
        .setOutputPath('public/build/')
        .setPublicPath('/build');
} else {
    Encore
        .setOutputPath('public/dist/')
        .setPublicPath('/dist');
}
Encore.addEntry('app-build', './assets/js/app-build.js')
    .enableVueLoader(() => {
    }, {
        useJsx: true,
        runtimeCompilerBuild: true,
    }).configureBabel(function (babelConfig) {
    babelConfig.plugins.push('@babel/plugin-transform-runtime');
    babelConfig.plugins.push('@babel/plugin-proposal-class-properties');
}).splitEntryChunks().configureSplitChunks(function (splitChunks) {
    splitChunks.cacheGroups = {
        default: false,
        vendors: false,
        vendor: {
            name: 'vendor',
            chunks: 'all',
            test: /node_modules/,
        },
    };
})
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
    // add VuetifyLoaderPlugin: This will load all the Vuetify components
    .addPlugin(new VuetifyLoaderPlugin())
    // enables Sass/SCSS support
    .enableSassLoader(options => {
        options.implementation = require('sass');
    }, {resolveUrlLoader: false})
    .configureWatchOptions(watchOptions => {
        watchOptions.poll = 250; // check for changes every 250 milliseconds
    })
    .addLoader({
        test: /\.worker\.js$/,
        loader: 'worker-loader',
        // inline: true because otherwise it will not work with the webpack-dev-server
        options: {inline: 'fallback'},
    })
    .configureDevServerOptions(options => {
        options.noInfo = true;
    })
// uncomment if you use TypeScript
// .enableTypeScriptLoader()

// uncomment to get integrity="..." attributes on your script & link tags
// requires WebpackEncoreBundle 1.4 or higher
//.enableIntegrityHashes(Encore.isProduction())

// uncomment if you use API Platform Admin (composer req api-admin)
//.enableReactPreset()
//.addEntry('admin', './assets/js/admin.js')
;
const config = Encore.getWebpackConfig();
config.resolve.alias['~'] = path.resolve(__dirname, 'assets/js');
config.resolve.alias['#'] = path.resolve(__dirname, 'assets/css');
config.resolve.alias['vue$'] = path.resolve(__dirname, 'node_modules/vue/dist/vue.runtime.esm.js');
config.output.globalObject = '(self || this)';
module.exports = config;
