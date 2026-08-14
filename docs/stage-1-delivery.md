# AgendaFlow — Entrega da Etapa 1

## Resultado

A fundação executável do SaaS está pronta com autenticação, verificação de e-mail, 2FA/passkeys, layout React/Inertia, onboarding inicial, ambiente Sail e isolamento multiempresa no backend.

## Arquivos principais criados ou personalizados

### Domínio e tenancy

- `app/Models/Company.php`
- `app/Models/CompanyOwnedModel.php`
- `app/Models/User.php`
- `app/Concerns/BelongsToCompany.php`
- `app/Support/Tenancy/CurrentCompany.php`
- `app/Http/Middleware/ResolveCurrentCompany.php`
- `app/Policies/CompanyPolicy.php`

### Cadastro e onboarding

- `app/Actions/Fortify/CreateNewUser.php`
- `app/Http/Controllers/AppEntryController.php`
- `app/Http/Controllers/OnboardingController.php`
- `app/Http/Requests/UpdateCompanyProfileRequest.php`
- `resources/js/pages/auth/register.tsx`
- `resources/js/pages/auth/login.tsx`
- `resources/js/pages/onboarding/index.tsx`

### Interface

- `resources/js/pages/dashboard.tsx`
- `resources/js/components/app-sidebar.tsx`
- `resources/js/components/nav-main.tsx`
- `resources/js/components/app-logo-icon.tsx`
- `resources/js/layouts/auth/auth-split-layout.tsx`
- `resources/css/app.css`
- `design-system/agendaflow/MASTER.md`

### Ambiente e qualidade

- `compose.yaml`
- `.env.example`
- `phpstan.neon`
- `tests/Feature/Tenancy/CurrentCompanyTest.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `database/factories/CompanyFactory.php`
- `database/seeders/DatabaseSeeder.php`

Os demais arquivos de autenticação, componentes e configuração vêm do starter oficial React do Laravel e permanecem dentro do projeto para customização completa.

## Migrations

| Migration | Responsabilidade |
|---|---|
| `0001_01_01_000000_create_users_table` | Usuários, recuperação de senha e sessões |
| `0001_01_01_000001_create_cache_table` | Cache e locks |
| `0001_01_01_000002_create_jobs_table` | Queue, batches e falhas |
| `2024_01_01_000000_create_passkeys_table` | Chaves de acesso WebAuthn |
| `2025_08_14_170933_add_two_factor_columns_to_users_table` | Autenticação em dois fatores |
| `2026_08_11_000000_create_companies_and_memberships` | Empresas, memberships, papéis e empresa ativa |

## Rotas da fundação

| Método | URI | Finalidade |
|---|---|---|
| `GET` | `/` | Entrada pública |
| `GET/POST` | `/register` | Cadastro de responsável e empresa |
| `GET/POST` | `/login` | Autenticação |
| `GET` | `/verify-email` | Verificação de e-mail |
| `GET` | `/app` | Resolve o destino após autenticar |
| `GET` | `/dashboard` | Painel inicial autenticado |
| `GET` | `/onboarding` | Configuração inicial da empresa |
| `PATCH` | `/onboarding/company` | Atualiza dados autorizados do tenant |
| `GET/PATCH/DELETE` | `/settings/profile` | Perfil do usuário |
| `GET/PUT` | `/settings/security` | Senha, 2FA e passkeys |

As rotas empresariais usam `auth`, `verified` e `company`. As rotas Fortify também aplicam CSRF e rate limiting.

## Como testar

Execução rápida sem Docker:

```bash
composer install
npm install
php artisan migrate:fresh --seed
php artisan test
npm run types:check
npm run build
```

Execução equivalente pelo ambiente oficial do projeto:

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail test
./vendor/bin/sail npm run build
```

## Verificações concluídas

- 43 testes e 146 asserções aprovados.
- PHPStan nível 7 sem erros.
- Pint, Prettier, ESLint e TypeScript sem erros.
- Build cliente e SSR concluídos.
- Docker Compose validado.
- Composer e npm sem advisories conhecidos.
- Verificação visual em 375 px, 812 px paisagem e 1440 px.
- Sem rolagem horizontal nos breakpoints verificados.
- Inputs e ações principais com área mínima de 44 px.

## Escopo não iniciado

Profissionais, serviços, clientes, horários, agenda, disponibilidade, página pública, notificações, relatórios e planos permanecem para suas etapas definidas no briefing.
