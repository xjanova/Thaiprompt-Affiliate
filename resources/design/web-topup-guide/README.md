# Web top-up guide image (FB page chat)

Source of `public/images/fortune/web-topup-guide.jpg`, sent by the page bot after it checks a slip a
web customer sent in the page chat (`JuntraChatSlipRelay::GUIDE_IMAGE_PATH`).

- No QR code and none of the words in `FacebookWebhookService::FB_WEB_ONLY_MONEY_PATTERN` on purpose
  (Facebook flags pages for payment QR / money words).
- `maemor.webp` = juntraweb `public/images/juntra/hero/maemor-2-600.webp` (copy it next to `guide.html`).
- Render 1080x1350 with headless Edge/Chrome:
  `msedge --headless=new --hide-scrollbars --force-device-scale-factor=1 --window-size=1080,1350 --virtual-time-budget=10000 --screenshot=guide.png guide.html`
  then `ffmpeg -i guide.png -q:v 3 web-topup-guide.jpg`.
- After replacing the image, bump `?v=` in `GUIDE_IMAGE_PATH` (Facebook caches images by URL).
