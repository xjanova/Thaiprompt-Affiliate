const hre = require("hardhat");
const fs = require("fs");
const path = require("path");

// Colors for console output
const colors = {
  reset: "\x1b[0m",
  green: "\x1b[32m",
  yellow: "\x1b[33m",
  blue: "\x1b[34m",
  red: "\x1b[31m",
  cyan: "\x1b[36m",
};

function log(message, color = colors.reset) {
  console.log(`${color}${message}${colors.reset}`);
}

async function main() {
  log("\n🚀 Starting TPIX Blockchain Deployment...\n", colors.cyan);

  const [deployer] = await hre.ethers.getSigners();
  log(`📍 Deployer address: ${deployer.address}`, colors.blue);

  const balance = await deployer.getBalance();
  log(`💰 Deployer balance: ${hre.ethers.utils.formatEther(balance)} TPIX\n`, colors.blue);

  const deployedContracts = {};
  const network = hre.network.name;

  // ===========================================
  // STEP 1: Deploy Native TPIX Token (ERC20)
  // ===========================================
  log("📝 Step 1: Deploying TPIX Native Token (ERC20)...", colors.yellow);

  const TPIXERC20 = await hre.ethers.getContractFactory("TPIXERC20");
  const totalSupply = hre.ethers.utils.parseEther("7000000000"); // 7 billion
  const tpixToken = await TPIXERC20.deploy("TPIX", "TPIX", totalSupply);
  await tpixToken.deployed();

  deployedContracts.TPIXToken = tpixToken.address;
  log(`✅ TPIX Token deployed at: ${tpixToken.address}`, colors.green);
  log(`   Total Supply: 7,000,000,000 TPIX\n`, colors.green);

  // ===========================================
  // STEP 2: Deploy DEX Factory
  // ===========================================
  log("📝 Step 2: Deploying DEX Factory...", colors.yellow);

  const TPIXDEXFactory = await hre.ethers.getContractFactory("TPIXDEXFactory");
  const dexFactory = await TPIXDEXFactory.deploy(deployer.address); // Fee setter
  await dexFactory.deployed();

  deployedContracts.DEXFactory = dexFactory.address;
  log(`✅ DEX Factory deployed at: ${dexFactory.address}\n`, colors.green);

  // ===========================================
  // STEP 3: Deploy WETH (Wrapped TPIX)
  // ===========================================
  log("📝 Step 3: Deploying WETH (Wrapped TPIX)...", colors.yellow);

  // For simplicity, we'll use TPIX token as WETH equivalent
  // In production, deploy actual WETH9 contract
  const wethAddress = tpixToken.address; // Placeholder
  deployedContracts.WETH = wethAddress;
  log(`✅ WETH address (using TPIX): ${wethAddress}\n`, colors.green);

  // ===========================================
  // STEP 4: Deploy DEX Router
  // ===========================================
  log("📝 Step 4: Deploying DEX Router...", colors.yellow);

  const TPIXDEXRouter02 = await hre.ethers.getContractFactory("TPIXDEXRouter02");
  const dexRouter = await TPIXDEXRouter02.deploy(dexFactory.address, wethAddress);
  await dexRouter.deployed();

  deployedContracts.DEXRouter = dexRouter.address;
  log(`✅ DEX Router deployed at: ${dexRouter.address}\n`, colors.green);

  // ===========================================
  // STEP 5: Create Initial Liquidity Pool
  // ===========================================
  log("📝 Step 5: Creating initial TPIX/WETH liquidity pool...", colors.yellow);

  // Approve router to spend tokens
  const approvalAmount = hre.ethers.utils.parseEther("1000000"); // 1M TPIX
  await tpixToken.approve(dexRouter.address, approvalAmount);
  log(`✅ Approved router to spend tokens`, colors.green);

  // Create pair via factory
  const createPairTx = await dexFactory.createPair(tpixToken.address, wethAddress);
  await createPairTx.wait();

  const pairAddress = await dexFactory.getPair(tpixToken.address, wethAddress);
  deployedContracts.TPIXWETHPair = pairAddress;
  log(`✅ TPIX/WETH pair created at: ${pairAddress}\n`, colors.green);

  // ===========================================
  // STEP 6: Save Deployment Info
  // ===========================================
  log("📝 Step 6: Saving deployment information...", colors.yellow);

  const deploymentInfo = {
    network: network,
    chainId: hre.network.config.chainId,
    deployer: deployer.address,
    timestamp: new Date().toISOString(),
    contracts: deployedContracts,
    explorerUrl: hre.network.config.chainId === 7000
      ? "http://explorer.tpix.com"
      : "http://testnet-explorer.tpix.com",
  };

  // Save to JSON file
  const outputDir = path.join(__dirname, "..", "deployments");
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  const outputFile = path.join(outputDir, `${network}-${Date.now()}.json`);
  fs.writeFileSync(outputFile, JSON.stringify(deploymentInfo, null, 2));
  log(`✅ Deployment info saved to: ${outputFile}`, colors.green);

  // Also save to Laravel storage
  const laravelStoragePath = path.join(__dirname, "..", "..", "storage", "app", "tpix", "deployments.json");
  const laravelStorageDir = path.dirname(laravelStoragePath);

  if (!fs.existsSync(laravelStorageDir)) {
    fs.mkdirSync(laravelStorageDir, { recursive: true });
  }

  fs.writeFileSync(laravelStoragePath, JSON.stringify(deploymentInfo, null, 2));
  log(`✅ Deployment info synced to Laravel: ${laravelStoragePath}\n`, colors.green);

  // ===========================================
  // STEP 7: Copy ABIs to Laravel
  // ===========================================
  log("📝 Step 7: Copying contract ABIs to Laravel...", colors.yellow);

  const contracts = [
    "TPIXERC20",
    "TPIXDEXFactory",
    "TPIXDEXRouter02",
    "TPIXDEXPair",
  ];

  const laravelAbiDir = path.join(__dirname, "..", "..", "storage", "app", "tpix", "abis");
  if (!fs.existsSync(laravelAbiDir)) {
    fs.mkdirSync(laravelAbiDir, { recursive: true });
  }

  for (const contractName of contracts) {
    const artifactPath = path.join(__dirname, "..", "artifacts", "contracts", `${contractName}.sol`, `${contractName}.json`);

    if (fs.existsSync(artifactPath)) {
      const artifact = JSON.parse(fs.readFileSync(artifactPath, "utf8"));
      const abiPath = path.join(laravelAbiDir, `${contractName}.json`);
      fs.writeFileSync(abiPath, JSON.stringify(artifact.abi, null, 2));
      log(`  ✅ Copied ${contractName} ABI`, colors.green);
    }
  }

  log("\n" + "=".repeat(60), colors.cyan);
  log("🎉 DEPLOYMENT COMPLETED SUCCESSFULLY! 🎉", colors.green);
  log("=".repeat(60), colors.cyan);

  log("\n📋 Deployment Summary:", colors.yellow);
  log(`   Network: ${network} (Chain ID: ${hre.network.config.chainId})`, colors.blue);
  log(`   Deployer: ${deployer.address}`, colors.blue);

  log("\n📝 Deployed Contracts:", colors.yellow);
  for (const [name, address] of Object.entries(deployedContracts)) {
    log(`   ${name}: ${address}`, colors.blue);
  }

  log("\n📖 Next Steps:", colors.yellow);
  log("   1. Update your .env file with contract addresses", colors.blue);
  log("   2. Run: php artisan tpix:sync-contracts", colors.blue);
  log("   3. Verify contracts: npm run verify", colors.blue);
  log("   4. Setup staking pools: npm run setup-pools", colors.blue);
  log("   5. Test deployment: npm run test-deployment\n", colors.blue);

  log("💡 Import to .env:", colors.yellow);
  log(`TPIX_TOKEN_ADDRESS=${deployedContracts.TPIXToken}`);
  log(`TPIX_DEX_FACTORY_ADDRESS=${deployedContracts.DEXFactory}`);
  log(`TPIX_DEX_ROUTER_ADDRESS=${deployedContracts.DEXRouter}`);
  log(`TPIX_WETH_ADDRESS=${deployedContracts.WETH}`);
  log(`TPIX_PAIR_ADDRESS=${deployedContracts.TPIXWETHPair}\n`);
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
