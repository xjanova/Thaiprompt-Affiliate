// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/**
 * @title UQ112x112 Library
 * @notice Fixed point library for price calculations
 * @dev Range: [0, 2^112 - 1] with 112 fractional bits
 *      Resolution: 1/2^112
 */
library UQ112x112 {
    uint224 constant Q112 = 2**112;

    /**
     * @notice Encode a uint112 as a UQ112x112
     */
    function encode(uint112 y) internal pure returns (uint224 z) {
        z = uint224(y) * Q112; // never overflows
    }

    /**
     * @notice Divide a UQ112x112 by a uint112, returning a UQ112x112
     */
    function uqdiv(uint224 x, uint112 y) internal pure returns (uint224 z) {
        z = x / uint224(y);
    }
}
