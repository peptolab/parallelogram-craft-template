.PHONY: install dev build setup

install:
	composer install
	npm install

setup: install
	cp -n .env.example.dev .env || true
	./craft setup

dev:
	npm run dev

build:
	npm run build
