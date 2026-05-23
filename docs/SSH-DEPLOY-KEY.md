# Generar clave SSH para GitHub Actions

Use una clave **dedicada al deploy** (no su clave personal).

## Linux / macOS / Git Bash (Windows)

```bash
ssh-keygen -t ed25519 -C "github-deploy-hoteles" -f ~/.ssh/hoteles_deploy -N ""
```

Archivos generados:

| Archivo | Uso |
|---------|-----|
| `~/.ssh/hoteles_deploy` | Secret `DEPLOY_PRIVATE_KEY` o `STAGING_DEPLOY_PRIVATE_KEY` |
| `~/.ssh/hoteles_deploy.pub` | Servidor → `~/.ssh/authorized_keys` del usuario deploy |

## Copiar clave pública al servidor

```bash
ssh-copy-id -i ~/.ssh/hoteles_deploy.pub deploy@203.0.113.10
```

O manualmente en el servidor (`~/.ssh/authorized_keys`):

```
ssh-ed25519 AAAA... github-deploy-hoteles
```

## Secret en GitHub

Settings → Secrets → Actions → New repository secret

- Nombre: `STAGING_DEPLOY_PRIVATE_KEY` (staging) o `DEPLOY_PRIVATE_KEY` (producción)
- Valor: contenido **completo** del archivo privado, incluyendo:

```
-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----
```

## Probar conexión

```bash
ssh -i ~/.ssh/hoteles_deploy deploy@203.0.113.10 "cd /var/www/hoteles-staging && git status"
```

## Checklist de secrets

Ver [`.github/SECRETS-CHECKLIST.md`](../.github/SECRETS-CHECKLIST.md)

Guía completa: [`DEPLOY-GITHUB.md`](../docs/DEPLOY-GITHUB.md)
