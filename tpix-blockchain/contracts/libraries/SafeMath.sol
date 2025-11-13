// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/**
 * @title SafeMath Library
 * @notice Safe math operations with overflow checks
 */
library SafeMath {
    function add(uint256 x, uint256 y) internal pure returns (uint256 z) {
        require((z = x + y) >= x, 'SafeMath: addition overflow');
    }

    function sub(uint256 x, uint256 y) internal pure returns (uint256 z) {
        require((z = x - y) <= x, 'SafeMath: subtraction overflow');
    }

    function mul(uint256 x, uint256 y) internal pure returns (uint256 z) {
        require(y == 0 || (z = x * y) / y == x, 'SafeMath: multiplication overflow');
    }
}
