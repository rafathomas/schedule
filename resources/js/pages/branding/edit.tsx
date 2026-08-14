import { Head, useForm } from '@inertiajs/react';
import {
    Check,
    ImagePlus,
    Palette,
    RotateCcw,
    Save,
    Trash2,
} from 'lucide-react';
import type { ChangeEvent, FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { brandForeground, brandStyle, defaultBrandColor } from '@/lib/brand';
import { cn } from '@/lib/utils';

type Props = {
    branding: {
        company_name: string;
        primary_color: string;
        logo_url: string | null;
    };
};

const presets = [
    { name: 'Azul profundo', value: '#1E3A8A' },
    { name: 'Índigo', value: '#4F46E5' },
    { name: 'Violeta', value: '#7C3AED' },
    { name: 'Rosa', value: '#BE185D' },
    { name: 'Verde', value: '#047857' },
    { name: 'Terracota', value: '#C2410C' },
];

const initials = (name: string) =>
    name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

export default function BrandingEdit({ branding }: Props) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const form = useForm<{
        primary_color: string;
        logo: File | null;
        remove_logo: boolean;
    }>({
        primary_color: branding.primary_color,
        logo: null,
        remove_logo: false,
    });

    useEffect(
        () => () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        },
        [previewUrl],
    );

    const currentLogo = form.data.remove_logo
        ? null
        : (previewUrl ?? branding.logo_url);

    const chooseLogo = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        setPreviewUrl(file ? URL.createObjectURL(file) : null);
        form.setData('logo', file);
        form.setData('remove_logo', false);
    };

    const removeLogo = () => {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        setPreviewUrl(null);
        form.setData('logo', null);
        form.setData('remove_logo', true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/personalizacao', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.setData('logo', null);
                form.setData('remove_logo', false);
                setPreviewUrl(null);
                toast.success('Identidade visual atualizada.');
            },
            onError: () =>
                toast.error('Revise os campos destacados e tente novamente.'),
        });
    };

    return (
        <>
            <Head title="Personalização" />
            <main
                id="main-content"
                className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <div>
                    <p className="text-sm font-semibold text-primary">
                        Identidade da empresa
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                        Personalização
                    </h1>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        Aplique sua marca no painel e na página pública de
                        agendamento. O texto é ajustado automaticamente para
                        manter a leitura confortável.
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="grid gap-6 lg:grid-cols-[1fr_22rem]"
                >
                    <div className="grid content-start gap-6">
                        <Card className="gap-6 shadow-none">
                            <CardHeader className="flex-row items-center gap-3">
                                <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <ImagePlus
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div>
                                    <CardTitle>Logo da empresa</CardTitle>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        PNG, JPG ou WebP de até 2 MB. Prefira
                                        fundo transparente.
                                    </p>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    <div className="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border bg-muted">
                                        {currentLogo ? (
                                            <img
                                                src={currentLogo}
                                                alt={`Logo atual de ${branding.company_name}`}
                                                className="size-full object-contain p-2"
                                            />
                                        ) : (
                                            <span className="text-xl font-semibold text-muted-foreground">
                                                {initials(
                                                    branding.company_name,
                                                )}
                                            </span>
                                        )}
                                    </div>
                                    <div className="grid flex-1 gap-3">
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                asChild
                                                type="button"
                                                variant="outline"
                                                className="min-h-11 cursor-pointer"
                                            >
                                                <label htmlFor="logo">
                                                    <ImagePlus
                                                        className="size-4"
                                                        aria-hidden="true"
                                                    />
                                                    Escolher imagem
                                                </label>
                                            </Button>
                                            {currentLogo && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    className="min-h-11 text-destructive hover:text-destructive"
                                                    onClick={removeLogo}
                                                >
                                                    <Trash2
                                                        className="size-4"
                                                        aria-hidden="true"
                                                    />
                                                    Remover logo
                                                </Button>
                                            )}
                                        </div>
                                        <input
                                            id="logo"
                                            name="logo"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            className="sr-only"
                                            onChange={chooseLogo}
                                        />
                                        <InputError
                                            message={form.errors.logo}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="gap-6 shadow-none">
                            <CardHeader className="flex-row items-center gap-3">
                                <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Palette
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div>
                                    <CardTitle>Cor principal</CardTitle>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Usada em botões, destaques, foco e
                                        navegação.
                                    </p>
                                </div>
                            </CardHeader>
                            <CardContent className="grid gap-5">
                                <fieldset>
                                    <legend className="text-sm font-medium">
                                        Cores sugeridas
                                    </legend>
                                    <div className="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                                        {presets.map((preset) => {
                                            const selected =
                                                form.data.primary_color.toUpperCase() ===
                                                preset.value;

                                            return (
                                                <button
                                                    key={preset.value}
                                                    type="button"
                                                    aria-label={preset.name}
                                                    aria-pressed={selected}
                                                    title={preset.name}
                                                    onClick={() =>
                                                        form.setData(
                                                            'primary_color',
                                                            preset.value,
                                                        )
                                                    }
                                                    className={cn(
                                                        'flex min-h-14 items-center justify-center rounded-xl border-2 transition-colors focus-visible:ring-3 focus-visible:ring-ring/40 focus-visible:outline-none',
                                                        selected
                                                            ? 'border-foreground'
                                                            : 'border-transparent',
                                                    )}
                                                    style={{
                                                        backgroundColor:
                                                            preset.value,
                                                    }}
                                                >
                                                    {selected && (
                                                        <Check
                                                            className="size-5"
                                                            style={{
                                                                color: brandForeground(
                                                                    preset.value,
                                                                ),
                                                            }}
                                                            aria-hidden="true"
                                                        />
                                                    )}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </fieldset>

                                <div className="grid gap-2 sm:max-w-xs">
                                    <Label htmlFor="primary_color">
                                        Código hexadecimal
                                    </Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="primary_color_picker"
                                            type="color"
                                            aria-label="Selecionar cor principal"
                                            value={form.data.primary_color}
                                            onChange={(event) =>
                                                form.setData(
                                                    'primary_color',
                                                    event.target.value.toUpperCase(),
                                                )
                                            }
                                            className="h-11 w-14 shrink-0 p-1"
                                        />
                                        <Input
                                            id="primary_color"
                                            name="primary_color"
                                            value={form.data.primary_color}
                                            onChange={(event) =>
                                                form.setData(
                                                    'primary_color',
                                                    event.target.value.toUpperCase(),
                                                )
                                            }
                                            placeholder="#1E3A8A"
                                            pattern="#[0-9A-Fa-f]{6}"
                                            maxLength={7}
                                            className="h-11 font-mono uppercase"
                                            aria-describedby="primary-color-help"
                                        />
                                    </div>
                                    <p
                                        id="primary-color-help"
                                        className="text-xs text-muted-foreground"
                                    >
                                        Formato: # seguido por seis letras ou
                                        números.
                                    </p>
                                    <InputError
                                        message={form.errors.primary_color}
                                    />
                                </div>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="min-h-11 w-fit"
                                    onClick={() =>
                                        form.setData(
                                            'primary_color',
                                            defaultBrandColor,
                                        )
                                    }
                                >
                                    <RotateCcw
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Restaurar cor padrão
                                </Button>
                            </CardContent>
                        </Card>

                        <Button
                            type="submit"
                            className="min-h-11 w-full sm:w-fit"
                            disabled={form.processing}
                        >
                            {form.processing ? (
                                <Spinner />
                            ) : (
                                <Save className="size-4" aria-hidden="true" />
                            )}
                            {form.processing
                                ? 'Salvando…'
                                : 'Salvar personalização'}
                        </Button>
                    </div>

                    <aside
                        className="lg:sticky lg:top-6 lg:self-start"
                        aria-label="Pré-visualização da marca"
                    >
                        <p className="mb-3 text-sm font-medium">
                            Pré-visualização
                        </p>
                        <div
                            className="brand-theme overflow-hidden rounded-2xl border bg-background shadow-sm"
                            style={brandStyle(form.data.primary_color)}
                        >
                            <div className="flex items-center gap-3 border-b p-4">
                                <div className="flex size-11 items-center justify-center overflow-hidden rounded-xl bg-primary/10 text-sm font-bold text-primary">
                                    {currentLogo ? (
                                        <img
                                            src={currentLogo}
                                            alt=""
                                            className="size-full object-contain p-1"
                                        />
                                    ) : (
                                        initials(branding.company_name)
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <p className="truncate font-semibold">
                                        {branding.company_name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Agendamento online
                                    </p>
                                </div>
                            </div>
                            <div className="p-5">
                                <p className="text-xs font-semibold text-primary">
                                    ESCOLHA UM HORÁRIO
                                </p>
                                <p className="mt-2 text-lg font-semibold">
                                    Reserve seu atendimento
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Uma amostra de como a marca aparecerá para
                                    seus clientes.
                                </p>
                                <button
                                    type="button"
                                    className="mt-5 min-h-11 w-full rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 focus-visible:ring-3 focus-visible:ring-ring/40 focus-visible:outline-none"
                                >
                                    Continuar
                                </button>
                            </div>
                        </div>
                    </aside>
                </form>
            </main>
        </>
    );
}
