# AgendaFlow — Entrega da Etapa 3

## Resultado

A agenda operacional está pronta. Proprietários e administradores podem criar, editar, reagendar e atualizar atendimentos, cadastrar clientes durante o fluxo e bloquear períodos; profissionais visualizam somente a própria agenda e podem operar os status dos próprios atendimentos.

## Funcionalidades entregues

- Visualização semanal principal e visualização diária, com navegação por período e filtro de profissional.
- Criação manual com cliente existente ou cadastro inline.
- Disponibilidade calculada por jornada, substituição profissional, intervalo, duração, buffer, ocupações, bloqueios, antecedência e fuso.
- Bloqueios por profissional ou para toda a empresa.
- Detalhes e edição de agendamento no contexto da agenda.
- Transições para confirmado, cancelado, concluído e não compareceu.
- Histórico de eventos e snapshots de duração e preço.
- Atualização das datas de primeiro e último agendamento do cliente.

## Integridade e concorrência

- Datas de agenda são armazenadas em UTC e convertidas usando o fuso da empresa.
- `ends_at` marca o fim do serviço; `blocks_until` inclui o intervalo técnico.
- O profissional é bloqueado no banco dentro da transação antes da última checagem e gravação, evitando dupla reserva concorrente.
- O backend nunca confia no slot mostrado anteriormente pelo navegador: ele recalcula a disponibilidade ao salvar.
- Consultas, validações e policies permanecem restritas ao tenant atual.
- Status terminais não podem voltar a estados ativos.

## Rotas

| Ação | Rota |
|---|---|
| Agenda | `GET /agenda` |
| Disponibilidade | `GET /agenda/disponibilidade` |
| Criar/editar | `POST /agenda/agendamentos`, `PATCH /agenda/agendamentos/{uuid}` |
| Atualizar status | `PATCH /agenda/agendamentos/{uuid}/status` |
| Criar/remover bloqueio | `POST /agenda/bloqueios`, `DELETE /agenda/bloqueios/{uuid}` |

## Interface

- Em mobile, os dias são empilhados para preservar leitura e áreas de toque; em telas largas, a grade cresce até sete colunas.
- Status combinam rótulo textual, borda e cor, inclusive no tema escuro.
- Formulários apresentam validação próxima ao campo, loading de disponibilidade e ações com no mínimo 44px.
- Consultas de slot descartam respostas obsoletas quando o usuário muda rapidamente profissional, serviço ou data.

## Verificações

- 61 testes e 271 asserções aprovados; a suíte de integração cobre tenancy, disponibilidade composta, criação inline, persistência UTC, conflito de horário, transições e permissões profissionais.
- PHPStan nível 7, Pint, Prettier, ESLint e TypeScript sem erros.
- Build cliente e SSR concluídos.

## Próximo escopo

A Etapa 4 deve consumir a mesma disponibilidade em uma página pública. Notifications, filas, lembretes e confirmação por token permanecem na Etapa 5.
