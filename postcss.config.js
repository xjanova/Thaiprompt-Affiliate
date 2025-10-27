import { defineConfig } from 'postcss';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

export default defineConfig({
  plugins: [tailwindcss, autoprefixer],
});
