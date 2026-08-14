# AgendaFlow — Personalização da empresa

## Objetivo

Permitir que `owner` e `admin` apliquem a identidade visual do tenant no painel e no agendamento público sem comprometer legibilidade, responsividade ou isolamento de dados.

## Regras da página

- Layout de configuração em duas colunas no desktop e uma coluna no mobile.
- Edição à esquerda e pré-visualização persistente à direita.
- Upload com formato e limite explícitos; sempre oferecer substituição e remoção.
- Paleta sugerida mais entrada hexadecimal e seletor nativo de cor.
- Cor e logo devem aparecer na pré-visualização antes do envio.
- Texto sobre a cor principal é calculado automaticamente entre claro e escuro.
- Controles interativos têm pelo menos 44 px, foco visível e feedback de loading.
- Nunca comunicar seleção somente pela cor: usar borda, `aria-pressed` e ícone.
- Em telas públicas, reservar espaço para a logo e usar iniciais como fallback.
- A personalização usa tokens sem alterar cores semânticas de sucesso, alerta e erro.

## Upload

- Formatos: PNG, JPG e WebP.
- Máximo: 2 MB, sem restrição adicional de largura ou altura.
- SVG não é aceito para evitar conteúdo ativo não sanitizado.
- Arquivos ficam em diretório próprio da empresa no disco público.
