# Cryptocurrency Icons

This directory contains 222+ cryptocurrency icons in SVG format for use in trading dashboards and crypto-related features.

## 📊 Icon Coverage

### Major Cryptocurrencies
- **BTC** (Bitcoin) - btc.svg
- **ETH** (Ethereum) - eth.svg
- **BNB** (Binance Coin) - bnb.svg
- **XRP** (Ripple) - xrp.svg
- **ADA** (Cardano) - ada.svg
- **SOL** (Solana) - sol.svg
- **DOT** (Polkadot) - dot.svg
- **DOGE** (Dogecoin) - doge.svg
- **MATIC** (Polygon) - matic.svg
- **AVAX** (Avalanche) - avax.svg
- **LTC** (Litecoin) - ltc.svg
- **LINK** (Chainlink) - link.svg
- **TRX** (Tron) - trx.svg
- **UNI** (Uniswap) - uni.svg

### Stablecoins
- **USDT** (Tether) - usdt.svg
- **USDC** (USD Coin) - usdc.svg
- **BUSD** (Binance USD) - busd.svg
- **DAI** (Dai) - dai.svg
- **TUSD** (TrueUSD) - tusd.svg
- **USDD** (USDD) - usdd.svg
- **FRAX** (Frax) - frax.svg

### DeFi Tokens
- **AAVE** - aave.svg
- **MKR** (Maker) - mkr.svg
- **SUSHI** (SushiSwap) - sushi.svg
- **COMP** (Compound) - comp.svg
- **CRV** (Curve) - crv.svg
- **YFI** (Yearn Finance) - yfi.svg
- **SNX** (Synthetix) - snx.svg
- **1INCH** - 1inch.svg
- **CAKE** (PancakeSwap) - cake.svg

### Layer 2 & Scaling Solutions
- **ARB** (Arbitrum) - arb.svg
- **OP** (Optimism) - op.svg
- **IMX** (Immutable X) - imx.svg
- **BLUR** - blur.svg
- **LDO** (Lido) - ldo.svg

### Gaming & Metaverse
- **SAND** (The Sandbox) - sand.svg
- **MANA** (Decentraland) - mana.svg
- **ENJ** (Enjin) - enj.svg
- **CHZ** (Chiliz) - chz.svg
- **GMT** - gmt.svg
- **MAGIC** - magic.svg

### AI & Data Tokens
- **AGIX** (SingularityNET) - agix.svg
- **FET** (Fetch.ai) - fet.svg
- **OCEAN** (Ocean Protocol) - ocean.svg
- **GRT** (The Graph) - grt.svg

### Exchange Tokens
- **CRO** (Crypto.com) - cro.svg
- **KCS** (KuCoin) - kcs.svg
- **OKB** (OKX) - okb.svg
- **HUOBI** (Huobi Token) - huobi.svg
- **NEXO** - nexo.svg
- **LEO** (UNUS SED LEO) - leo.svg

### Other Popular Coins
Over 150+ additional cryptocurrencies including altcoins, utility tokens, and emerging projects.

## 📖 Usage

### In Blade Templates
```blade
<!-- Display Bitcoin icon -->
<img src="{{ asset('icons/cryptocurrency/btc.svg') }}" alt="Bitcoin" width="24" height="24">

<!-- Display Ethereum icon -->
<img src="{{ asset('icons/cryptocurrency/eth.svg') }}" alt="Ethereum" width="32" height="32">
```

### In Trading Dashboard
```blade
@foreach($cryptos as $crypto)
    <div class="crypto-item">
        <img src="{{ asset('icons/cryptocurrency/' . strtolower($crypto->symbol) . '.svg') }}"
             alt="{{ $crypto->name }}"
             class="crypto-icon">
        <span>{{ $crypto->symbol }}</span>
        <span>{{ $crypto->price }}</span>
    </div>
@endforeach
```

### With Icon Component
```blade
<!-- If using icon helper component -->
<x-icon name="btc" category="cryptocurrency" size="lg" />
```

## 🎨 Format & Quality

- **Format**: SVG (Scalable Vector Graphics)
- **Color**: Full color icons
- **Quality**: High quality, optimized for web
- **Source**: [Cryptocurrency Icons by SPOT](https://github.com/spothq/cryptocurrency-icons)

## 🔍 Finding Icons

All icons are named using lowercase cryptocurrency symbols. For example:
- Bitcoin → `btc.svg`
- Ethereum → `eth.svg`
- Cardano → `ada.svg`

## 📝 Notes

- Icons are optimized for trading dashboards and crypto displays
- SVG format ensures perfect scaling at any size
- Icons maintain consistent styling across all cryptocurrencies
- Free to use under MIT License (from cryptocurrency-icons repository)

## 🔄 Adding More Icons

To add more cryptocurrency icons:
1. Visit [Cryptocurrency Icons Repository](https://github.com/spothq/cryptocurrency-icons)
2. Download the SVG icon for your desired crypto
3. Place it in this directory with the correct naming format (lowercase symbol)

---

**Total Icons**: 222+
**Last Updated**: 2025-11-08
**Version**: 1.0.0
