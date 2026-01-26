# Documentation de la Pipeline CircleCI

## Vue d'ensemble

Cette documentation décrit la configuration de la pipeline CI/CD pour le projet DevSecOps utilisant CircleCI. La pipeline est définie dans le fichier `.circleci/config.yml` et suit les principes DevSecOps en intégrant des vérifications de qualité de code, de sécurité, de tests et de déploiement automatisé.

## Structure de la Configuration

La configuration utilise CircleCI 2.1 et comprend :

- **Executors** : Environnements d'exécution pour les jobs
- **Jobs** : Tâches individuelles
- **Workflows** : Orchestration des jobs

### Executors

Trois executors sont définis :

1. **php-executor** : Utilise l'image `cimg/php:8.2` pour les tâches PHP
2. **builder-executor** : Utilise `cimg/php:8.2-node` pour les builds incluant Node.js
3. **simple-executor** : Utilise `cimg/base:stable` pour les déploiements simples

## Jobs Détaillés

### 1. debug-info
**Executor** : php-executor  
**Description** : Job de débogage qui affiche des informations sur l'environnement d'exécution.  
**Étapes** :
- Exécution de commandes pour afficher l'utilisateur, le répertoire home, le shell, l'OS, le PATH, le répertoire courant et la date.
- Affichage de toutes les variables d'environnement.

**Utilité** : Aide au diagnostic des problèmes dans la pipeline.

### 2. build-setup
**Executor** : php-executor  
**Description** : Prépare l'environnement en installant les dépendances PHP.  
**Étapes** :
- Checkout du code source
- Restauration du cache des dépendances (basé sur `composer.lock` ou `composer.json`)
- Installation des dépendances via `composer install`
- Sauvegarde du cache
- Persistance du workspace pour les jobs suivants

**Cache** : Utilise les clés `v2-dependencies-{{ checksum "composer.lock" }}` pour optimiser les builds.

### 3. lint-phpcs
**Executor** : php-executor  
**Description** : Vérifie la conformité du code avec les standards de codage.  
**Étapes** :
- Attachement du workspace
- Exécution de PHP_CodeSniffer avec la configuration `phpcs.xml`
- Génération d'un rapport Checkstyle
- Stockage du rapport comme artefact

**Configuration** : Utilise PSR12 et PHPCompatibility pour PHP 7.4+.

### 4. lint-phpmd
**Executor** : php-executor  
**Description** : Analyse statique du code pour détecter les problèmes de qualité.  
**Étapes** :
- Attachement du workspace
- Création du répertoire `reports`
- Exécution de PHPMD avec les règles cleancode, codesize, controversial, design, naming, unusedcode
- Génération de rapports texte et XML
- Stockage des artefacts

### 5. lint-php-doc-check
**Executor** : php-executor  
**Description** : Vérifie la documentation PHP.  
**Étapes** :
- Attachement du workspace
- Création du répertoire `reports`
- Exécution de php-doc-check sur le répertoire `src`
- Sauvegarde de la sortie dans un fichier
- Stockage de l'artefact

### 6. security-check-dependencies
**Executor** : php-executor  
**Description** : Vérifie les vulnérabilités de sécurité dans les dépendances.  
**Étapes** :
- Attachement du workspace
- Téléchargement de local-php-security-checker
- Exécution du checker sur les dépendances
- Génération d'un rapport JSON
- Stockage de l'artefact

### 7. test-phpunit
**Executor** : php-executor  
**Description** : Exécute les tests unitaires.  
**Étapes** :
- Attachement du workspace
- Vérification de la présence de `phpunit.xml`
- Exécution de PHPUnit sur la suite de tests Unit

**Condition** : Le job est ignoré si `phpunit.xml` est absent.

### 8. metrics-phpmetrics
**Executor** : php-executor  
**Description** : Génère des métriques de complexité du code.  
**Étapes** :
- Attachement du workspace
- Création du répertoire `reports`
- Exécution de PHPMetrics avec rapports HTML et JSON
- Exclusion des répertoires vendor et tests
- Stockage des artefacts

### 9. metrics-phploc
**Executor** : php-executor  
**Description** : Génère des métriques de lignes de code.  
**Étapes** :
- Attachement du workspace
- Création du répertoire `reports`
- Exécution de PHPLOC avec rapport JSON et texte
- Exclusion des répertoires vendor et tests
- Stockage des artefacts

### 10. build-docker-image
**Executor** : builder-executor  
**Description** : Construit et pousse l'image Docker vers GitHub Container Registry.  
**Étapes** :
- Checkout du code
- Configuration de Docker distant avec cache des layers
- Construction de l'image avec des arguments de build (date, tag, commit, etc.)
- Push vers GHCR

**Variables** : Utilise des variables d'environnement comme `GHCR_USERNAME`, `GHCR_PAT`, etc.

### 11. deploy-ssh-staging
**Executor** : simple-executor  
**Description** : Déploie sur l'environnement de staging via SSH.  
**Étapes** :
- Ajout des clés SSH
- Connexion SSH au serveur de staging
- Pull des changements Git
- Installation des dépendances
- Redémarrage de PHP-FPM

**Filtre** : Branches `release/*`

### 12. deploy-ssh-production
**Executor** : simple-executor  
**Description** : Déploie sur l'environnement de production via SSH.  
**Étapes** :
- Ajout des clés SSH
- Connexion SSH au serveur de production
- Arrêt et suppression du conteneur existant
- Pull de la nouvelle image Docker
- Lancement du nouveau conteneur

**Filtre** : Branches `main` ou `master`

## Workflows

### main_workflow
**Déclenchement** : Toutes les branches  
**Jobs** :
- `debug-info` (parallèle)
- `build-setup` (parallèle)
- Jobs de lint, sécurité, tests, métriques (dépendent de `build-setup`)
- `build-docker-image` (dépend de `test-phpunit`)
- `deploy-ssh-production` (dépend de `build-docker-image`, branches main/master)
- `deploy-ssh-staging` (dépend de `build-docker-image`, branches release/*)

### container_workflow
**Déclenchement** : Branches master, main, develop, feature/*, release/*, hotfix/*, bugfix/*  
**Jobs** :
- `build-docker-image` uniquement

## Extension de la Pipeline

### Ajouter un Nouveau Job

1. Définir le job dans la section `jobs`
2. Spécifier l'executor approprié
3. Ajouter les étapes nécessaires
4. Intégrer le job dans un workflow avec `requires` si nécessaire

### Exemple d'Extension

Pour ajouter un job de test d'intégration :

```yaml
test-integration:
  executor: php-executor
  steps:
    - *attach_workspace
    - run:
        name: Run Integration Tests
        command: ./vendor/bin/phpunit --testsuite=Feature
```

Puis l'ajouter au workflow :

```yaml
workflows:
  main_workflow:
    jobs:
      # ... autres jobs
      - test-integration:
          requires:
            - build-setup
```

### Variables d'Environnement

Les variables suivantes sont configurées dans CircleCI :

- `GHCR_USERNAME` : Nom d'utilisateur GitHub pour GHCR
- `GHCR_PAT` : Personal Access Token pour GHCR
- `STAGING_SSH_FINGERPRINT` : Empreinte de la clé SSH de staging
- `STAGING_SSH_USER` : Utilisateur SSH pour staging
- `STAGING_SSH_HOST` : Hôte SSH pour staging
- `STAGING_DEPLOY_DIRECTORY` : Répertoire de déploiement sur staging
- `STAGING_DOCKER_IMAGE` : Image Docker pour staging
- `INFISICAL_TOKEN`, `INFISICAL_PROJECT_ID`, `INFISICAL_ENVIRONMENT` : Pour la gestion des secrets

Dans Infisical on a défini les variables métiers :
- `APP_SECRET` : Secret de l'application

### Optimisations

- **Cache** : Utilisé pour les dépendances Composer
- **Workspace** : Partage des fichiers entre jobs
- **Artefacts** : Stockage des rapports pour analyse post-build
- **Filtres de branches** : Contrôle du déploiement par branche
