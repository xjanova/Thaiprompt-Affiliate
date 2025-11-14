const hre = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  console.log("🏊 Setting up initial liquidity pools...\n");

  // Load deployment info
  const deploymentPath = path.join(__dirname, "..", "deployments");
  const files = fs.readdirSync(deploymentPath);
  const latestDeployment = files
    .filter((f) => f.startsWith(hre.network.name))
    .sort()
    .reverse()[0];

  const deploymentInfo = JSON.parse(
    fs.readFileSync(path.join(deploymentPath, latestDeployment), "utf8")
  );

  const [deployer] = await hre.ethers.getSigners();

  // Get contracts
  const tpixToken = await hre.ethers.getContractAt(
    "TPIXERC20",
    deploymentInfo.contracts.TPIXToken
  );
  const dexRouter = await hre.ethers.getContractAt(
    "TPIXDEXRouter02",
    deploymentInfo.contracts.DEXRouter
  );

  // Add initial liquidity to TPIX/WETH pair
  console.log("Adding liquidity to TPIX/WETH pair...");

  const tpixAmount = hre.ethers.utils.parseEther("1000000"); // 1M TPIX
  const wethAmount = hre.ethers.utils.parseEther("100000"); // 100K WETH

  // Approve tokens
  await tpixToken.approve(dexRouter.address, tpixAmount);
  console.log("✅ Approved TPIX");

  // Add liquidity
  const deadline = Math.floor(Date.now() / 1000) + 60 * 20; // 20 minutes
  const tx = await dexRouter.addLiquidity(
    tpixToken.address,
    deploymentInfo.contracts.WETH,
    tpixAmount,
    wethAmount,
    0, // min amounts (slippage tolerance)
    0,
    deployer.address,
    deadline
  );

  await tx.wait();
  console.log("✅ Liquidity added successfully!");

  // Get LP token balance
  const pairAddress = deploymentInfo.contracts.TPIXWETHPair;
  const pair = await hre.ethers.getContractAt("TPIXDEXPair", pairAddress);
  const lpBalance = await pair.balanceOf(deployer.address);

  console.log(`\n💰 Your LP Token Balance: ${hre.ethers.utils.formatEther(lpBalance)}`);
  console.log("\n🎉 Pool setup complete!");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
