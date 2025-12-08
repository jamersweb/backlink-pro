FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli mbstring zip exif pcntl posix gd opcache intl

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js (for frontend builds)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Python3 and pip (needed for scheduler to run automation worker)
# Also install Playwright/Chromium system dependencies manually
# (Playwright's --with-deps tries to install Ubuntu packages on Debian, which fails)
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    # Playwright/Chromium dependencies (complete list for Debian)
    libnss3 \
    libnspr4 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libdbus-1-3 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    libatspi2.0-0 \
    # Additional X11 and graphics dependencies
    libc6 \
    libcairo2 \
    libexpat1 \
    libfontconfig1 \
    libgcc1 \
    libglib2.0-0 \
    libglib2.0-bin \
    libgtk-3-0 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcursor1 \
    libxext6 \
    libxi6 \
    libxrender1 \
    libxtst6 \
    # Fonts (using Debian package names)
    fonts-liberation \
    fonts-unifont \
    fonts-dejavu-core \
    # Additional dependencies
    libxss1 \
    libxshmfence1 \
    lsb-release \
    xdg-utils \
    && rm -rf /var/lib/apt/lists/* \
    && ldconfig \
    && echo "Verifying libglib installation..." \
    && (ldconfig -p | grep -q "libglib-2.0.so.0" && echo "✓ libglib-2.0.so.0 found in library cache" || echo "WARNING: libglib-2.0.so.0 not in cache") \
    && (find /usr/lib* /lib* -name "libglib-2.0.so*" 2>/dev/null | head -1 && echo "✓ libglib-2.0.so found on filesystem" || echo "WARNING: libglib-2.0.so not found on filesystem")

# Set working directory
WORKDIR /var/www/html

# Install Python dependencies for automation worker
# Copy requirements first for better Docker layer caching
# Using --break-system-packages is safe in Docker containers (isolated environment)
COPY python/requirements.txt /tmp/python-requirements.txt
RUN pip3 install --no-cache-dir --break-system-packages -r /tmp/python-requirements.txt && \
    rm /tmp/python-requirements.txt

# Install Playwright browsers (needed for automation worker)
# Note: Dependencies already installed above, so we don't use --with-deps
RUN python3 -m playwright install chromium

# Copy existing application directory permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
