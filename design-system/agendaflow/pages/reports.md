# Relatórios

## Direção

Override do design system para uma superfície de dados densa e profissional. Preservar a paleta azul, cartões discretos e Instrument Sans usados no produto; reduzir movimento e priorizar comparabilidade.

## Hierarquia

1. Cabeçalho com período efetivamente carregado.
2. Filtros rápidos e revelação progressiva do intervalo personalizado.
3. Oito KPIs em grade responsiva.
4. Série temporal e distribuição por status.
5. Rankings de serviços e profissionais.

## Gráficos

- Tendência temporal usa linha SVG, grade discreta e pontos com valores exatos.
- Rankings usam barras horizontais ordenadas de forma decrescente.
- Status nunca depende apenas de cor: sempre mostrar rótulo, contagem e percentual.
- Fornecer tabela alternativa para a série temporal.
- Até 62 dias usam pontos diários; períodos maiores são agrupados semanalmente.
- Não usar donut ou pizza para seis status.

## Responsividade

- Mobile: uma coluna, filtros quebram linha e conteúdo prioritário aparece primeiro.
- Tablet: duas colunas para indicadores e seções principais empilhadas.
- Desktop amplo: quatro indicadores por linha e gráfico principal com distribuição lateral.
- Nenhuma tabela deve forçar rolagem horizontal na página; a alternativa tabular fica em região própria.

## Interação e acessibilidade

- Botões com pelo menos 44 px e intervalo mínimo de 8 px.
- Loading desabilita novos filtros, reduz opacidade dos dados e anuncia o estado por `aria-live`.
- Campos personalizados possuem labels visíveis, tipo `date` e limites coerentes.
- SVG inclui `role=img`, título, descrição e fallback textual.
- Valores monetários, percentuais e contagens usam números tabulares.
- Transições limitadas a 200 ms e desabilitadas em `prefers-reduced-motion`.
- Contraste deve permanecer legível nos temas claro e escuro.
