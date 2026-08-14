# AgendaFlow — Entrega da Etapa 2

## Resultado

A camada de cadastros está pronta e integrada à fundação multiempresa. Proprietários e administradores podem manter profissionais, serviços, clientes e horários; profissionais autenticados têm leitura da equipe, catálogo e grade semanal, sem permissão de escrita.

## Funcionalidades entregues

- Profissionais com contato, apresentação, status, foto opcional e relação N:N com serviços.
- Serviços com categoria, duração, intervalo técnico, preço e ativação.
- Clientes com busca por nome/telefone/e-mail, contato, nascimento, observações e perfil preparado para o histórico da Etapa 3.
- Jornada semanal padrão da empresa e substituições por profissional.
- Dashboard com totais reais de clientes, profissionais ativos e serviços ativos.
- Checklist de onboarding ligado aos primeiros cadastros e aos horários.
- Seed do Studio Bella com 3 profissionais, 6 serviços, 15 clientes e 7 dias de atendimento.

## Segurança e tenancy

- Todos os recursos empresariais estendem `CompanyOwnedModel` e recebem o escopo de `company_id`.
- UUIDs são usados nas URLs públicas; IDs numéricos não são expostos.
- Form Requests restringem validações de unicidade e existência ao tenant atual.
- Policies diferenciam leitura e gestão por papel.
- Recursos recebidos pela URL são consultados dentro do tenant já resolvido; um UUID externo retorna 404.
- Atualização de profissional/serviços e gravação semanal de horários usam transações.

## Rotas

| Recurso | Rotas |
|---|---|
| Profissionais | `GET/POST /profissionais`, `PATCH/DELETE /profissionais/{uuid}` |
| Serviços | `GET/POST /servicos`, `PATCH/DELETE /servicos/{uuid}` |
| Clientes | `GET/POST /clientes`, `GET/PATCH/DELETE /clientes/{uuid}` |
| Horários | `GET /horarios`, `PUT /horarios/empresa`, `PUT /horarios/profissionais/{uuid}` |

Todas exigem `auth`, `verified` e `company`.

## Interface

- Páginas mobile-first em React/Inertia, com cartões que evitam tabelas horizontais em telas pequenas.
- Busca explícita, paginação localizada, estados vazios diferentes de “sem resultados” e confirmação para exclusão.
- Modais com foco inicial, fechamento acessível, campos rotulados, erros próximos ao campo e ações com loading.
- Inputs e ações principais com área mínima de 44 px, foco visível, tema claro/escuro e movimento reduzido.

## Verificações concluídas

- 55 testes e 214 asserções aprovados.
- PHPStan nível 7 sem erros.
- Pint, Prettier, ESLint e TypeScript sem erros.
- Build cliente e SSR concluídos.
- Migração completa e seed executados em SQLite local.
- Revisão funcional da busca, perfil de cliente e navegação.
- Revisão visual em 375 × 812, 812 × 375 e 1440 × 900.
- Sem rolagem horizontal nos breakpoints verificados; tema escuro e estados de modal inspecionados.

## Próximo escopo

A Etapa 3 deve introduzir agenda, disponibilidade calculada, bloqueios e agendamentos. Nenhuma entidade de agenda foi antecipada nesta entrega.
