const { getConfig } = require('@craftcms/webpack');

module.exports = getConfig({
  type: null,
  context: __dirname,
  config: {
    entry: {
      'compose-share': './compose-share.js',
      'entry-share-counter': './entry-share-counter.js',
      'social-media-connect': './social-media-connect.scss',
      'account-index': './account-index.js',
    },
  },
});
