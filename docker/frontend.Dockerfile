# ===== 构建阶段 =====
FROM node:18-alpine AS builder

WORKDIR /app

COPY src/frontend/package.json src/frontend/package-lock.json* ./
RUN npm ci --no-audit --no-fund

COPY src/frontend/ ./

# 构建时通过 build-arg 传入 API 地址，避免硬编码
ARG VITE_API_BASE_URL
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL
RUN npm run build

# ===== 运行阶段：nginx 托管静态资源 =====
FROM nginx:alpine

COPY --from=builder /app/dist /usr/share/nginx/html

# SPA 路由 fallback
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /usr/share/nginx/html; \
    index index.html; \
    location / { try_files $uri $uri/ /index.html; } \
}' > /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
