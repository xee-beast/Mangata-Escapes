FROM surnet/alpine-wkhtmltopdf:3.7-0.12.4-small as wkhtmltopdf
FROM laravelphp/vapor:php81

# Install dependencies for wkhtmltopdf
RUN apk add --no-cache \
    libxrender \
    libxext \
    libxcb \
    libx11 \
    fontconfig \
    freetype \
    ttf-freefont \
    --repository http://dl-cdn.alpinelinux.org/alpine/v3.7/main \
    libssl1.0 \
    libcrypto1.0

# Copy wkhtmltopdf files from docker-wkhtmltopdf image
COPY --from=wkhtmltopdf /bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

COPY . /var/task
