const { getConfig } = require('@craftcms/webpack');

module.exports = getConfig({
  type: null,
  context: __dirname,
  config: {
    entry: {
      'compose-share': './compose-share.js',
      'social-media-connect': './social-media-connect.scss',
    },
  },
});
