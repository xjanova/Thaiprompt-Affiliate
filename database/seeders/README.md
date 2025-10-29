# Database Seeders

## Test Users Seeder

This seeder creates 2 test user accounts that are not connected to any affiliate line.

### Running the Seeder

To create the test users, run:

```bash
php artisan db:seed --class=TestUsersSeeder
```

Or to run all seeders:

```bash
php artisan db:seed
```

### Test User Credentials

After running the seeder, you can login with these credentials:

**Test User 1:**
- Email: `testuser1@example.com`
- Password: `password`
- Language: Thai (ไทย)
- Affiliate: Not connected to any affiliate line

**Test User 2:**
- Email: `testuser2@example.com`
- Password: `password`
- Language: English
- Affiliate: Not connected to any affiliate line

### Notes

- Both users have basic permissions (view_dashboard)
- They are regular users (not admin or super admin)
- They are not connected to any affiliate line (affiliate_id is null)
- The seeder uses `firstOrCreate` so it won't create duplicates if run multiple times
