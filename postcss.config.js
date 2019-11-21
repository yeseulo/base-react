const isDevelopment = process.env.NODE_ENV !== "production";

module.exports = {
  plugins: {
    "autoprefixer": {},
    "postcss-preset-env": {
      autoprefixer: { grid: true },
      stage: 1,
      features: {
        "nesting-rules": true
      },
      warnForDuplicates: false,
      map: true,
      remove: false,
      browsers: ["last 2 versions", "> 1%", "IE 11"]
    },
    "css-mqpacker": {},
    cssnano: isDevelopment
      ? false
      : {
          reduceIdents: false,
          zindex: false,
          preset: [
            "default",
            {
              discardComments: {
                removeAll: true
              }
            }
          ]
        }
  }
};
