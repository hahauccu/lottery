#!/bin/bash

# 顏色輸出
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Starting Lottery App Development Services...${NC}"
echo ""

# 檢查是否安裝 concurrently
if ! command -v npx &> /dev/null; then
    echo -e "${YELLOW}⚠️  npx not found. Installing dependencies...${NC}"
    npm install
fi

# 使用 npx concurrently 同時啟動三個服務
npx concurrently \
  -c "blue,magenta,cyan" \
  -n "Laravel,Reverb,Vite" \
  "php artisan serve --host=127.0.0.1 --port=8007" \
  "php artisan reverb:start" \
  "npm run dev"
