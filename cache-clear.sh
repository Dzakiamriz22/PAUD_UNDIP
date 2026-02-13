#!/bin/bash

# Quick Cache Clear Script untuk PAUD UNDIP
# Gunakan script ini ketika fitur tidak muncul atau ada issue cache

echo "🧹 Menghapus Cache PAUD UNDIP..."
echo "================================"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Clear Application Cache
echo -e "${YELLOW}[1/5]${NC} Clearing application cache..."
docker-compose exec -T app-filament php artisan cache:clear 2>/dev/null
if [ $? -eq 0 ]; then
  echo -e "${GREEN}✅ Application cache cleared${NC}"
else
  echo -e "${RED}❌ Failed to clear application cache${NC}"
fi

# Step 2: Clear Route Cache
echo -e "${YELLOW}[2/5]${NC} Clearing route cache..."
docker-compose exec -T app-filament php artisan route:clear 2>/dev/null
if [ $? -eq 0 ]; then
  echo -e "${GREEN}✅ Route cache cleared${NC}"
else
  echo -e "${RED}❌ Failed to clear route cache${NC}"
fi

# Step 3: Clear View Cache
echo -e "${YELLOW}[3/5]${NC} Clearing view cache..."
docker-compose exec -T app-filament php artisan view:clear 2>/dev/null
if [ $? -eq 0 ]; then
  echo -e "${GREEN}✅ View cache cleared${NC}"
else
  echo -e "${RED}❌ Failed to clear view cache${NC}"
fi

# Step 4: Clear Config Cache
echo -e "${YELLOW}[4/5]${NC} Clearing config cache..."
docker-compose exec -T app-filament php artisan config:clear 2>/dev/null
if [ $? -eq 0 ]; then
  echo -e "${GREEN}✅ Config cache cleared${NC}"
else
  echo -e "${RED}❌ Failed to clear config cache${NC}"
fi

# Step 5: Clear Filament Cache
echo -e "${YELLOW}[5/5]${NC} Clearing Filament component cache..."
docker-compose exec -T app-filament php artisan filament:cache-components 2>/dev/null
if [ $? -eq 0 ]; then
  echo -e "${GREEN}✅ Filament cache cleared${NC}"
else
  echo -e "${YELLOW}⚠️  Filament cache clear skipped (optional)${NC}"
fi

echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}✅ Cache clearing completed!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo "📝 Next steps:"
echo "1. Restart your browser (close all tabs & reopen)"
echo "2. Clear browser cache (Ctrl+Shift+Delete)"
echo "3. Access http://localhost:8080/admin"
echo "4. Login again with your credentials"
echo ""
echo "Jika masih ada issue, lihat: TROUBLESHOOTING_ROLE_PERMISSION.md"
