import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

type Props = {
    htmlFor: string;
    label: string;
    error?: string;
    hint?: string;
    required?: boolean;
    children: ReactNode;
};

export function FormField({
    htmlFor,
    label,
    error,
    hint,
    required,
    children,
}: Props) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={htmlFor}>
                {label}
                {required && (
                    <span className="text-destructive" aria-hidden="true">
                        {' '}
                        *
                    </span>
                )}
            </Label>
            {children}
            {hint && !error && (
                <p className="text-xs leading-5 text-muted-foreground">
                    {hint}
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}
