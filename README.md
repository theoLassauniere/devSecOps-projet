# devSecOps-projet
Projet DevSecOps 2026

## Lancer l'image Docker localement

Nécessite Docker installé. A la racine du projet, faire :

```bash
docker compose build
docker compose up -d
```

Puis pour finaliser l'installation et la vérifier :

```bash
docker ps # Pour récupérer le container_id
docker exec -it <container_id> bash
php -v
composer install
sqlite3 --version
exit
```

Se rendre sur http://localhost:8080/ pour voir l'app PHP.

## Pipeline CI/CD avec CircleCI

Ce projet utilise CircleCI pour l'intégration continue et le déploiement continu (CI/CD). La configuration est définie dans `.circleci/config.yml`.

### Workflows

- **main_workflow** : Workflow principal qui exécute tous les jobs de vérification qualité, tests, métriques, construction d'image Docker et déploiement.
- **container_workflow** : Workflow spécialisé pour la construction d'images Docker sur certaines branches (master, main, develop, feature/*, release/*, hotfix/*, bugfix/*).

### Jobs

Le pipeline comprend les jobs suivants :

1. **debug-info** : Affiche des informations de débogage sur l'environnement.
2. **build-setup** : Installe les dépendances PHP via Composer et met en cache.
3. **lint-phpcs** : Vérifie la conformité du code avec les standards PSR12 et PHPCompatibility.
4. **lint-phpmd** : Analyse le code avec PHPMD pour détecter les problèmes de qualité.
5. **lint-php-doc-check** : Vérifie la documentation PHP.
6. **security-check-dependencies** : Vérifie les vulnérabilités de sécurité dans les dépendances.
7. **test-phpunit** : Exécute les tests unitaires avec PHPUnit.
8. **metrics-phpmetrics** : Génère des métriques de code avec PHPMetrics.
9. **metrics-phploc** : Génère des métriques de lignes de code avec PHPLOC.
10. **build-docker-image** : Construit et pousse l'image Docker vers GitHub Container Registry (GHCR).
11. **deploy-ssh-staging** : Déploie sur l'environnement de staging via SSH (branches release/*).
12. **deploy-ssh-production** : Déploie sur l'environnement de production via SSH (branches main/master).

Pour plus de détails sur la configuration et l'extension du pipeline, consultez la documentation dédiée : [Documentation CircleCI Pipeline](docs/CircleCI-Pipeline.md).
