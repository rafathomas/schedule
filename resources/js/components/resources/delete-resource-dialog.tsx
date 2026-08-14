import { useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    title: string;
    description: string;
    url: string;
    compact?: boolean;
};

export function DeleteResourceDialog({
    title,
    description,
    url,
    compact = false,
}: Props) {
    const form = useForm({});

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size={compact ? 'icon' : 'default'}
                    className="min-h-11 text-destructive hover:bg-destructive/10 hover:text-destructive"
                    aria-label={compact ? `Excluir ${title}` : undefined}
                >
                    <Trash2 className="size-4" aria-hidden="true" />
                    {!compact && 'Excluir'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Excluir {title}?</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline" className="min-h-11">
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        className="min-h-11"
                        disabled={form.processing}
                        onClick={() =>
                            form.delete(url, { preserveScroll: true })
                        }
                    >
                        {form.processing && <Spinner />}
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
