module.exports = function (api) {
  api.cache(true);
  return {
    presets: ["babel-preset-expo"],
    plugins: [
      "nativewind/babel",
      // reanimated/plugin ต้องอยู่สุดท้ายเสมอ
      "react-native-reanimated/plugin",
    ],
  };
};
