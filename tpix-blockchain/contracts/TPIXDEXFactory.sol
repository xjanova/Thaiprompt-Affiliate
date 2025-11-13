// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import './TPIXDEXPair.sol';

/**
 * @title TPIX DEX Factory
 * @notice Factory contract for creating and managing liquidity pairs
 * @dev Based on Uniswap V2 Factory with TPIX-specific enhancements
 */
contract TPIXDEXFactory {
    address public feeTo;
    address public feeToSetter;

    mapping(address => mapping(address => address)) public getPair;
    address[] public allPairs;

    event PairCreated(address indexed token0, address indexed token1, address pair, uint256);

    constructor(address _feeToSetter) {
        feeToSetter = _feeToSetter;
    }

    /**
     * @notice Get total number of pairs created
     */
    function allPairsLength() external view returns (uint256) {
        return allPairs.length;
    }

    /**
     * @notice Create a new liquidity pair
     * @param tokenA Address of first token
     * @param tokenB Address of second token
     * @return pair Address of created pair
     */
    function createPair(address tokenA, address tokenB) external returns (address pair) {
        require(tokenA != tokenB, 'TPIX: IDENTICAL_ADDRESSES');
        (address token0, address token1) = tokenA < tokenB ? (tokenA, tokenB) : (tokenB, tokenA);
        require(token0 != address(0), 'TPIX: ZERO_ADDRESS');
        require(getPair[token0][token1] == address(0), 'TPIX: PAIR_EXISTS');

        bytes memory bytecode = type(TPIXDEXPair).creationCode;
        bytes32 salt = keccak256(abi.encodePacked(token0, token1));
        assembly {
            pair := create2(0, add(bytecode, 32), mload(bytecode), salt)
        }

        TPIXDEXPair(pair).initialize(token0, token1);
        getPair[token0][token1] = pair;
        getPair[token1][token0] = pair; // populate mapping in the reverse direction
        allPairs.push(pair);

        emit PairCreated(token0, token1, pair, allPairs.length);
    }

    /**
     * @notice Set fee recipient address
     * @param _feeTo Address to send protocol fees
     */
    function setFeeTo(address _feeTo) external {
        require(msg.sender == feeToSetter, 'TPIX: FORBIDDEN');
        feeTo = _feeTo;
    }

    /**
     * @notice Set fee setter address
     * @param _feeToSetter New fee setter address
     */
    function setFeeToSetter(address _feeToSetter) external {
        require(msg.sender == feeToSetter, 'TPIX: FORBIDDEN');
        feeToSetter = _feeToSetter;
    }

    /**
     * @notice Get pair address or create if doesn't exist
     * @param tokenA First token address
     * @param tokenB Second token address
     * @return pair Pair address
     */
    function getOrCreatePair(address tokenA, address tokenB) external returns (address pair) {
        (address token0, address token1) = tokenA < tokenB ? (tokenA, tokenB) : (tokenB, tokenA);
        pair = getPair[token0][token1];

        if (pair == address(0)) {
            pair = this.createPair(tokenA, tokenB);
        }

        return pair;
    }

    /**
     * @notice Calculate pair address without deploying
     * @param tokenA First token address
     * @param tokenB Second token address
     * @return pair Predicted pair address
     */
    function pairFor(address tokenA, address tokenB) external view returns (address pair) {
        (address token0, address token1) = tokenA < tokenB ? (tokenA, tokenB) : (tokenB, tokenA);
        pair = address(uint160(uint256(keccak256(abi.encodePacked(
            hex'ff',
            address(this),
            keccak256(abi.encodePacked(token0, token1)),
            keccak256(type(TPIXDEXPair).creationCode)
        )))));
    }
}
