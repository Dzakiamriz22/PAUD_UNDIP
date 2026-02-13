#!/bin/bash

# Verify Role & Permission Script untuk PAUD UNDIP
# Script ini memastikan role & permission sudah ter-register dengan benar

echo "🔍 Verifying Role & Permission Setup..."
echo "========================================"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check 1: Docker containers running
echo -e "${BLUE}[1]${NC} Checking Docker containers..."
DOCKER_STATUS=$(docker-compose ps --services --filter "status=running" | wc -l)
if [ $DOCKER_STATUS -ge 6 ]; then
  echo -e "${GREEN}✅ Docker containers running${NC}"
else
  echo -e "${RED}❌ Docker containers not running. Start with: docker-compose up -d${NC}"
  exit 1
fi
echo ""

# Check 2: Permissions in database
echo -e "${BLUE}[2]${NC} Checking permissions in database..."
PERM_COUNT=$(docker-compose exec -T db-filament mysql -u root -proot db_paud -e "SELECT COUNT(*) as count FROM permissions;" 2>/dev/null | tail -1)
echo "   Permissions found: $PERM_COUNT"
if [ "$PERM_COUNT" -gt 0 ]; then
  echo -e "${GREEN}✅ Permissions registered${NC}"
else
  echo -e "${YELLOW}⚠️  No permissions found. Running seeder...${NC}"
  docker-compose exec -T app-filament php artisan db:seed --class=PermissionSeeder 2>/dev/null
  echo -e "${GREEN}✅ Permissions seeded${NC}"
fi
echo ""

# Check 3: Roles in database
echo -e "${BLUE}[3]${NC} Checking roles in database..."
ROLE_COUNT=$(docker-compose exec -T db-filament mysql -u root -proot db_paud -e "SELECT COUNT(*) as count FROM roles;" 2>/dev/null | tail -1)
echo "   Roles found: $ROLE_COUNT"
if [ "$ROLE_COUNT" -gt 0 ]; then
  echo -e "${GREEN}✅ Roles registered${NC}"
else
  echo -e "${YELLOW}⚠️  No roles found${NC}"
fi
echo ""

# Check 4: Super Admin User
echo -e "${BLUE}[4]${NC} Checking Super Admin user..."
ADMIN_COUNT=$(docker-compose exec -T db-filament mysql -u root -proot db_paud -e "SELECT COUNT(*) as count FROM users WHERE username='superadmin';" 2>/dev/null | tail -1)
if [ "$ADMIN_COUNT" -gt 0 ]; then
  echo -e "${GREEN}✅ Super Admin user exists${NC}"
else
  echo -e "${YELLOW}⚠️  Super Admin user not found${NC}"
fi
echo ""

# Check 5: Model_has_roles (user-role assignment)
echo -e "${BLUE}[5]${NC} Checking user-role assignments..."
ASSIGNMENT_COUNT=$(docker-compose exec -T db-filament mysql -u root -proot db_paud -e "SELECT COUNT(*) as count FROM model_has_roles;" 2>/dev/null | tail -1)
echo "   Assignments found: $ASSIGNMENT_COUNT"
if [ "$ASSIGNMENT_COUNT" -gt 0 ]; then
  echo -e "${GREEN}✅ User-role assignments exist${NC}"
else
  echo -e "${YELLOW}⚠️  No user-role assignments${NC}"
fi
echo ""

# Check 6: Filament Shield table
echo -e "${BLUE}[6]${NC} Checking Filament Shield setup..."
SHIELD_CHECK=$(docker-compose exec -T db-filament mysql -u root -proot db_paud -e "SHOW TABLES LIKE 'role_has_permissions';" 2>/dev/null | grep -c "role_has_permissions")
if [ "$SHIELD_CHECK" -gt 0 ]; then
  echo -e "${GREEN}✅ Filament Shield tables exist${NC}"
else
  echo -e "${RED}❌ Filament Shield not properly installed${NC}"
  echo "   Running: php artisan vendor:publish --tag=filament-shield-config"
  docker-compose exec -T app-filament php artisan vendor:publish --tag=filament-shield-config --force 2>/dev/null
fi
echo ""

# Summary
echo "========================================"
echo -e "${BLUE}Summary:${NC}"
echo "  • Permissions: $PERM_COUNT"
echo "  • Roles: $ROLE_COUNT"
echo "  • Super Admin: $([ $ADMIN_COUNT -gt 0 ] && echo 'Yes' || echo 'No')"
echo "  • Assignments: $ASSIGNMENT_COUNT"
echo ""
echo "🚀 If all checks pass, refresh your browser and you should see Role & Permission menu!"
echo ""
echo "📖 If still having issues, check: TROUBLESHOOTING_ROLE_PERMISSION.md"
