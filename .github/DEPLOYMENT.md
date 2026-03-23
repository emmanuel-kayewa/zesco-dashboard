# GitHub Actions Deployment Setup

This document describes the automated deployment setup for the ZESCO Executive Dashboard application.

## Overview

The application uses GitHub Actions to automatically deploy to two environments:

- **Production Server**: Deploys automatically when code is pushed to the `main` branch
- **Test Server**: Deploys automatically when code is pushed to the `develop` branch

## Server Requirements

Ensure your Oracle Linux 8 servers have the following installed:

- Git
- PHP 8.1 or higher
- Composer
- Node.js and NPM
- MySQL/MariaDB
- Web server (Apache/Nginx)

## GitHub Secrets Configuration

Before the workflows can run, you need to configure the following secrets in your GitHub repository:

### To add secrets:
1. Go to your GitHub repository
2. Navigate to **Settings** > **Secrets and variables** > **Actions**
3. Click **New repository secret**
4. Add each of the following secrets:

### Production Server Secrets

| Secret Name | Description | Example |
|------------|-------------|---------|
| `PROD_HOST` | Production server IP or hostname | `192.168.1.100` or `prod.example.com` |
| `PROD_USERNAME` | SSH username for production server | `deployer` or `root` |
| `PROD_SSH_KEY` | Private SSH key for authentication | Full content of your private key |
| `PROD_PORT` | SSH port (default is 22) | `22` |
| `PROD_APP_PATH` | Full path to application on server | `/var/www/zesco-dashboard` |

### Test Server Secrets

| Secret Name | Description | Example |
|------------|-------------|---------|
| `TEST_HOST` | Test server IP or hostname | `192.168.1.101` or `test.example.com` |
| `TEST_USERNAME` | SSH username for test server | `deployer` or `root` |
| `TEST_SSH_KEY` | Private SSH key for authentication | Full content of your private key |
| `TEST_PORT` | SSH port (default is 22) | `22` |
| `TEST_APP_PATH` | Full path to application on server | `/var/www/zesco-dashboard-test` |

## SSH Key Setup

### 1. Generate SSH Key Pair (if you don't have one)

On your local machine or a secure location:

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f github-deploy-key
```

This creates two files:
- `github-deploy-key` (private key) - Add this to GitHub Secrets
- `github-deploy-key.pub` (public key) - Add this to your servers

### 2. Add Public Key to Servers

On each server, add the public key to the authorized_keys file:

```bash
# SSH into your server
ssh username@server-ip

# Add the public key
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "YOUR_PUBLIC_KEY_CONTENT" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### 3. Add Private Key to GitHub

Copy the entire content of the private key file and add it as a secret in GitHub:

```bash
cat github-deploy-key
```

Copy the output and paste it into the `PROD_SSH_KEY` or `TEST_SSH_KEY` secret in GitHub.

## Server Setup

### 1. Clone Repository on Servers

On each server, clone the repository to the appropriate path:

#### Production Server:
```bash
cd /var/www
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git zesco-dashboard
cd zesco-dashboard
git checkout main
```

#### Test Server:
```bash
cd /var/www
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git zesco-dashboard-test
cd zesco-dashboard-test
git checkout develop
```

### 2. Configure Application

On each server:

```bash
# Copy environment file
cp .env.example .env

# Edit .env file with appropriate settings
nano .env

# Install dependencies
composer install
npm install

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Configure Git for Deployment User

Ensure the deployment user can pull from the repository:

```bash
# If using HTTPS (you'll need to configure credentials)
git config --global credential.helper store

# OR if using SSH (recommended)
# Add your server's SSH key to GitHub as a deploy key
ssh-keygen -t ed25519 -C "server-deploy"
cat ~/.ssh/id_ed25519.pub
# Add this to GitHub: Settings > Deploy keys > Add deploy key
```

## Deployment Process

### Automatic Deployment

Once configured, deployments happen automatically:

1. **Production**: Push to `main` branch
   ```bash
   git push origin main
   ```

2. **Test**: Push to `develop` branch
   ```bash
   git push origin develop
   ```

### Manual Deployment

If needed, you can manually trigger a workflow:

1. Go to **Actions** tab in GitHub
2. Select the workflow (Deploy to Production or Deploy to Test)
3. Click **Run workflow**
4. Select the branch and click **Run workflow**

## Deployment Steps

Each deployment workflow performs the following steps:

1. ✅ Checkout code from repository
2. 🔄 Pull latest code from the appropriate branch
3. 📦 Install Composer dependencies
4. 📦 Install NPM dependencies
5. 🏗️ Build frontend assets with Vite
6. 🔧 Run database migrations
7. 🧹 Clear Laravel caches
8. 💾 Cache configuration, routes, and views
9. 🔗 Optimize application
10. 📋 Restart queue workers

## Troubleshooting

### Deployment Fails with "Permission Denied"

- Check that the SSH key is correctly added to GitHub secrets
- Verify the public key is in `~/.ssh/authorized_keys` on the server
- Ensure the deployment user has write permissions to the application directory

### Git Pull Fails

- Ensure the server can access GitHub (check SSH keys or HTTPS credentials)
- Verify the branch exists on the server: `git branch -a`
- Check if there are uncommitted changes: `git status`
- Reset local changes if safe: `git reset --hard origin/main` (or develop)

### Composer/NPM Install Fails

- Check that Composer and NPM are installed and in PATH
- Verify the server has internet access
- Check disk space: `df -h`

### Migration Fails

- Check database connection in `.env` file
- Verify database credentials are correct
- Ensure database exists and is accessible

### Queue Workers Not Restarting

- Check if supervisor or another process manager is being used
- You may need to add custom restart commands to the workflow
- Example for supervisor: `sudo supervisorctl restart all`

## Monitoring

To view deployment logs:

1. Go to your GitHub repository
2. Click on **Actions** tab
3. Select the workflow run you want to inspect
4. Click on the job to view detailed logs

## Security Best Practices

1. ✅ Use SSH keys instead of passwords
2. ✅ Create a dedicated deployment user with limited permissions
3. ✅ Use separate SSH keys for production and test environments
4. ✅ Never commit `.env` files or secrets to the repository
5. ✅ Regularly rotate SSH keys
6. ✅ Use GitHub deploy keys (read-only) when possible
7. ✅ Keep server software updated

## Additional Configuration

### For Supervisor (Queue Workers)

If using Supervisor to manage queue workers, add this to the deployment script:

```yaml
echo "🔄 Restarting Supervisor workers..."
sudo supervisorctl restart all
```

### For Reverb WebSocket Server

If using Laravel Reverb, you may need to restart it:

```yaml
echo "🔄 Restarting Reverb server..."
pkill -f "artisan reverb:start" || true
nohup php artisan reverb:start > /dev/null 2>&1 &
```

### For Apache/Nginx Restart

If you need to restart the web server:

```yaml
# Apache
sudo systemctl restart httpd

# Nginx  
sudo systemctl restart nginx
```

## Support

For issues or questions about the deployment setup, please contact the development team.
