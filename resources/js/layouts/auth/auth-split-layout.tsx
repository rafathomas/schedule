import { Link, usePage } from '@inertiajs/react';
import { CalendarCheck2, MessageCircle, ShieldCheck } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative grid min-h-dvh flex-col bg-background lg:grid-cols-[1.05fr_0.95fr]">
            <div className="relative hidden min-h-dvh flex-col overflow-hidden bg-primary p-10 text-primary-foreground lg:flex dark:border-r">
                <div className="absolute -top-24 -right-24 size-80 rounded-full border border-primary-foreground/15" />
                <div className="absolute right-28 bottom-20 size-44 rounded-full border border-primary-foreground/10" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center text-lg font-medium"
                >
                    <AppLogoIcon className="mr-2 size-8 text-primary-foreground" />
                    {name}
                </Link>
                <div className="relative z-10 my-auto max-w-xl">
                    <p className="mb-5 text-sm font-semibold tracking-[0.16em] text-primary-foreground/90 uppercase">
                        Sua agenda, no controle
                    </p>
                    <h2 className="max-w-lg text-4xl leading-tight font-semibold tracking-tight xl:text-5xl">
                        Menos faltas. Mais tempo para cuidar do seu negócio.
                    </h2>
                    <p className="mt-6 max-w-lg text-lg leading-8 text-primary-foreground/90">
                        Organize horários e automatize confirmações sem perder a
                        proximidade com seus clientes.
                    </p>
                    <div className="mt-10 grid gap-4 text-sm text-primary-foreground">
                        {[
                            [CalendarCheck2, 'Agenda simples de visualizar'],
                            [MessageCircle, 'Confirmações automáticas'],
                            [ShieldCheck, 'Dados isolados por empresa'],
                        ].map(([Icon, label]) => (
                            <div
                                key={label as string}
                                className="flex items-center gap-3"
                            >
                                <span className="flex size-10 items-center justify-center rounded-lg bg-primary-foreground/10">
                                    <Icon
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span className="font-medium">
                                    {label as string}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
                <p className="relative z-10 text-sm text-primary-foreground/90">
                    Feito para negócios que atendem com hora marcada.
                </p>
            </div>
            <div className="flex min-h-dvh w-full items-center px-5 py-8 sm:px-8 lg:p-12">
                <div className="mx-auto flex w-full max-w-md flex-col justify-center space-y-7">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <AppLogoIcon className="size-11 text-primary" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        <p className="text-base text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
