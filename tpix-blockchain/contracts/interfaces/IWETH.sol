// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/**
 * @title IWETH Interface
 * @notice Wrapped ETH (or in this case, Wrapped TPIX) interface
 */
interface IWETH {
    function deposit() external payable;
    function transfer(address to, uint value) external returns (bool);
    function withdraw(uint) external;
}
