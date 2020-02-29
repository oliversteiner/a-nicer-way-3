module.exports = {
  devServer: {
    proxy: 'http://a-nicer-view.test'
  },

  // output built static files to Laravel's public dir.
  // note the "build" script in package.json needs to be modified as well.
  outputDir: '../../public/assets/presentation',

  publicPath: process.env.NODE_ENV === 'production'
    ? '/assets/presentation/'
    : '/',

  // modify the location of the generated HTML file.
  indexPath: process.env.NODE_ENV === 'production'
    ? '../../../resources/views/presentation.blade.php'
    : 'index.html'
}
