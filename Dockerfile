# Use an official PHP image as the base image
# We recommend using a specific version tag (e.g., php:8.3-cli)
FROM php:8.3-cli

# Set the working directory inside the container
WORKDIR /app

# Copy all files from your application directory into the working directory in the container
# This assumes your index.php and any folders for static assets (e.g., css/, js/, images/)
# are in the same directory level as your Dockerfile on your local machine.
COPY . /app/

# Command to run the PHP built-in development server
# It will listen on all network interfaces (0.0.0.0) on port 8000
# and serve files from the working directory (/app). The built-in server
# can serve static files if they exist in the specified directory.
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]