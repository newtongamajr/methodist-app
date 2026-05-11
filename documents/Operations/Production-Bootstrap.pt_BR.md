# Bootstrap em produção

A aplicação inclui dois comandos artisan que tornam o deploy inicial em
produção seguro, sem precisar mexer no banco manualmente.

## 1) Migrar e popular dados canônicos

```bash
php artisan app:install
```

Equivale a:

```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=EcclesiasticalRegionSeeder --force
```

`app:install` é **idempotente**:

- Roles (`global_manager`, `local_manager`, `user`) e o conjunto de
  permissions usado pelas policies são atualizados via `Role::findOrCreate`
  e `syncPermissions`. Rodar de novo não duplica linhas.
- As 10 regiões eclesiásticas (RE1–RE8, REMA, REMNE) são upserted pelo
  `code` único.

Use `--fresh` para dropar e recriar todas as tabelas (com confirmação em
`production`):

```bash
php artisan app:install --fresh
```

`DemoChurchSeeder` e `DemoUserSeeder` só rodam em `local` / `testing` —
produção nunca recebe dados de demonstração.

## 2) Criar (ou promover) o primeiro super usuário

Não existe UI para conceder `global_manager` porque, por definição, ninguém
ainda tem poderes administrativos. Use:

```bash
php artisan app:make-super --email=admin@seudominio.com
```

Se o e-mail já existir, a conta é **promovida** para `global_manager`.
Se for novo, o comando **cria** a conta — pergunta nome + senha
(ou aceita `--name=… --password=…`).

Depois disso, esse usuário pode:

- Cadastrar igrejas em `/admin/churches`. Ao criar a igreja, o formulário
  também cria o **usuário master** dela (um `local_manager` vinculado à
  igreja).
- Administrar usuários em `/admin/users` para qualquer igreja.

O usuário master, por sua vez, pode entrar e criar outros admins
`local_manager` **para sua própria igreja** pela mesma página
`/admin/users` (igreja + role são fixados no servidor).

## Dependências de sistema

Dois shell scripts em `scripts/` cuidam dos pedaços que precisam de `sudo`
em um host novo. São idempotentes — rodar duas vezes é seguro.

```bash
# Limites de upload do PHP (100M / 100M), reload do php-fpm, permissões
# de storage/ + bootstrap/cache para o user www-data do Docker.
sudo bash scripts/setup-host.sh

# Pipeline de mídia: php8.4-imagick + imagemagick + ghostscript + ffmpeg,
# mais o patch na policy do ImageMagick que destrava a renderização de PDFs.
# Depois disso, os image generators Pdf e Video da Spatie produzem thumbs
# de primeira página / primeiro frame para cada upload de PDF e vídeo.
sudo bash scripts/install-media-deps.sh
```

Os pacotes composer dos quais esses generators dependem
(`spatie/pdf-to-image`, `php-ffmpeg/php-ffmpeg`) acompanham o projeto —
`composer install` resolve o lado da aplicação.

## Sequência sugerida de deploy

```bash
# 0. Configure o .env (credenciais do DB, APP_KEY, MAIL_*, TINYMCE_API_KEY)

# 1. Dependências de sistema (sudo)
sudo bash scripts/setup-host.sh
sudo bash scripts/install-media-deps.sh

# 2. Dependências do projeto
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Caches da aplicação
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Banco
php artisan app:install

# 5. Promova o primeiro super usuário
php artisan app:make-super --email=admin@seudominio.com

# 6. Symlink de storage (uma vez)
php artisan storage:link

# 7. (Opcional) regere as conversões `thumb` de qualquer mídia já existente,
#     útil quando o stack de mídia é adicionado a um host que já tinha uploads.
php artisan media-library:regenerate
```

Após o passo 5 o super usuário consegue entrar em `/login` e começar a
configurar igrejas e usuários master pela área administrativa.