# AgendaFlow — Entrega da Etapa 5

## Resultado

A camada de comunicação automática está integrada aos agendamentos. Criação, reagendamento, confirmação e cancelamento geram intenções idempotentes, entregues por jobs; o scheduler cria confirmações e lembretes configuráveis; clientes respondem por links exclusivos.

## Funcionalidades entregues

- Configuração por empresa de confirmação, antecedência, lembretes e canais.
- Antecedências padrão de 48 horas, 24 horas e 2 horas.
- `MessagingProviderInterface`, `WhatsAppService` e provider local substituível.
- E-mails via Laravel Notifications dentro da fila.
- Fila dedicada `notifications`, três tentativas e backoff progressivo.
- Scheduler a cada minuto com processamento em lotes e trava contra sobreposição.
- Outbox/histórico com mensagem, canal, destinatário, data, tentativa, erro e status.
- Chave idempotente única que impede duplicidade por evento, canal, horário e antecedência.
- Confirmação e cancelamento sem login por token exclusivo.
- Cancelamento libera o slot e avisa cliente, proprietários e administradores.
- Tela administrativa responsiva de configuração e histórico.

## Segurança

- O token é buscado somente pelo hash SHA-256; a cópia necessária ao scheduler usa cast criptografado do Laravel.
- Links públicos não contêm IDs numéricos.
- GET não altera estado; confirmação e cancelamento usam POST, CSRF e rate limiting.
- Middleware do token resolve uma empresa ativa, instala o tenant durante a requisição e o remove no `finally`.
- Jobs transportam apenas o ID do log e restauram o tenant antes de acessar o agendamento.
- Nenhum provider externo é chamado durante a requisição HTTP.

## Operação

```bash
php artisan queue:work --queue=notifications,default
php artisan schedule:work
```

Para simular manualmente a varredura:

```bash
php artisan appointments:dispatch-reminders
```

No `.env`, `MESSAGING_DRIVER=local` registra WhatsApp no log. O binding pode ser trocado por outro adapter sem alterar jobs ou regras de agendamento.

## Rotas

| Ação | Rota |
|---|---|
| Configuração e histórico | `GET /comunicacao` |
| Salvar configuração | `PUT /comunicacao` |
| Exibir confirmação | `GET /booking/{token}/confirm` |
| Confirmar presença | `POST /booking/{token}/confirm` |
| Exibir cancelamento | `GET /booking/{token}/cancel` |
| Cancelar atendimento | `POST /booking/{token}/cancel` |

## Verificações

- 75 testes e 384 asserções aprovados, incluindo idempotência, scheduler, provider local, configuração e respostas públicas.
- PHPStan nível 7 e Pint sem erros.
- ESLint, TypeScript e builds cliente/SSR sem erros.
- Migrations e seed executáveis em SQLite e MySQL.

## Próximo escopo

A Etapa 6 adicionará relatórios operacionais, financeiros e de recorrência.
