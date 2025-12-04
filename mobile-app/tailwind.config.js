/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/**/*.{js,jsx,ts,tsx}",
    "./components/**/*.{js,jsx,ts,tsx}",
  ],
  presets: [require("nativewind/preset")],
  theme: {
    extend: {
      colors: {
        // ========================================
        // 🔥 LAVA LAMP THEME + SHOPPING COLORS
        // ========================================

        // Primary - Coral/Orange (Shopping CTA)
        primary: {
          DEFAULT: "#FF6B35",
          50: "#FFF5F0",
          100: "#FFE8DB",
          200: "#FFD4BD",
          300: "#FFBA94",
          400: "#FF9966",
          500: "#FF6B35",
          600: "#E5561F",
          700: "#CC4A16",
          800: "#A33D12",
          900: "#7A2E0D",
        },

        // Secondary - Teal (Success/Action)
        secondary: {
          DEFAULT: "#2EC4B6",
          50: "#E6FAF8",
          100: "#C2F4EF",
          200: "#9AEDE4",
          300: "#6DE4D7",
          400: "#45DBC9",
          500: "#2EC4B6",
          600: "#25A89C",
          700: "#1D8A81",
          800: "#156C65",
          900: "#0E4F4A",
        },

        // Accent - Purple (Highlights)
        accent: {
          DEFAULT: "#7B2CBF",
          50: "#F5EAFA",
          100: "#E8D0F4",
          200: "#D3A6E9",
          300: "#BD7BDD",
          400: "#A651D2",
          500: "#7B2CBF",
          600: "#6523A3",
          700: "#501B87",
          800: "#3B146B",
          900: "#260D4F",
        },

        // Lava - Warm Gradient Colors
        lava: {
          red: "#FF6B6B",
          orange: "#FF8E53",
          yellow: "#FFE66D",
          coral: "#FF7F7F",
          pink: "#FF9A9E",
        },

        // Shopping Theme
        shop: {
          sale: "#FF4757",      // Sale/Discount
          gold: "#FFD700",      // Premium
          green: "#2ED573",     // In Stock
          gray: "#747D8C",      // Neutral
        },

        // Dark Theme Background (Lava Dark)
        dark: {
          DEFAULT: "#0F0F23",
          50: "#1A1A2E",
          100: "#16213E",
          200: "#1F2937",
          300: "#374151",
          400: "#4B5563",
          500: "#6B7280",
        },

        // Glass Effect Colors
        glass: {
          light: "rgba(255, 255, 255, 0.15)",
          dark: "rgba(0, 0, 0, 0.25)",
          white: "rgba(255, 255, 255, 0.25)",
          black: "rgba(0, 0, 0, 0.5)",
        },

        // Status Colors
        success: "#2ED573",
        warning: "#FFA502",
        error: "#FF4757",
        info: "#1E90FF",
      },

      // Gradients (เรียกใช้ใน Linear Gradient)
      gradientColorStops: {
        'lava-start': '#FF6B6B',
        'lava-mid': '#FF8E53',
        'lava-end': '#FFE66D',
        'ocean-start': '#4ECDC4',
        'ocean-end': '#556270',
        'sunset-start': '#FF6B35',
        'sunset-mid': '#7B2CBF',
        'sunset-end': '#2EC4B6',
      },

      fontFamily: {
        sans: ["OpenSans", "System"],
        heading: ["OpenSans-Bold", "System"],
        semibold: ["OpenSans-SemiBold", "System"],
      },

      borderRadius: {
        "4xl": "2rem",
        "5xl": "2.5rem",
      },

      boxShadow: {
        'lava': '0 4px 30px rgba(255, 107, 107, 0.3)',
        'glow': '0 0 20px rgba(255, 107, 53, 0.5)',
        'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.37)',
      },

      animation: {
        "pulse-slow": "pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite",
        "bounce-slow": "bounce 2s infinite",
        "blob": "blob 7s infinite",
        "glow": "glow 2s ease-in-out infinite alternate",
        "float": "float 3s ease-in-out infinite",
        "shimmer": "shimmer 2s linear infinite",
      },

      keyframes: {
        blob: {
          "0%": {
            transform: "translate(0px, 0px) scale(1)",
          },
          "33%": {
            transform: "translate(30px, -50px) scale(1.1)",
          },
          "66%": {
            transform: "translate(-20px, 20px) scale(0.9)",
          },
          "100%": {
            transform: "translate(0px, 0px) scale(1)",
          },
        },
        glow: {
          "from": {
            boxShadow: "0 0 10px #FF6B6B, 0 0 20px #FF6B6B, 0 0 30px #FF6B6B",
          },
          "to": {
            boxShadow: "0 0 20px #FF8E53, 0 0 30px #FF8E53, 0 0 40px #FFE66D",
          },
        },
        float: {
          "0%, 100%": { transform: "translateY(0)" },
          "50%": { transform: "translateY(-10px)" },
        },
        shimmer: {
          "0%": { backgroundPosition: "-1000px 0" },
          "100%": { backgroundPosition: "1000px 0" },
        },
      },

      backdropBlur: {
        xs: "2px",
      },
    },
  },
  plugins: [],
};
