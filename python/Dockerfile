FROM python:3.11-slim

# Install system dependencies and Playwright browser dependencies
RUN apt-get update && apt-get install -y \
    # Basic utilities
    curl \
    wget \
    gnupg \
    ca-certificates \
    # Playwright/Chromium dependencies - CRITICAL: libglib2.0-0 must be included
    libnss3 \
    libnspr4 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libatspi2.0-0 \
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
    # Critical: GLib library (required for Chromium)
    libglib2.0-0 \
    libglib2.0-bin \
    # Additional X11 and graphics libraries
    libcairo2 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libgtk-3-0 \
    libgdk-pixbuf-2.0-0 \
    libfontconfig1 \
    libfreetype6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcb-dri3-0 \
    libxcb-shm0 \
    libxcb-xfixes0 \
    libxext6 \
    libxrender1 \
    libxtst6 \
    # Fonts (using available packages)
    fonts-liberation \
    fonts-unifont \
    fonts-dejavu-core \
    # Additional dependencies
    libxss1 \
    libxshmfence1 \
    && rm -rf /var/lib/apt/lists/* \
    && ldconfig \
    && echo "Verifying libglib installation..." \
    && (ldconfig -p | grep -q "libglib-2.0.so.0" && echo "✓ libglib-2.0.so.0 found in library cache" || echo "WARNING: libglib-2.0.so.0 not in cache")

# Set working directory
WORKDIR /app

# Copy requirements first (for better caching)
COPY requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Install Playwright browsers (dependencies already installed above)
RUN playwright install chromium

# Copy application code
COPY . .

# Set environment variables
ENV PYTHONUNBUFFERED=1
ENV PYTHONDONTWRITEBYTECODE=1

# Default command (will be overridden in docker-compose)
CMD ["python", "worker.py"]

