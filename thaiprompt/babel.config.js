/**
 * Babel config — Expo SDK 57
 *
 * - babel-preset-expo ใส่ react-native-worklets/plugin (Reanimated 4) ให้อัตโนมัติแล้ว
 *   ห้ามใส่ "react-native-reanimated/plugin" ซ้ำ
 * - nativewind/babel = NativeWind v2 (แปลง className เป็น style ตอน build)
 * - production bundle: ตัด console.log / info / debug ออก เก็บ console.error / warn ไว้
 */
module.exports = function (api) {
  // ใช้กติกาเดียวกับ babel-preset-expo (getIsProd): เชื่อ Metro caller ก่อน แล้วค่อยดู env
  const isProduction = api.caller((caller) =>
    caller && caller.isDev != null
      ? caller.isDev === false
      : process.env.BABEL_ENV === 'production' || process.env.NODE_ENV === 'production'
  );

  const plugins = ['nativewind/babel'];

  if (isProduction) {
    plugins.push(['transform-remove-console', { exclude: ['error', 'warn'] }]);
  }

  return {
    presets: ['babel-preset-expo'],
    plugins,
  };
};
