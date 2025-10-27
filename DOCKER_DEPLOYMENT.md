# 🐳 Docker Deployment Guide

คู่มือการ deploy ThaiPrompt Marketplace ด้วย Docker และ Docker Compose

---

## 📋 สารบัญ

1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Development Environment](#development-environment)
4. [Production Environment](#production-environment)
5. [Docker Commands](#docker-commands)
6. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### ติดตั้ง Docker และ Docker Compose

**Ubuntu/Debian:**
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo apt install docker-compose-plugin

# Verify installation
docker --version
docker compose version
```

**macOS:**
```bash
# Install Docker Desktop
brew install --cask docker
```

**Windows:**
- ดาวน์โหลดและติดตั้ง [Docker Desktop](https://www.docker.com/products/docker-desktop)

---

## Quick Start

### 1. Clone Repository

```bash
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate
```

### 2. Copy Environment File

```bash
cp .env.example .env
```

### 3. Start Services

```bash
docker compose up -d
```

### 4. Install Dependencies

```bash
# Install PHP dependencies
docker compose exec app composer install

# Install Node dependencies
docker compose exec app npm install

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate

# Build assets
docker compose exec app npm run build
```

### 5. Access Application

เปิดเบราว์เซอร์ไปที่: http://localhost:8000

---

## Development Environment

### เริ่มต้นใช้งาน Development

```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f

# Stop services
docker compose down

# Stop and remove volumes
docker compose down -v
```

### Run Commands in Container

```bash
# PHP Artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan cache:clear

# Composer commands
docker compose exec app composer install
docker compose exec app composer require package-name

# NPM commands
docker compose exec app npm install
docker compose exec app npm run dev
docker compose exec app npm run build

# Access container shell
docker compose exec app sh
```

### Database Access

```bash
# Access MySQL container
docker compose exec mysql mysql -uroot -ppassword

# Or use MySQL client from host
mysql -h127.0.0.1 -P3306 -uroot -ppassword thaiprompt_marketplace

# Import SQL file
docker compose exec -T mysql mysql -uroot -ppassword thaiprompt_marketplace < backup.sql
```

### Redis Access

```bash
# Access Redis CLI
docker compose exec redis redis-cli

# Test Redis
docker compose exec redis redis-cli ping
```

### Queue Worker

```bash
# View queue worker logs
docker compose logs -f queue

# Restart queue worker
docker compose restart queue
```

---

## Production Environment

### 1. Build Production Image

```bash
# Build production image
docker build --target production -t thaiprompt-marketplace:latest .
```

### 2. Production docker-compose.yml

สร้างไฟล์ `docker-compose.prod.yml`:

```yaml
version: '3.8'

services:
  app:
    image: thaiprompt-marketplace:latest
    restart: always
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=mysql
      - REDIS_HOST=redis
    networks:
      - thaiprompt
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/production.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
    networks:
      - thaiprompt
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - thaiprompt

  redis:
    image: redis:7-alpine
    restart: always
    volumes:
      - redis_data:/data
    networks:
      - thaiprompt

networks:
  thaiprompt:
    driver: bridge

volumes:
  mysql_data:
  redis_data:
```

### 3. Deploy to Production

```bash
# Build and push image to registry
docker build --target production -t your-registry.com/thaiprompt:latest .
docker push your-registry.com/thaiprompt:latest

# On production server
docker pull your-registry.com/thaiprompt:latest
docker compose -f docker-compose.prod.yml up -d
```

---

## Docker Commands

### Container Management

```bash
# List running containers
docker compose ps

# View container logs
docker compose logs app
docker compose logs -f app  # Follow logs

# Restart container
docker compose restart app

# Stop container
docker compose stop app

# Remove container
docker compose rm app

# Rebuild container
docker compose up -d --build app
```

### Database Management

```bash
# Backup database
docker compose exec mysql mysqldump -uroot -ppassword thaiprompt_marketplace > backup.sql

# Restore database
docker compose exec -T mysql mysql -uroot -ppassword thaiprompt_marketplace < backup.sql

# Create database backup with timestamp
docker compose exec mysql mysqldump -uroot -ppassword thaiprompt_marketplace | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Volume Management

```bash
# List volumes
docker volume ls

# Inspect volume
docker volume inspect thaiprompt-affiliate_mysql_data

# Remove unused volumes
docker volume prune

# Backup volume
docker run --rm -v thaiprompt-affiliate_mysql_data:/data -v $(pwd):/backup ubuntu tar czf /backup/mysql_backup.tar.gz /data
```

### Network Management

```bash
# List networks
docker network ls

# Inspect network
docker network inspect thaiprompt-affiliate_thaiprompt

# Remove unused networks
docker network prune
```

### System Cleanup

```bash
# Remove stopped containers
docker container prune

# Remove unused images
docker image prune

# Remove unused volumes
docker volume prune

# Remove everything unused
docker system prune -a

# View disk usage
docker system df
```

---

## Optimization

### Production Dockerfile Optimization

```dockerfile
# Use multi-stage builds
FROM php:8.1-fpm-alpine AS base
# ... base setup ...

FROM base AS dependencies
# Install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader

FROM base AS production
# Copy only what's needed
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative
```

### Image Size Optimization

```bash
# Use alpine images
FROM php:8.1-fpm-alpine

# Combine RUN commands
RUN apk add --no-cache package1 package2 && \
    rm -rf /var/cache/apk/*

# Use .dockerignore
# Add unnecessary files to .dockerignore
```

---

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker compose logs app

# Check container status
docker compose ps

# Inspect container
docker inspect thaiprompt_app
```

### Permission Issues

```bash
# Fix storage permissions
docker compose exec app chown -R www-data:www-data /var/www/html/storage
docker compose exec app chmod -R 775 /var/www/html/storage
```

### Database Connection Issues

```bash
# Test database connection
docker compose exec app php artisan db:show

# Check MySQL is running
docker compose exec mysql mysqladmin -uroot -ppassword ping

# View MySQL logs
docker compose logs mysql
```

### Port Already in Use

```bash
# Find process using port
sudo lsof -i :8000

# Kill process
sudo kill -9 <PID>

# Or change port in docker-compose.yml
ports:
  - "8080:80"  # Use 8080 instead
```

### Reset Everything

```bash
# Stop and remove everything
docker compose down -v

# Remove images
docker rmi $(docker images 'thaiprompt*' -q)

# Start fresh
docker compose up -d --build
```

---

## Best Practices

### Development

1. **Use docker-compose.override.yml** สำหรับ local settings
2. **Mount volumes** สำหรับ hot reload
3. **Use .env.local** สำหรับ local environment variables
4. **Enable Xdebug** สำหรับ debugging

### Production

1. **Use specific image tags** (not :latest)
2. **Implement health checks**
3. **Set resource limits**
4. **Use secrets management**
5. **Enable logging**
6. **Implement monitoring**
7. **Regular backups**
8. **Security scanning**

### Example Production Configuration

```yaml
services:
  app:
    image: thaiprompt:1.0.0  # Specific version
    restart: always
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
        reservations:
          cpus: '1'
          memory: 1G
    healthcheck:
      test: ["CMD", "php", "artisan", "health:check"]
      interval: 30s
      timeout: 10s
      retries: 3
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

---

## Monitoring

### View Resource Usage

```bash
# Real-time stats
docker stats

# Container resource usage
docker compose stats
```

### Health Checks

```bash
# Check container health
docker inspect --format='{{.State.Health.Status}}' thaiprompt_app

# View health check logs
docker inspect --format='{{range .State.Health.Log}}{{.Output}}{{end}}' thaiprompt_app
```

---

## Security

### Scan for Vulnerabilities

```bash
# Install Trivy
brew install aquasecurity/trivy/trivy

# Scan image
trivy image thaiprompt-marketplace:latest

# Scan with severity filter
trivy image --severity HIGH,CRITICAL thaiprompt-marketplace:latest
```

### Use Non-Root User

```dockerfile
# Create user
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser

# Switch to user
USER appuser
```

---

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker Best Practices](https://laravel.com/docs/10.x/sail)

---

## 🔗 Related Documents

- [DEPLOYMENT.md](./DEPLOYMENT.md) - Traditional deployment
- [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md) - Deployment checklist
- [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md) - Installation guide

---

**Last Updated:** 2025-10-27
**Version:** 1.1.0
