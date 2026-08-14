# AgendaFlow — Entrega da Etapa 4

## Resultado

Cada empresa ativa possui uma página pública em `/agenda/{slug}`. O visitante escolhe serviço, profissional ou “qualquer disponível”, data e horário, informa contato, revisa os dados e confirma sem criar conta.

## Funcionalidades entregues

- Jornada pública em seis etapas com progresso e resumo persistente.
- Catálogo e profissionais isolados pelo slug da empresa.
- Opção de qualquer profissional disponível.
- Disponibilidade calculada pelo mesmo serviço usado no painel administrativo.
- Cadastro ou reutilização do cliente pelo WhatsApp.
- Revalidação transacional antes da reserva e escolha segura de profissional alternativo.
- Página de sucesso com empresa, serviço, profissional, data, horário, endereço e contato.
- Download de evento `.ics` para adicionar o atendimento ao calendário.
- Link público ativado no dashboard.

## Segurança e privacidade

- Middleware público aceita apenas empresas ativas e instala o tenant durante a requisição.
- Serviços, profissionais, agendamentos e páginas de sucesso permanecem sob o escopo do slug resolvido.
- UUIDs são usados nos links de sucesso; IDs internos não são expostos.
- Endpoints de consulta e gravação possuem rate limiting.
- A disponibilidade é recalculada dentro da transação após bloquear os profissionais elegíveis.
- Nenhuma notificação ou confirmação automática foi antecipada da Etapa 5.

## Rotas

| Ação | Rota |
|---|---|
| Jornada pública | `GET /agenda/{slug}` |
| Consultar horários | `GET /agenda/{slug}/disponibilidade` |
| Confirmar reserva | `POST /agenda/{slug}` |
| Sucesso | `GET /agenda/{slug}/sucesso/{appointment}` |
| Calendário | `GET /agenda/{slug}/calendario/{appointment}.ics` |

## Verificações

- 67 testes e 324 asserções aprovados; a suíte cobre catálogo por tenant, empresa inativa, disponibilidade agregada, seleção de profissional alternativo, conflito, sucesso e `.ics`.
- PHPStan nível 7, Pint, Prettier, ESLint e TypeScript sem erros.
- Interface preparada para mobile, landscape, desktop e temas claro/escuro.

## Próximo escopo

A Etapa 5 deverá adicionar notificações, fila, scheduler, lembretes, confirmação e cancelamento por token seguro.
