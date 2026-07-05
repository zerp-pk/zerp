import { router } from '@inertiajs/react';

interface DeleteOptions {
    routeName: string;
    id: number | string;
    message?: string;
    onSuccess?: () => void;
    onError?: (error: any) => void;
}

export const handleDelete = ({
    routeName,
    id,
    message = 'Are you sure you want to delete this item?',
    onSuccess,
    onError
}: DeleteOptions) => {
    if (confirm(message)) {
        router.delete(route(routeName, id), {
            onSuccess,
            onError
        });
    }
};