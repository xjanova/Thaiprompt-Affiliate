# TPIX Token Assets Storage

This directory stores all public assets related to TPIX tokens.

## Directory Structure

```
tokens/
├── logos/          - Token logo images (PNG, JPG, SVG)
├── contracts/      - Smart contract source files (.sol)
└── documents/      - Token documentation (whitepaper, etc.)
```

## Usage

### Token Logos
- Accepted formats: PNG, JPG, JPEG, SVG
- Max file size: 2MB
- Recommended dimensions: 256x256px or 512x512px
- Naming convention: `{token_id}_{symbol}.{ext}`
- Example: `1_MTK.png`

### Smart Contracts
- Solidity source files (.sol)
- Verified contract code
- Naming convention: `{token_id}_{contract_name}.sol`
- Example: `1_MyToken.sol`

### Documents
- Whitepaper (PDF)
- Token documentation (PDF, DOC, DOCX)
- Audit reports
- Naming convention: `{token_id}_{document_type}.{ext}`
- Example: `1_whitepaper.pdf`

## Access

These files are publicly accessible via:
`{APP_URL}/storage/tokens/{subdirectory}/{filename}`

Example:
`https://tpix.com/storage/tokens/logos/1_MTK.png`

## Permissions

- Storage path must be linked: `php artisan storage:link`
- Files must be readable by web server
- Recommended permissions: 755 for directories, 644 for files
