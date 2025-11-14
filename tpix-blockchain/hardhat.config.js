require("@nomicfoundation/hardhat-toolbox");
require("@nomiclabs/hardhat-ethers");
require("@nomiclabs/hardhat-etherscan");
require("dotenv").config();

/** @type import('hardhat/config').HardhatUserConfig */
module.exports = {
  solidity: {
    version: "0.8.20",
    settings: {
      optimizer: {
        enabled: true,
        runs: 200,
      },
      viaIR: true, // Enable IR-based code generation for better optimization
    },
  },

  networks: {
    // Local development network
    hardhat: {
      chainId: 7000,
      accounts: {
        count: 10,
        accountsBalance: "10000000000000000000000", // 10000 ETH
      },
    },

    // Local Polygon Edge node
    localhost: {
      url: "http://127.0.0.1:8545",
      chainId: 7000,
      accounts: process.env.PRIVATE_KEY ? [process.env.PRIVATE_KEY] : [],
    },

    // TPIX Mainnet (Polygon Edge)
    tpixMainnet: {
      url: process.env.TPIX_RPC_URL || "http://tpix-node-1.yourdomain.com:8545",
      chainId: parseInt(process.env.TPIX_CHAIN_ID || "7000"),
      accounts: process.env.PRIVATE_KEY ? [process.env.PRIVATE_KEY] : [],
      gasPrice: 0, // No gas fees on TPIX chain
      gas: 8000000,
      timeout: 60000,
    },

    // TPIX Testnet
    tpixTestnet: {
      url: process.env.TPIX_TESTNET_RPC_URL || "http://testnet.tpix.com:8545",
      chainId: 7001,
      accounts: process.env.TESTNET_PRIVATE_KEY ? [process.env.TESTNET_PRIVATE_KEY] : [],
      gasPrice: 0,
      gas: 8000000,
    },
  },

  etherscan: {
    apiKey: {
      tpixMainnet: process.env.TPIX_EXPLORER_API_KEY || "your-api-key",
    },
    customChains: [
      {
        network: "tpixMainnet",
        chainId: 7000,
        urls: {
          apiURL: process.env.TPIX_EXPLORER_API_URL || "http://explorer.tpix.com/api",
          browserURL: process.env.TPIX_EXPLORER_URL || "http://explorer.tpix.com",
        },
      },
    ],
  },

  paths: {
    sources: "./contracts",
    tests: "./test",
    cache: "./cache",
    artifacts: "./artifacts",
  },

  mocha: {
    timeout: 60000,
  },

  gasReporter: {
    enabled: process.env.REPORT_GAS === "true",
    currency: "THB",
    coinmarketcap: process.env.COINMARKETCAP_API_KEY,
  },
};
