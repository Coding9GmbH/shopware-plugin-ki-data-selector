# Makefile for Coding9 KI Data Selector Plugin
# Automatisches Build-System für Shopware Plugin Releases

.PHONY: help build clean release test install

# Plugin name
PLUGIN_NAME = Coding9KIDataSelector

# Get version from composer.json
VERSION := $(shell grep '"version":' composer.json | head -1 | sed 's/.*"version": "\(.*\)".*/\1/')

# Directories
RELEASES_DIR = releases
BUILD_DIR = build
CURRENT_DIR = $(shell pwd)
PLUGIN_DIR = $(shell basename $(CURRENT_DIR))

# ZIP filename
ZIP_NAME = $(PLUGIN_NAME)-$(VERSION).zip
ZIP_PATH = $(RELEASES_DIR)/$(ZIP_NAME)

# Files and directories to exclude from ZIP
EXCLUDES = \
	-x "*.DS_Store" \
	-x "*__MACOSX*" \
	-x "*.git*" \
	-x "*.gitignore" \
	-x "*node_modules*" \
	-x "*.idea*" \
	-x "*.vscode*" \
	-x "*vendor*" \
	-x "*.log" \
	-x "*composer.lock" \
	-x "*package-lock.json" \
	-x "*/test/*" \
	-x "*/tests/*" \
	-x "*/Test/*" \
	-x "*/Tests/*" \
	-x "*Makefile" \
	-x "*BUILD.md" \
	-x "*/releases/*" \
	-x "*/build/*" \
	-x "*.zip"

# Colors for output
RED = \033[0;31m
GREEN = \033[0;32m
YELLOW = \033[1;33m
BLUE = \033[0;34m
NC = \033[0m # No Color

##@ General

help: ## Display this help
	@echo "$(BLUE)Coding9 KI Data Selector Plugin Build System$(NC)"
	@echo ""
	@echo "$(GREEN)Current Version: $(VERSION)$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make $(CYAN)<target>$(NC)\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  $(CYAN)%-15s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(YELLOW)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Build

release: clean build ## Create production release ZIP (default target)
	@echo "$(GREEN)✓ Release $(VERSION) created successfully!$(NC)"
	@echo "$(BLUE)→ Location: $(ZIP_PATH)$(NC)"
	@ls -lh $(ZIP_PATH)

build: check-version prepare-release create-zip verify-zip ## Build the plugin ZIP file
	@echo "$(GREEN)✓ Build complete!$(NC)"

prepare-release: ## Prepare release directory
	@echo "$(BLUE)→ Preparing release directory...$(NC)"
	@mkdir -p $(RELEASES_DIR)
	@if [ -f "$(ZIP_PATH)" ]; then \
		echo "$(YELLOW)⚠ Warning: $(ZIP_NAME) already exists, creating backup...$(NC)"; \
		mv $(ZIP_PATH) $(ZIP_PATH).backup; \
	fi

create-zip: ## Create the ZIP file
	@echo "$(BLUE)→ Creating ZIP archive: $(ZIP_NAME)$(NC)"
	@cd .. && zip -r $(PLUGIN_DIR)/$(ZIP_PATH) $(PLUGIN_DIR) $(EXCLUDES) -q
	@echo "$(GREEN)✓ ZIP created$(NC)"

verify-zip: ## Verify the ZIP file is clean
	@echo "$(BLUE)→ Verifying ZIP file...$(NC)"
	@if unzip -l $(ZIP_PATH) | grep -i "__MACOSX" > /dev/null; then \
		echo "$(RED)✗ Error: __MACOSX folder found in ZIP!$(NC)"; \
		exit 1; \
	else \
		echo "$(GREEN)✓ ZIP is clean (no __MACOSX)$(NC)"; \
	fi
	@if unzip -l $(ZIP_PATH) | grep -i ".DS_Store" > /dev/null; then \
		echo "$(YELLOW)⚠ Warning: .DS_Store files found$(NC)"; \
	else \
		echo "$(GREEN)✓ No .DS_Store files$(NC)"; \
	fi
	@echo "$(BLUE)→ ZIP Contents:$(NC)"
	@unzip -l $(ZIP_PATH) | head -20

check-version: ## Check and display current version
	@echo "$(BLUE)→ Plugin Version: $(VERSION)$(NC)"
	@if [ -z "$(VERSION)" ]; then \
		echo "$(RED)✗ Error: Could not detect version from composer.json$(NC)"; \
		exit 1; \
	fi

##@ Development

install: ## Install plugin in Shopware (requires Shopware root path)
	@echo "$(BLUE)→ Installing plugin...$(NC)"
	@if [ -z "$(SHOPWARE_ROOT)" ]; then \
		echo "$(RED)✗ Error: SHOPWARE_ROOT not set$(NC)"; \
		echo "Usage: make install SHOPWARE_ROOT=/path/to/shopware"; \
		exit 1; \
	fi
	@echo "$(BLUE)→ Copying plugin to $(SHOPWARE_ROOT)/custom/plugins/$(NC)"
	@rsync -av --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'releases' . $(SHOPWARE_ROOT)/custom/plugins/$(PLUGIN_NAME)/
	@echo "$(BLUE)→ Refreshing plugins...$(NC)"
	@cd $(SHOPWARE_ROOT) && bin/console plugin:refresh
	@cd $(SHOPWARE_ROOT) && bin/console plugin:install --activate $(PLUGIN_NAME)
	@cd $(SHOPWARE_ROOT) && bin/console cache:clear
	@echo "$(GREEN)✓ Plugin installed$(NC)"

test: ## Run plugin tests (if available)
	@echo "$(BLUE)→ Running tests...$(NC)"
	@if [ -f "composer.json" ] && grep -q "phpunit" composer.json; then \
		vendor/bin/phpunit; \
	else \
		echo "$(YELLOW)⚠ No tests configured$(NC)"; \
	fi

##@ Cleanup

clean: ## Clean build artifacts
	@echo "$(BLUE)→ Cleaning build artifacts...$(NC)"
	@rm -rf $(BUILD_DIR)
	@echo "$(GREEN)✓ Clean complete$(NC)"

clean-releases: ## Remove all release ZIPs
	@echo "$(RED)→ Removing all releases...$(NC)"
	@rm -rf $(RELEASES_DIR)/*.zip
	@echo "$(GREEN)✓ Releases cleaned$(NC)"

##@ Utilities

list-releases: ## List all release ZIPs
	@echo "$(BLUE)Available Releases:$(NC)"
	@if [ -d "$(RELEASES_DIR)" ]; then \
		ls -lh $(RELEASES_DIR)/*.zip 2>/dev/null || echo "$(YELLOW)No releases found$(NC)"; \
	else \
		echo "$(YELLOW)No releases directory$(NC)"; \
	fi

show-excludes: ## Show what files will be excluded from ZIP
	@echo "$(BLUE)Excluded patterns:$(NC)"
	@echo "$(EXCLUDES)" | tr ' ' '\n' | grep -v "^-x" | grep -v "^$$"

version: ## Show current version
	@echo "$(GREEN)$(VERSION)$(NC)"

bump-patch: ## Bump patch version (e.g., 1.0.0 -> 1.0.1)
	@echo "$(BLUE)→ Bumping patch version...$(NC)"
	@$(MAKE) _bump-version TYPE=patch

bump-minor: ## Bump minor version (e.g., 1.0.0 -> 1.1.0)
	@echo "$(BLUE)→ Bumping minor version...$(NC)"
	@$(MAKE) _bump-version TYPE=minor

bump-major: ## Bump major version (e.g., 1.0.0 -> 2.0.0)
	@echo "$(BLUE)→ Bumping major version...$(NC)"
	@$(MAKE) _bump-version TYPE=major

_bump-version:
	@OLD_VERSION=$(VERSION); \
	IFS='.' read -r MAJOR MINOR PATCH <<< "$$OLD_VERSION"; \
	case "$(TYPE)" in \
		patch) NEW_VERSION="$$MAJOR.$$MINOR.$$((PATCH + 1))" ;; \
		minor) NEW_VERSION="$$MAJOR.$$((MINOR + 1)).0" ;; \
		major) NEW_VERSION="$$((MAJOR + 1)).0.0" ;; \
	esac; \
	echo "$(YELLOW)$$OLD_VERSION → $$NEW_VERSION$(NC)"; \
	sed -i.bak "s/\"version\": \"$$OLD_VERSION\"/\"version\": \"$$NEW_VERSION\"/" composer.json; \
	sed -i.bak "s/<version>$$OLD_VERSION<\/version>/<version>$$NEW_VERSION<\/version>/" src/Resources/config/plugin.xml; \
	sed -i.bak "s/const PLUGIN_VERSION = '$$OLD_VERSION'/const PLUGIN_VERSION = '$$NEW_VERSION'/" src/$(PLUGIN_NAME).php; \
	rm -f composer.json.bak src/Resources/config/plugin.xml.bak src/$(PLUGIN_NAME).php.bak; \
	echo "$(GREEN)✓ Version bumped to $$NEW_VERSION$(NC)"; \
	echo "$(YELLOW)Don't forget to commit the changes!$(NC)"

##@ Docker

docker-build: ## Build ZIP inside Docker container
	@echo "$(BLUE)→ Building in Docker...$(NC)"
	docker-compose exec app make release
	@echo "$(GREEN)✓ Docker build complete$(NC)"

docker-install: ## Install plugin in Docker container
	@echo "$(BLUE)→ Installing in Docker...$(NC)"
	docker-compose exec app sh -c "rm -rf var/cache/* && bin/console plugin:refresh && bin/console plugin:install --activate $(PLUGIN_NAME) && bin/console cache:clear"
	@echo "$(GREEN)✓ Plugin installed in Docker$(NC)"
