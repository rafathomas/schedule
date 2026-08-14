# AgendaFlow

SaaS multiempresa de agendamento com foco em reduzir faltas e automatizar confirmações. O projeto contém as **Etapas 1 a 6**: fundação, cadastros, agenda operacional, agendamento público, comunicação automática e inteligência operacional.

Cada empresa pode personalizar a cor principal e a logo usadas no painel e no agendamento público em `/personalizacao`.

## Stack

- Laravel 13 / PHP 8.3+
- React 19 / TypeScript / Inertia 3
- Tailwind CSS 4 / shadcn / Radix
- MySQL 8.4, Redis e Mailpit via Laravel Sail
- Vite 8

## Início rápido com Sail

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Acesse `http://localhost`. O Mailpit fica disponível em `http://localhost:8025`.

Para testar no WhatsApp, defina `APP_PUBLIC_URL` com um hostname que aponte para o computador na mesma rede, como `http://192.168.0.10.nip.io`, ou use uma URL HTTPS de túnel. O hostname `nip.io` resolve para o IP incluído no endereço e permite que o WhatsApp reconheça o caminho completo como link. `localhost` e IPs puros não são adequados para esse teste.

O Compose mantém a fila de notificações e o scheduler ativos automaticamente. Para acompanhar os processos:

```bash
docker compose logs -f queue scheduler
```

O Sail também inicia a Evolution API em `http://localhost:8080`, com PostgreSQL próprio e um namespace separado no Redis. Em **Comunicação → WhatsApp da empresa**, clique em **Gerar QR Code** e leia o código em **WhatsApp → Aparelhos conectados**. Cada empresa recebe uma instância independente e os envios usam o número conectado por ela. E-mails continuam disponíveis no Mailpit.

Mantenha a mesma `EVOLUTION_API_KEY` no AgendaFlow e na Evolution API. Em produção, troque a chave do `.env`, use HTTPS para a URL pública e mantenha a API fora do acesso público sempre que possível.

Usuário de demonstração após o seed:

```text
E-mail: ana@studiobella.test
Senha: password
```

Use essas credenciais apenas no ambiente local.

## Qualidade

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=512M
npm run format:check
npm run lint:check
npm run types:check
npm run build:ssr
composer audit
npm audit --omit=dev
```

## Documentação

- [Arquitetura das Etapas 1 a 6](docs/architecture.md)
- [Entrega da Etapa 1](docs/stage-1-delivery.md)
- [Entrega da Etapa 2](docs/stage-2-delivery.md)
- [Entrega da Etapa 3](docs/stage-3-delivery.md)
- [Entrega da Etapa 4](docs/stage-4-delivery.md)
- [Entrega da Etapa 5](docs/stage-5-delivery.md)
- [Entrega da Etapa 6](docs/stage-6-delivery.md)
- [Design system](design-system/agendaflow/MASTER.md)
- [Regras visuais dos cadastros](design-system/agendaflow/pages/cadastros.md)
- [Regras visuais da agenda](design-system/agendaflow/pages/agenda.md)
- [Regras do agendamento público](design-system/agendaflow/pages/public-booking.md)
- [Regras de comunicação](design-system/agendaflow/pages/communication.md)
- [Regras de relatórios](design-system/agendaflow/pages/reports.md)
- [Regras de personalização da marca](design-system/agendaflow/pages/settings-branding.md)

## Próxima etapa

A Etapa 7 adicionará planos, assinaturas e administração da plataforma SaaS.
