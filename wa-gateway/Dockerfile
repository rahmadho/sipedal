# =============================================================================
# CuciKu WhatsApp Gateway
# -----------------------------------------------------------------------------
# Two build modes controlled by the WA_WITH_DEPS build arg:
#
#   WA_WITH_DEPS=false (default) → zero-dependency image (mock provider only).
#                                  Builds offline & fast, no npm install.
#   WA_WITH_DEPS=true            → installs optionalDependencies
#                                  (@whiskeysockets/baileys, pino, qrcode-terminal)
#                                  so WA_PROVIDER=baileys works.
#
# Example (docker compose):
#   wa-gateway:
#     build:
#       context: ./infra/wa-gateway
#       args: { WA_WITH_DEPS: "true" }
# =============================================================================
FROM node:22-alpine AS base
ENV NODE_ENV=production
WORKDIR /app

ARG WA_WITH_DEPS=false

# qrcode is a small runtime dep used by the HTML console in BOTH modes;
# baileys + pino + qrcode-terminal land in optionalDependencies and are only
# pulled when WA_WITH_DEPS=true. `--omit=dev` keeps the image lean.
COPY package.json ./
RUN if [ "$WA_WITH_DEPS" = "true" ]; then \
      npm install --omit=dev --no-audit --no-fund; \
    else \
      npm install --omit=dev --omit=optional --no-audit --no-fund; \
    fi && npm cache clean --force

COPY src ./src
COPY scripts ./scripts

# Writable session volume for Baileys auth (owned by the app user).
RUN mkdir -p /data/wa-auth && chown -R node:node /data/wa-auth

# Drop privileges
USER node

ENV BAILEYS_AUTH_DIR=/data/wa-auth
EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=3 \
  CMD node -e "fetch('http://localhost:8080/health').then(r=>process.exit(r.status<500?0:1)).catch(()=>process.exit(1))"

CMD ["node", "src/server.js"]
