{{--
    Technical Specifications Section - รายละเอียดทางเทคนิคแบบละเอียด

    ประกอบด้วย:
    - Blockchain Specifications
    - Smart Contract Details
    - Network Infrastructure
    - Performance Metrics
    - API Documentation
    - SDK & Tools
--}}

<section id="technical-specs" class="section-padding bg-gradient-to-br from-slate-50 via-gray-50 to-zinc-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="container mx-auto px-4">

        {{-- Header --}}
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4">
                🔧 Technical Specifications
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                รายละเอียดทางเทคนิค
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                ข้อมูลทางเทคนิคแบบละเอียดสำหรับ developers และ technical investors
            </p>
        </div>

        {{-- Blockchain Core Specifications --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                ⛓️ Blockchain Core Specifications
            </h3>
            <div class="glass rounded-3xl p-8">
                <div class="grid md:grid-cols-2 gap-8">

                    {{-- Left Column --}}
                    <div class="space-y-6">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <h5 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-cube text-blue-500"></i> Blockchain Engine
                            </h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Base:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">Go-Ethereum (Geth) v1.13.x</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Fork From:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">Ethereum (EVM Compatible)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Consensus:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">IBFT 2.0 (Istanbul Byzantine Fault Tolerant)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Chain ID:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400">7000</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <h5 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-clock text-purple-500"></i> Block Parameters
                            </h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Block Time:</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">2 seconds</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Block Gas Limit:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">30,000,000 gas</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Block Size:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">~8 MB</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Finality:</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">Instant (2 seconds)</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <h5 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-tachometer-alt text-green-500"></i> Performance
                            </h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Max TPS:</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">1,500 transactions/sec</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Avg TPS:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">750-1,000 TPS</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Transaction Cost:</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">&lt;$0.01 per TX</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Confirmation Time:</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">2-4 seconds</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column --}}
                    <div class="space-y-6">
                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                            <h5 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-shield-alt text-orange-500"></i> Security Features
                            </h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Byzantine Fault Tolerant</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">ทนต่อ malicious validators ได้ถึง 33%</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Double-Spend Protection</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">Instant finality ป้องกัน double-spend</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Cryptographic Signing</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">ECDSA secp256k1 (Ethereum standard)</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Validator Authorization</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">Permissioned validator set</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl">
                            <h5 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-code text-cyan-500"></i> Smart Contract VM
                            </h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Virtual Machine:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">EVM (Ethereum Virtual Machine)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Solidity Version:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">0.8.x compatible</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Gas Model:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">Ethereum-compatible</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Opcodes:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">Full EVM support</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-pink-50 dark:bg-pink-900/20 rounded-xl">
                            <h5 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-network-wired text-pink-500"></i> Network
                            </h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Protocol:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">DevP2P (Ethereum Protocol)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Node Discovery:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">Kademlia DHT</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Max Peers:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">50 peers per node</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Encryption:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">RLPx (encrypted P2P)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Smart Contract Specifications --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                📜 Smart Contract Specifications
            </h3>
            <div class="grid md:grid-cols-3 gap-6">

                {{-- TPIX Token Contract --}}
                <div class="glass rounded-3xl p-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-coins text-2xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-3">TPIX Token Contract</h4>
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Standard:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">ERC-20</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Decimals:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">18</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Supply:</span>
                            <span class="font-semibold text-blue-600">7,000,000,000</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Mintable:</span>
                            <span class="font-semibold text-red-600">No (Fixed)</span>
                        </div>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Contract Address:</div>
                        <div class="text-xs font-mono text-gray-900 dark:text-white break-all">0x742d...7a4f</div>
                    </div>
                </div>

                {{-- Staking Contract --}}
                <div class="glass rounded-3xl p-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-lock text-2xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Staking Contract</h4>
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Type:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Time-locked Staking</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Lock Periods:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">30/90/180/365d</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">APY Range:</span>
                            <span class="font-semibold text-green-600">30-120%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Compound:</span>
                            <span class="font-semibold text-green-600">Auto/Manual</span>
                        </div>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Contract Address:</div>
                        <div class="text-xs font-mono text-gray-900 dark:text-white break-all">0x8a3b...2c1d</div>
                    </div>
                </div>

                {{-- Commission Contract --}}
                <div class="glass rounded-3xl p-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-percentage text-2xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Commission Contract</h4>
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Type:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">MLM Distribution</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Levels:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">5 Levels</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Rate:</span>
                            <span class="font-semibold text-purple-600">11.5%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Distribution:</span>
                            <span class="font-semibold text-purple-600">Real-time</span>
                        </div>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Contract Address:</div>
                        <div class="text-xs font-mono text-gray-900 dark:text-white break-all">0x5c9e...8f2a</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- API & Integration --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🔌 API & Integration
            </h3>
            <div class="glass rounded-3xl p-8">
                <div class="grid md:grid-cols-2 gap-8">

                    {{-- RPC Endpoints --}}
                    <div>
                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-server text-blue-500"></i> RPC Endpoints
                        </h4>
                        <div class="space-y-3">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                <div class="font-semibold text-gray-900 dark:text-white mb-2">Mainnet RPC</div>
                                <div class="p-2 bg-white dark:bg-gray-900 rounded font-mono text-xs break-all">
                                    https://rpc.tpix.network
                                </div>
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    Chain ID: 7000 | HTTP/HTTPS
                                </div>
                            </div>
                            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                <div class="font-semibold text-gray-900 dark:text-white mb-2">WebSocket RPC</div>
                                <div class="p-2 bg-white dark:bg-gray-900 rounded font-mono text-xs break-all">
                                    wss://ws.tpix.network
                                </div>
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    Real-time events & subscriptions
                                </div>
                            </div>
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <div class="font-semibold text-gray-900 dark:text-white mb-2">Explorer API</div>
                                <div class="p-2 bg-white dark:bg-gray-900 rounded font-mono text-xs break-all">
                                    https://api.tpixscan.com
                                </div>
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    Block explorer & analytics
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Developer Tools --}}
                    <div>
                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-toolbox text-orange-500"></i> Developer Tools
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-code text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white">Web3.js / Ethers.js</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">JavaScript libraries สำหรับ interact กับ blockchain</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-wallet text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white">MetaMask Compatible</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">เชื่อมต่อกับ MetaMask, WalletConnect, Coinbase Wallet</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-hammer text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white">Hardhat / Truffle</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Development frameworks สำหรับ deploy smart contracts</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fab fa-github text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white">TPIX SDK</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Official SDK: JavaScript, Python, PHP, Go</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Infrastructure --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🏗️ Network Infrastructure
            </h3>
            <div class="grid md:grid-cols-4 gap-6">

                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-4xl mb-3">🖥️</div>
                    <div class="text-3xl font-black text-blue-600 dark:text-blue-400 mb-2">12</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Validator Nodes</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Distributed across 6 countries</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-4xl mb-3">🌐</div>
                    <div class="text-3xl font-black text-green-600 dark:text-green-400 mb-2">99.98%</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Uptime</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Last 12 months average</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-4xl mb-3">⚡</div>
                    <div class="text-3xl font-black text-purple-600 dark:text-purple-400 mb-2">&lt;50ms</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Latency</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Average RPC response time</p>
                </div>

                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-4xl mb-3">💾</div>
                    <div class="text-3xl font-black text-orange-600 dark:text-orange-400 mb-2">~15GB</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Chain Size</h5>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Current blockchain data size</p>
                </div>

            </div>
        </div>

        {{-- Code Example --}}
        <div class="glass rounded-3xl p-8 bg-gradient-to-r from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                💻 Quick Start Code Example
            </h3>
            <div class="grid md:grid-cols-2 gap-6">

                {{-- Web3.js Example --}}
                <div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-3">Web3.js (JavaScript)</h5>
                    <div class="bg-gray-900 rounded-xl p-4 overflow-x-auto">
<pre class="text-sm text-green-400 font-mono"><code>// Connect to TPIX Network
const Web3 = require('web3');
const web3 = new Web3('https://rpc.tpix.network');

// Check balance
const balance = await web3.eth.getBalance(address);
console.log(web3.utils.fromWei(balance, 'ether'));

// Send transaction
const tx = await web3.eth.sendTransaction({
  from: senderAddress,
  to: receiverAddress,
  value: web3.utils.toWei('100', 'ether'),
  gas: 21000
});
</code></pre>
                    </div>
                </div>

                {{-- Ethers.js Example --}}
                <div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-3">Ethers.js (JavaScript)</h5>
                    <div class="bg-gray-900 rounded-xl p-4 overflow-x-auto">
<pre class="text-sm text-blue-400 font-mono"><code>// Connect to TPIX Network
const { ethers } = require('ethers');
const provider = new ethers.JsonRpcProvider(
  'https://rpc.tpix.network'
);

// Get TPIX balance
const balance = await provider.getBalance(address);
console.log(ethers.formatEther(balance));

// Send TPIX
const tx = await wallet.sendTransaction({
  to: receiverAddress,
  value: ethers.parseEther('100')
});
</code></pre>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>
