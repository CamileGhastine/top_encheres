# Installation du projet Symfony (Docker) Top-Encheres

## Prérequis

* Docker
* Docker Compose

## Conteneurs utilisés

* `encheres_php`
* `encheres_nginx`
* `encheres_mysql`
* `encheres_phpmyadmin`
* `encheres_mailhog`

## Étapes d’installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/CamileGhastine/top_encheres.git
cd top_encheres
```

### 2. Démarrer les conteneurs

Vérifier que les ports ne sont pas déjà utilisés

```bash
docker-compose up -d --build
```

### 3. Installer les dépendances Symfony

```bash
docker exec -it encheres_php composer install
```

### 4. Configurer l’environnement

Créer le fichier `.env.local` :

```bash
cp .env .env.local
```

Vérifier la configuration de la base de données :

```
DATABASE_URL="mysql://user:pwd@encheres:3306/db_name"
```

Renseigner votre clef API Stripe
```
STRIPE_SECRET=sk_test_***
```

Adapter les autres variables d’environnement si nécessaire


### 5. Lancer les migrations

```bash
docker exec -it encheres_php php bin/console doctrine:migrations:migrate
```

### 6. Charger les fausses donées si besoin

Voir le cahier des charges

## Accès aux services

* Application Symfony : http://localhost:8080
* PHPMyAdmin : http://localhost:8081
* MailHog : http://localhost:8025
Les emails envoyés par l’application sont visibles via MailHog

## Commandes utiles

* Accéder au conteneur PHP :

```bash
docker exec -it encheres_php sh
```

* Voir les logs :

```bash
docker-compose logs -f
```

* Arrêter les conteneurs :

```bash
docker-compose down
```
