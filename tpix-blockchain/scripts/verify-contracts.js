const hre = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  console.log("🔍 Verifying contracts on TPIX Explorer...\n");

  // Load deployment info
  const deploymentPath = path.join(__dirname, "..", "deployments");
  const files = fs.readdirSync(deploymentPath);
  const latestDeployment = files
    .filter((f) => f.startsWith(hre.network.name))
    .sort()
    .reverse()[0];

  if (!latestDeployment) {
    console.error("❌ No deployment found for this network");
    process.exit(1);
  }

  const deploymentInfo = JSON.parse(
    fs.readFileSync(path.join(deploymentPath, latestDeployment), "utf8")
  );

  console.log(`📄 Using deployment: ${latestDeployment}\n`);

  // Verify TPIX Token
  console.log("Verifying TPIX Token...");
  try {
    await hre.run("verify:verify", {
      address: deploymentInfo.contracts.TPIXToken,
      constructorArguments: [
        "TPIX",
        "TPIX",
        hre.ethers.utils.parseEther("7000000000"),
      ],
    });
    console.log("✅ TPIX Token verified\n");
  } catch (error) {
    console.log("⚠️  TPIX Token:", error.message, "\n");
  }

  // Verify DEX Factory
  console.log("Verifying DEX Factory...");
  try {
    await hre.run("verify:verify", {
      address: deploymentInfo.contracts.DEXFactory,
      constructorArguments: [deploymentInfo.deployer],
    });
    console.log("✅ DEX Factory verified\n");
  } catch (error) {
    console.log("⚠️  DEX Factory:", error.message, "\n");
  }

  // Verify DEX Router
  console.log("Verifying DEX Router...");
  try {
    await hre.run("verify:verify", {
      address: deploymentInfo.contracts.DEXRouter,
      constructorArguments: [
        deploymentInfo.contracts.DEXFactory,
        deploymentInfo.contracts.WETH,
      ],
    });
    console.log("✅ DEX Router verified\n");
  } catch (error) {
    console.log("⚠️  DEX Router:", error.message, "\n");
  }

  console.log("🎉 Verification complete!");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
