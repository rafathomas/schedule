# AgendaFlow — Entrega da Etapa 6

## Resultado

O dashboard passou a refletir a operação real e proprietários ou administradores agora contam com uma tela de relatórios responsiva para acompanhar volume, faturamento previsto, comparecimento, confirmação e recorrência.

## Funcionalidades entregues

- Dashboard com agendamentos de hoje, confirmação, confirmados e faturamento previsto.
- Lista dos cinco próximos atendimentos ativos.
- Filtros de hoje, últimos 7 dias, últimos 30 dias, mês atual e intervalo personalizado.
- Intervalo personalizado limitado a 366 dias.
- Indicadores de agendamentos, receita prevista, concluídos, cancelamentos, faltas, taxa de confirmação, clientes novos e recorrentes.
- Série temporal diária ou semanal, adaptada à extensão do período.
- Distribuição por todos os status.
- Ranking dos cinco serviços mais agendados e profissionais com maior volume.
- Gráfico SVG sem dependência adicional, com valores exatos por ponto e tabela expansível.
- Seed demonstrativo com histórico anterior e recente para produzir tendências e recorrência.

## Regras de cálculo

- Datas do filtro são interpretadas no fuso da empresa e persistem como limites UTC nas consultas.
- Receita prevista usa o preço salvo no agendamento e exclui cancelamentos e faltas.
- Rankings ignoram cancelamentos.
- Cliente novo tem o primeiro atendimento dentro do período.
- Cliente recorrente teve o primeiro atendimento antes do período e voltou dentro dele.
- Confirmações explícitas, atendimentos confirmados e concluídos contam para a taxa de confirmação.

## Segurança e desempenho

- A rota `/relatorios` exige autenticação, verificação, tenant e papel `owner` ou `admin`.
- Todas as consultas continuam sob o escopo global da empresa atual.
- Profissionais não recebem o item de navegação nem acessam o endpoint consolidado.
- O dashboard de profissional filtra pelo vínculo de usuário; ausência de vínculo resulta em zero dados.
- O agregador usa `lazyById(500)` e relações carregadas por lote para manter memória previsível.
- Nenhuma regra financeira ou de recorrência fica no frontend.

## Interface e acessibilidade

- Filtros e ações têm área mínima de 44 px, foco visível e estado de loading.
- KPIs usam números tabulares e formatação `pt-BR`.
- Cores de status são acompanhadas por rótulo e valor.
- O SVG possui título, descrição e valores por ponto.
- A tabela alternativa permite ler datas, agendamentos e receita sem depender do gráfico.
- Verificação em desktop, 375 × 812, 812 × 375 e tema escuro, sem rolagem horizontal.

## Rotas e migrations

| Método | Rota | Finalidade |
|---|---|---|
| `GET` | `/dashboard` | Resumo real do dia e próximos atendimentos |
| `GET` | `/relatorios` | Métricas, gráficos, rankings e filtros |

Nenhuma migration foi necessária: a etapa agrega dados das entidades já existentes.

## Verificações

- Testes cobrem valores exatos, receita, status, confirmação, recorrência, período personalizado, autorização, tenancy e dashboard por papel.
- PHPStan nível 7, Pint, Prettier, ESLint e TypeScript sem erros.
- Build cliente e SSR concluídos.

## Próximo escopo

A Etapa 7 adicionará planos, assinaturas e administração da plataforma SaaS.
