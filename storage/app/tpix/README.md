# TPIX Private Storage

This directory stores private TPIX system files (NOT publicly accessible).

## Directory Structure

```
tpix/
├── compiled-contracts/  - Compiled bytecode from Solidity compiler
└── abis/               - Contract ABIs (Application Binary Interface)
```

## Usage

### Compiled Contracts
- Contains bytecode from `solc` compiler
- Format: JSON files with bytecode, ABI, and metadata
- Naming convention: `{contract_name}_{timestamp}.json`
- Example: `MyToken_1699901234.json`
- Used by: `SmartContractCompilerService`

### ABIs
- Contract ABIs for blockchain interaction
- Format: JSON array
- Naming convention: `{contract_address}.json`
- Example: `0x1234...abcd.json`
- Used by: `Web3Service` for contract calls

## Security

⚠️ **IMPORTANT**: These files are NOT publicly accessible.
- No web access via `/storage/` symlink
- Only accessible by PHP application code
- Contains sensitive contract compilation data
- Do not expose to public

## Maintenance

- Old compiled contracts can be cleaned up after 30 days
- Keep ABIs for active contracts only
- Recommended automated cleanup:
  ```bash
  find storage/app/tpix/compiled-contracts -mtime +30 -delete
  ```

## Permissions

- Recommended permissions: 750 for directories, 640 for files
- Owner: www-data (or your web server user)
- Group: www-data
