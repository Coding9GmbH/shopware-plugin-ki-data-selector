#
# Makefile for Coding9KiDataSelector Plugin
#

.PHONY: help
.DEFAULT_GOAL := help

PLUGIN_NAME := Coding9KiDataSelector
PLUGIN_DIR := src/custom/plugins/$(PLUGIN_NAME)
VERSION := $(shell grep '"version"' $(PLUGIN_DIR)/composer.json | cut -d'"' -f4)
ZIP_NAME := $(PLUGIN_NAME)-$(VERSION).zip
VERSIONS_DIR := versions

help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: clean ## Build plugin ZIP for distribution
	@echo "Building $(PLUGIN_NAME) version $(VERSION)..."
	@mkdir -p $(VERSIONS_DIR)
	@cd $(PLUGIN_DIR) && zip -r ../../../../$(VERSIONS_DIR)/$(ZIP_NAME) . \
		-x "*.git*" \
		-x "*node_modules/*" \
		-x "*vendor/*" \
		-x "*.DS_Store" \
		-x "*BUILD.md" \
		-x "*CREATE_ZIP.md" \
		-x "*COMPATIBILITY.md" \
		-x "*MULTI_VERSION_SUPPORT.md" \
		-x "*ADMIN_COMPATIBILITY_SUMMARY.md" \
		-x "Makefile"
	@echo "✓ Plugin ZIP created: $(VERSIONS_DIR)/$(ZIP_NAME)"

clean: ## Clean build artifacts
	@echo "Cleaning build artifacts..."
	@rm -rf $(VERSIONS_DIR)/*.zip
	@echo "✓ Clean complete"

install: ## Install plugin in local Shopware instance (requires Docker)
	@echo "Installing plugin..."
	docker exec -u root shop bash -c 'php bin/console plugin:refresh'
	docker exec -u root shop bash -c 'php bin/console plugin:install --activate $(PLUGIN_NAME)'
	docker exec -u root shop bash -c 'php bin/console cache:clear'
	@echo "✓ Plugin installed"

uninstall: ## Uninstall plugin from local Shopware instance (requires Docker)
	@echo "Uninstalling plugin..."
	docker exec -u root shop bash -c 'php bin/console plugin:uninstall $(PLUGIN_NAME)'
	docker exec -u root shop bash -c 'php bin/console cache:clear'
	@echo "✓ Plugin uninstalled"

setup-dev: ## Setup development environment with Docker
	@echo "Setting up development environment..."
	@echo "Switching to init docker-compose configuration..."
	@rm -f docker-compose.yml
	@cp docker-compose.yml.init docker-compose.yml
	@echo "Starting Docker containers..."
	docker-compose down
	docker-compose up -d
	@echo "Copying plugin files..."
	docker exec -u root shop bash -c 'rm -rf /var/www/html/custom/plugins'
	docker cp ./$(PLUGIN_DIR) shop:/var/www/html/custom/plugins/
	@echo "Downloading Shopware files..."
	mkdir -p src
	docker cp shop:/var/www/html/. ./src
	@echo "Switching back to standard docker-compose configuration..."
	@rm -f docker-compose.yml
	@cp docker-compose.yml.bkp docker-compose.yml
	@echo "Restarting containers..."
	docker-compose down
	docker-compose up -d
	@echo "Installing plugin..."
	@$(MAKE) install
	@echo ""
	@echo "⚠️  IMPORTANT: Configure API Key"
	@echo "Run the following command with your OpenAI API key:"
	@echo "docker exec -u root shop bash -c 'php bin/console system:config:set $(PLUGIN_NAME).config.apiKey \"YOUR_API_KEY_HERE\"'"
	@echo ""
	@echo "✓ Development environment ready"

download: ## Download files from Docker container to local
	@echo "Downloading files from container..."
	mkdir -p src
	docker cp shop:/var/www/html/. ./src
	@echo "✓ Download complete"
