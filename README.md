# HabitQuest v2

Application de gamification par quêtes (Symfony API + React) : combat, boutique, profil, admin.

**Version actuelle : 2.0.0**

## Prérequis

PHP 8.2+, Composer, Node 18+, PostgreSQL.

## Lancer l'application

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php -S 127.0.0.1:8000 -t public

cd frontend
npm install
npm run dev
```

Le front (port 5173) proxy `/api` vers le backend (8000). Démarrer l'API avant le front.

