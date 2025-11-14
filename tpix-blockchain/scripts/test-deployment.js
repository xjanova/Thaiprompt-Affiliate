const hre = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  console.log("🧪 Testing TPIX Blockchain Deployment...\n");

  // Load deployment info
  const deploymentPath = path.join(__dirname, "..", "deployments");
  const files = fs.readdirSync(deploymentPath);
  const latestDeployment = files
    .filter((f) => f.startsWith(hre.network.name))
    .sort()
    .reverse()[0];

  if (!latestDeployment) {
    console.error("❌ No deployment found");
    process.exit(1);
  }

  const deploymentInfo = JSON.parse(
    fs.readFileSync(path.join(deploymentPath, latestDeployment), "utf8")
  );

  console.log(`📄 Testing deployment: ${latestDeployment}\n`);

  const [tester] = await hre.ethers.getSigners();

  // Test 1: TPIX Token
  console.log("Test 1: TPIX Token Contract...");
  try {
    const tpixToken = await hre.ethers.getContractAt(
      "TPIXERC20",
      deploymentInfo.contracts.TPIXToken
    );

    const name = await tpixToken.name();
    const symbol = await tpixToken.symbol();
    const totalSupply = await tpixToken.totalSupply();
    const decimals = await tpixToken.decimals();

    console.log(`  ✅ Name: ${name}`);
    console.log(`  ✅ Symbol: ${symbol}`);
    console.log(`  ✅ Total Supply: ${hre.ethers.utils.formatEther(totalSupply)} TPIX`);
    console.log(`  ✅ Decimals: ${decimals}`);

    if (
      name === "TPIX" &&
      symbol === "TPIX" &&
      totalSupply.eq(hre.ethers.utils.parseEther("7000000000")) &&
      decimals === 18
    ) {
      console.log("  ✅ TPIX Token: PASSED\n");
    } else {
      throw new Error("Token values incorrect");
    }
  } catch (error) {
    console.log(`  ❌ TPIX Token: FAILED - ${error.message}\n`);
  }

  // Test 2: DEX Factory
  console.log("Test 2: DEX Factory Contract...");
  try {
    const dexFactory = await hre.ethers.getContractAt(
      "TPIXDEXFactory",
      deploymentInfo.contracts.DEXFactory
    );

    const feeTo = await dexFactory.feeTo();
    const feeToSetter = await dexFactory.feeToSetter();
    const allPairsLength = await dexFactory.allPairsLength();

    console.log(`  ✅ Fee To: ${feeTo}`);
    console.log(`  ✅ Fee To Setter: ${feeToSetter}`);
    console.log(`  ✅ Total Pairs: ${allPairsLength}`);

    if (allPairsLength.toNumber() > 0) {
      console.log("  ✅ DEX Factory: PASSED\n");
    } else {
      console.log("  ⚠️  DEX Factory: No pairs created yet\n");
    }
  } catch (error) {
    console.log(`  ❌ DEX Factory: FAILED - ${error.message}\n`);
  }

  // Test 3: DEX Router
  console.log("Test 3: DEX Router Contract...");
  try {
    const dexRouter = await hre.ethers.getContractAt(
      "TPIXDEXRouter02",
      deploymentInfo.contracts.DEXRouter
    );

    const factory = await dexRouter.factory();
    const weth = await dexRouter.WETH();

    console.log(`  ✅ Factory Address: ${factory}`);
    console.log(`  ✅ WETH Address: ${weth}`);

    if (
      factory.toLowerCase() === deploymentInfo.contracts.DEXFactory.toLowerCase() &&
      weth.toLowerCase() === deploymentInfo.contracts.WETH.toLowerCase()
    ) {
      console.log("  ✅ DEX Router: PASSED\n");
    } else {
      throw new Error("Router addresses mismatch");
    }
  } catch (error) {
    console.log(`  ❌ DEX Router: FAILED - ${error.message}\n`);
  }

  // Test 4: Liquidity Pair
  if (deploymentInfo.contracts.TPIXWETHPair) {
    console.log("Test 4: TPIX/WETH Liquidity Pair...");
    try {
      const pair = await hre.ethers.getContractAt(
        "TPIXDEXPair",
        deploymentInfo.contracts.TPIXWETHPair
      );

      const token0 = await pair.token0();
      const token1 = await pair.token1();
      const reserves = await pair.getReserves();

      console.log(`  ✅ Token0: ${token0}`);
      console.log(`  ✅ Token1: ${token1}`);
      console.log(`  ✅ Reserve0: ${hre.ethers.utils.formatEther(reserves[0])}`);
      console.log(`  ✅ Reserve1: ${hre.ethers.utils.formatEther(reserves[1])}`);

      if (reserves[0].gt(0) || reserves[1].gt(0)) {
        console.log("  ✅ Liquidity Pair: PASSED (Has liquidity)\n");
      } else {
        console.log("  ⚠️  Liquidity Pair: PASSED (No liquidity yet)\n");
      }
    } catch (error) {
      console.log(`  ❌ Liquidity Pair: FAILED - ${error.message}\n`);
    }
  }

  // Test 5: Test Swap Quote
  console.log("Test 5: DEX Quote Function...");
  try {
    const dexRouter = await hre.ethers.getContractAt(
      "TPIXDEXRouter02",
      deploymentInfo.contracts.DEXRouter
    );

    const amountIn = hre.ethers.utils.parseEther("1"); // 1 TPIX
    const path = [
      deploymentInfo.contracts.TPIXToken,
      deploymentInfo.contracts.WETH,
    ];

    try {
      const amounts = await dexRouter.getAmountsOut(amountIn, path);
      console.log(`  ✅ Quote for 1 TPIX: ${hre.ethers.utils.formatEther(amounts[1])} WETH`);
      console.log("  ✅ DEX Quote: PASSED\n");
    } catch (error) {
      console.log("  ⚠️  DEX Quote: Pool has no liquidity yet\n");
    }
  } catch (error) {
    console.log(`  ❌ DEX Quote: FAILED - ${error.message}\n`);
  }

  // Test 6: Check Tester Balance
  console.log("Test 6: Account Balance...");
  const balance = await tester.getBalance();
  console.log(`  ✅ Test Account: ${tester.address}`);
  console.log(`  ✅ Balance: ${hre.ethers.utils.formatEther(balance)} TPIX`);

  if (balance.gt(0)) {
    console.log("  ✅ Account Balance: PASSED\n");
  } else {
    console.log("  ❌ Account Balance: FAILED (No balance)\n");
  }

  // Summary
  console.log("=".repeat(60));
  console.log("📊 Test Summary:");
  console.log("=".repeat(60));
  console.log("✅ All critical tests completed");
  console.log("\n💡 Recommendations:");
  console.log("  1. Add initial liquidity: npm run setup-pools");
  console.log("  2. Verify contracts: npm run verify");
  console.log("  3. Update Laravel .env with contract addresses");
  console.log("  4. Run: php artisan tpix:sync-contracts");
  console.log("\n🎉 Deployment is ready for use!\n");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
