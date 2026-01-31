.PHONY: help postgres

help:
	@echo "Available commands:"
	@echo "  make postgres     - Start PostgreSQL in Docker container"
	@echo "  make db-create    - Create PostgreSQL database with local credentials"
	@echo "  make db-start     - Start PostgreSQL service"
	@echo "  make db-stop      - Stop PostgreSQL service"
	@echo "  make db-reset     - Drop and recreate database"
	@echo "  make db-connect   - Connect to PostgreSQL database"

postgres:
	@echo "Starting PostgreSQL in Docker..."
	@docker run --name abdiku-postgres -e POSTGRES_USER=local -e POSTGRES_PASSWORD=local -e POSTGRES_DB=postgres -p 5432:5432 -d postgres:17 || docker start abdiku-postgres
	@echo "PostgreSQL is running on localhost:5432"
	@echo "  User: local"
	@echo "  Password: local"
	@echo "  Database: postgres"
